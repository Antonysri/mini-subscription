<?php

namespace App\Http\Controllers;

// 🌟 Models: Payment update panni, Subscription activate panna
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// 🌟 Stripe Webhook & Exception Classes:
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    /**
     * MAIN WEBHOOK INGESTION ENDPOINT (POST /api/stripe/webhook)
     * 
     * ❓ INTHA FUNCTION ENNA PANNUTHU?
     * 1. Stripe anupura raw payload & signature header-ah edukum.
     * 2. Secret key vachu HMAC-SHA256 signature verify pannum (Hacker fake requests-ah reject pannum).
     * 3. Event type paathu (completed, failed) thagapadi business logic-ku route pannum.
     * 4. Stripe-ku instant-ah 200 OK acknowledgement tharum.
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        // 1. Raw Payload & Signature Extraction:
        // Express equivalent: req.rawBody & req.headers['stripe-signature']
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook.secret');

        // Signature header illana direct-ah 400 Bad Request:
        if (! $sigHeader) {
            return response()->json(['error' => 'Missing Stripe-Signature header'], 400);
        }

        // 2. Cryptographic Signature Verification:
        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $webhookSecret,
                config('services.stripe.webhook.tolerance', 300) // 300s replay attack defense
            );
        } catch (UnexpectedValueException $e) {
            // Payload valid JSON illana:
            Log::error('Stripe Webhook Error: Invalid payload', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            // Signature mismatch aana (Fake request from hacker or expired timestamp):
            Log::error('Stripe Webhook Error: Invalid signature', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // 3. Event Routing (Event Type-ku thagapadi handle panrom):
        switch ($event->type) {
            // Standard Card checkout success aana:
            case 'checkout.session.completed':
            // Async payment methods (SEPA, ACH, Netbanking) late-ah success aana:
            case 'checkout.session.async_payment_succeeded':
                $this->handleCheckoutSessionCompleted($event->data->object);
                break;

            // Async payment fail aana:
            case 'checkout.session.async_payment_failed':
                $this->handleCheckoutSessionFailed($event->data->object);
                break;

            default:
                // Namma track pannatha matha events vantha log pannitu 200 return panrom:
                Log::info("Unhandled Stripe webhook event received: {$event->type}");
        }

        // 4. Stripe SLA: Always return 200 OK within 3 seconds!
        return response()->json(['status' => 'success'], 200);
    }

    /**
     * PRIVATE HELPER: Checkout Session Completed
     * 
     * ❓ INTHA METHOD ENNA PANNUTHU?
     * 1. Database-la antha pending payment row-ah lock pannum (Row-level lock).
     * 2. Idempotency Check: Already 'paid' aagiruntha safe-ah skip pannum (Double grant prevent panna).
     * 3. Payment-ah 'paid' nu mathi, stripe_payment_intent_id save pannum.
     * 4. Subscription table-la 'active' record create panni dates calculate pannum!
     */
    private function handleCheckoutSessionCompleted(object $session): void
    {
        DB::transaction(function () use ($session) {
            // 🔒 Concurrency Shield: lockForUpdate() prevents race conditions
            $payment = Payment::where('stripe_checkout_session_id', $session->id)
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                Log::warning("Stripe Webhook: Payment not found for session {$session->id}");
                return;
            }

            // 🌟 IDEMPOTENCY CHECK:
            // Oruvelai Stripe intha event-ah retry panni anupiruntha, already 'paid' aagiyirukum.
            // Athanaala marubadiyum subscription create pannama return aaiduvom!
            if ($payment->status === 'paid') {
                Log::info("Stripe Webhook: Payment {$payment->id} already processed. Skipping duplicate.");
                return;
            }

            // 1. Payment status-ah 'paid' nu mathuroam:
            $payment->update([
                'status'                   => 'paid',
                'stripe_payment_intent_id' => $session->payment_intent ?? null,
                'paid_at'                  => now(),
            ]);

            // 2. Plan duration days edukrom:
            $plan = $payment->plan;
            $durationDays = $plan ? $plan->duration_days : 30;

            // 3. Subscriptions table-la pudhu Active Subscription activate panrom:
            Subscription::create([
                'user_id'    => $payment->user_id,
                'plan_id'    => $payment->plan_id,
                'payment_id' => $payment->id,
                'status'     => 'active',
                'starts_at'  => now(),
                'expires_at' => now()->addDays($durationDays),
            ]);

            Log::info("Stripe Webhook: Successfully activated subscription for user {$payment->user_id} on plan {$payment->plan_id}");
        });
    }

    /**
     * PRIVATE HELPER: Checkout Session Failed
     * Async payment fail aana pending status-ah 'failed' nu mark pannum.
     */
    private function handleCheckoutSessionFailed(object $session): void
    {
        DB::transaction(function () use ($session) {
            $payment = Payment::where('stripe_checkout_session_id', $session->id)
                ->lockForUpdate()
                ->first();

            if ($payment && $payment->status === 'pending') {
                $payment->update([
                    'status' => 'failed',
                ]);
                Log::warning("Stripe Webhook: Payment {$payment->id} marked as failed.");
            }
        });
    }
}
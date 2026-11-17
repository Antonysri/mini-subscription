<?php

namespace App\Http\Controllers;

// 🌟 Models: Database kooda interact panna
use App\Models\Payment;
use App\Models\Plan;

// 🌟 Form Request: Clean & secure validation layer
use App\Http\Requests\CheckoutRequest;

// 🌟 Standard JSON envelope trait: { success, message, data }
use App\Traits\ApiResponse;

use Illuminate\Http\JsonResponse;

// 🌟 Stripe Official Client: AppServiceProvider-la bind panna Singleton class
use Stripe\StripeClient;

class CheckoutController extends Controller
{
    /**
     * ApiResponse TRAIT:
     * Standard JSON envelope format { success: true, data: [...], message: "..." }
     * return panna itha use panrom.
     */
    use ApiResponse;

    /**
     * CONSTRUCTOR DEPENDENCY INJECTION:
     * ❓ INTHA METHOD ENNA PANNUTHU?
     * Laravel-oda Service Container (AppServiceProvider) kitta irunthu
     * configured 'StripeClient' singleton instance-ah automatic-ah inject pannikkum.
     * 
     * 💡 Controller kulla 'new StripeClient(...)' ezhutha thevaiye illa!
     */
    public function __construct(
        private StripeClient $stripe
    ) {}

    /**
     * CREATE STRIPE CHECKOUT SESSION (POST /api/checkout)
     * 
     * ❓ INTHA FUNCTION ENNA PANNUTHU?
     * 1. JWT Token-la irunthu login panna user-ah edukum.
     * 2. Selected plan-ah DB-la verify panni, DB price-ah Cents-ah ($ -> cents) mathum.
     * 3. Stripe Server kooda communicate panni Hosted Checkout URL generate pannum.
     * 4. Payments table-la 'pending' status-oda transaction record insert pannum.
     * 5. User-ku direct Checkout URL-ah JSON response-ah return pannum!
     */
    public function checkout(CheckoutRequest $request): JsonResponse
    {
        // 1. Current logged-in user details (JWT Token moolama):
        $user = auth('api')->user();

        // 2. Client anupina plan_id-ah vachu Server DB-la irunthu plan record edukrom.
        // Hacker price maatha mudiyathu - DB price thaan 100% source of truth!
        $plan = Plan::findOrFail($request->validated('plan_id'));

        // 3. Smallest Currency Unit (Cents Rule):
        // Stripe $29.00 mathiri decimals ethukathu, integer cents mattum thaan ethukkum.
        // Calculation: $29.00 * 100 = 2900 cents.
        $unitAmountInCents = (int) round($plan->price * 100);

        // Dynamic Frontend Redirect URL (Works on Localhost AND Live Cloud):
        $frontendUrl = rtrim(env('FRONTEND_URL') ?: $request->header('Origin') ?: $request->getSchemeAndHttpHost(), '/');

        // 4. Stripe Hosted Checkout Session Creation:
        // Notice: Modern 2026 Stripe uses Dynamic Payment Methods configured in Stripe Dashboard.
        // Explicit 'payment_method_types' is deprecated and removed!
        $session = $this->stripe->checkout->sessions->create([
            // 'payment' mode for one-time fixed period charges:
            'mode' => 'payment',

            // User Stripe page open pannumpothu avanga email automatic-ah pre-fill aagidum:
            'customer_email' => $user->email,

            // Stripe dashboard-la user-ah track panna reference:
            'client_reference_id' => (string) $user->id,

            // Cart Items / Line Items:
            'line_items' => [[
                'price_data' => [
                    'currency' => $plan->currency,
                    'product_data' => [
                        'name' => $plan->name,
                        'description' => $plan->description ?? "Subscription for {$plan->duration_days} days",
                    ],
                    'unit_amount' => $unitAmountInCents,
                ],
                'quantity' => 1,
            ]],

            // 🌟 METADATA BRIDGE:
            // Phase 6 Webhook-ku intha details romba mukkiyam!
            // User pay pannathum Stripe intha exact metadata-va namma webhook-ku thirumba anupum!
            'metadata' => [
                'user_id' => (string) $user->id,
                'plan_id' => (string) $plan->id,
            ],

            // User payment success aana redirect aaga vendiya URL (frontend dashboard):
            'success_url' => $frontendUrl . '/dashboard?session_id={CHECKOUT_SESSION_ID}',

            // User cancel click pannina return aaga vendiya URL (frontend pricing):
            'cancel_url' => $frontendUrl . '/plans',
        ]);

        // 5. Audit Trail: Payment table-la 'pending' nu record insert panrom:
        $payment = Payment::create([
            'user_id'                    => $user->id,
            'plan_id'                    => $plan->id,
            'stripe_checkout_session_id' => $session->id,
            'amount'                     => $plan->price,
            'currency'                   => $plan->currency,
            'status'                     => 'pending',
        ]);

        // 6. User-ku standardized JSON envelope-la Checkout URL return panrom:
        return $this->successResponse([
            'checkout_url' => $session->url,
            'session_id'   => $session->id,
            'payment_id'   => $payment->id,
        ], 'Checkout session created successfully', 201);
    }
}
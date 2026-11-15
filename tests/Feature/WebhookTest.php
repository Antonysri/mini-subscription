<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\WebhookSignature;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    /**
     * RefreshDatabase TRAIT:
     * Ovvoru test mudiyum pothum test database-ah clean pannidum!
     */
    use RefreshDatabase;

    private string $webhookSecret = 'whsec_test_secret_for_phpunit_verification';

    protected function setUp(): void
    {
        parent::setUp();
        // Test environment-kaga secret key-ah mock config-la set panrom:
        config(['services.stripe.webhook.secret' => $this->webhookSecret]);
    }

    /**
     * HELPER METHOD: Generate Genuine Stripe HMAC Signature
     * Namma test payload-ku real Stripe timestamp + HMAC-SHA256 signature generate pannum.
     */
    private function generateSignatureHeader(string $payload): string
    {
        return WebhookSignature::generateSignatureHeader($payload, $this->webhookSecret);
    }

    /**
     * TEST 1: Stripe-Signature header illana request reject aaganum
     * Expected: HTTP 400 Bad Request
     */
    public function test_webhook_fails_without_signature_header(): void
    {
        $response = $this->postJson('/api/stripe/webhook', [
            'type' => 'checkout.session.completed',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Missing Stripe-Signature header',
            ]);
    }

    /**
     * TEST 2: Thappaana signature header vantha request reject aaganum (Hacker Defense)
     * Expected: HTTP 400 Bad Request
     */
    public function test_webhook_fails_with_invalid_signature(): void
    {
        $payload = json_encode(['type' => 'checkout.session.completed']);

        $response = $this->call(
            'POST',
            '/api/stripe/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE'           => 'application/json',
                'HTTP_STRIPE_SIGNATURE'  => 't=1234567890,v1=fake_hacker_signature_hash',
            ],
            $payload
        );

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Invalid signature',
            ]);
    }

    /**
     * TEST 3: SUCCESSFUL ACTIVATION (checkout.session.completed)
     * Expected: HTTP 200, Payment status = 'paid', Subscription = 'active' for 30 days!
     */
    public function test_webhook_successfully_activates_subscription_on_checkout_completed(): void
    {
        // 1. User & 30 Days Plan create panrom:
        $user = User::factory()->create();
        $plan = Plan::create([
            'name'          => 'Pro Membership',
            'description'   => 'Pro features',
            'price'         => 29.00,
            'currency'      => 'usd',
            'duration_days' => 30, // 👈 30 days plan!
            'is_active'     => true,
        ]);

        // 2. Initial Pending Payment create panrom:
        $payment = Payment::create([
            'user_id'                    => $user->id,
            'plan_id'                    => $plan->id,
            'stripe_checkout_session_id' => 'cs_test_session_active_123',
            'amount'                     => 29.00,
            'currency'                   => 'usd',
            'status'                     => 'pending',
        ]);

        // 3. Stripe Event Payload simulate panrom:
        $payload = json_encode([
            'id'     => 'evt_test_checkout_completed',
            'object' => 'event',
            'type'   => 'checkout.session.completed',
            'data'   => [
                'object' => [
                    'id'             => 'cs_test_session_active_123',
                    'payment_intent' => 'pi_test_real_intent_789',
                    'customer_email' => $user->email,
                ],
            ],
        ]);

        $signatureHeader = $this->generateSignatureHeader($payload);

        // 4. Webhook POST request anupurom (with Raw Body & Valid Signature):
        $response = $this->call(
            'POST',
            '/api/stripe/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE'          => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signatureHeader,
            ],
            $payload
        );

        // 5. Assertions:
        // A. Response HTTP 200 OK:
        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        // B. Payment Table: status 'paid' aagi, payment_intent_id save aagirukanum:
        $this->assertDatabaseHas('payments', [
            'id'                       => $payment->id,
            'status'                   => 'paid',
            'stripe_payment_intent_id' => 'pi_test_real_intent_789',
        ]);

        // C. Subscriptions Table: status 'active' aagi, exact-ah 30 days subscription create aagirukanum:
        $this->assertDatabaseHas('subscriptions', [
            'user_id'    => $user->id,
            'plan_id'    => $plan->id,
            'payment_id' => $payment->id,
            'status'     => 'active',
        ]);

        $subscription = Subscription::where('payment_id', $payment->id)->first();
        $this->assertNotNull($subscription);
        $this->assertTrue($subscription->isActive());
        // Duration days check:
        $this->assertEquals(30, round($subscription->starts_at->diffInDays($subscription->expires_at)));
    }

    /**
     * TEST 4: IDEMPOTENCY TEST (Duplicate webhook deliveries will NOT double-grant!)
     * Expected: Second call returns 200 OK, but Subscription count remains exactly 1!
     */
    public function test_webhook_is_idempotent_and_prevents_duplicate_subscriptions(): void
    {
        $user = User::factory()->create();
        $plan = Plan::create([
            'name'          => 'Pro Membership',
            'price'         => 29.00,
            'duration_days' => 30,
            'is_active'     => true,
        ]);

        $payment = Payment::create([
            'user_id'                    => $user->id,
            'plan_id'                    => $plan->id,
            'stripe_checkout_session_id' => 'cs_test_session_idempotent',
            'amount'                     => 29.00,
            'status'                     => 'pending',
        ]);

        $payload = json_encode([
            'id'     => 'evt_test_idempotency',
            'object' => 'event',
            'type'   => 'checkout.session.completed',
            'data'   => [
                'object' => [
                    'id'             => 'cs_test_session_idempotent',
                    'payment_intent' => 'pi_test_intent_idempotent',
                ],
            ],
        ]);

        $signatureHeader = $this->generateSignatureHeader($payload);

        // 1. FIRST DELIVERY:
        $response1 = $this->call('POST', '/api/stripe/webhook', [], [], [], [
            'CONTENT_TYPE'          => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signatureHeader,
        ], $payload);

        $response1->assertStatus(200);
        $this->assertEquals(1, Subscription::count());

        // 2. SECOND DELIVERY (Stripe retries the EXACT same event!):
        $response2 = $this->call('POST', '/api/stripe/webhook', [], [], [], [
            'CONTENT_TYPE'          => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signatureHeader,
        ], $payload);

        $response2->assertStatus(200);

        // 🌟 IDEMPOTENCY CHECK: Count STILL MUST BE 1! Duplicate grant aagave koodathu!
        $this->assertEquals(1, Subscription::count());
    }

    /**
     * TEST 5: ASYNC PAYMENT FAILED (checkout.session.async_payment_failed)
     * Expected: Payment status transitions from 'pending' to 'failed'
     */
    public function test_webhook_marks_payment_as_failed_on_async_failure(): void
    {
        $user = User::factory()->create();
        $plan = Plan::create([
            'name'          => 'Pro Membership',
            'price'         => 29.00,
            'duration_days' => 30,
            'is_active'     => true,
        ]);

        $payment = Payment::create([
            'user_id'                    => $user->id,
            'plan_id'                    => $plan->id,
            'stripe_checkout_session_id' => 'cs_test_session_failed',
            'amount'                     => 29.00,
            'status'                     => 'pending',
        ]);

        $payload = json_encode([
            'id'     => 'evt_test_failed',
            'object' => 'event',
            'type'   => 'checkout.session.async_payment_failed',
            'data'   => [
                'object' => [
                    'id' => 'cs_test_session_failed',
                ],
            ],
        ]);

        $signatureHeader = $this->generateSignatureHeader($payload);

        $response = $this->call('POST', '/api/stripe/webhook', [], [], [], [
            'CONTENT_TYPE'          => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signatureHeader,
        ], $payload);

        $response->assertStatus(200);

        // Payment status 'failed' nu maari irukanum:
        $this->assertDatabaseHas('payments', [
            'id'     => $payment->id,
            'status' => 'failed',
        ]);

        // Subscription create aagirukka koodathu:
        $this->assertEquals(0, Subscription::count());
    }
}
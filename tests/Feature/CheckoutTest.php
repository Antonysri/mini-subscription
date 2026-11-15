<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\StripeClient;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    /**
     * RefreshDatabase TRAIT:
     * Ovvoru test mudiyum pothum test database-ah clean pannidum!
     */
    use RefreshDatabase;

    /**
     * TEST 1: Token illatha unauthenticated user checkout access panna mudiyaathu
     * Expected: HTTP 401 Unauthorized
     */
    public function test_unauthenticated_user_cannot_access_checkout_endpoint(): void
    {
        $response = $this->postJson('/api/checkout', [
            'plan_id' => 1,
        ]);

        $response->assertStatus(401);
    }

    /**
     * TEST 2: Validation fail aaguratha check panrom (Missing / Non-existent plan_id)
     * Expected: HTTP 422 Unprocessable Entity
     */
    public function test_checkout_validation_fails_with_invalid_plan_id(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        // Case A: plan_id anupave illa:
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/checkout', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['plan_id']);

        // Case B: Database-la illatha plan_id (eg: 99999):
        $responseNonExistent = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/checkout', [
                'plan_id' => 99999,
            ]);

        $responseNonExistent->assertStatus(422)
            ->assertJsonValidationErrors(['plan_id']);
    }

    /**
     * TEST 3: Deactivate / Inactive aana plan-ah checkout panna mudiyaathu
     * Expected: HTTP 422 Unprocessable Entity
     */
    public function test_checkout_validation_fails_for_inactive_plan(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        // Inactive plan create panrom:
        $inactivePlan = Plan::create([
            'name'          => 'Archived Plan',
            'description'   => 'Old deprecated plan',
            'price'         => 19.99,
            'currency'      => 'usd',
            'duration_days' => 30,
            'is_active'     => false, // 👈 Inactive!
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/checkout', [
                'plan_id' => $inactivePlan->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['plan_id']);
    }

    /**
     * TEST 4: SUCCESSFUL CHECKOUT (With Mocked StripeClient & Database Audit Check)
     * Expected: HTTP 201 Created, checkout_url, payments table-la 'pending' record!
     */
    public function test_authenticated_user_can_create_checkout_session_successfully(): void
    {
        // 1. User & Active Plan ready panrom:
        $user = User::factory()->create([
            'email' => 'antony_buyer@test.com',
        ]);
        $token = auth('api')->login($user);

        $plan = Plan::create([
            'name'          => 'Pro Membership',
            'description'   => 'Pro features for 30 days',
            'price'         => 29.99,
            'currency'      => 'usd',
            'duration_days' => 30,
            'is_active'     => true,
        ]);

        // 2. STRIPE CLIENT MOCKING (Zero network calls!):
        // Real Stripe server-ku pogama dummy response mock panrom:
        $mockStripe = Mockery::mock(StripeClient::class);
        $mockCheckout = Mockery::mock();
        $mockSessions = Mockery::mock();

        $dummySession = (object) [
            'id'  => 'cs_test_mock_abc123',
            'url' => 'https://checkout.stripe.com/c/pay/cs_test_mock_abc123',
        ];

        // StripeClient chain setup: $stripe->checkout->sessions->create([...])
        $mockStripe->checkout = $mockCheckout;
        $mockCheckout->sessions = $mockSessions;

        // Verify parameters passed to Stripe:
        $mockSessions->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($params) use ($user, $plan) {
                return $params['mode'] === 'payment'
                    && $params['customer_email'] === $user->email
                    && $params['client_reference_id'] === (string) $user->id
                    && $params['metadata']['user_id'] === (string) $user->id
                    && $params['metadata']['plan_id'] === (string) $plan->id
                    // Cents conversion check: $29.99 * 100 = 2999 cents!
                    && $params['line_items'][0]['price_data']['unit_amount'] === 2999;
            }))
            ->andReturn($dummySession);

        // Namma AppServiceProvider-la irukura real StripeClient-ku pathila intha Mock-ah swap panrom:
        $this->app->instance(StripeClient::class, $mockStripe);

        // 3. API Call execute panrom:
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/checkout', [
                'plan_id' => $plan->id,
            ]);

        // 4. Assertions:
        // A. Response HTTP Status 201 Created:
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Checkout session created successfully',
                'data'    => [
                    'checkout_url' => 'https://checkout.stripe.com/c/pay/cs_test_mock_abc123',
                    'session_id'   => 'cs_test_mock_abc123',
                ],
            ]);

        // B. Database Audit Trail: payments table-la status = 'pending' record create aagiruka nu verify panrom:
        $this->assertDatabaseHas('payments', [
            'user_id'                    => $user->id,
            'plan_id'                    => $plan->id,
            'stripe_checkout_session_id' => 'cs_test_mock_abc123',
            'amount'                     => 29.99,
            'currency'                   => 'usd',
            'status'                     => 'pending',
        ]);
    }
}
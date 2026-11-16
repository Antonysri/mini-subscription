<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingTest extends TestCase
{
    /**
     * RefreshDatabase TRAIT:
     * Ovvoru test mudiyum pothum test database-ah clean pannidum!
     */
    use RefreshDatabase;

    /**
     * TEST 1: Token illatha unauthenticated user billing APIs access panna mudiyaathu
     * Expected: HTTP 401 Unauthorized
     */
    public function test_unauthenticated_user_cannot_access_billing_endpoints(): void
    {
        // Subscription check:
        $this->getJson('/api/me/subscription')->assertStatus(401);
        $this->getJson('/api/subscription')->assertStatus(401);

        // Payments check:
        $this->getJson('/api/me/payments')->assertStatus(401);
        $this->getJson('/api/payments')->assertStatus(401);
    }

    /**
     * TEST 2: Innum subscription vaangatha user-ku graceful-ah { data: null } return aaganum
     * Expected: HTTP 200 OK, data = null
     */
    public function test_user_without_subscription_returns_graceful_null_response(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/me/subscription');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'No active subscription found',
                'data'    => null,
            ]);
    }

    /**
     * TEST 3: Active Subscription details, plan info, and days_remaining check panrom
     * Expected: HTTP 200 OK, is_active = true, days_remaining = 20, plan details present
     */
    public function test_authenticated_user_can_view_active_subscription(): void
    {
        // 🌟 TIME FREEZE:
        // Milliseconds drift aagi 20 days -> 19.99 days nu aagama irukka
        // Computer clock-ah intha test mudiyura varaikum freeze panrom:
        Carbon::setTestNow(now());

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $plan = Plan::create([
            'name'          => 'Pro Membership',
            'description'   => 'Pro features',
            'price'         => 29.00,
            'currency'      => 'usd',
            'duration_days' => 30,
            'is_active'     => true,
        ]);

        $payment = Payment::create([
            'user_id'                    => $user->id,
            'plan_id'                    => $plan->id,
            'stripe_checkout_session_id' => 'cs_test_billing_sub_123',
            'amount'                     => 29.00,
            'currency'                   => 'usd',
            'status'                     => 'paid',
        ]);

        // Innum 20 days balance irukura mathiri subscription create panrom:
        $subscription = Subscription::create([
            'user_id'    => $user->id,
            'plan_id'    => $plan->id,
            'payment_id' => $payment->id,
            'status'     => 'active',
            'starts_at'  => now()->subDays(10), // 10 days munnaadi start aachu
            'expires_at' => now()->addDays(20), // Innum 20 days balance irukku
        ]);

        // API Call (/api/me/subscription):
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/me/subscription');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'id'             => $subscription->id,
                    'status'         => 'active',
                    'is_active'      => true,
                    'days_remaining' => 20, // 👈 Exact 20 days match aagum!
                    'plan'           => [
                        'id'            => $plan->id,
                        'name'          => 'Pro Membership',
                        'price'         => '29.00',
                        'currency'      => 'usd',
                        'duration_days' => 30,
                    ],
                ],
            ]);

        // Shortcut alias check (/api/subscription):
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/subscription')
            ->assertStatus(200)
            ->assertJsonPath('data.id', $subscription->id);

        // 🌟 TIME UNFREEZE:
        Carbon::setTestNow();
    }

    /**
     * TEST 4: Paginated Payment History with metadata check panrom
     * Expected: HTTP 200 OK, items count = 2, total = 3, has_more = true
     */
    public function test_authenticated_user_can_view_paginated_payments_history(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $plan = Plan::create([
            'name'          => 'Starter Plan',
            'price'         => 9.00,
            'duration_days' => 30,
            'is_active'     => true,
        ]);

        // User 3 payments pannirukaaru:
        for ($i = 1; $i <= 3; $i++) {
            Payment::create([
                'user_id'                    => $user->id,
                'plan_id'                    => $plan->id,
                'stripe_checkout_session_id' => "cs_test_session_{$i}",
                'amount'                     => 9.00,
                'currency'                   => 'usd',
                'status'                     => 'paid',
                'paid_at'                    => now(),
            ]);
        }

        // Page size 2 kuduthu request anupurom (?per_page=2):
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/me/payments?per_page=2');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'items' => [
                        '*' => ['id', 'amount', 'currency', 'status', 'stripe_checkout_session_id', 'plan'],
                    ],
                    'pagination' => [
                        'total',
                        'per_page',
                        'current_page',
                        'last_page',
                        'has_more',
                    ],
                ],
            ])
            ->assertJsonPath('data.pagination.total', 3)
            ->assertJsonPath('data.pagination.per_page', 2)
            ->assertJsonPath('data.pagination.current_page', 1)
            ->assertJsonPath('data.pagination.last_page', 2)
            ->assertJsonPath('data.pagination.has_more', true);

        $this->assertCount(2, $response->json('data.items'));
    }

    /**
     * TEST 5: MULTI-TENANT ISOLATION (User A cannot see User B's billing data)
     * Expected: User A calls return null/empty, even when User B has active data!
     */
    public function test_tenant_data_isolation_user_cannot_access_other_users_billing(): void
    {
        // 1. User A (Attacker or different customer):
        $userA = User::factory()->create(['email' => 'user_a@test.com']);
        $tokenA = auth('api')->login($userA);

        // 2. User B (Victim / Paid Customer):
        $userB = User::factory()->create(['email' => 'user_b@test.com']);
        $plan = Plan::create([
            'name'          => 'Enterprise Plan',
            'price'         => 99.00,
            'duration_days' => 30,
            'is_active'     => true,
        ]);

        $paymentB = Payment::create([
            'user_id'                    => $userB->id, // 👈 Belongs to User B!
            'plan_id'                    => $plan->id,
            'stripe_checkout_session_id' => 'cs_test_user_b',
            'amount'                     => 99.00,
            'status'                     => 'paid',
        ]);

        Subscription::create([
            'user_id'    => $userB->id, // 👈 Belongs to User B!
            'plan_id'    => $plan->id,
            'payment_id' => $paymentB->id,
            'status'     => 'active',
            'starts_at'  => now(),
            'expires_at' => now()->addDays(30),
        ]);

        // 3. User A tries to view subscriptions:
        // MUST BE NULL! (User B-yoda subscription User A-ku theriya koodathu):
        $subResponse = $this->withHeader('Authorization', 'Bearer ' . $tokenA)
            ->getJson('/api/me/subscription');

        $subResponse->assertStatus(200)
            ->assertJsonPath('data', null);

        // 4. User A tries to view payment history:
        // MUST BE EMPTY! (User B-yoda payments User A-ku varave koodathu):
        $payResponse = $this->withHeader('Authorization', 'Bearer ' . $tokenA)
            ->getJson('/api/me/payments');

        $payResponse->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 0);

        $this->assertEmpty($payResponse->json('data.items'));
    }
}
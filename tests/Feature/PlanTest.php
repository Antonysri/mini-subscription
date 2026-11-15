<?php

namespace Tests\Feature;

use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PlanTest extends TestCase
{
    /**
     * RefreshDatabase TRAIT:
     * Test database-ah ovvoru test mudiyum pothum clean pannidum.
     */
    use RefreshDatabase;

    /**
     * TEST 1: GET /api/plans active plans-ah mattum eduthu Cache-la save pannutha nu check panrom
     */
    public function test_can_fetch_active_plans_and_caches_result(): void
    {
        // 1. Oru Active plan & oru Inactive plan create panrom:
        Plan::create([
            'name'          => 'Starter Plan',
            'description'   => 'Starter description',
            'price'         => 9.99,
            'currency'      => 'usd',
            'duration_days' => 30,
            'is_active'     => true,
        ]);

        Plan::create([
            'name'          => 'Archived Plan',
            'description'   => 'Inactive description',
            'price'         => 19.99,
            'currency'      => 'usd',
            'duration_days' => 30,
            'is_active'     => false,
        ]);

        // 2. Initial-ah Cache-la key iruka koodathu:
        $this->assertFalse(Cache::has('subscription_plans'));

        // 3. API endpoint-ku GET request anupurom:
        $response = $this->getJson('/api/plans');

        // 4. Assertions:
        // Status 200 OK vara vendum
        // Active plan mattum (Count: 1) return aaga vendum
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Subscription plans retrieved successfully',
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Starter Plan');

        // 5. Request mudinjathum 'subscription_plans' key Cache-la create aagi irukanum!
        $this->assertTrue(Cache::has('subscription_plans'));
    }

    /**
     * TEST 2: Second request Cache-la irunthu return aaguratha verify panrom
     */
    public function test_cached_plans_are_returned_on_subsequent_requests(): void
    {
        Plan::create([
            'name'          => 'Pro Plan',
            'description'   => 'Pro description',
            'price'         => 29.99,
            'currency'      => 'usd',
            'duration_days' => 30,
            'is_active'     => true,
        ]);

        // First call - Cache populate aagum:
        $this->getJson('/api/plans')->assertStatus(200);
        $this->assertTrue(Cache::has('subscription_plans'));

        // Cache-ku theriyama direct-ah Database-la name-ah mathurom:
        Plan::query()->update(['name' => 'Database Directly Changed']);

        // Second call: Database-ku pogaama, Cache memory-la irunthu 'Pro Plan'-e return aaga vendum!
        $response = $this->getJson('/api/plans');
        $response->assertStatus(200)
            ->assertJsonPath('data.0.name', 'Pro Plan');
    }

    /**
     * TEST 3: Plan update aana PlanObserver Cache-ah flush pannutha nu check panrom
     */
    public function test_updating_a_plan_invalidates_cache_via_observer(): void
    {
        $plan = Plan::create([
            'name'          => 'Business Plan',
            'description'   => 'Business description',
            'price'         => 99.99,
            'currency'      => 'usd',
            'duration_days' => 30,
            'is_active'     => true,
        ]);

        // Initial-ah Cache-la data podurom:
        Cache::put('subscription_plans', [$plan], 600);
        $this->assertTrue(Cache::has('subscription_plans'));

        // Eloquent update execute panrom -> PlanObserver::updated() trigger aaganum:
        $plan->update(['price' => 119.99]);

        // Cache automatically clear aagirukanum (Cache::has() false aaganum):
        $this->assertFalse(Cache::has('subscription_plans'));
    }

    /**
     * TEST 4: Pudhusa Plan create aana Cache clear aagutha nu check panrom
     */
    public function test_creating_a_plan_invalidates_cache_via_observer(): void
    {
        Cache::put('subscription_plans', ['old_cached_plans'], 600);
        $this->assertTrue(Cache::has('subscription_plans'));

        // Pudhusa oru plan create panrom -> PlanObserver::created() trigger aaganum:
        Plan::create([
            'name'          => 'Enterprise Plan',
            'description'   => 'Enterprise description',
            'price'         => 299.99,
            'currency'      => 'usd',
            'duration_days' => 365,
            'is_active'     => true,
        ]);

        // Cache clear aagirukanum:
        $this->assertFalse(Cache::has('subscription_plans'));
    }

    /**
     * TEST 5: Plan delete aana Cache clear aagutha nu check panrom
     */
    public function test_deleting_a_plan_invalidates_cache_via_observer(): void
    {
        $plan = Plan::create([
            'name'          => 'Old Plan',
            'description'   => 'Old description',
            'price'         => 5.00,
            'currency'      => 'usd',
            'duration_days' => 7,
            'is_active'     => true,
        ]);

        Cache::put('subscription_plans', [$plan], 600);
        $this->assertTrue(Cache::has('subscription_plans'));

        // Plan delete panrom -> PlanObserver::deleted() trigger aaganum:
        $plan->delete();

        // Cache clear aagirukanum:
        $this->assertFalse(Cache::has('subscription_plans'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

// 🌟 Laravel Cache Facade: Redis kooda communicate panna itha import panrom
use Illuminate\Support\Facades\Cache;

class PlanController extends Controller
{
    /**
     * ApiResponse TRAIT INJECTION:
     * Standard JSCacheON envelope { success, message, data } use panna itha use panrom.
     */
    use ApiResponse;

    /**
     * GET ALL ACTIVE SUBSCRIPTION PLANS (GET /api/plans)
     * 
     * ❓ INTHA FUNCTION ENNA PANNUTHU?
     * SaaS platform-la irukura active plans-ah (Starter, Pro, Business)
     * Redis Cache moolama ultra-fast-ah eduthu user-ku anupum.
     * 
     * ❓ YEN REDIS CACHE PODROM?
     * Pricing page-ah daily thousands of users paapanga.
     * Ovvoru visitor-kum MySQL query execute aana database crash aagidum.
     * Athanaala 10 minutes (600s) RAM-la cache vachukirom!
     */
    public function index(): JsonResponse
    {
        // 🌟 Cache::remember:
        // Param 1: 'subscription_plans' -> Redis Key Name
        // Param 2: 600 -> Cache Time To Live (TTL in seconds = 10 minutes)
        // Param 3: Closure Function -> Cache illatha pothu mattum MySQL-la query pannum!
        $plans = Cache::remember('subscription_plans', 600, function () {
            // is_active = true irukura plans mattum thaan display aaganum
            return Plan::where('is_active', true)->get();
        });

        return $this->successResponse($plans, 'Subscription plans retrieved successfully');
    }
}
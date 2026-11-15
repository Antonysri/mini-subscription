<?php

namespace App\Observers;

use App\Models\Plan;

// 🌟 Cache Facade: Redis-la irukura key-ah flush panna import panrom
use Illuminate\Support\Facades\Cache;

class PlanObserver
{
    /**
     * PRIVATE HELPER METHOD: Flush Plans Cache
     * 
     * ❓ INTHA METHOD ENNA PANNUTHU?
     * Redis RAM-la namma store panni vachurukura 'subscription_plans' key-ah
     * antha nodiyilaye delete panni clear pannidum.
     * 
     * ❓ YEN PRIVATE FUNCTION-AH VACHUKUROM?
     * Created, Updated, Deleted nu ella event-kum ore line code thaan.
     * Code duplication-ah thavirkka intha common method-ah eluthurom.
     */
    private function clearPlansCache(): void
    {
        // 🌟 Cache::forget:
        // Redis command equivalent: DEL mini_sub_cache:subscription_plans
        Cache::forget('subscription_plans');
    }

    /**
     * EVENT 1: Pudhu plan Create aagum pothu (When a new plan is created):
     */
    public function created(Plan $plan): void
    {
        $this->clearPlansCache();
    }

    /**
     * EVENT 2: Irukura plan Update aagum pothu (When an existing plan is updated, eg: Price $29 -> $39):
     */
    public function updated(Plan $plan): void
    {
        $this->clearPlansCache();
    }

    /**
     * EVENT 3: Oru plan Delete aagum pothu (When a plan is deleted):
     */
    public function deleted(Plan $plan): void
    {
        $this->clearPlansCache();
    }

    /**
     * EVENT 4: Soft-delete-la irunthu Restore aagum pothu:
     */
    public function restored(Plan $plan): void
    {
        $this->clearPlansCache();
    }
}
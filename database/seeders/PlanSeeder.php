<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

// Namma create panna Plan model-ah import panrom
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    /**
     * Intha method thaan `php artisan db:seed` run aagum pothu execute aagum.
     */
    public function run(): void
    {
        /**
         * DEFAULT PLANS ARRAY:
         * Namma SaaS platform-la vikka pora 3 plans-oda details inga iruku.
         */
        $plans = [
            [
                'name' => 'Starter',
                'description' => 'Basic access with standard features, perfect for beginners and individuals.',
                'price' => 9.00,
                'currency' => 'usd',
                'duration_days' => 30, // 1 month validity
                'stripe_price_id' => null, // Phase 5 Stripe integrate pannum pothu real Stripe Price ID poduvom
                'is_active' => true,
            ],
            [
                'name' => 'Professional',
                'description' => 'Advanced features with priority email support, ideal for professionals and freelancers.',
                'price' => 29.00,
                'currency' => 'usd',
                'duration_days' => 30, // 1 month validity
                'stripe_price_id' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Business',
                'description' => 'Full enterprise suite with unlimited team access and 24/7 dedicated support.',
                'price' => 99.00,
                'currency' => 'usd',
                'duration_days' => 30, // 1 month validity
                'stripe_price_id' => null,
                'is_active' => true,
            ],
        ];

        /**
         * IDEMPOTENT INSERTION (updateOrCreate):
         * 
         * ❓ Yen `Plan::create()` use pannama `Plan::updateOrCreate()` use panrom?
         * 
         * Logic:
         * Oruvela neenga `php artisan db:seed`-ah 2 vaati run pannitinga na:
         * - `Plan::create()` use panna -> DB-la duplicate Starter plans create aagidum (Starter, Starter, Starter nu 3 rows).
         * - `Plan::updateOrCreate(['name' => $plan['name']], $plan)` use panna ->
         *   Laravel check pannum: "Starter nu already oru plan iruka?"
         *   - Iruntha: Athaiye update pannum (Pudhusa create pannathu).
         *   - Illana: Pudhusa create pannum.
         * Ithaala ethana thadava seeder run pannalum namma database 100% clean-ah irukkum!
         */
        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['name' => $planData['name']], // Check panna criteria (Unique identifier)
                $planData                      // Insert or update panna vendiya data
            );
        }
    }
}
<?php

namespace App\Providers;
use Stripe\StripeClient;
use Illuminate\Support\ServiceProvider;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 🌟 Modern 2026 DI: StripeClient-ah IoC Container-la Singleton-ah bind panrom
        $this->app->singleton(StripeClient::class, function () {
            return new StripeClient(config('services.stripe.secret'));
        });
    }

    public function boot(): void
    {
        // 🌟 1. AUTH RATE LIMITER (Brute-force Protection):
        // Login / Register-ku: 1 nimishathukku max 5 attempts mattum!
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->input('email') ?: $request->ip());
        });
        // 🌟 2. API RATE LIMITER (DoS Protection):
        // Logged-in user-ku: 1 nimishathukku max 60 requests!
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}

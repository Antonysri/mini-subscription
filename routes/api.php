<?php

use Illuminate\Support\Facades\Route;

// Controllers import panrom
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PlanController; // 👈 1. PlanController-ah import panrom
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\BillingController;
/**
 * 🔓 PUBLIC AUTH ROUTES (Brute-Force Protected):
 * 1 nimishathukku max 5 attempts mattum allow aagum ('throttle:auth').
 * Express: router.post('/register', authLimiter, authController.register);
 */
Route::middleware('throttle:auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// 🌟 GET ALL PLANS (Redis Cached):
// Express equivalent: app.get('/api/plans', planController.index);
Route::get('/plans', [PlanController::class, 'index']); // 👈 2. Intha route-ah add panrom

// 🌟 STRIPE WEBHOOK LISTENER (POST /api/stripe/webhook):
// Stripe server events anupa intha route use aagum.
// JWT token theva illa, 'Stripe-Signature' HMAC hash verify aagum!
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);

/**
 * 🔒 PROTECTED ROUTES (JWT Middleware + Tiered Rate Limiting):
 * 1. 'auth:api': Header-la valid JWT token irukanum.
 * 2. 'throttle:api': Per-user 60 requests per minute limit.
 * 
 * Express: router.use(verifyTokenMiddleware, apiLimiter);
 */
Route::middleware(['auth:api', 'throttle:api'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    // 🌟 STRIPE CHECKOUT ROUTE (POST /api/checkout):
    // Login panna user mattum thaan subscription buy panna checkout URL generate panna mudiyum.
    Route::post('/checkout', [CheckoutController::class, 'checkout']);

    // 🌟 ACTIVE SUBSCRIPTION & PAYMENT HISTORY (Phase 7):
    Route::get('/me/subscription', [BillingController::class, 'subscription']);
    Route::get('/subscription', [BillingController::class, 'subscription']); // Shortcut alias
    Route::get('/me/payments', [BillingController::class, 'payments']);
    Route::get('/payments', [BillingController::class, 'payments']); // Shortcut alias
});
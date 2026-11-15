<?php

use Illuminate\Support\Facades\Route;

// Controllers import panrom
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PlanController; // 👈 1. PlanController-ah import panrom
use App\Http\Controllers\CheckoutController;
/**
 * 🔓 PUBLIC ROUTES:
 * Yar vena access pannalam (Token thevailla).
 * Express: router.post('/register', authController.register);
 */
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// 🌟 GET ALL PLANS (Redis Cached):
// Express equivalent: app.get('/api/plans', planController.index);
Route::get('/plans', [PlanController::class, 'index']); // 👈 2. Intha route-ah add panrom


/**
 * 🔒 PROTECTED ROUTES (JWT Middleware):
 * Intha routes-ku request anupum pothu kandippa Header-la:
 * `Authorization: Bearer <token>` anupanum.
 * Token illana or fake/expired token-ah iruntha Laravel 401 Unauthorized nu reject pannidum!
 * 
 * Express: router.get('/me', verifyTokenMiddleware, authController.me);
 */
Route::middleware('auth:api')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    // 🌟 STRIPE CHECKOUT ROUTE (POST /api/checkout):
    // Login panna user mattum thaan subscription buy panna checkout URL generate panna mudiyum.
    Route::post('/checkout', [CheckoutController::class, 'checkout']);
});
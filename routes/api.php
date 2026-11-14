<?php

use Illuminate\Support\Facades\Route;

// Namma AuthController-ah import panrom
use App\Http\Controllers\AuthController;

/**
 * 🔓 PUBLIC ROUTES:
 * Yar vena access pannalam (Token thevailla).
 * Express: router.post('/register', authController.register);
 */
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

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
});
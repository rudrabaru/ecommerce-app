<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AddressController;
use Modules\Payments\Http\Controllers\StripeController;
use Modules\Payments\Http\Controllers\RazorpayController;

// User address routes
Route::middleware('auth:api')->prefix('user')->group(function () {
    Route::apiResource('addresses', AddressController::class);
});

// Payment initiation endpoints (authenticated)
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::post('/payment/stripe/initiate', [StripeController::class, 'initiate']);
    Route::post('/payment/razorpay/initiate', [RazorpayController::class, 'initiate']);
});

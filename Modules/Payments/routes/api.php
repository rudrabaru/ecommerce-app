<?php

use Illuminate\Support\Facades\Route;
use Modules\Payments\Http\Controllers\PaymentsController;
use Modules\Payments\Http\Controllers\StripeController;
use Modules\Payments\Http\Controllers\RazorpayController;

/*
 *--------------------------------------------------------------------------
 * API Routes
 *--------------------------------------------------------------------------
 *
 * Here is where you can register API routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * is assigned the "api" middleware group. Enjoy building your API!
 *
*/

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('payments', PaymentsController::class)->names('payments');

    Route::post('/payment/stripe/initiate', [StripeController::class, 'initiate'])->name('payment.stripe.initiate');
    Route::post('/payment/razorpay/initiate', [RazorpayController::class, 'initiate'])->name('payment.razorpay.initiate');
});

Route::post('/payment/stripe/webhook', [StripeController::class, 'webhook'])->name('payment.stripe.webhook');
Route::post('/payment/razorpay/webhook', [RazorpayController::class, 'webhook'])->name('payment.razorpay.webhook');

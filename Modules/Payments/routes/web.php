<?php

use Illuminate\Support\Facades\Route;
use Modules\Payments\Http\Controllers\PaymentsController;
use Modules\Payments\Http\Controllers\StripeController;
use Modules\Payments\Http\Controllers\RazorpayController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Public Stripe webhook endpoint for Stripe CLI/local webhooks
// CSRF should be disabled for this endpoint
Route::post('/payment/stripe/webhook', [StripeController::class, 'webhook'])
    ->name('payment.stripe.webhook')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

Route::middleware(['web','auth'])->group(function () {
    // Admin routes - only admins can access
    Route::middleware(['ensure_role:admin'])->prefix('admin')->as('admin.')->group(function () {
        Route::get('payments/data', [PaymentsController::class, 'data'])->name('payments.data');
        Route::resource('payments', PaymentsController::class)->names('payments');
    });

    // Provider routes - only providers can access
    Route::middleware(['ensure_role:provider'])->prefix('provider')->as('provider.')->group(function () {
        Route::get('payments/data', [PaymentsController::class, 'data'])->name('payments.data');
        Route::resource('payments', PaymentsController::class)->names('payments');
    });

    // Initiation endpoints for logged-in users (session auth)
    Route::post('/payment/stripe/initiate', [StripeController::class, 'initiate'])->name('payment.stripe.initiate');
    Route::post('/payment/stripe/confirm', [StripeController::class, 'confirm'])->name('payment.stripe.confirm');
    Route::post('/payment/razorpay/initiate', [RazorpayController::class, 'initiate'])->name('payment.razorpay.initiate');
    Route::post('/payment/razorpay/confirm', [RazorpayController::class, 'confirm'])->name('payment.razorpay.confirm');
});

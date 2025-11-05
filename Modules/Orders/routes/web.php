<?php

use Illuminate\Support\Facades\Route;
use Modules\Orders\Http\Controllers\OrdersController;

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

Route::middleware(['web', 'auth'])->group(function () {
    // Provider Orders - only providers can access
    Route::middleware(['ensure_role:provider'])->prefix('provider')->as('provider.')->group(function () {
        Route::get('orders/data', [OrdersController::class, 'data'])->name('orders.data');
        Route::get('orders/modal-data', [OrdersController::class, 'modalData'])->name('orders.modal-data');
        Route::post('orders/{order}/update-status', [OrdersController::class, 'updateStatus'])->name('orders.update-status');
        Route::post('orders/{order}/items/{item}/update-status', [OrdersController::class, 'updateItemStatus'])->name('orders.items.update-status');
        Route::resource('orders', OrdersController::class)->names('orders');
    });

    // Admin Orders - only admins can access
    Route::middleware(['ensure_role:admin'])->prefix('admin')->as('admin.')->group(function () {
        Route::get('orders/data', [OrdersController::class, 'data'])->name('orders.data');
        Route::get('orders/modal-data', [OrdersController::class, 'modalData'])->name('orders.modal-data');
        Route::post('orders/eligible-discounts', [OrdersController::class, 'eligibleDiscounts'])->name('orders.eligible_discounts');
        Route::post('orders/{order}/update-status', [OrdersController::class, 'updateStatus'])->name('orders.update-status');
        Route::post('orders/{order}/items/{item}/update-status', [OrdersController::class, 'updateItemStatus'])->name('orders.items.update-status');
        Route::resource('orders', OrdersController::class)->names('orders');
    });

    // User Orders - users can cancel their own pending orders and poll status
    Route::middleware(['ensure_role:user'])->prefix('user')->as('user.')->group(function () {
        Route::post('orders/{order}/cancel', [OrdersController::class, 'cancel'])->name('orders.cancel');
        Route::get('orders/status', [OrdersController::class, 'userStatuses'])->name('orders.status');
    });

    // Order item status update routes removed
});

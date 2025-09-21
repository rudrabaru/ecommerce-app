<?php

use Illuminate\Support\Facades\Route;
use Modules\Provider\Http\Controllers\ProviderController;
use Modules\Provider\Http\Controllers\ProviderProfileController;

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

Route::middleware(['web','auth','ensure_role:provider'])->group(function () {
    Route::get('/provider/dashboard', function () {
        if (request()->ajax()) {
            return view('provider::dashboard');
        }
        return view('provider::dashboard');
    })->name('provider.dashboard');
    Route::get('/provider/profile', [ProviderProfileController::class, 'edit'])->name('provider.profile');
    
    // Dashboard data endpoints
    Route::get('/provider/dashboard/stats', [\App\Http\Controllers\ProviderDashboardController::class, 'stats'])->name('provider.dashboard.stats');
    Route::get('/provider/dashboard/recent-orders', [\App\Http\Controllers\ProviderDashboardController::class, 'recentOrders'])->name('provider.dashboard.recent-orders');
    Route::get('/provider/dashboard/my-products', [\App\Http\Controllers\ProviderDashboardController::class, 'myProducts'])->name('provider.dashboard.my-products');
});

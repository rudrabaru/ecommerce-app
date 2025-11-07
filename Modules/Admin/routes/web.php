<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\AdminController;
use Modules\Admin\Http\Controllers\AdminProfileController;
use Modules\Admin\app\Http\Controllers\DiscountCodeController;

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

Route::middleware(['web','auth','ensure_role:admin'])->group(function () {
    Route::get('/admin/dashboard', function () {
        return view('admin::dashboard');
    })->name('admin.dashboard');
    Route::get('/admin/profile', [AdminProfileController::class, 'edit'])->name('admin.profile');

    // Dashboard data endpoints
    Route::get('/admin/dashboard/stats', [\App\Http\Controllers\AdminDashboardController::class, 'stats'])->name('admin.dashboard.stats');
    Route::get('/admin/dashboard/recent-users', [\App\Http\Controllers\AdminDashboardController::class, 'recentUsers'])->name('admin.dashboard.recent-users');
    Route::get('/admin/dashboard/recent-products', [\App\Http\Controllers\AdminDashboardController::class, 'recentProducts'])->name('admin.dashboard.recent-products');

    // Users management (role=user) - Full CRUD with AJAX datatables
    Route::get('/admin/users', [AdminController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/users/create', [AdminController::class, 'create'])->name('admin.users.create');
    Route::post('/admin/users', [AdminController::class, 'store'])->name('admin.users.store');
    Route::get('/admin/users/{user}/edit', [AdminController::class, 'edit'])->name('admin.users.edit');
    Route::put('/admin/users/{user}', [AdminController::class, 'update'])->name('admin.users.update');
    Route::get('/admin/users/data', [AdminController::class, 'data'])->name('admin.users.data');
    Route::delete('/admin/users/{user}', [AdminController::class, 'destroy'])->name('admin.users.destroy');
    // Verify toggle endpoint
    Route::post('/admin/users/{user}/verify', [AdminController::class, 'verify'])->name('admin.users.verify');
    Route::post('/admin/users/{user}/promote', [AdminController::class, 'promoteToProvider'])->name('admin.users.promote');

    // Providers management (role=provider) - Full CRUD with AJAX datatables
    Route::get('/admin/providers', [AdminController::class, 'providersIndex'])->name('admin.providers.index');
    Route::get('/admin/providers/create', [AdminController::class, 'createProvider'])->name('admin.providers.create');
    Route::post('/admin/providers', [AdminController::class, 'storeProvider'])->name('admin.providers.store');
    Route::get('/admin/providers/{user}/edit', [AdminController::class, 'editProvider'])->name('admin.providers.edit');
    Route::put('/admin/providers/{user}', [AdminController::class, 'updateProvider'])->name('admin.providers.update');
    Route::get('/admin/providers/data', [AdminController::class, 'providersData'])->name('admin.providers.data');
    Route::delete('/admin/providers/{user}', [AdminController::class, 'destroyProvider'])->name('admin.providers.destroy');

    // Discount Codes
    Route::get('/admin/discount-codes', [DiscountCodeController::class, 'index'])->name('admin.discounts.index');
    Route::get('/admin/discount-codes/data', [DiscountCodeController::class, 'data'])->name('admin.discounts.data');
    Route::get('/admin/discount-codes/create', [DiscountCodeController::class, 'create'])->name('admin.discounts.create');
    Route::post('/admin/discount-codes', [DiscountCodeController::class, 'store'])->name('admin.discounts.store');
    Route::get('/admin/discount-codes/{discount_code}/edit', [DiscountCodeController::class, 'edit'])->name('admin.discounts.edit');
    Route::put('/admin/discount-codes/{discount_code}', [DiscountCodeController::class, 'update'])->name('admin.discounts.update');
    Route::delete('/admin/discount-codes/{discount_code}', [DiscountCodeController::class, 'destroy'])->name('admin.discounts.destroy');
});

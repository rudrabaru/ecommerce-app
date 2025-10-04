<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\AdminController;
use Modules\Admin\Http\Controllers\AdminProfileController;

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
        if (request()->ajax()) {
            return view('admin::dashboard');
        }
        return view('admin::dashboard');
    })->name('admin.dashboard');
    Route::get('/admin/profile', [AdminProfileController::class, 'edit'])->name('admin.profile');
    
    // Dashboard data endpoints
    Route::get('/admin/dashboard/stats', [\App\Http\Controllers\AdminDashboardController::class, 'stats'])->name('admin.dashboard.stats');
    Route::get('/admin/dashboard/recent-users', [\App\Http\Controllers\AdminDashboardController::class, 'recentUsers'])->name('admin.dashboard.recent-users');
    Route::get('/admin/dashboard/recent-products', [\App\Http\Controllers\AdminDashboardController::class, 'recentProducts'])->name('admin.dashboard.recent-products');
    
    // Users management (role=user)
    Route::get('/admin/users', [AdminController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/users/data', [AdminController::class, 'data'])->name('admin.users.data');
    Route::resource('admin/users', AdminController::class)->names('admin.users')->except(['index']);
    Route::post('/admin/users/{user}/promote', [AdminController::class, 'promoteToProvider'])->name('admin.users.promote');

    // Providers management (role=provider)
    Route::get('/admin/providers', [AdminController::class, 'providersIndex'])->name('admin.providers.index');
    Route::get('/admin/providers/data', [AdminController::class, 'providersData'])->name('admin.providers.data');
    
    // Products management
    Route::get('/admin/products', [\App\Http\Controllers\Admin\ProductController::class, 'index'])->name('admin.products.index');
    Route::get('/admin/products/data', [\App\Http\Controllers\Admin\ProductController::class, 'data'])->name('admin.products.data');
    Route::get('/admin/products/create', [\App\Http\Controllers\Admin\ProductController::class, 'create'])->name('admin.products.create');
    Route::post('/admin/products', [\App\Http\Controllers\Admin\ProductController::class, 'store'])->name('admin.products.store');
    Route::get('/admin/products/{product}/edit', [\App\Http\Controllers\Admin\ProductController::class, 'edit'])->name('admin.products.edit');
    Route::put('/admin/products/{product}', [\App\Http\Controllers\Admin\ProductController::class, 'update'])->name('admin.products.update');
    Route::delete('/admin/products/{product}', [\App\Http\Controllers\Admin\ProductController::class, 'destroy'])->name('admin.products.destroy');
});

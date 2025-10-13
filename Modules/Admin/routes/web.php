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

    // Users management (role=user) - Read-only with AJAX datatables
    Route::get('/admin/users', [AdminController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/users/data', [AdminController::class, 'data'])->name('admin.users.data');
    Route::delete('/admin/users/{user}', [AdminController::class, 'destroy'])->name('admin.users.destroy');
    // Verify toggle endpoint
    Route::post('/admin/users/{user}/verify', [AdminController::class, 'verify'])->name('admin.users.verify');
    Route::post('/admin/users/{user}/promote', [AdminController::class, 'promoteToProvider'])->name('admin.users.promote');

    // Providers management (role=provider) - Read-only with AJAX datatables
    Route::get('/admin/providers', [AdminController::class, 'providersIndex'])->name('admin.providers.index');
    Route::get('/admin/providers/data', [AdminController::class, 'providersData'])->name('admin.providers.data');
});

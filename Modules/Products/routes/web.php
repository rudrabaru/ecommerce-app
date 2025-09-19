<?php

use Illuminate\Support\Facades\Route;
use Modules\Products\Http\Controllers\ProductsController;
use Modules\Products\Http\Controllers\CategoryController;

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
    // Provider routes
    Route::prefix('provider')->as('provider.')->group(function () {
        Route::get('products/data', [ProductsController::class, 'data'])->name('products.data');
        Route::resource('products', ProductsController::class)->names('products');
    });

    // Admin routes
    Route::prefix('admin')->as('admin.')->group(function () {
        Route::get('products/data', [ProductsController::class, 'data'])->name('products.data');
        Route::resource('products', ProductsController::class)->names('products');
        Route::get('products/{product}/approve', [ProductsController::class, 'approve'])->name('products.approve');
        Route::get('products/{product}/block', [ProductsController::class, 'block'])->name('products.block');

        Route::get('categories/data', [CategoryController::class, 'data'])->name('categories.data');
        Route::resource('categories', CategoryController::class)->names('categories')->except(['show']);
    });
});

<?php

use Illuminate\Support\Facades\Route;
use Modules\Cart\Http\Controllers\CartController;

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

// Disabled to avoid conflicts with core session cart in app/Http/Controllers/CartController
// If needed later, scope under provider/admin prefixes with proper middleware
// Route::middleware(['web','auth','ensure_role:provider'])->prefix('provider')->as('provider.')->group(function () {
//     Route::resource('cart', CartController::class)->names('cart');
// });

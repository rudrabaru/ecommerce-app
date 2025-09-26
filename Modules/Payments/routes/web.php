<?php

use Illuminate\Support\Facades\Route;
use Modules\Payments\Http\Controllers\PaymentsController;

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

// Temporarily disabled to avoid exposing placeholder endpoints; gate behind auth when enabled
// Route::middleware(['web','auth'])->group(function () {
//     Route::resource('payments', PaymentsController::class)->names('payments');
// });

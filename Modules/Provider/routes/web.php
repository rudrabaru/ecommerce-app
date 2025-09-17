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
    Route::get('/provider/dashboard', fn () => view('provider::dashboard'))->name('provider.dashboard');
    Route::get('/provider/profile', [ProviderProfileController::class, 'edit'])->name('provider.profile');
});

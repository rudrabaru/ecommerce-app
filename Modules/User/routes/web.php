<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Http\Controllers\UserController;
use Modules\User\Http\Controllers\UserProfileController;

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

Route::middleware(['web','auth','ensure_role:user'])->group(function () {
    Route::get('/user/dashboard', fn () => view('user::dashboard'))->name('user.dashboard');
    Route::get('/user/profile', [UserProfileController::class, 'edit'])->name('user.profile');
});

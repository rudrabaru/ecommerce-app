<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Remove default Breeze dashboard in favor of role-based dashboards

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// Load modular routes if present
foreach (glob(base_path('Modules/*/Routes/web.php')) as $moduleRoutes) {
    require $moduleRoutes;
}

// OTP verification routes
Route::middleware('auth')->group(function () {
    Route::get('/verify-otp', [\App\Http\Controllers\Auth\EmailOtpController::class, 'show'])->name('verification.otp.notice');
    Route::get('/verify-otp/send', [\App\Http\Controllers\Auth\EmailOtpController::class, 'send'])->name('verification.otp.send');
    Route::post('/verify-otp', [\App\Http\Controllers\Auth\EmailOtpController::class, 'verify'])->name('verification.otp.verify');
});

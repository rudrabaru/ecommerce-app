<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AddressController;

// User address routes
Route::middleware('auth:api')->prefix('user')->group(function () {
    Route::apiResource('addresses', AddressController::class);
});

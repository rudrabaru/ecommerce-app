<?php

use Illuminate\Support\Facades\Route;
use Modules\Location\Http\Controllers\LocationController;
use App\Http\Controllers\AddressController;

// Location routes for address modal
Route::group(['prefix' => 'v1/locations', 'as' => 'api.locations.'], function () {
    Route::get('countries', [LocationController::class, 'countries'])->name('countries');
    Route::get('states/{country}', [LocationController::class, 'states'])->name('states');
    Route::get('cities/{state}', [LocationController::class, 'cities'])->name('cities');
    Route::get('phone-codes', [LocationController::class, 'phoneCodes'])->name('phonecodes');
});

// User address routes
Route::middleware('auth:api')->prefix('user')->group(function () {
    Route::apiResource('addresses', AddressController::class);
});
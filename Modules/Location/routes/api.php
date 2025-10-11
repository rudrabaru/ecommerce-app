<?php

use Illuminate\Support\Facades\Route;
use Modules\Location\Http\Controllers\LocationController;

/*
 *--------------------------------------------------------------------------
 * API Routes
 *--------------------------------------------------------------------------
 *
 * Here is where you can register API routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * is assigned the "api" middleware group. Enjoy building your API!
 *
*/

Route::group([
    'prefix' => 'v1',
    'middleware' => ['api'],
    'as' => 'locations.',
], function () {
    Route::get('locations/countries', [LocationController::class, 'countries'])
        ->withoutMiddleware(['auth:sanctum'])
        ->name('countries');
    
    Route::get('locations/states/{country}', [LocationController::class, 'states'])
        ->withoutMiddleware(['auth:sanctum'])
        ->name('states');
    
    Route::get('locations/cities/{state}', [LocationController::class, 'cities'])
        ->withoutMiddleware(['auth:sanctum'])
        ->name('cities');
    
    Route::get('locations/phone-codes', [LocationController::class, 'phoneCodes'])
        ->withoutMiddleware(['auth:sanctum'])
        ->name('phonecodes');
});

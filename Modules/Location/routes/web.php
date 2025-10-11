<?php

use Illuminate\Support\Facades\Route;
use Modules\Location\Http\Controllers\LocationController;

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

Route::group(['as' => 'locations.'], function () {
    Route::get('locations/countries', [LocationController::class, 'countries'])->name('countries');
    Route::get('locations/states/{country}', [LocationController::class, 'states'])->name('states');
    Route::get('locations/cities/{state}', [LocationController::class, 'cities'])->name('cities');
    Route::get('locations/phone-codes', [LocationController::class, 'phoneCodes'])->name('phonecodes');
});

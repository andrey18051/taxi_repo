<?php

use App\Http\Controllers\AndroidController;
use App\Http\Controllers\ComboTestController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\ServicesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::get('/driver', [DriverController::class, 'index'])->name('driver');

Route::get('/driverAuto/{city}/{first_name}/{second_name}/{mail}/{phone}/{brand}/{model}/{type}/{color}/{year}/{number}/{services}', [DriverController::class, 'auto'])->name('brand');
Route::get('/driverAuto/sendCode/{phone}', [DriverController::class, 'sendCode'])->name('sendCode');
Route::get('/driverAuto/approvedPhones/{phone}/{code}', [DriverController::class, 'approvedPhones'])->name('approvedPhones');

Route::get('/servicesAdd/{name}/{email}', [ServicesController::class, 'servicesAdd'])->name('servicesAdd');
Route::get('/servicesAll', [ServicesController::class, 'servicesAll'])->name('servicesAll');
Route::get('/servicesAll/Android', [ServicesController::class, 'servicesAllAndroid'])->name('servicesAllAndroid');

/**
 * Android Taxi
 */
Route::get('/android', [AndroidController::class, 'index'])->name('driver');

Route::get('/android/comboTest', [ComboTestController::class, 'insertComboTest'])
    ->name('insertComboTest');

Route::get('/android/comboTest/index', [ComboTestController::class, 'index'])
    ->name('insertComboTestIndex');

Route::middleware('throttle:6,1')->get('/android/costMap/{originLatitude}/{originLongitude}/{destLatitude}/{destLongitude}/{tarif}', [AndroidController::class, 'costMap'])
    ->name('costMap');
Route::middleware('throttle:6,1')->get('/android/orderMap/{originLatitude}/{originLongitude}/{destLatitude}/{destLongitude}/{tarif}/{phone}', [AndroidController::class, 'orderMap'])
    ->name('orderMap');

Route::get('/android/costSearch/{from}/{from_number}/{to}/{to_number}/{tarif}', [AndroidController::class, 'costSearch'])
    ->name('costSearch');
Route::middleware('throttle:6,1')->get('/android/orderSearch/{from}/{from_number}/{to}/{to_number}/{tarif}/{phone}', [AndroidController::class, 'orderSearch'])
    ->name('orderSearch');

Route::get('/android/costSearchGeo/{originLatitude}/{originLongitude}/{to}/{to_number}/{tarif}', [AndroidController::class, 'costSearchGeo'])
    ->name('costSearchGeo');
Route::get('/android/orderSearchGeo/{originLatitude}/{originLongitude}/{to}/{to_number}/{tarif}/{phone}', [AndroidController::class, 'orderSearchGeo'])
    ->name('orderSearchGeo');

Route::get('/android/fromSearchGeo/{originLatitude}/{originLongitude}', [AndroidController::class, 'fromSearchGeo'])
    ->name('fromSearchGeo');


Route::get('/android/sendCode/{phone}', [AndroidController::class, 'sendCode'])->name('sendCode');
Route::get('/android/approvedPhones/{phone}/{code}', [AndroidController::class, 'approvedPhones'])->name('approvedPhones');

Route::get('/android/sendCodeTest/{phone}', [AndroidController::class, 'sendCodeTest'])->name('sendCode');
Route::get('/android/approvedPhonesTest/{phone}/{code}', [AndroidController::class, 'approvedPhonesTest'])->name('approvedPhones');
Route::get('/android/autocompleteSearchComboHid/{name}', [AndroidController::class, 'autocompleteSearchComboHid'])->name('autocompleteSearchComboHid');

Route::get('/android/sentPhone/{message}', [AndroidController::class, 'sentPhone'])->name('sentPhone');

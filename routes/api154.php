<?php

use App\Http\Controllers\Android154Controller;
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
Route::get('/android', [Android154Controller::class, 'index'])->name('driver');

Route::get('/android/comboTest', [ComboTestController::class, 'insertComboTest'])
    ->name('insertComboTest');

Route::get('/android/comboTest/index', [ComboTestController::class, 'index'])
    ->name('insertComboTestIndex');

Route::middleware('throttle:6,1')->get('/android/costMap/{originLatitude}/{originLongitude}/{destLatitude}/{destLongitude}/{tarif}', [Android154Controller::class, 'costMap'])
    ->name('costMap');
Route::middleware('throttle:6,1')->get('/android/orderMap/{originLatitude}/{originLongitude}/{destLatitude}/{destLongitude}/{tarif}/{phone}', [Android154Controller::class, 'orderMap'])
    ->name('orderMap');

Route::get('/android/costSearch/{from}/{from_number}/{to}/{to_number}/{tarif}/{phone}/{user}/', [Android154Controller::class, 'costSearch'])
    ->name('costSearch');
Route::middleware('throttle:6,1')->get('/android/orderSearch/{from}/{from_number}/{to}/{to_number}/{tarif}/{phone}/{user}', [Android154Controller::class, 'orderSearch'])
    ->name('orderSearch');

Route::get('/android/costSearchGeo/{originLatitude}/{originLongitude}/{to}/{to_number}/{tarif}/{phone}/{user}/', [Android154Controller::class, 'costSearchGeo'])
    ->name('costSearchGeo');
Route::get('/android/orderSearchGeo/{originLatitude}/{originLongitude}/{to}/{to_number}/{tarif}/{phone}/{user}', [Android154Controller::class, 'orderSearchGeo'])
    ->name('orderSearchGeo');

Route::get('/android/fromSearchGeo/{originLatitude}/{originLongitude}', [Android154Controller::class, 'fromSearchGeo'])
    ->name('fromSearchGeo');


Route::get('/android/sendCode/{phone}', [Android154Controller::class, 'sendCode'])->name('sendCode');
Route::get('/android/approvedPhones/{phone}/{code}', [Android154Controller::class, 'approvedPhones'])->name('approvedPhones');

Route::get('/android/sendCodeTest/{phone}', [Android154Controller::class, 'sendCodeTest'])->name('sendCode');
Route::get('/android/approvedPhonesTest/{phone}/{code}', [Android154Controller::class, 'approvedPhonesTest'])->name('approvedPhones');
Route::get('/android/autocompleteSearchComboHid/{name}', [Android154Controller::class, 'autocompleteSearchComboHid'])->name('autocompleteSearchComboHid');

Route::get('/android/sentPhone/{message}', [Android154Controller::class, 'sentPhone'])->name('sentPhone');
Route::get('/android/checkDomain/{domain}', [AndroidController::class, 'checkDomain'])->name('checkDomain');



Route::get('/android/addUser/{name}/{email}', [Android154Controller::class, 'addUser'])->name('checkDomain');
Route::get('/android/verifyBlackListUser/{email}', [Android154Controller::class, 'verifyBlackListUser'])->name('verifyBlackListUser');

Route::get('/android/startIP', [Android154Controller::class, 'startIP'])->name('startIP');

Route::get('/android/costSearchMarkers/{originLatitude}/{originLongitude}/{toLatitude}/{toLongitude}/{tarif}/{phone}/{user}/', [Android154Controller::class, 'costSearchMarkers'])
    ->name('costSearchMarkers');

Route::get('/android/orderSearchMarkers/{originLatitude}/{originLongitude}/{toLatitude}/{toLongitude}/{tarif}/{phone}/{user}/', [Android154Controller::class, 'orderSearchMarkers'])
    ->name('orderSearchMarkers');

<?php

use App\Http\Controllers\AndroidPas3Controller;
use App\Http\Controllers\ComboTestController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\ServicesController;
use App\Http\Controllers\VisicomController;
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
Route::get('/android', [AndroidPas3Controller::class, 'index'])->name('driver');

Route::get('/android/comboPas2', [ComboTestController::class, 'insertComboTest'])
    ->name('insertComboTest');

Route::get('/android/comboTest/index', [ComboTestController::class, 'index'])
    ->name('insertComboTestIndex');

Route::middleware('throttle:6,1')->get('/android/costMap/{originLatitude}/{originLongitude}/{destLatitude}/{destLongitude}/{tarif}', [AndroidPas3Controller::class, 'costMap'])
    ->name('costMap');
Route::middleware('throttle:6,1')->get('/android/orderMap/{originLatitude}/{originLongitude}/{destLatitude}/{destLongitude}/{tarif}/{phone}', [AndroidPas3Controller::class, 'orderMap'])
    ->name('orderMap');

Route::get('/android/costSearch/{from}/{from_number}/{to}/{to_number}/{tarif}/{phone}/{user}/{services}/', [AndroidPas3Controller::class, 'costSearch'])
    ->name('costSearch');
Route::get('/android/orderSearch/{from}/{from_number}/{to}/{to_number}/{tarif}/{phone}/{user}/{add_cost}/{time}/{comment}/{date}/{services}', [AndroidPas3Controller::class, 'orderSearch'])
    ->name('orderSearch');

Route::get('/android/costSearchGeo/{originLatitude}/{originLongitude}/{to}/{to_number}/{tarif}/{phone}/{user}/{services}', [AndroidPas3Controller::class, 'costSearchGeo'])
    ->name('costSearchGeo');
Route::get('/android/orderSearchGeo/{originLatitude}/{originLongitude}/{to}/{to_number}/{tarif}/{phone}/{user}/{add_cost}/{time}/{comment}/{date}/{services}', [AndroidPas3Controller::class, 'orderSearchGeo'])
    ->name('orderSearchGeo');

Route::get('/android/fromSearchGeo/{originLatitude}/{originLongitude}', [AndroidPas3Controller::class, 'fromSearchGeo'])
    ->name('fromSearchGeo');


Route::get('/android/sendCode/{phone}', [AndroidPas3Controller::class, 'sendCode'])->name('sendCode');
Route::get('/android/approvedPhones/{phone}/{code}', [AndroidPas3Controller::class, 'approvedPhones'])->name('approvedPhones');

Route::get('/android/sendCodeTest/{phone}', [AndroidPas3Controller::class, 'sendCodeTest'])->name('sendCode');
Route::get('/android/approvedPhonesTest/{phone}/{code}', [AndroidPas3Controller::class, 'approvedPhonesTest'])->name('approvedPhones');
Route::get('/android/autocompleteSearchComboHid/{name}', [AndroidPas3Controller::class, 'autocompleteSearchComboHid'])->name('autocompleteSearchComboHid');

Route::get('/android/sentPhone/{message}', [AndroidPas3Controller::class, 'sentPhone'])->name('sentPhone');
Route::get('/android/checkDomain/{domain}', [AndroidController::class, 'checkDomain'])->name('checkDomain');



Route::get('/android/addUser/{name}/{email}', [AndroidPas3Controller::class, 'addUser'])->name('checkDomain');
Route::get('/android/verifyBlackListUser/{email}', [AndroidPas3Controller::class, 'verifyBlackListUser'])->name('verifyBlackListUser');

Route::get('/android/startIP', [AndroidPas3Controller::class, 'startIP'])->name('startIP');

Route::get('/android/costSearchMarkers/{originLatitude}/{originLongitude}/{toLatitude}/{toLongitude}/{tarif}/{phone}/{user}/{services}/', [AndroidPas3Controller::class, 'costSearchMarkers'])
    ->name('costSearchMarkers');

Route::get('/android/orderSearchMarkers/{originLatitude}/{originLongitude}/{toLatitude}/{toLongitude}/{tarif}/{phone}/{user}/{add_cost}/{time}/{comment}/{date}/{services}', [AndroidPas3Controller::class, 'orderSearchMarkers'])
    ->name('orderSearchMarkers');

Route::get('/android/versionAPI/', [AndroidPas3Controller::class, 'version'])
    ->name('version');

Route::get('/android/myHistory/', [AndroidPas3Controller::class, 'myHistory'])
    ->name('myHistory');

Route::get('/android/historyUID/{uid}', [AndroidPas3Controller::class, 'historyUID'])
    ->name('myHistory');

Route::get('/android/apiVersion/', [AndroidPas3Controller::class, 'apiVersion'])
    ->name('apiVersion');

Route::get('/android/geoDataSearch/{to}/{to_number}', [AndroidPas3Controller::class, 'geoDataSearch'])
    ->name('geoDataSearch');

Route::get('/android/geoDataSearchStreet/{to}/{to_number}', [AndroidPas3Controller::class, 'geoDataSearchStreet'])
    ->name('geoDataSearchStreet');

Route::get('/android/geoDataSearchObject/{to}', [AndroidPas3Controller::class, 'geoDataSearchObject'])
    ->name('geoDataSearchObject');

Route::get('/android/geoLatLanSearch/{originLatitude}/{originLongitude}', [AndroidPas3Controller::class, 'geoLatLanSearch'])
    ->name('geoLatLanSearch');

Route::get('/android/visicom/show/{settlement}', [VisicomController::class, 'show'])
    ->name('visicom_show');

Route::get('/android/webordersCancel/{uid}', [AndroidPas3Controller::class, 'webordersCancel'])
    ->name('webordersCancel');

Route::get('/android/historyUIDStatus/{uid}', [AndroidPas3Controller::class, 'historyUIDStatus'])
    ->name('myHistoryStatus');

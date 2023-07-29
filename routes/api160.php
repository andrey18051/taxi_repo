<?php

use App\Http\Controllers\Android160Controller;
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
Route::get('/android', [Android160Controller::class, 'index'])->name('driver');

Route::get('/android/comboTest', [ComboTestController::class, 'insertComboTest'])
    ->name('insertComboTest');

Route::get('/android/comboTest/index', [ComboTestController::class, 'index'])
    ->name('insertComboTestIndex');

Route::middleware('throttle:6,1')->get('/android/costMap/{originLatitude}/{originLongitude}/{destLatitude}/{destLongitude}/{tarif}', [Android160Controller::class, 'costMap'])
    ->name('costMap');
Route::middleware('throttle:6,1')->get('/android/orderMap/{originLatitude}/{originLongitude}/{destLatitude}/{destLongitude}/{tarif}/{phone}', [Android160Controller::class, 'orderMap'])
    ->name('orderMap');

Route::get('/android/costSearch/{from}/{from_number}/{to}/{to_number}/{tarif}/{phone}/{user}/{services}/', [Android160Controller::class, 'costSearch'])
    ->name('costSearch');
Route::get('/android/orderSearch/{from}/{from_number}/{to}/{to_number}/{tarif}/{phone}/{user}/{add_cost}/{time}/{comment}/{date}/{services}', [Android160Controller::class, 'orderSearch'])
    ->name('orderSearch');

Route::get('/android/costSearchGeo/{originLatitude}/{originLongitude}/{to}/{to_number}/{tarif}/{phone}/{user}/{services}', [Android160Controller::class, 'costSearchGeo'])
    ->name('costSearchGeo');
Route::get('/android/orderSearchGeo/{originLatitude}/{originLongitude}/{to}/{to_number}/{tarif}/{phone}/{user}/{add_cost}/{time}/{comment}/{date}/{services}', [Android160Controller::class, 'orderSearchGeo'])
    ->name('orderSearchGeo');

Route::get('/android/fromSearchGeo/{originLatitude}/{originLongitude}', [Android160Controller::class, 'fromSearchGeo'])
    ->name('fromSearchGeo');


Route::get('/android/sendCode/{phone}', [Android160Controller::class, 'sendCode'])->name('sendCode');
Route::get('/android/approvedPhones/{phone}/{code}', [Android160Controller::class, 'approvedPhones'])->name('approvedPhones');

Route::get('/android/sendCodeTest/{phone}', [Android160Controller::class, 'sendCodeTest'])->name('sendCode');
Route::get('/android/approvedPhonesTest/{phone}/{code}', [Android160Controller::class, 'approvedPhonesTest'])->name('approvedPhones');
Route::get('/android/autocompleteSearchComboHid/{name}', [Android160Controller::class, 'autocompleteSearchComboHid'])->name('autocompleteSearchComboHid');

Route::get('/android/sentPhone/{message}', [Android160Controller::class, 'sentPhone'])->name('sentPhone');
Route::get('/android/checkDomain/{domain}', [AndroidController::class, 'checkDomain'])->name('checkDomain');



Route::get('/android/addUser/{name}/{email}', [Android160Controller::class, 'addUser'])->name('checkDomain');
Route::get('/android/verifyBlackListUser/{email}', [Android160Controller::class, 'verifyBlackListUser'])->name('verifyBlackListUser');

Route::get('/android/startIP', [Android160Controller::class, 'startIP'])->name('startIP');

Route::get('/android/costSearchMarkers/{originLatitude}/{originLongitude}/{toLatitude}/{toLongitude}/{tarif}/{phone}/{user}/{services}/', [Android160Controller::class, 'costSearchMarkers'])
    ->name('costSearchMarkers');

Route::get('/android/orderSearchMarkers/{originLatitude}/{originLongitude}/{toLatitude}/{toLongitude}/{tarif}/{phone}/{user}/{add_cost}/{time}/{comment}/{date}/{services}', [Android160Controller::class, 'orderSearchMarkers'])
    ->name('orderSearchMarkers');

Route::get('/android/versionAPI/', [Android160Controller::class, 'version'])
    ->name('version');

Route::get('/android/myHistory/', [Android160Controller::class, 'myHistory'])
    ->name('myHistory');

Route::get('/android/historyUID/{uid}', [Android160Controller::class, 'historyUID'])
    ->name('myHistory');

Route::get('/android/apiVersion/', [Android160Controller::class, 'apiVersion'])
    ->name('apiVersion');

Route::get('/android/geoDataSearch/{to}/{to_number}', [Android160Controller::class, 'geoDataSearch'])
    ->name('geoDataSearch');

Route::get('/android/geoDataSearchStreet/{to}/{to_number}', [Android160Controller::class, 'geoDataSearchStreet'])
    ->name('geoDataSearchStreet');

Route::get('/android/geoDataSearchObject/{to}', [Android160Controller::class, 'geoDataSearchObject'])
    ->name('geoDataSearchObject');

Route::get('/android/geoLatLanSearch/{originLatitude}/{originLongitude}', [Android160Controller::class, 'geoLatLanSearch'])
    ->name('geoLatLanSearch');

Route::get('/android/visicom/show/{settlement}', [VisicomController::class, 'show'])
    ->name('visicom_show');

Route::get('/android/webordersCancel/{uid}', [Android160Controller::class, 'webordersCancel'])
    ->name('webordersCancel');

Route::get('/android/historyUIDStatus/{uid}', [Android160Controller::class, 'historyUIDStatus'])
    ->name('myHistoryStatus');

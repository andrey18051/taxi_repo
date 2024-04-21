<?php

use App\Http\Controllers\AndroidAppController;
use App\Http\Controllers\ComboTestController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\ServicesController;
use App\Http\Controllers\UniversalAndroidFunctionController;
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
Route::get('/android', [AndroidAppController::class, 'index'])->name('driver');

Route::get('/android/comboTest', [ComboTestController::class, 'insertComboTest'])
    ->name('insertComboTest');

Route::get('/android/comboTest/index', [ComboTestController::class, 'index'])
    ->name('insertComboTestIndex');

Route::middleware('throttle:6,1')->get('/android/costMap/{originLatitude}/{originLongitude}/{destLatitude}/{destLongitude}/{tarif}', [AndroidAppController::class, 'costMap'])
    ->name('costMap');
Route::middleware('throttle:6,1')->get('/android/orderMap/{originLatitude}/{originLongitude}/{destLatitude}/{destLongitude}/{tarif}/{phone}', [AndroidAppController::class, 'orderMap'])
    ->name('orderMap');

Route::get('/android/costSearch/{from}/{from_number}/{to}/{to_number}/{tarif}/{phone}/{user}/{services}/{city}/{application}/', [AndroidAppController::class, 'costSearch'])
    ->name('costSearch');
Route::get('/android/orderSearch/{from}/{from_number}/{to}/{to_number}/{tarif}/{phone}/{user}/{add_cost}/{time}/{comment}/{date}/{services}/{city}/{application}/', [AndroidAppController::class, 'orderSearch'])
    ->name('orderSearch');

Route::get('/android/costSearchGeo/{originLatitude}/{originLongitude}/{to}/{to_number}/{tarif}/{phone}/{user}/{services}', [AndroidAppController::class, 'costSearchGeo'])
    ->name('costSearchGeo');
Route::get('/android/orderSearchGeo/{originLatitude}/{originLongitude}/{to}/{to_number}/{tarif}/{phone}/{user}/{add_cost}/{time}/{comment}/{date}/{services}', [AndroidAppController::class, 'orderSearchGeo'])
    ->name('orderSearchGeo');

Route::get('/android/fromSearchGeo/{originLatitude}/{originLongitude}', [AndroidAppController::class, 'fromSearchGeo'])
    ->name('fromSearchGeo');


Route::get('/android/sendCode/{phone}', [AndroidAppController::class, 'sendCode'])->name('sendCode');
Route::get('/android/approvedPhones/{phone}/{code}', [AndroidAppController::class, 'approvedPhones'])->name('approvedPhones');

Route::get('/android/sendCodeTest/{phone}', [AndroidAppController::class, 'sendCodeTest'])->name('sendCode');
Route::get('/android/approvedPhonesTest/{phone}/{code}', [AndroidAppController::class, 'approvedPhonesTest'])->name('approvedPhones');
Route::get('/android/autocompleteSearchComboHid/{name}/{city}/{app}', [AndroidAppController::class, 'autocompleteSearchComboHid'])->name('autocompleteSearchComboHid');

Route::get('/android/sentPhone/{message}', [AndroidAppController::class, 'sentPhone'])->name('sentPhone');
Route::get('/android/checkDomain/{domain}', [AndroidController::class, 'checkDomain'])->name('checkDomain');



Route::get('/android/addUser/{name}/{email}', [AndroidAppController::class, 'addUser'])->name('checkDomain');
Route::get('/android/verifyBlackListUser/{email}', [AndroidAppController::class, 'verifyBlackListUser'])->name('verifyBlackListUser');



Route::get('/android/costSearchMarkers/{originLatitude}/{originLongitude}/{toLatitude}/{toLongitude}/{tarif}/{phone}/{user}/{services}/{city}/{application}/', [AndroidAppController::class, 'costSearchMarkers'])
    ->name('costSearchMarkers');

Route::get('/android/orderSearchMarkers/{originLatitude}/{originLongitude}/{toLatitude}/{toLongitude}/{tarif}/{phone}/{user}/{add_cost}/{time}/{comment}/{date}/{services}/{city}/{application}/', [AndroidAppController::class, 'orderSearchMarkers'])
    ->name('orderSearchMarkers');

Route::get('/android/orderSearchMarkersVisicom/{originLatitude}/{originLongitude}/{toLatitude}/{toLongitude}/{tarif}/{phone}/{user}/{add_cost}/{time}/{comment}/{date}/{start}/{finish}/{services}/{city}/{application}/', [AndroidAppController::class, 'orderSearchMarkersVisicom'])
    ->name('orderSearchMarkers');

Route::get('/android/versionAPI/', [AndroidAppController::class, 'version'])
    ->name('version');

Route::get('/android/myHistory/', [AndroidAppController::class, 'myHistory'])
    ->name('myHistory');

Route::get('/android/historyUID/{uid}', [AndroidAppController::class, 'historyUID'])
    ->name('myHistory');

Route::get('/android/apiVersion/', [AndroidAppController::class, 'apiVersion'])
    ->name('apiVersion');

Route::get('/android/geoDataSearch/{to}/{to_number}', [AndroidAppController::class, 'geoDataSearch'])
    ->name('geoDataSearch');

Route::get('/android/geoDataSearchStreet/{to}/{to_number}', [AndroidAppController::class, 'geoDataSearchStreet'])
    ->name('geoDataSearchStreet');

Route::get('/android/geoDataSearchObject/{to}', [AndroidAppController::class, 'geoDataSearchObject'])
    ->name('geoDataSearchObject');

Route::get('/android/geoLatLanSearch/{originLatitude}/{originLongitude}', [AndroidAppController::class, 'geoLatLanSearch'])
    ->name('geoLatLanSearch');

Route::get('/android/visicom/show/{settlement}', [VisicomController::class, 'show'])
    ->name('visicom_show');

Route::get('/android/webordersCancel/{uid}/{city}/{application}', [AndroidAppController::class, 'webordersCancel'])
    ->name('webordersCancel');

Route::get('/android/webordersCancelDouble/{uid}/{uid_Double}/{payment_type}/{city}/{application}', [AndroidAppController::class, 'webordersCancelDouble'])
    ->name('webordersCancelDouble');

Route::get('/android/historyUIDStatus/{uid}/{city}/{application}', [AndroidAppController::class, 'historyUIDStatus'])
    ->name('myHistoryStatus');


/**
 * UID
 */

Route::get('/android/UIDStatus/{uid}', [AndroidAppController::class, 'UIDStatus'])
    ->name('UIDStatus');


/**
 * Base Comdo_Test
 */

Route::get('/versionComboOdessa', [AndroidAppController::class, 'versionComboOdessa'])
    ->name('versionComboOdessa');

/**
 * Permissions
 */
Route::get('/android/permissions/{email}', [UniversalAndroidFunctionController::class, 'userPermissions'])
    ->name('userPermissions');

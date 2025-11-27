<?php

use App\Http\Controllers\AndroidTestOSMController;
use App\Http\Controllers\ComboTestController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\FCMController;
use App\Http\Controllers\PusherController;
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
Route::get('/brandsAll/Android', [ServicesController::class, 'brandsAllAndroid'])->name('servicesAllAndroid');

/**
 * Android Taxi
 */
Route::get('/android', [AndroidTestOSMController::class, 'index'])->name('driver');

Route::get('/android/comboTest', [ComboTestController::class, 'insertComboTest'])
    ->name('insertComboTest');

Route::get('/android/comboTest/index', [ComboTestController::class, 'index'])
    ->name('insertComboTestIndex');

Route::middleware('throttle:6,1')->get('/android/costMap/{originLatitude}/{originLongitude}/{destLatitude}/{destLongitude}/{tarif}', [AndroidTestOSMController::class, 'costMap'])
    ->name('costMap');
Route::middleware('throttle:6,1')->get('/android/orderMap/{originLatitude}/{originLongitude}/{destLatitude}/{destLongitude}/{tarif}/{phone}', [AndroidTestOSMController::class, 'orderMap'])
    ->name('orderMap');

Route::get('/android/costSearch/{from}/{from_number}/{to}/{to_number}/{tarif}/{phone}/{user}/{services}/{city}/{application}/', [AndroidTestOSMController::class, 'costSearch'])
    ->name('costSearch');
Route::get('/android/costSearchTime/{from}/{from_number}/{to}/{to_number}/{tarif}/{phone}/{user}/{time}/{date}/{services}/{city}/{application}/', [AndroidTestOSMController::class, 'costSearchTime'])
    ->name('costSearchTime');
Route::get('/android/orderSearch/{from}/{from_number}/{to}/{to_number}/{tarif}/{phone}/{user}/{add_cost}/{time}/{comment}/{date}/{services}/{city}/{application}/', [AndroidTestOSMController::class, 'orderSearch'])
    ->name('orderSearch');
Route::get('/android/orderSearchWfpInvoice/{from}/{from_number}/{to}/{to_number}/{tarif}/{phone}/{user}/{add_cost}/{time}/{comment}/{date}/{wfpInvoice}/{services}/{city}/{application}/', [AndroidTestOSMController::class, 'orderSearchWfpInvoice'])
    ->name('orderSearchWfpInvoice');

Route::get('/android/orderOldClientCost/{from}/{from_number}/{to}/{to_number}/{tarif}/{phone}/{clientCost}/{user}/{add_cost}/{time}/{comment}/{date}/{wfpInvoice}/{services}/{city}/{application}/', [AndroidTestOSMController::class, 'orderOldClientCost'])
    ->name('orderOldClientCost');

Route::get('/android/costSearchGeo/{originLatitude}/{originLongitude}/{to}/{to_number}/{tarif}/{phone}/{user}/{services}', [AndroidTestOSMController::class, 'costSearchGeo'])
    ->name('costSearchGeo');
Route::get('/android/orderSearchGeo/{originLatitude}/{originLongitude}/{to}/{to_number}/{tarif}/{phone}/{user}/{add_cost}/{time}/{comment}/{date}/{services}', [AndroidTestOSMController::class, 'orderSearchGeo'])
    ->name('orderSearchGeo');

Route::get('/android/fromSearchGeo/{originLatitude}/{originLongitude}', [AndroidTestOSMController::class, 'fromSearchGeo'])
    ->name('fromSearchGeo');
Route::get('/android/fromSearchGeoLocal/{originLatitude}/{originLongitude}/{local}', [AndroidTestOSMController::class, 'fromSearchGeoLocal'])
    ->name('fromSearchGeo');


Route::get('/android/sendCode/{phone}', [AndroidTestOSMController::class, 'sendCode'])->name('sendCode');
Route::get('/android/approvedPhones/{phone}/{code}', [AndroidTestOSMController::class, 'approvedPhones'])->name('approvedPhones');

Route::get('/android/sendCodeTest/{phone}', [AndroidTestOSMController::class, 'sendCodeTest'])->name('sendCode');
Route::get('/android/approvedPhonesTest/{phone}/{code}', [AndroidTestOSMController::class, 'approvedPhonesTest'])->name('approvedPhones');
Route::get('/android/autocompleteSearchComboHid/{name}/{city}', [AndroidTestOSMController::class, 'autocompleteSearchComboHid'])->name('autocompleteSearchComboHid');

Route::get('/android/sentPhone/{message}', [AndroidTestOSMController::class, 'sentPhone'])->name('sentPhone');
Route::get('/android/checkDomain/{domain}', [AndroidController::class, 'checkDomain'])->name('checkDomain');



Route::get('/android/addUser/{name}/{email}', [AndroidTestOSMController::class, 'addUser'])->name('checkDomain');
Route::get('/android/verifyBlackListUser/{email}', [AndroidTestOSMController::class, 'verifyBlackListUser'])->name('verifyBlackListUser');



Route::get('/android/costSearchMarkers/{originLatitude}/{originLongitude}/{toLatitude}/{toLongitude}/{tarif}/{phone}/{user}/{services}/{city}/{application}/', [AndroidTestOSMController::class, 'costSearchMarkers'])
    ->name('costSearchMarkers');

Route::get('/android/costSearchMarkersTime/{originLatitude}/{originLongitude}/{toLatitude}/{toLongitude}/{tarif}/{phone}/{user}/{time}/{date}/{services}/{city}/{application}', [AndroidTestOSMController::class, 'costSearchMarkersTime'])
    ->name('costSearchMarkersTime');

Route::get('/android/costSearchMarkersTimeMyApi/{originLatitude}/{originLongitude}/{toLatitude}/{toLongitude}/{tarif}/{phone}/{user}/{time}/{date}/{services}/{city}/{application}', [AndroidTestOSMController::class, 'costSearchMarkersTimeMyApi'])
    ->name('costSearchMarkersTimeMyApi');

Route::get('/android/costSearchMarkersLocal/{originLatitude}/{originLongitude}/{toLatitude}/{toLongitude}/{tarif}/{phone}/{user}/{services}/{city}/{application}/{local}', [AndroidTestOSMController::class, 'costSearchMarkersLocal'])
    ->name('costSearchMarkersLocal');

Route::get('/android/costSearchMarkersLocalTariffs/{originLatitude}/{originLongitude}/{toLatitude}/{toLongitude}/{user}/{services}/{city}/{application}', [AndroidTestOSMController::class, 'costSearchMarkersLocalTariffs'])
    ->name('costSearchMarkersLocalTariffs');

Route::get('/android/costSearchMarkersLocalTariffsTime/{originLatitude}/{originLongitude}/{toLatitude}/{toLongitude}/{user}/{time}/{date}/{services}/{city}/{application}', [AndroidTestOSMController::class, 'costSearchMarkersLocalTariffsTime'])
    ->name('costSearchMarkersLocalTariffsTime');

Route::get('/android/orderSearchMarkers/{originLatitude}/{originLongitude}/{toLatitude}/{toLongitude}/{tarif}/{phone}/{user}/{add_cost}/{time}/{comment}/{date}/{services}/{city}/{application}/', [AndroidTestOSMController::class, 'orderSearchMarkers'])
    ->name('orderSearchMarkers');

Route::get('/android/orderSearchMarkersVisicom/{originLatitude}/{originLongitude}/{toLatitude}/{toLongitude}/{tarif}/{phone}/{user}/{add_cost}/{time}/{comment}/{date}/{start}/{finish}/{services}/{city}/{application}/', [AndroidTestOSMController::class, 'orderSearchMarkersVisicom'])
    ->name('orderSearchMarkers');

Route::get('/android/orderSearchMarkersVisicomWfpInvoice/{originLatitude}/{originLongitude}/{toLatitude}/{toLongitude}/{tarif}/{phone}/{user}/{add_cost}/{time}/{comment}/{date}/{start}/{finish}/{wfpInvoice}/{services}/{city}/{application}', [AndroidTestOSMController::class, 'orderSearchMarkersVisicomWfpInvoice'])
    ->name('orderSearchMarkersVisicomWfpInvoice');

Route::get('/android/orderSearchMarkersVisicomWfpInvoiceChannel/{originLatitude}/{originLongitude}/{toLatitude}/{toLongitude}/{tarif}/{phone}/{user}/{add_cost}/{time}/{comment}/{date}/{start}/{finish}/{wfpInvoice}/{services}/{city}/{application}', [AndroidTestOSMController::class, 'orderSearchMarkersVisicomWfpInvoiceChannel'])
    ->name('orderSearchMarkersVisicomWfpInvoiceChannel');


Route::get('/android/orderClientCost/{originLatitude}/{originLongitude}/{toLatitude}/{toLongitude}/{tarif}/{phone}/{clientCost}/{user}/{add_cost}/{time}/{comment}/{date}/{start}/{finish}/{wfpInvoice}/{services}/{city}/{application}', [AndroidTestOSMController::class, 'orderClientCost'])
    ->name('orderClientCost');

Route::get('/android/orderCacheReorder/{originLatitude}/{originLongitude}/{toLatitude}/{toLongitude}/{tarif}/{phone}/{clientCost}/{user}/{add_cost}/{time}/{comment}/{date}/{start}/{finish}/{wfpInvoice}/{services}/{city}/{application}/{uid}', [AndroidTestOSMController::class, 'orderCacheReorder'])
    ->name('orderCacheReorder');

Route::get('/android/orderSearchMarkersVisicomWfpInvoiceSpeed/{originLatitude}/{originLongitude}/{toLatitude}/{toLongitude}/{tarif}/{phone}/{user}/{add_cost}/{time}/{comment}/{date}/{start}/{finish}/{wfpInvoice}/{services}/{city}/{application}', [AndroidTestOSMController::class, 'orderSearchMarkersVisicomWfpInvoiceSpeed'])
    ->name('orderSearchMarkersVisicomWfpInvoiceSpeed');

Route::get('/android/orderFullCostFromPas/{originLatitude}/{originLongitude}/{toLatitude}/{toLongitude}/{tarif}/{phone}/{user}/{cost}/{time}/{comment}/{date}/{start}/{finish}/{services}/{city}/{application}/', [AndroidTestOSMController::class, 'orderFullCostFromPas'])
    ->name('orderFullCostFromPas');

Route::get('/android/searchOrderToDelete/{originLatitude}/{originLongitude}/{toLatitude}/{toLongitude}/{email}/{start}/{finish}/{payment_type}/{city}/{application}/', [AndroidTestOSMController::class, 'searchOrderToDelete'])
    ->name('searchOrderToDelete');

Route::get('/android/versionAPI/', [AndroidTestOSMController::class, 'version'])
    ->name('version');

Route::get('/android/myHistory/', [AndroidTestOSMController::class, 'myHistory'])
    ->name('myHistory');

Route::get('/android/historyUID/{uid}', [AndroidTestOSMController::class, 'historyUID'])
    ->name('myHistory');

Route::get('/android/apiVersion/', [AndroidTestOSMController::class, 'apiVersion'])
    ->name('apiVersion');

Route::get('/android/geoDataSearch/{to}/{to_number}', [AndroidTestOSMController::class, 'geoDataSearch'])
    ->name('geoDataSearch');

Route::get('/android/geoDataSearchStreet/{to}/{to_number}', [AndroidTestOSMController::class, 'geoDataSearchStreet'])
    ->name('geoDataSearchStreet');

Route::get('/android/geoDataSearchObject/{to}', [AndroidTestOSMController::class, 'geoDataSearchObject'])
    ->name('geoDataSearchObject');

Route::get('/android/geoLatLanSearch/{originLatitude}/{originLongitude}', [AndroidTestOSMController::class, 'geoLatLanSearch'])
    ->name('geoLatLanSearch');

Route::get('/android/visicom/show/{settlement}', [VisicomController::class, 'show'])
    ->name('visicom_show');

Route::get('/android/webordersCancel/{uid}/{city}/{application}', [AndroidTestOSMController::class, 'webordersCancel'])
    ->name('webordersCancel');

Route::get('/android/webordersCancelDouble/{uid}/{uid_Double}/{payment_type}/{city}/{application}', [AndroidTestOSMController::class, 'webordersCancelDouble'])
    ->name('webordersCancelDouble');

Route::get('/android/webordersCancelVod/{uid}', [AndroidTestOSMController::class, 'webordersCancelVod'])
    ->name('webordersCancelVod');

Route::get('/android/webordersCancelDoubleNew/{uid}/{uid_Double}/{payment_type}/{city}/{application}', [AndroidTestOSMController::class, 'webordersCancelDoubleNew'])
    ->name('webordersCancelDoubleNew');


Route::get('/android/webordersCancelDoubleWithotMemory/{uid}/{uid_Double}/{payment_type}/{city}/{application}', [AndroidTestOSMController::class, 'webordersCancelDoubleWithotMemory'])
    ->name('webordersCancelDoubleWithotMemory');

Route::get('/android/historyUIDStatus/{uid}/{city}/{application}', [AndroidTestOSMController::class, 'historyUIDStatus'])
    ->name('myHistoryStatus');

Route::get('/android/historyUIDStatusNew/{uid}/{city}/{application}', [AndroidTestOSMController::class, 'historyUIDStatusNew'])
    ->name('historyUIDStatusNew');

Route::get('/android/historyUIDStatusWithoutMemory/{uid}/{city}/{application}', [AndroidTestOSMController::class, 'historyUIDStatusWithoutMemory'])
    ->name('historyUIDStatusWithoutMemory');

Route::get('/android/drivercarposition/{uid}/{city}/{application}', [AndroidTestOSMController::class, 'driverCarPosition'])
    ->name('drivercarposition');

Route::get('/android/calculateTimeToStart/{uid}', [FCMController::class, 'calculateTimeToStart'])
    ->name('calculateTimeToStart');


/**
 * UID
 */

Route::get('/android/UIDStatus/{uid}', [AndroidTestOSMController::class, 'UIDStatus'])
    ->name('UIDStatus');


/**
 * Base Comdo_Test
 */

Route::get('/versionComboOdessa', [AndroidTestOSMController::class, 'versionComboOdessa'])
    ->name('versionComboOdessa');

/**
 * Permissions
 */
Route::get('/android/permissions/{email}', [UniversalAndroidFunctionController::class, 'userPermissions'])
    ->name('userPermissions');
/**
 * sentErrorMessage
 */
Route::get('/android/sentErrorMessage/{email}', [UniversalAndroidFunctionController::class, 'sentErrorMessage'])
    ->name('sentErrorMessage');

/**
 * city server verify
 *
 */
Route::get('/android/findCityJson/{startLat}/{startLan}', [UniversalAndroidFunctionController::class, 'findCityJson'])
    ->name('findCityJson');

/**
 * broadcasting push
 *
 */
Route::get('/android/testPush/{order_uid}', [PusherController::class, 'testPush'])
    ->name('testPush');

Route::get('/android/test ', [PusherController::class, 'test'])
    ->name('test');

Route::get('/android/sentUid/{order_uid} ', [PusherController::class, 'sentUid'])
    ->name('sentUid');

Route::get('/android/sentUidApp/{order_uid}/{app} ', [PusherController::class, 'sentUidApp'])
    ->name('sentUidApp');

Route::get('/android/sentUidAppEmail/{order_uid}/{app}/{email} ', [PusherController::class, 'sentUidAppEmail'])
    ->name('sentUidApp');

Route::get('/android/sentCostApp/{order_cost}/{app}/{email} ', [PusherController::class, 'sentCostApp'])
    ->name('sentCostApp');

Route::get('/android/sentActivateBlackUser/{active}/{email} ', [PusherController::class, 'sentActivateBlackUser'])
    ->name('sentActivateBlackUser');

Route::get('/android/sendOrderResponse/{app}/{email}', [UniversalAndroidFunctionController::class, 'sendOrderResponse'])
    ->name('sendOrderResponse');

Route::get('/android/parseOrderResponse/{response}/{dispatching_order_uid_Double}', [UniversalAndroidFunctionController::class, 'parseOrderResponse'])
    ->name('parseOrderResponse');

Route::get('/android/getChain/{dispatching_order_uid_Double}', [\App\Http\Controllers\MemoryOrderChangeController::class, 'getChain'])
    ->name('getChain');

Route::get('/android/searchAutoOrderService/{uid}/{mess_info}', [\App\Http\Controllers\UniversalAndroidFunctionController::class, 'searchAutoOrderService'])
    ->name('searchAutoOrderService');

Route::get('/android/searchAutoOrderServiceAll/{email}/{app}/{mess_info}', [\App\Http\Controllers\UniversalAndroidFunctionController::class, 'searchAutoOrderServiceAll'])
    ->name('searchAutoOrderServiceAll');

/*
 * Cost actions
 */

Route::get('/android/saveFinishCost/{uid}/{cost}', [\App\Http\Controllers\CostController::class, 'save_finish_cost'])
    ->name('save_finish_cost');

Route::get('/android/showFinishCost/{uid}', [\App\Http\Controllers\CostController::class, 'show_finish_cost'])
    ->name('show_finish_cost');

<?php

use App\Http\Controllers\AndroidController;
use App\Http\Controllers\ComboTestController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\ServicesController;
use App\Http\Controllers\CityTariffController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\RateLimiter;

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

/**
 * Api check
 */


Route::get('/check', function () {
    return response()->json(['status' => 'OK'], 200);
});

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
Route::get('/brandsAll/Android', [ServicesController::class, 'brandsAllAndroid'])->name('brandsAllAndroid');
Route::get('/brandAdd/Android/{newBrand}', [ServicesController::class, 'brandAdd'])->name('brandAdd');

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

Route::middleware('throttle:6,1')->get('/android/costSearch/{from}/{from_number}/{to}/{to_number}/{tarif}/{phone}/{user}/', [AndroidController::class, 'costSearch'])
    ->name('costSearch');
Route::middleware('throttle:6,1')->get('/android/orderSearch/{from}/{from_number}/{to}/{to_number}/{tarif}/{phone}/{user}', [AndroidController::class, 'orderSearch'])
    ->name('orderSearch');

Route::middleware('throttle:6,1')->get('/android/costSearchGeo/{originLatitude}/{originLongitude}/{to}/{to_number}/{tarif}/{phone}/{user}/', [AndroidController::class, 'costSearchGeo'])
    ->name('costSearchGeo');
Route::middleware('throttle:6,1')->get('/android/orderSearchGeo/{originLatitude}/{originLongitude}/{to}/{to_number}/{tarif}/{phone}/{user}', [AndroidController::class, 'orderSearchGeo'])
    ->name('orderSearchGeo');

Route::get('/android/fromSearchGeo/{originLatitude}/{originLongitude}', [AndroidController::class, 'fromSearchGeo'])
    ->name('fromSearchGeo');


Route::get('/android/sendCode/{phone}', [AndroidController::class, 'sendCode'])->name('sendCode');
Route::get('/android/approvedPhones/{phone}/{code}', [AndroidController::class, 'approvedPhones'])->name('approvedPhones');

Route::get('/android/sendCodeTest/{phone}', [AndroidController::class, 'sendCodeTest'])->name('sendCode');
Route::get('/android/approvedPhonesTest/{phone}/{code}', [AndroidController::class, 'approvedPhonesTest'])->name('approvedPhones');
Route::get('/android/autocompleteSearchComboHid/{name}', [AndroidController::class, 'autocompleteSearchComboHid'])->name('autocompleteSearchComboHid');

Route::get('/android/sentPhone/{message}', [AndroidController::class, 'sentPhone'])->name('sentPhone');
Route::get('/android/checkDomain/{domain}', [AndroidController::class, 'checkDomain'])->name('checkDomain');




Route::get('/android/addUser/{name}/{email}', [AndroidController::class, 'addUser'])->name('checkDomain');
Route::get('/android/verifyBlackListUser/{email}', [AndroidController::class, 'verifyBlackListUser'])->name('verifyBlackListUser');

Route::get('/test-rate-limit', function (Request $request) {
    $key = 'request_limit:' . $request->ip();

    if (RateLimiter::tooManyAttempts($key, 5)) {
        return response('Too Many Requests', 429);
    }

    RateLimiter::hit($key);
    return response('Request allowed.');
});


/*
 * Тарифі по городам
 */
Route::apiResource('tariffs', CityTariffController::class);

// Дополнительные маршруты
Route::get('tariffs/city/{city}', [CityTariffController::class, 'getByCity']);
Route::post('tariffs/{city}/calculate', [CityTariffController::class, 'calculatePrice']);

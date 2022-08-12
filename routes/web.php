<?php

use App\Http\Controllers\TaxiController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    return view('welcome');
});

Auth::routes(['verify' => true]);

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->middleware('verified')->name('home');


Route::get('/users/all', [UserController::class,'index']);
Route::get('/users/destroy/{id}', [UserController::class,'destroy'])->middleware('verified');
Route::get('/users/edit/{id}/{name}/{email}', [UserController::class,'edit']);

Route::get('/users/show/{id}', [UserController::class,'show']);

Route::get('/admin', function () {
    return view('admin.home');
})->name('admin')->middleware('role:superadministrator');

Route::get('/admin/{any}', function () {
    return view('admin.home');
})->where('any', '.*')->middleware('role:superadministrator');

Route::get('/taxi/account', [TaxiController::class, 'account'])->name('taxi-account');
Route::get('/taxi/changePassword', [TaxiController::class, 'changePassword'])->name('taxi-changePassword');
Route::get('/taxi/restoreSendConfirmCode', [TaxiController::class, 'restoreSendConfirmCode'])->name('taxi-restoreSendConfirmCode');
Route::get('/taxi/restoreСheckConfirmCode', [TaxiController::class, 'restoreСheckConfirmCode'])->name('taxi-restoreСheckConfirmCode');
Route::get('/taxi/restorePassword', [TaxiController::class, 'restorePassword'])->name('taxi-restorePassword');
Route::get('/taxi/sendConfirmCode', [TaxiController::class, 'sendConfirmCode'])->name('taxi-sendConfirmCode');
Route::get('/taxi/register', [TaxiController::class, 'register'])->name('taxi-register');
Route::get('/taxi/approvedPhonesSendConfirmCode', [TaxiController::class, 'approvedPhonesSendConfirmCode'])->name('taxi-approvedPhonesSendConfirmCode');
Route::get('/taxi/approvedPhones', [TaxiController::class, 'approvedPhones'])->name('taxi-approvedPhones');
Route::get('/taxi/version', [TaxiController::class, 'version'])->name('taxi-version');
Route::get('/taxi/cost', [TaxiController::class, 'cost'])->name('taxi-cost');
Route::get('/taxi/weborders', [TaxiController::class, 'weborders'])->name('taxi-weborders');
Route::get('/taxi/tariffs', [TaxiController::class, 'tariffs'])->name('taxi-tariffs');
Route::get('/taxi/webordersUid', [TaxiController::class, 'webordersUid'])->name('taxi-webordersUid');

Route::get('/taxi/profile', [TaxiController::class, 'profile'])->name('taxi-profile');
Route::get('/taxi/addresses', [TaxiController::class, 'addresses'])->name('taxi-addresses');
Route::get('/taxi/lastaddresses', [TaxiController::class, 'lastaddresses'])->name('taxi-lastaddresses');
Route::get('/taxi/ordersHistory', [TaxiController::class, 'ordersHistory'])->name('taxi-ordersHistory');
Route::get('/taxi/ordersReport', [TaxiController::class, 'ordersReport'])->name('taxi-ordersReport');
Route::get('/taxi/bonusReport', [TaxiController::class, 'bonusReport'])->name('taxi-bonusReport');
Route::get('/taxi/profile/put', [TaxiController::class, 'profileput'])->name('taxi-profile-put');
Route::get('/taxi/objects', [TaxiController::class, 'objects'])->name('taxi-objects');
Route::get('/taxi/objectssearch', [TaxiController::class, 'objectsSearch'])->name('taxi-objectsSearch');
Route::get('/taxi/streets', [TaxiController::class, 'streets'])->name('taxi-streets');
Route::get('/taxi/streetssearch', [TaxiController::class, 'streetsSearch'])->name('taxi-streetsSearch');
Route::get('/taxi/geodatasearch', [TaxiController::class, 'geodataSearch'])->name('taxi-geodataSearch');
Route::get('/taxi/geodataSearchLatLng', [TaxiController::class, 'geodataSearchLatLng'])->name('taxi-geodataSearchLatLng');
Route::get('/taxi/geodataNearest', [TaxiController::class, 'geodataNearest'])->name('taxi-geodataNearest');
Route::get('/taxi/settings', [TaxiController::class, 'settings'])->name('taxi-settings');
Route::get('/taxi/addCostIncrementValue', [TaxiController::class, 'addCostIncrementValue'])->name('taxi-addCostIncrementValue');
Route::get('/taxi/time', [TaxiController::class, 'time'])->name('taxi-time');
Route::get('/taxi/tnVersion', [TaxiController::class, 'tnVersion'])->name('taxi-tnVersion');
Route::get('/taxi/driversPosition', [TaxiController::class, 'driversPosition'])->name('taxi-driversPosition');

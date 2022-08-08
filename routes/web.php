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

Route::get('/taxi/version', [TaxiController::class, 'version'])->name('taxi-version');
Route::get('/taxi/profile', [TaxiController::class, 'profile'])->name('taxi-profile');
Route::get('/taxi/addresses', [TaxiController::class, 'addresses'])->name('taxi-addresses');
Route::get('/taxi/lastaddresses', [TaxiController::class, 'lastaddresses'])->name('taxi-lastaddresses');
Route::get('/taxi/tariffs', [TaxiController::class, 'tariffs'])->name('taxi-tariffs');
Route::get('/taxi/ordershistory', [TaxiController::class, 'ordershistory'])->name('taxi-ordershistory');
Route::get('/taxi/ordersreport', [TaxiController::class, 'ordersreport'])->name('taxi-ordersreport');
Route::get('/taxi/bonusreport', [TaxiController::class, 'bonusreport'])->name('taxi-bonusreport');
Route::get('/taxi/profile/put', [TaxiController::class, 'profileput'])->name('taxi-profile-put');

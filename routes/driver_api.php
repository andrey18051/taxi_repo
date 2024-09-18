<?php

use App\Http\Controllers\AndroidController;
use App\Http\Controllers\ComboTestController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\FCMController;
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


Route::get('/orderTaking/{uid}', [DriverController::class, 'orderTaking'])->name('orderTaking');
Route::get('/orderUnTaking/{uid}', [DriverController::class, 'orderUnTaking'])->name('orderUnTaking');
Route::get('/driverInStartPoint/{uid}', [DriverController::class, 'driverInStartPoint'])->name('driverInStartPoint');
Route::get('/driverCloseOrder/{uid}', [DriverController::class, 'driverCloseOrder'])->name('driverCloseOrder');
Route::get('/driverCardPayToBalance/{uidDriver}/{amount}/{language}', [DriverController::class, 'driverCardPayToBalance'])
    ->name('driverCardPayToBalance');

Route::get('/writeDocumentToBalanceFirestore/{uid}/{uidDriver}/{status}', [FCMController::class, 'writeDocumentToBalanceFirestore'])
    ->name('writeDocumentToBalanceFirestore');

Route::get('/uidDriver/{uid}', [DriverController::class, 'uidDriver'])
    ->name('uidDriver');

Route::get('/readUserInfoFromFirestore/{uid}', [FCMController::class, 'readUserInfoFromFirestore'])
    ->name('readUserInfoFromFirestore');

Route::get('/findUserByEmail/{email}', [FCMController::class, 'findUserByEmail'])
    ->name('findUserByEmail');

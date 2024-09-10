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


Route::get('/orderTaking/{uid}', [DriverController::class, 'orderTaking'])->name('orderTaking');
Route::get('/orderUnTaking/{uid}', [DriverController::class, 'orderUnTaking'])->name('orderUnTaking');
Route::get('/driverInStartPoint/{uid}', [DriverController::class, 'driverInStartPoint'])->name('driverInStartPoint');

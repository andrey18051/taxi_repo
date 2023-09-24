<?php

use App\Http\Controllers\AndroidController;
use App\Http\Controllers\ComboTestController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\ExecutionStatusController;
use App\Http\Controllers\ServicesController;
use App\Http\Controllers\UniversalAndroidFunctionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Emulator Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::get('/set_status_exec', [ExecutionStatusController::class, 'index'])
    ->name('set_status_exec');
Route::post('/bonus-exec', [ExecutionStatusController::class, 'bonusExec'])
    ->name('bonus-exec');
Route::post('/double-exec', [ExecutionStatusController::class, 'doubleExec'])
    ->name('double-exec');
Route::get('/update_status_exec', [ExecutionStatusController::class, 'updateStatusExec'])
    ->name('update_status_exec');
Route::get('/getExecutionStatusEmu/{order_type}', [UniversalAndroidFunctionController::class, 'getExecutionStatusEmu'])
    ->name('getExecutionStatusEmu');
Route::get('/lastUpdateOrderTimeInSeconds/{order}', [ExecutionStatusController::class, 'lastUpdateOrderTimeInSeconds'])
    ->name('lastUpdateOrderTimeInSeconds');

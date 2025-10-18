<?php

use App\Http\Controllers\AndroidController;
use App\Http\Controllers\ComboTestController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\ServicesController;
use App\Http\Controllers\TaxiAiController;
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

Route::post('/taxi-ai/parse', [TaxiAiController::class, 'parse']);
Route::post('/taxi-ai/create-order', [TaxiAiController::class, 'createOrder']);
Route::post('/taxi-ai/cancel-order', [TaxiAiController::class, 'cancelOrder']);


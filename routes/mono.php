<?php

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
/**
 * Generation text for news
 */

use App\Http\Controllers\FondyController;
use App\Http\Controllers\MonobankController;
use Illuminate\Support\Facades\Route;

Route::get('/redirectUrl', [MonobankController::class, 'redirectUrl'])->name('redirectUrl');
Route::post('/webHookUrl', [MonobankController::class, 'webHookUrl'])->name('webHookUrl');


Route::get('/errorView', [FondyController::class, 'errorView'])->name('errorView');
Route::get('/subscriptionView', [FondyController::class, 'subscriptionView'])->name('subscriptionView');
Route::get('/callBack', [FondyController::class, 'callBack'])->name('callBack');
Route::get('/chargebackCallBack', [FondyController::class, 'chargebackCallBack'])->name('chargebackCallBack');


Route::get('/orderIdMemory/{fondy_order_id}/{uid}', [FondyController::class, 'orderIdMemory'])->name('orderIdMemory');

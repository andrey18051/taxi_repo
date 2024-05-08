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

use App\Http\Controllers\WfpController;
use Illuminate\Support\Facades\Route;

Route::get('/createInvoice/{application}/{city}/{orderReference}/{amount}/{language}/{productName}/{clientEmail}/{clientPhone}', [WfpController::class, 'createInvoice'])->name('createInvoice');
Route::get('/verify/{application}/{city}/{orderReference}/{clientEmail}/{clientPhone}', [WfpController::class, 'verify'])->name('verify');
Route::get('/pay/{application}/{city}/{orderReference}/{amount}/{language}/{productName}/{clientEmail}/{clientPhone}', [WfpController::class, 'pay'])->name('pay');
Route::get('/returnUrl', [WfpController::class, 'returnUrl'])->name('returnUrl');
Route::post('/serviceUrl', [WfpController::class, 'serviceUrl'])->name('serviceUrl');
Route::post('/serviceUrl/verify', [WfpController::class, 'serviceUrlVerify'])->name('serviceUrl');

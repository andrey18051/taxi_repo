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

use App\Http\Controllers\CardsController;
use App\Http\Controllers\UniversalAndroidFunctionController;
use App\Http\Controllers\WfpController;
use Illuminate\Support\Facades\Route;

Route::get('/createInvoice/{application}/{city}/{orderReference}/{amount}/{language}/{productName}/{clientEmail}/{clientPhone}', [WfpController::class, 'createInvoice'])->name('createInvoice');
Route::get('/charge/{application}/{city}/{orderReference}/{amount}/{productName}/{clientEmail}/{clientPhone}/{recToken}', [WfpController::class, 'charge'])->name('charge');

Route::get('/chargeActiveToken/{application}/{city}/{orderReference}/{amount}/{productName}/{clientEmail}/{clientPhone}/', [WfpController::class, 'chargeActiveToken'])->name('chargeActiveToken');

Route::get('/refund/{application}/{city}/{orderReference}/{amount}', [WfpController::class, 'refund'])->name('refund');
Route::get('/refundVerifyCards/{application}/{city}/{orderReference}/{amount}', [WfpController::class, 'refundVerifyCards'])->name('refundVerifyCards');
Route::get('/settle/{application}/{city}/{orderReference}/{amount}', [WfpController::class, 'settle'])->name('settle');
Route::get('/verify/{application}/{city}/{orderReference}/{clientEmail}/{clientPhone}/{language}', [WfpController::class, 'verify'])->name('verify');
Route::get('/checkStatus/{application}/{city}/{orderReference}', [WfpController::class, 'checkStatus'])->name('checkStatus');
Route::get('/purchase/{application}/{city}/{orderReference}/{amount}/{productName}/{clientEmail}/{clientPhone}/{recToken}', [WfpController::class, 'purchase'])->name('purchase');
Route::post('/returnUrl', [WfpController::class, 'returnUrl'])->name('returnUrl');
Route::post('/serviceUrl', [WfpController::class, 'serviceUrl'])->name('serviceUrl');

//Route::post('/serviceUrl/PAS1', [WfpController::class, 'serviceUrl_PAS1'])->name('serviceUrl_PAS1');
//Route::post('/serviceUrl/PAS2', [WfpController::class, 'serviceUrl_PAS2'])->name('serviceUrl_PAS2');
//Route::post('/serviceUrl/PAS4', [WfpController::class, 'serviceUrl_PAS4'])->name('serviceUrl_PAS4');
//Route::post('/serviceUrl/VOD', [WfpController::class, 'serviceUrl_VOD'])->name('serviceUrl_VOD');

Route::post('/serviceUrl/PAS1', [WfpController::class, 'serviceUrl_PAS1_app'])->name('serviceUrl_PAS1');
Route::post('/serviceUrl/PAS2', [WfpController::class, 'serviceUrl_PAS2_app'])->name('serviceUrl_PAS2');
Route::post('/serviceUrl/PAS4', [WfpController::class, 'serviceUrl_PAS4_app'])->name('serviceUrl_PAS4');
Route::post('/serviceUrl/VOD', [WfpController::class, 'serviceUrl_VOD_app'])->name('serviceUrl_VOD');

Route::post('/serviceUrl/verify', [WfpController::class, 'serviceUrlVerify'])->name('serviceUrl');
Route::get('/transactionList/{merchant}', [WfpController::class, 'transactionList'])->name('transactionList');
Route::get('/transactionListJob/', [WfpController::class, 'transactionListJob'])->name('transactionListJob');

/**
 * Cards active section
 */

//Route::get('/getActiveCard/{email}/{city}/{application}', [CardsController::class, 'getActiveCard'])->name('getActiveCard');
Route::get('/setActiveCard/{email}/{id}/{city}/{application}', [CardsController::class, 'setActiveCard'])->name('setActiveCard');
Route::get('/getCardTokenIdApp/{application}/{city}/{email}/{pay_system}', [CardsController::class, 'getCardTokenIdApp']);
Route::get('/deleteCardToken/{id}', [CardsController::class, 'deleteCardToken']);

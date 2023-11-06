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

use App\Http\Controllers\AndroidSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/getPaySystem', [AndroidSettingsController::class, 'getPaySystem'])->name('getPaySystem');
Route::get('/setPaySystem', [AndroidSettingsController::class, 'setPaySystem'])->name('setPaySystem');

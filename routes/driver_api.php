<?php

use App\Http\Controllers\AndroidController;
use App\Http\Controllers\ComboTestController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\DriverKarmaController;
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


Route::get('/orderTaking/{uid}/{uidDriver}', [DriverController::class, 'orderTaking'])->name('orderTaking');
Route::get('/orderUnTaking/{uid}/{uidDriver}', [DriverController::class, 'orderUnTaking'])->name('orderUnTaking');
Route::get('/orderUnTakingPersonal/{uid}/{uidDriver}', [DriverController::class, 'orderUnTakingPersonal'])->name('orderUnTakingPersonal');
Route::get('/driverInRout/{uid}/{uidDriver}', [DriverController::class, 'driverInRout'])->name('driverInRout');

Route::get('/driverInStartPoint/{uid}/{uidDriver}', [DriverController::class, 'driverInStartPoint'])
    ->name('driverInStartPoint');

Route::get('/toStartPointOffline/{uid}/{minutesToAdd}', [DriverController::class, 'toStartPointOffline'])
    ->name('toStartPointOffline');

Route::get('/driverInStartPoint/{uid}/{uidDriver}', [DriverController::class, 'driverInStartPoint'])
    ->name('driverInStartPoint');
Route::get('/driverCloseOrder/{uid}/{uidDriver}', [DriverController::class, 'driverCloseOrder'])
    ->name('driverCloseOrder');

Route::get('/driverUpdateInfo/{uidDriver}', [DriverController::class, 'driverUpdateInfo'])
    ->name('driverUpdateInfo');

Route::get('/driverUpdateCarInfo/{uidDriver}/{carId}', [DriverController::class, 'driverUpdateCarInfo'])
    ->name('driverUpdateCarInfo');

Route::get('/verifyDriverUpdateInfo/{uidDriver}', [DriverController::class, 'verifyDriverUpdateInfo'])
    ->name('verifyDriverUpdateInfo');

Route::get('/verifyDriverUpdateCarInfo/{carId}', [DriverController::class, 'verifyDriverUpdateCarInfo'])
    ->name('driverUpdateCarInfo');

Route::get('/driverCardPayToBalance/{uidDriver}/{amount}/{language}', [DriverController::class, 'driverCardPayToBalance'])
    ->name('driverCardPayToBalance');


Route::get('/driverCardPayDownBalance/{uidDriver}/{amount}/{comment}/{selectedTypeCode}', [DriverController::class, 'driverCardPayDownBalance'])
    ->name('driverCardPayDownBalance');

Route::get('/driverDeleteOrder/{id}', [DriverController::class, 'driverDeleteOrder'])
    ->name('driverDeleteOrder');

Route::get('/driverDownBalanceAdmin/{uidDriver}/{documentId}', [FCMController::class, 'driverDownBalanceAdmin'])
    ->name('driverDownBalanceAdmin');

Route::get('/driverAdminDeleteOrder/{orderId}', [FCMController::class, 'driverDeleteOrderAdmin'])
    ->name('driverAdminDeleteOrder');

Route::get('/driver/driverDownBalanceAdmin/', [DriverController::class, 'driverDownBalanceAdmin'])
    ->name('driverDownBalanceAdmin');



Route::get('/driverCardPayByTokenToBalance/{uidDriver}/{amount}/{recToken}', [DriverController::class, 'driverCardPayByTokenToBalance'])
    ->name('driverCardPayByTokenToBalance');

Route::get('/mapboxAccessToken', [DriverController::class, 'mapboxAccessToken'])
    ->name('mapboxAccessToken');

Route::get('/writeDocumentToBalanceFirestore/{uid}/{uidDriver}/{status}', [FCMController::class, 'writeDocumentToBalanceFirestore'])
    ->name('writeDocumentToBalanceFirestore');

Route::get('/addAmountToBalanceCurrent/{uid}/{uidDriver}', [FCMController::class, 'addAmountToBalanceCurrent'])
    ->name('addAmountToBalanceCurrent');

Route::get('/uidDriver/{uid}', [DriverController::class, 'uidDriver'])
    ->name('uidDriver');

Route::get('/readUserInfoFromFirestore/{uid}', [FCMController::class, 'readUserInfoFromFirestore'])
    ->name('readUserInfoFromFirestore');

Route::get('/findUserByEmail/{email}', [FCMController::class, 'findUserByEmail'])
    ->name('findUserByEmail');

Route::get('/deleteDocumentsByDriverUid/{uidDriver}', [FCMController::class, 'deleteDocumentsByDriverUid'])
    ->name('deleteDocumentsByDriverUid');

Route::get('/driverCurrentSectorLocationStoreFirestore/{uidDriver}/{latitude}/{longitude}', [DriverController::class, 'driverCurrentSectorLocationStoreFirestore'])
    ->name('driverCurrentSectorLocationStoreFirestore');

// Создать новую запись (store) с параметрами в сегментах URL
Route::get('/driver-karmas/create/{driver_id}/{order_id}/{action}',
    [DriverKarmaController::class, 'store'])->name('driver-karmas.store');

// Получить запись по ID (show)
Route::get('/driver-karmas/{id}', [DriverKarmaController::class, 'show'])->name('driver-karmas.show');

// Обновить запись по ID (update)
Route::put('/driver-karmas/{id}', [DriverKarmaController::class, 'update'])->name('driver-karmas.update');

// Удалить запись по ID (destroy)
Route::delete('/driver-karmas/{id}', [DriverKarmaController::class, 'destroy'])->name('driver-karmas.destroy');

// Подсчитать записи по action для driver_id
Route::get('/driver-karmas/count/{uidDriver}', [DriverKarmaController::class, 'countByAction'])->name('driver-karmas.countByAction');


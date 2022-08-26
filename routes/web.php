<?php

use App\Http\Controllers\TaxiController;
use App\Http\Controllers\TypeaheadController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebOrderController;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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






Route::get('/welcome', function () {
    return view('welcome');
});

Auth::routes(['verify' => true]);

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->middleware('verified')->name('home-admin');


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

/**
/***********************************************************************************************************************
*/

Route::get('/', function () {
    $WebOrder = new \App\Http\Controllers\WebOrderController();
    $tariffs = $WebOrder->tariffs();
    $response_arr = json_decode($tariffs, true);
    $ii = 0;
    for ($i = 0; $i < count($response_arr); $i++) {
        switch ($response_arr[$i]['name']) {
    /*        case '1,5':
            case '2.0':
            case 'Универсал':
            case 'Микроавтобус':
            case 'Премиум-класс':
            case 'Манго':
            case 'Онлайн платный':
                break;*/
            case 'Базовый':
            case 'Бизнес-класс':
            case 'Эконом-класс':

                $json_arr[$ii]['name'] = $response_arr[$i]['name'];
                $ii++;
        }
    }
    return view('taxi.home', ['json_arr' => $json_arr]);
})->name('home');

Route::get('/homeorder/{id}', function ($id) {
    $WebOrder = new \App\Http\Controllers\WebOrderController();
    $tariffs = $WebOrder->tariffs();
    $response_arr = json_decode($tariffs, true);
    $ii = 0;
    for ($i = 0; $i < count($response_arr); $i++) {
        switch ($response_arr[$i]['name']) {
      /*      case '1,5':
            case '2.0':
            case 'Универсал':
            case 'Микроавтобус':
            case 'Премиум-класс':
            case 'Манго':
            case 'Онлайн платный':
                break;*/
            case 'Базовый':
            case 'Бизнес-класс':
            case 'Эконом-класс':

                $json_arr[$ii]['name'] = $response_arr[$i]['name'];
                $ii++;
        }
    }
    $orderId = json_decode(Order::where('id', $id)->get(), true);
    return view('taxi.orderEdit', ['json_arr' => $json_arr, 'orderId' => $orderId, 'id' => $id]);
})->name('home-id');

Route::get('/homeorder/afterorder/{id}', function ($id) {
    $WebOrder = new \App\Http\Controllers\WebOrderController();
    $tariffs = $WebOrder->tariffs();
    $response_arr = json_decode($tariffs, true);
    $ii = 0;
    for ($i = 0; $i < count($response_arr); $i++) {
        switch ($response_arr[$i]['name']) {
            /*case '1,5':
            case '2.0':
            case 'Универсал':
            case 'Микроавтобус':
            case 'Премиум-класс':
            case 'Манго':
            case 'Онлайн платный':
                break;*/
            case 'Базовый':
            case 'Бизнес-класс':
            case 'Эконом-класс':
                $json_arr[$ii]['name'] = $response_arr[$i]['name'];
                $ii++;
        }
    }
    $orderId = json_decode(Order::where('id', $id)->get(), true);
    return view('taxi.homeblank', ['json_arr' => $json_arr, 'orderId' => $orderId, 'id' => $id]);
})->name('home-id-afterorder');

/**
 * Профиль
 */
Route::get('/login-taxi', function () {
    return view('taxi.login');
})->name('login-taxi');

Route::get('/profile', [WebOrderController::class, 'profile'])->name('profile');

Route::get('/profile/view/{authorization}', function ($authorization) {
    $response = new WebOrderController();
    $response = $response->account($authorization);
    return view('taxi.profile', ['authorization' => $authorization, 'response' => $response]);
})->name('profile-view');

Route::get('/profile/edit/form/{authorization}', function ($authorization) {
    $response = new WebOrderController();
    $response = $response->account($authorization);
    return view('taxi.profileEdit', ['authorization' => $authorization, 'response' => $response]);
})->name('profile-edit-form');

Route::get('/profile/edit', [WebOrderController::class, 'profileput'])->name('profile-edit');

/**
 * Регистрация
 */
Route::get('/registration/sms', function () {
    return view('taxi.registerSMS');
})->name('registration-sms');

Route::get('/sendConfirmCode', [WebOrderController::class, 'sendConfirmCode'])->name('sendConfirmCode');

Route::get('/registration/form', function () {
    return view('taxi.register');
})->name('registration-form');

Route::get('/registration/confirm-code', [WebOrderController::class, 'register'])->name('registration');

Route::get('/search', function () {
    return view('search');
});
/**
 * Поиск по улицам
 */
Route::get('/search-home', [TypeaheadController::class, 'index'])->name('search-home');
Route::get('/autocomplete-search', [TypeaheadController::class, 'autocompleteSearch']);

/**
 * Расчет стоимости
 */

Route::get('/cost', [WebOrderController::class, 'cost'])->name('cost');
Route::get('/search/cost', [WebOrderController::class, 'cost'])->name('search-cost');
/**
 * Расчет стоимости исправленного заказа
 */
Route::get('/search/cost/edit/{id}', [WebOrderController::class, 'costEdit'])->name('search-cost-edit');
/**
 * Заказы
 * Поиск всех расчетов пользователя
 */
Route::get('/costhistory-orders/{user_login}', function ($user_login) {
    return response()->json(Order::where('user_phone', $user_login)->orderBy('created_at', 'desc')->get());
})->name('costhistory-orders');



Route::get('/costhistory/{authorization}', function ($authorization) {
    $response = new WebOrderController();
    $response = $response->account($authorization);
    return view('taxi.costhistory', ['authorization' => $authorization, 'response' => $response]);
})->name('costhistory');

/**
 * Редактирование расчета
 */
Route::get('/costhistory/orders/edit/{id}', function ($id){
    //   return ;
    $WebOrder = new \App\Http\Controllers\WebOrderController();
    $tariffs = $WebOrder->tariffs();
    $response_arr = json_decode($tariffs, true);
    $ii = 0;
    for ($i = 0; $i < count($response_arr); $i++) {
        switch ($response_arr[$i]['name']) {
            case '1,5':
            case '2.0':
            case 'Универсал':
            case 'Микроавтобус':
            case 'Премиум-класс':
                break;
            case 'Базовый':
            case 'Бизнес-класс':
            case 'Эконом-класс':
            case 'Манго':
            case 'Онлайн платный':
                $json_arr[$ii]['name'] = $response_arr[$i]['name'];
                $ii++;
        }
    }

    $orderId = json_decode(Order::where('id', $id)->get(), true);

    return view('taxi.orderEdit', ['json_arr' => $json_arr, 'orderId' => $orderId])->with('success', 'Уважно перевірте та підтвердіть замовлення');
})->name('costhistory-orders-id');

/**
 * Удаление расчета
 */
Route::get('/costhistory/orders/destroy/{id}/{authorization}', function ($id, $authorization) {
    $response = new WebOrderController();
    $response = $response->account($authorization);

    Order::where('id', $id)->delete();
    return redirect()->route('costhistory', ['authorization' => $authorization])
        ->with('success', "Запис успішно видалений");
})->name('costhistory-orders-id-destroy');
/**
 * Отправка заказа
 */
Route::get('/costhistory/orders/neworder/{id}', function ($id) {
    $WebOrder = new \App\Http\Controllers\WebOrderController();
    $tariffs = $WebOrder->tariffs();
    $response_arr = json_decode($tariffs, true);
    $ii = 0;
    for ($i = 0; $i < count($response_arr); $i++) {
        switch ($response_arr[$i]['name']) {
            case '1,5':
            case '2.0':
            case 'Универсал':
            case 'Микроавтобус':
            case 'Премиум-класс':
                break;
            case 'Базовый':
            case 'Бизнес-класс':
            case 'Эконом-класс':
            case 'Манго':
            case 'Онлайн платный':
                $json_arr[$ii]['name'] = $response_arr[$i]['name'];
                $ii++;
        }
    }

    $WebOrder->costWebOrder($id);
    return redirect()->route('home', ['json_arr' => $json_arr]);
})->name('costhistory-orders-neworder');




Route::get('/profile/edit/form/{authorization}', function ($authorization) {
    $response = new WebOrderController();
    $response = $response->account($authorization);
    return view('taxi.profileEdit', ['authorization' => $authorization, 'response' => $response]);
})->name('profile-edit-form');


Route::get('/costhistory/orders', function (){
    return response()->json(Order::get());
})->name('costhistory-orders');

Route::get('/costhistory/orders/{id}', function ($id){
 //   return ;
    $WebOrder = new \App\Http\Controllers\WebOrderController();
    $tariffs = $WebOrder->tariffs();
    $response_arr = json_decode($tariffs, true);
    $ii = 0;
    for ($i = 0; $i < count($response_arr); $i++) {
        switch ($response_arr[$i]['name']) {
            case '1,5':
            case '2.0':
            case 'Универсал':
            case 'Микроавтобус':
            case 'Премиум-класс':
                break;
            case 'Базовый':
            case 'Бизнес-класс':
            case 'Эконом-класс':
            case 'Манго':
            case 'Онлайн платный':
                $json_arr[$ii]['name'] = $response_arr[$i]['name'];
                $ii++;
        }
    }

    $orderId = json_decode(Order::where('id', $id)->get(), true);

    return view('taxi.orderEdit', ['json_arr' => $json_arr, 'orderId' => $orderId])->with('success', 'Уважно перевірте та підтвердіть замовлення');
})->name('costhistory-orders-id');

Route::get('/login/taxi', function () {
    return view('taxi.login');
})->name('taxi-login');

Route::get('/account/edit/', [WebOrderController::class, 'profileput'])->name('account-edit');

Route::get('/taxi/account', [TaxiController::class, 'account'])->name('taxi-account');
Route::get('/taxi/changePassword', [TaxiController::class, 'changePassword'])->name('taxi-changePassword');
Route::get('/taxi/restoreSendConfirmCode', [TaxiController::class, 'restoreSendConfirmCode'])->name('taxi-restoreSendConfirmCode');
Route::get('/taxi/restoreСheckConfirmCode', [TaxiController::class, 'restoreСheckConfirmCode'])->name('taxi-restoreСheckConfirmCode');
Route::get('/taxi/restorePassword', [TaxiController::class, 'restorePassword'])->name('taxi-restorePassword');
Route::get('/taxi/sendConfirmCode', [TaxiController::class, 'sendConfirmCode'])->name('taxi-sendConfirmCode');
Route::get('/taxi/register', [TaxiController::class, 'register'])->name('taxi-register');
Route::get('/taxi/approvedPhonesSendConfirmCode', [TaxiController::class, 'approvedPhonesSendConfirmCode'])->name('taxi-approvedPhonesSendConfirmCode');
Route::get('/taxi/approvedPhones', [TaxiController::class, 'approvedPhones'])->name('taxi-approvedPhones');

Route::get('/taxi/version', [TaxiController::class, 'version'])->name('taxi-version');
Route::get('/taxi/cost', [TaxiController::class, 'cost'])->name('taxi-cost');
Route::get('/taxi/weborders', [TaxiController::class, 'weborders'])->name('taxi-weborders');
Route::get('/taxi/tariffs', [TaxiController::class, 'tariffs'])->name('taxi-tariffs');
Route::get('/taxi/webordersUid', [TaxiController::class, 'webordersUid'])->name('taxi-webordersUid');
Route::get('/taxi/webordersUidDriver', [TaxiController::class, 'webordersUidDriver'])->name('taxi-webordersUidDriver');
Route::get('/taxi/webordersUidCostAdditionalGet', [TaxiController::class, 'webordersUidCostAdditionalGet'])->name('taxi-webordersUidCostAdditionalGet');
Route::get('/taxi/webordersUidCostAdditionalPost', [TaxiController::class, 'webordersUidCostAdditionalPost'])->name('taxi-webordersUidCostAdditionalPost');
Route::get('/taxi/webordersUidCostAdditionalPut', [TaxiController::class, 'webordersUidCostAdditionalPut'])->name('taxi-webordersUidCostAdditionalPut');
Route::get('/taxi/webordersUidCostAdditionalDelete', [TaxiController::class, 'webordersUidCostAdditionalDelete'])->name('taxi-webordersUidCostAdditionalDelete');
Route::get('/taxi/webordersDrivercarPosition', [TaxiController::class, 'webordersDrivercarPosition'])->name('taxi-webordersDrivercarPosition');
Route::get('/taxi/webordersCancel', [TaxiController::class, 'webordersCancel'])->name('taxi-webordersCancel');
Route::get('/taxi/webordersRate', [TaxiController::class, 'webordersRate'])->name('taxi-webordersRate');
Route::get('/taxi/webordersHide', [TaxiController::class, 'webordersHide'])->name('taxi-webordersHide');

Route::get('/taxi/ordersReport', [TaxiController::class, 'ordersReport'])->name('taxi-ordersReport');
Route::get('/taxi/ordersHistory', [TaxiController::class, 'ordersHistory'])->name('taxi-ordersHistory');
Route::get('/taxi/ordersBonusreport', [TaxiController::class, 'ordersBonusreport'])->name('taxi-ordersBonusreport');
Route::get('/taxi/lastaddresses', [TaxiController::class, 'lastaddresses'])->name('taxi-lastaddresses');
Route::get('/taxi/profile', [TaxiController::class, 'profile'])->name('taxi-profile');
Route::get('/taxi/profile/put', [TaxiController::class, 'profileput'])->name('taxi-profile-put');
Route::get('/taxi/profile/credential', [TaxiController::class, 'credential'])->name('taxi-credential');
Route::get('/taxi/profile/changePhoneSendConfirmCode', [TaxiController::class, 'changePhoneSendConfirmCode'])->name('taxi-changePhoneSendConfirmCode');
Route::get('/taxi/profile/clientsChangePhone', [TaxiController::class, 'clientsChangePhone'])->name('taxi-clientsChangePhone');
Route::get('/taxi/profile/clientsBalanceTransactions', [TaxiController::class, 'clientsBalanceTransactions'])->name('taxi-clientsBalanceTransactions');
Route::get('/taxi/profile/clientsBalanceTransactionsGet', [TaxiController::class, 'clientsBalanceTransactionsGet'])->name('taxi-clientsBalanceTransactionsGet');
Route::get('/taxi/profile/clientsBalanceTransactionsGetHistory', [TaxiController::class, 'clientsBalanceTransactionsGetHistory'])->name('taxi-clientsBalanceTransactionsGetHistory');
Route::get('/taxi/addresses', [TaxiController::class, 'addresses'])->name('taxi-addresses');
Route::get('/taxi/addressesPost', [TaxiController::class, 'addressesPost'])->name('taxi-addressesPost');
Route::get('/taxi/addressesPut', [TaxiController::class, 'addressesPut'])->name('taxi-addressesPut');
Route::get('/taxi/addressesDelete', [TaxiController::class, 'addressesDelete'])->name('taxi-addressesDelete');

Route::get('/taxi/objects', [TaxiController::class, 'objects'])->name('taxi-objects');
Route::get('/taxi/objectssearch', [TaxiController::class, 'objectsSearch'])->name('taxi-objectsSearch');
Route::get('/taxi/streets', [TaxiController::class, 'streets'])->name('taxi-streets');
Route::get('/taxi/streetssearch', [TaxiController::class, 'streetsSearch'])->name('taxi-streetsSearch');
Route::get('/taxi/geodatasearch', [TaxiController::class, 'geodataSearch'])->name('taxi-geodataSearch');
Route::get('/taxi/geodataSearchLatLng', [TaxiController::class, 'geodataSearchLatLng'])->name('taxi-geodataSearchLatLng');
Route::get('/taxi/geodataNearest', [TaxiController::class, 'geodataNearest'])->name('taxi-geodataNearest');
Route::get('/taxi/settings', [TaxiController::class, 'settings'])->name('taxi-settings');
Route::get('/taxi/addCostIncrementValue', [TaxiController::class, 'addCostIncrementValue'])->name('taxi-addCostIncrementValue');
Route::get('/taxi/time', [TaxiController::class, 'time'])->name('taxi-time');
Route::get('/taxi/tnVersion', [TaxiController::class, 'tnVersion'])->name('taxi-tnVersion');
Route::get('/taxi/driversPosition', [TaxiController::class, 'driversPosition'])->name('taxi-driversPosition');

<?php

use App\Http\Controllers\TaxiController;
use App\Http\Controllers\TypeaheadController;
use App\Http\Controllers\TypeaheadObjectController;
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

    date_default_timezone_set("Europe/Kiev");
    // Время интервала
    $start_time = strtotime(config('app.start_time')); // начальное время
    $end_time = strtotime(config('app.end_time')); // конечное время

    $time = strtotime(date("h:i:sa")); // проверяемое время

    // Выполняем проверку
    if ($time >= $start_time && $time <= $end_time) {
         return view('taxi.homeWellcomeWar', ['phone' => '000']);
    } else {
         return view('taxi.homeWellcome', ['phone' => '000', 'user_name' => "Новий замовник"]);
    }
})->name('home');

Route::get('/time/{phone}/{user_name}', function ($phone, $user_name) {

    $WebOrder = new \App\Http\Controllers\WebOrderController();
    $tariffs = $WebOrder->tariffs();
    $response_arr = json_decode($tariffs, true);
    $ii = 0;

    date_default_timezone_set("Europe/Kiev");
    // Время интервала
    $start_time = strtotime(config('app.start_time')); // начальное время
    $end_time = strtotime(config('app.end_time')); // конечное время

    $time = strtotime(date("h:i:sa")); // проверяемое время

    // Выполняем проверку
    if ($time >= $start_time && $time <= $end_time) {
        return view('taxi.homeWellcomeWar', ['phone' => $phone]);
    } else {
        return view('taxi.homeWellcome', ['phone' => $phone, 'user_name' => $user_name]);
    }
})->name('home-phone-user_name');



Route::get('/home-Street/{phone}/{user_name}', function ($phone, $user_name) {
    $WebOrder = new \App\Http\Controllers\WebOrderController();
    $tariffs = $WebOrder->tariffs();
    $response_arr = json_decode($tariffs, true);
    $ii = 0;
    for ($i = 0; $i < count($response_arr); $i++) {
        switch ($response_arr[$i]['name']) {
            case 'Базовый':
            case 'Бизнес-класс':
            case 'Эконом-класс':
                $json_arr[$ii]['name'] = $response_arr[$i]['name'];
                $ii++;
        }
    }
    return view('taxi.homeStreet', ['json_arr' => $json_arr, 'phone' => $phone, 'user_name' => $user_name]);
})->name('homeStreet');


/*Route::get('/home-Street-New', function () {
    $WebOrder = new \App\Http\Controllers\WebOrderController();
    $tariffs = $WebOrder->tariffs();
    $response_arr = json_decode($tariffs, true);
    $ii = 0;
    for ($i = 0; $i < count($response_arr); $i++) {
        switch ($response_arr[$i]['name']) {
            case 'Базовый':
            case 'Бизнес-класс':
            case 'Эконом-класс':
                $json_arr[$ii]['name'] = $response_arr[$i]['name'];
                $ii++;
        }
    }
    return view('taxi.homeStreetNew', ['json_arr' => $json_arr]);
})->name('homeStreet');*/



Route::get('/home-Object/{phone}/{user_name}', function ($phone, $user_name) {
    $WebOrder = new \App\Http\Controllers\WebOrderController();
    $tariffs = $WebOrder->tariffs();
    $response_arr = json_decode($tariffs, true);
    $ii = 0;
    for ($i = 0; $i < count($response_arr); $i++) {
        switch ($response_arr[$i]['name']) {
            case 'Базовый':
            case 'Бизнес-класс':
            case 'Эконом-класс':
                $json_arr[$ii]['name'] = $response_arr[$i]['name'];
                $ii++;
        }
    }
    return view('taxi.homeObject', ['json_arr' => $json_arr, 'phone' => $phone, 'user_name' => $user_name]);
})->name('homeObject');

Route::get('/home-Map/{phone}/{user_name}', function ($phone, $user_name) {
    $WebOrder = new \App\Http\Controllers\WebOrderController();
    $tariffs = $WebOrder->tariffs();
    $response_arr = json_decode($tariffs, true);
    $ii = 0;
    for ($i = 0; $i < count($response_arr); $i++) {
        switch ($response_arr[$i]['name']) {
            case 'Базовый':
            case 'Бизнес-класс':
            case 'Эконом-класс':
                $json_arr[$ii]['name'] = $response_arr[$i]['name'];
                $ii++;
        }
    }
    return view('taxi.homeMap', ['json_arr' => $json_arr, 'phone' => $phone, 'user_name' => $user_name]);
})->name('homeMap');


Route::get('/taxi-gdbr', function () {
    return view('taxi.gdpr');
})->name('taxi-gdbr');

Route::get('/taxi-umovy', function () {
    return view('taxi.umovy');
})->name('taxi-umovy');


Route::get('/homeorder/{id}', function ($id) {
    $WebOrder = new \App\Http\Controllers\WebOrderController();
    $tariffs = $WebOrder->tariffs();
    $response_arr = json_decode($tariffs, true);
    $ii = 0;
    for ($i = 0; $i < count($response_arr); $i++) {
        switch ($response_arr[$i]['name']) {
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

Route::get('/homeorder-object/{id}', function ($id) {
    $WebOrder = new \App\Http\Controllers\WebOrderController();
    $tariffs = $WebOrder->tariffs();
    $response_arr = json_decode($tariffs, true);
    $ii = 0;
    for ($i = 0; $i < count($response_arr); $i++) {
        switch ($response_arr[$i]['name']) {
            case 'Базовый':
            case 'Бизнес-класс':
            case 'Эконом-класс':

                $json_arr[$ii]['name'] = $response_arr[$i]['name'];
                $ii++;
        }
    }
    $orderId = json_decode(Order::where('id', $id)->get(), true);
    return view('taxi.orderObjectEdit', ['json_arr' => $json_arr, 'orderId' => $orderId, 'id' => $id]);
})->name('home-id-object');


Route::get('/homeorder-object/{id}', function ($id) {
    $WebOrder = new \App\Http\Controllers\WebOrderController();
    $tariffs = $WebOrder->tariffs();
    $response_arr = json_decode($tariffs, true);
    $ii = 0;
    for ($i = 0; $i < count($response_arr); $i++) {
        switch ($response_arr[$i]['name']) {
            case 'Базовый':
            case 'Бизнес-класс':
            case 'Эконом-класс':
                $json_arr[$ii]['name'] = $response_arr[$i]['name'];
                $ii++;
        }
    }
    $orderId = json_decode(Order::where('id', $id)->get(), true);
    return view('taxi.orderObjectEdit', ['json_arr' => $json_arr, 'orderId' => $orderId, 'id' => $id]);
})->name('home-object-id');



Route::get('/homeorder/afterorder/{id}', function ($id) {
    $WebOrder = new \App\Http\Controllers\WebOrderController();
    $tariffs = $WebOrder->tariffs();
    $response_arr = json_decode($tariffs, true);
    $ii = 0;
    for ($i = 0; $i < count($response_arr); $i++) {
        switch ($response_arr[$i]['name']) {
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

Route::get('/homeblank', function () {
    return view('taxi.homeblank');
})->name('homeblank');

Route::get('/homeblank/{id}', function ($id) {
    return view('taxi.homeblank', ['id' => $id]);
})->name('homeblank-id');

/**
 * Профиль
 */
Route::get('/login-taxi', function () {
    return view('taxi.login');
})->name('login-taxi');

Route::get('/login-taxi/{phone}', function ($phone) {
    return view('taxi.login-phone', ['phone' => $phone]);
})->name('login-taxi-phone');

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

Route::get('/registration/sms/{phone}', function ($phone) {
    return view('taxi.registerSMS-phone', ['phone' => $phone]);
})->name('registration-sms-phone');


/**
 * Запрос смс подтверждения
 */
Route::middleware('throttle:1,1')->get('/sendConfirmCode', [WebOrderController::class, 'sendConfirmCode'])
    ->name('sendConfirmCode');

Route::get('/registration/form', function () {
    return view('taxi.register');
})->name('registration-form');

Route::get('/registration/form/{phone}', function ($phone) {
    return view('taxi.register-phone', ['phone' => $phone]);
})->name('registration-form-phone');


Route::get('/registration/confirm-code', [WebOrderController::class, 'register'])->name('registration');
/**
 * Восстановление пароля
 */
Route::get('/restore/sms', function () {
    return view('taxi.restoreSMS');
})->name('restore-sms');
/**
 * Запрос смс подтверждения
 */
Route::middleware('throttle:1,1')->get('/restoreSendConfirmCode', [WebOrderController::class, 'restoreSendConfirmCode'])
    ->name('restoreSendConfirmCode');

Route::get('/restore/form', function () {
    return view('taxi.restore-phone');
})->name('restore-form');

Route::get('/restore/form/{phone}', function ($phone) {
    return view('taxi.restore-phone', ['phone' => $phone]);
})->name('restore-form-phone');


Route::get('/restore/confirm-code', [WebOrderController::class, 'restorePassword'])->name('restore');


Route::get('/search', function () {
    return view('search');
});
/**
 * Поиск по улицам
 */
Route::get('/search-home', [TypeaheadController::class, 'index'])->name('search-home');
Route::get('/autocomplete-search', [TypeaheadController::class, 'autocompleteSearch']);
Route::get('/autocomplete-search2', [TypeaheadController::class, 'autocompleteSearch2']);
/**
 * Поиск по объектам
 */
Route::get('/search-home-object', [TypeaheadObjectController::class, 'index'])->name('search-home-object');
Route::get('/autocomplete-search-object', [TypeaheadObjectController::class, 'autocompleteSearch']);
Route::get('/autocomplete-search-object-2', [TypeaheadObjectController::class, 'autocompleteSearch2']);

/**
 * Расчет стоимости
 */

Route::middleware('throttle:6,1')->get('/cost', [WebOrderController::class, 'cost'])->name('cost');
Route::middleware('throttle:6,1')->get('/search/cost', [WebOrderController::class, 'cost'])->name('search-cost');
Route::middleware('throttle:6,1')->get('/search/cost-object', [WebOrderController::class, 'costobject'])->name('search-cost-object');
Route::middleware('throttle:6,1')->get('/search/cost-map', [WebOrderController::class, 'costmap'])->name('search-cost-map');
/**
 * Расчет стоимости исправленного заказа
 */
Route::get('/search/cost/edit/{id}', [WebOrderController::class, 'costEdit'])->name('search-cost-edit');
Route::get('/search/cost/edit-object/{id}', [WebOrderController::class, 'costobjectEdit'])->name('search-cost-edit-object');
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
Route::middleware('throttle:1,1')->get('/costhistory/orders/neworder/{id}', function ($id) {
    $WebOrder = new \App\Http\Controllers\WebOrderController();
    $tariffs = $WebOrder->tariffs();
    $response_arr = json_decode($tariffs, true);
    $ii = 0;
    for ($i = 0; $i < count($response_arr); $i++) {
        switch ($response_arr[$i]['name']) {
            case 'Базовый':
            case 'Бизнес-класс':
            case 'Эконом-класс':
                $json_arr[$ii]['name'] = $response_arr[$i]['name'];
                $ii++;
        }
    }

     return $WebOrder->costWebOrder($id);
})->name('costhistory-orders-neworder');

/**
 * Запрос на отмену заказа
 */

Route::get('/webordersCancel/{id}', [WebOrderController::class, 'webordersCancel'])
    ->name('webordersCancel');

Route::get('/profile/edit/form/{authorization}', function ($authorization) {
    $response = new WebOrderController();
    $response = $response->account($authorization);
    return view('taxi.profileEdit', ['authorization' => $authorization, 'response' => $response]);
})->name('profile-edit-form');


Route::get('/costhistory/orders', function (){
    return response()->json(Order::get());
})->name('costhistory-orders');

Route::get('/costhistory/orders/{id}', function ($id){

    $WebOrder = new \App\Http\Controllers\WebOrderController();
    $tariffs = $WebOrder->tariffs();
    $response_arr = json_decode($tariffs, true);
    $ii = 0;
    for ($i = 0; $i < count($response_arr); $i++) {
        switch ($response_arr[$i]['name']) {
            case 'Базовый':
            case 'Бизнес-класс':
            case 'Эконом-класс':

                $json_arr[$ii]['name'] = $response_arr[$i]['name'];
                $ii++;
        }
    }

    $orderId = json_decode(Order::where('id', $id)->get(), true);

    return view('taxi.orderEdit', ['json_arr' => $json_arr, 'orderId' => $orderId])
        ->with('success', 'Уважно перевірте та підтвердіть замовлення');
})->name('costhistory-orders-id');

Route::get('/login/taxi', function () {
    return view('taxi.login');
})->name('taxi-login');

Route::get('/account/edit/', [WebOrderController::class, 'profileput'])->name('account-edit');


/**
 * Обратная связь
 */

Route::get('/feedback', function () {
    return view('taxi.feedback');
})->name('feedback');

Route::get('/feedback/email', [WebOrderController::class, 'feedbackEmail'])->name('feedback-email');

Route::get('/callBackForm', function () {
    return view('taxi.callBack');
})->name('callBackForm');

Route::get('/callBackForm/{phone}', function ($phone) {
    return view('taxi.callBack-phone', ['phone' => $phone]);
})->name('callBackForm-phone');

Route::get('/callBack', [WebOrderController::class, 'callBack'])->name('callBack');


/**
 * Машины в радиусе старта заказа
 */
Route::get('/driversPosition', [WebOrderController::class, 'driversPosition'])->name('driversPosition');

/**
 * Работа с объектами
 */
Route::get('/taxi-objects', [WebOrderController::class, 'objects'])->name('objects');


/**
 * Модальные окна
 */
Route::get('/modal-error', function () {
    return view('modal.error');
})->name('modal-error');

/*
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
Route::get('/taxi/driversPosition', [TaxiController::class, 'driversPosition'])->name('taxi-driversPosition');*/

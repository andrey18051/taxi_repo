<?php

use App\Http\Controllers\AndroidPAS2_Cherkasy_Controller;
use App\Http\Controllers\AndroidPas2_Dnipro_Controller;
use App\Http\Controllers\AndroidPAS2_Odessa_Controller;
use App\Http\Controllers\AndroidPAS2_Zaporizhzhia_Controller;
use App\Http\Controllers\AndroidPas4001_Dnipro_Controller;
use App\Http\Controllers\AndroidTestController;
use App\Http\Controllers\BlackListController;
use App\Http\Controllers\BonusBalanceController;
use App\Http\Controllers\BonusController;
use App\Http\Controllers\BredoGeneratorController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\Confirmation;
use App\Http\Controllers\FacebookController;
use App\Http\Controllers\GithubController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\IPController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\LinkedinController;
use App\Http\Controllers\OpenStreetMapController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\ServicesController;
use App\Http\Controllers\TaxiController;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\TwitterController;
use App\Http\Controllers\TypeaheadController;
use App\Http\Controllers\TypeaheadObjectController;
use App\Http\Controllers\UIDController;
use App\Http\Controllers\UniversalAndroidFunctionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ViberController;
use App\Http\Controllers\ViberCustomsController;
use App\Http\Controllers\WeatherController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\WebhookViberController;
use App\Http\Controllers\WebhookCustomsViberController;
use App\Http\Controllers\WebOrderController;
use App\Http\Controllers\WidgetsController;
use App\Models\NewsList;
use App\Models\Order;
use App\Models\Orderweb;
use App\Models\Services;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use Stevebauman\Location\Facades\Location;

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

Route::get('/textGenerate', [BredoGeneratorController::class, 'textGenerate'])->name('textGenerate');

/**
 * Test
 */

Route::get('/test', function () {
    $user_id ='tL0zpzMcNDlklD9V5dqEKg==';
    $finduser = User::where('viber_id', $user_id)->first();
    dd($finduser);
})->name('test');


/**
 * Проверка телефона
 */

Route::get('/verifyPhoneInBase/{phone}', [Confirmation::class, 'verifyPhoneInBase'])
    ->name('verifyPhoneInBase');


Route::get('/sendConfirmCode/{phone}', [Confirmation::class, 'sendConfirmCode'])
    ->name('sendConfirmCode');

Route::get('/approvedPhones/{phone}/{confirm_code}', [Confirmation::class, 'approvedPhones'])
    ->name('approvedPhones');

/**
 * PromoList
 */

Route::get('/promoSize/{promoCode}', [PromoController::class, 'promoSize'])
    ->name('promoSize');

Route::get('/promoCreat', [PromoController::class, 'promoCreat'])
    ->name('promoCreat');

Route::get('/promoCodeNew/{email}', [PromoController::class, 'promoCreat'])
    ->name('promoCodeNew');

Route::get('/promo', function () {
    return view('admin.promo');
})->name('admin-promo')->middleware('role:superadministrator');

Route::get('/version_combo', [WebOrderController::class, 'version_combo'])
    ->name('version_combo')->middleware('role:superadministrator');


/**
 * Погода
 */

Route::get('/weather', [WeatherController::class, 'temp'])->name('weather');

/**
 * Viber Bot Customs
 */
Route::get('/viberCustoms', [ViberCustomsController::class, 'viberCustoms'])
    ->name('ViberCustoms');

Route::get('/chatViberCustoms', [ViberCustomsController::class, 'chatViber'])
    ->name('chatViberCustoms');
Route::get('/setWebhookViberCustoms', [ViberCustomsController::class, 'setWebhook'])
    ->name('setWebhookViberCustoms');
Route::get('/getAccountInfoCustoms', [ViberCustomsController::class, 'getAccountInfo'])
    ->name('getAccountInfoCustoms');
Route::get('/getUserDetailsCustoms/{user_id}', [ViberCustomsController::class, 'getUserDetails'])
    ->name('getUserDetailsCustoms');
Route::get('/sendMessageCustoms/{user_id}/{message}/{phone}', [ViberCustomsController::class, 'sendMessage'])
    ->name('sendMessageCustoms');


Route::post('/webhookViberCustoms', [WebhookCustomsViberController::class, 'index']);



/**
 * Viber Bot
 */
Route::get('/chatViber', [ViberController::class, 'chatViber'])->name('chatViber');
Route::get('/setWebhookViber', [ViberController::class, 'setWebhook'])->name('setWebhookViber');
Route::get('/getAccountInfo', [ViberController::class, 'getAccountInfo'])->name('getAccountInfo');
Route::get('/getUserDetails/{user_id}', [ViberController::class, 'getUserDetails'])->name('getUserDetails');
Route::get('/sendMessage/{user_id}/{message}/{phone}', [ViberController::class, 'sendMessage'])->name('sendMessage');

Route::post('/webhookViber', [WebhookViberController::class, 'index']);

/**
 * Viber регистрация
 */

Route::get('register/viber', [ViberController::class, 'registerViber'])->name('registerViber');
Route::get('handleViberCallback/{user_id}/{name}/{phone}', [ViberController::class, 'handleViberCallback'])->name('handleViberCallback');

/**
 * Telegram Bot
 */

Route::get('/telegramBot', [TelegramController::class, 'chatBotSendKeyboard'])->name('telegramBot');
Route::get('/setWebhook', [TelegramController::class, 'setWebhook'])->name('setWebhook');
Route::get('/getWebhook', [TelegramController::class, 'getWebhook'])->name('getWebhook');
Route::get('/getWebhookInfo', [TelegramController::class, 'getWebhookInfo'])->name('getWebhookInfo');
Route::get('/sendDocument', [TelegramController::class, 'sendDocument'])->name('sendDocument');
Route::get('/sendAlarm/{message}', [TelegramController::class, 'sendAlarmMessage'])->name('sendAlarm');
Route::get('/sendOffice/{message}', [TelegramController::class, 'sendOfficeMessage'])->name('sendOfficeMessage');

Route::group(['namespace' => '\App\Http\Controllers\Controllers'], function () {
    Route::post('/webhook', [WebhookController::class, 'index']);
});

Route::get('/registerSmsFail', function () {
    return view('auth.registerSmsFail', ['info' => 'Виникла помилка перевірки телефону.']);
})->name('registerSmsFail');

/**
 * Расшифровка IP
 */
Route::get('/get-ip-details', function () {
    $ip = '34.145.8.204';
    $data = Location::get($ip);
    dd($data);
});


/**
 * Вход через социальные сети
 */

/**
 * Telegram
 */

Route::get('email/telegram', [TelegramController::class, 'emailTelegram'])->name('email-Telegram');

Route::get('auth/telegram', [TelegramController::class, 'redirectToTelegram'])->name('auth-telegram');
Route::get('auth/telegram/callback', [TelegramController::class, 'handleTelegramCallback']);

Route::get('register/telegram', [TelegramController::class, 'registerTelegram'])->name('registerTelegram');


/**
 * Twitter
 */
Route::get('auth/twitter', [TwitterController::class, 'redirectToTwitter']);
Route::get('auth/twitter/callback', [TwitterController::class, 'handleTwitterCallback']);

/**
 * Github
 */
Route::get('auth/github', [GithubController::class, 'redirectToGithub']);
Route::get('auth/github/callback', [GithubController::class, 'handleGithubCallback']);



/**
 * linkedin
 */
Route::get('auth/linkedin', [LinkedinController::class, 'redirectToLinkedin']);
Route::get('auth/linkedin/callback', [LinkedinController::class, 'handleLinkedinCallback']);

/**
 * Google
 */
Route::get('auth/google', [GoogleController::class, 'redirectToGoogle']);
Route::get('auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);

/**
 * Facebook
 */
Route::get('auth/facebook', [FacebookController::class, 'redirectToFacebook']);
Route::get('auth/facebook/callback', [FacebookController::class, 'handleFacebookCallback']);



Route::get('/welcome', function () {
    return view('welcome');
});

Auth::routes(['verify' => true]);

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home-admin')->middleware('role:superadministrator');


Route::get('/users/all', [UserController::class,'index']);
Route::get('/users/destroy/{id}', [UserController::class,'destroy'])->middleware('verified');
Route::get('/users/edit/{id}/{name}/{email}/{bonus}', [UserController::class,'edit']);

Route::get('/users/show/{id}', [UserController::class,'show']);

Route::get('/admin', function () {
    return view('admin.home');
})->name('admin')->middleware('role:superadministrator');

Route::get('/admin/{any}', function () {
    return view('admin.home');
})->where('any', '.*')->middleware('role:superadministrator');

/**
 * Службы такси для андроида
 */

Route::get('/services/all', [ServicesController::class,'index']);
Route::get('/services/destroy/{id}', [ServicesController::class,'destroy']);
Route::get('/services/edit/{id}/{name}/{email}/{telegram_id}/{viber_id}/{link}', [ServicesController::class,'edit']);
Route::get('/services/serviceNew', function () {
    return view('admin.services');
})->name('services-new');
Route::get('/services/serviceCreat', [ServicesController::class,'serviceCreat'])->name('services-save');

/**
 * Цитаты
 */
Route::get('/quite', function () {
    return view('admin.quite');
})->name('admin-quite')->middleware('role:superadministrator');


Route::get('/quite-save', [TaxiController::class, 'quite'])
    ->name('quite-save');
/**
* Новости
*/
Route::get('/news', function () {
    $bredNews = new BredoGeneratorController();
    $news = $bredNews->textGenerate();
    return view('admin.news', ['news' => $news]);
})->name('admin-news')->middleware('role:superadministrator');

Route::get('/news-save', function (Request $req) {
    $news = new NewsList();
    $news->short = $req->short;
    $news->full = $req->full;
    $news->author = $req->author;
    $news->save();
    return redirect()->route('admin-news');
})->name('news-save');


Route::get('/news-short', [BredoGeneratorController::class, 'allNews'])->name('news-short');
Route::get('/breakingNews/{id}', [BredoGeneratorController::class, 'breakingNews'])->name('breakingNews');
Route::get('/randomNews/{id}', [BredoGeneratorController::class, 'randomNews'])->name('randomNews');
Route::get('/addTextForNews', [BredoGeneratorController::class, 'addTextForNews'])->name('addTextForNews');

/**
 * Servers
 */

Route::get('/servers', function () {
    $serversInfo = ServerController::serverInfo();
    return view('admin.servers', ['serversInfo' => $serversInfo]);
})->name('admin-servers');

Route::get("pingInfo/{ip}", [ServerController::class, 'pingInfo'])
    ->name('connectAPIInfo');
Route::get("/connectInfo/{ip}", [ServerController::class, 'connectInfo'])
    ->name('connectInfo');

/**
/***********************************************************************************************************************
*/

Route::get('/homeWelcome', function () {

    date_default_timezone_set("Europe/Kiev");
    // Время интервала
    $start_time = strtotime(config('app.start_time')); // начальное время
    $end_time = strtotime(config('app.end_time')); // конечное время

    $time = strtotime(date("h:i:sa")); // проверяемое время

    // Выполняем проверку

    if ($start_time  <= $end_time) {
        if ($time >= $start_time && $time <= $end_time) {
            return view('taxi.homeWelcomeWar', ['phone' => '000',   'time' => date("h:i:sa")]);
        } else {
            return view('taxi.homeWelcome', ['phone' => '000', 'user_name' => "Новий замовник",  'time' => date("h:i:sa")]);
        }
    } else {
        if ($time >= $start_time || $time <= $end_time) {
            return view('taxi.homeWelcomeWar', ['phone' => '000',   'time' => date("h:i:sa")]);
        } else {
            return view('taxi.homeWelcome', ['phone' => '000', 'user_name' => "Новий замовник",  'time' => date("h:i:sa")]);
        }
    }
})->name('home');


Route::get('/', function () {
    IPController::getIP('/');
    WebOrderController::connectAPI();
    return view('taxi.homeNewsCombo');
})->name('home-news');

Route::get('/home-news/{user_id}', function ($user_id) {
    $finduser = User::where('viber_id', $user_id)->first();
    if ($finduser) {
        Auth::login($finduser);
    }
    return redirect()->route('home-news');
})->name('home-newsViber');


Route::get('/time/{phone}/{user_name}', function ($phone, $user_name) {

    date_default_timezone_set("Europe/Kiev");
    // Время интервала
    $start_time = strtotime(config('app.start_time')); // начальное время
    $end_time = strtotime(config('app.end_time')); // конечное время

    $time = strtotime(date("h:i:sa")); // проверяемое время

    // Выполняем проверку

    if ($start_time  <= $end_time) {
        if ($time >= $start_time && $time <= $end_time) {
            return view('taxi.homeWelcomeWar', ['phone' => '000',   'time' => date("h:i:sa")]);
        } else {
            return view('taxi.homeWelcome', ['phone' => $phone, 'user_name' => $user_name,  'time' => date("h:i:sa")]);  }
    } else {
        if ($time >= $start_time || $time <= $end_time) {
            return view('taxi.homeWelcomeWar', ['phone' => '000', 'time' => date("h:i:sa")]);
        } else {
            return view('taxi.homeWelcome', ['phone' => $phone, 'user_name' => $user_name, 'time' => date("h:i:sa")]);
        }
    }
})->name('home-phone-user_name');


Route::get('/home-Combo', function () {
    IPController::getIP('/home-Combo');
    $json_arr = WebOrderController::tariffs();

    date_default_timezone_set("Europe/Kiev");
    // Время интервала
    $start_time = strtotime(config('app.start_time')); // начальное время
    $end_time = strtotime(config('app.end_time')); // конечное время

    $time = strtotime(date("h:i:sa")); // проверяемое время

    // Выполняем проверку

    if ($start_time  <= $end_time) {
        if ($time >= $start_time && $time <= $end_time) {
            return view('taxi.homeWelcomeWarCombo');
        } else {
            $connectAPI = WebOrderController::connectAPInoEmail();

            if ($connectAPI == 400) {
                return redirect()->route('home-news')->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
            } else {
                    return view('taxi.homeCombo', ['json_arr' => $json_arr]);
            }
        }
    } else {
        if ($time >= $start_time || $time <= $end_time) {
            return view('taxi.homeWelcomeWarCombo');
        } else {
            $connectAPI = WebOrderController::connectAPInoEmail();

            if ($connectAPI == 400) {
                return  redirect()->route('home-news')->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
            } else {
                return view('taxi.homeCombo', ['json_arr' => $json_arr]);
            }
        }
    }
})->name('homeCombo');

Route::get('/home-Combo/{user_id}', function ($user_id) {
    $finduser = User::where('viber_id', $user_id)->first();
    if ($finduser) {
        Auth::login($finduser);
    }
    return redirect()->route('homeCombo');
})->name('homeComboViber');

Route::get('/map', function () {
    return view('map3');
});

Route::get('/home-Object/{phone}/{user_name}', function ($phone, $user_name) {
    $connectAPI = WebOrderController::connectAPInoEmail();
    if ($connectAPI == 400) {
        return redirect()->route('home-news')
            ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
    }
    $json_arr = WebOrderController::tariffs();
    return view('taxi.homeObject', ['json_arr' => $json_arr, 'phone' => $phone, 'user_name' => $user_name]);
})->name('homeObject');

Route::get('/home-Map/{phone}/{user_name}', function ($phone, $user_name) {
    $connectAPI = WebOrderController::connectAPInoEmail();
    if ($connectAPI == 400) {
        return redirect()->route('home-news')
            ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
    }
    $json_arr = WebOrderController::tariffs();
    return view('taxi.homeMap', ['json_arr' => $json_arr, 'phone' => $phone, 'user_name' => $user_name]);
})->name('homeMap');

Route::get('/home-Map-Combo', function () {
    IPController::getIP('home-Map-Combo');
    $json_arr = WebOrderController::tariffs();

    date_default_timezone_set("Europe/Kiev");
    // Время интервала
    $start_time = strtotime(config('app.start_time')); // начальное время
    $end_time = strtotime(config('app.end_time')); // конечное время

    $time = strtotime(date("h:i:sa")); // проверяемое время

    // Выполняем проверку

    if ($start_time  <= $end_time) {
        if ($time >= $start_time && $time <= $end_time) {
            return view('taxi.homeWelcomeWarCombo');
        } else {
            $connectAPI = WebOrderController::connectAPInoEmail();

            if ($connectAPI == 400) {
                return redirect()->route('home-news')->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
            } else {
                return view('taxi.homeMapCombo', ['json_arr' => $json_arr]);
            }
        }
    } else {
        if ($time >= $start_time || $time <= $end_time) {
            return view('taxi.homeWelcomeWarCombo');
        } else {
            $connectAPI = WebOrderController::connectAPInoEmail();

            if ($connectAPI == 400) {
                return  redirect()->route('home-news')->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
            } else {
                return view('taxi.homeMapCombo', ['json_arr' => $json_arr]);
            }
        }
    }
})->name('homeMapCombo');

Route::get('/home-Map-Combo/{user_id}',  function ($user_id) {
    $finduser = User::where('viber_id', $user_id)->first();
    if ($finduser) {
        Auth::login($finduser);
    }
    return redirect()->route('homeMapCombo');
})->name('homeMapComboViber');


Route::get('/taxi-gdbr', function () {
    IPController::getIP('/taxi-gdbr');
    return view('taxi.gdpr');
})->name('taxi-gdbr');

Route::get('/taxi-privacy_policy', function () {
    IPController::getIP('/taxi-privacy_policy');
    return view('taxi.privacy_policy');
})->name('taxi-privacy_policy');

Route::get('/privacy_policy', function () {
    IPController::getIP('/taxi-privacy_policy');
    return view('taxi.privacy_policy');
})->name('privacy_policy');

Route::get('/taxi-umovy', function () {
    IPController::getIP('/taxi-umovy');
    return view('taxi.umovy');
})->name('taxi-umovy');


Route::get('/homeorder/{id}', function ($id) {
    IPController::getIP('/homeorder/' . $id);
    $json_arr = WebOrderController::tariffs();
    $orderId = json_decode(Order::where('id', $id)->get(), true);
    return view('taxi.orderEdit', ['json_arr' => $json_arr, 'orderId' => $orderId, 'id' => $id]);
})->name('home-id');

Route::get('/homeorder-object/{id}', function ($id) {

    $json_arr = WebOrderController::tariffs();
    $orderId = json_decode(Order::where('id', $id)->get(), true);
    return view('taxi.orderObjectEdit', ['json_arr' => $json_arr, 'orderId' => $orderId, 'id' => $id]);
})->name('home-id-object');


Route::get('/homeorder-object/{id}', function ($id) {
    $connectAPI = WebOrderController::connectAPInoEmail();
    if ($connectAPI == 400) {
        return redirect()->route('home-news')
            ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
    }
    $json_arr = WebOrderController::tariffs();

    $orderId = json_decode(Order::where('id', $id)->get(), true);
    return view('taxi.orderObjectEdit', ['json_arr' => $json_arr, 'orderId' => $orderId, 'id' => $id]);
})->name('home-object-id');



Route::get('/homeorder/afterorder/{id}', function ($id) {
    IPController::getIP('/homeorder/afterorder/' . $id);
    $orderId = json_decode(Order::where('id', $id)->get(), true);

    $routeArr['from'] = WebOrderController::geodataSearch($orderId[0]["routefrom"], $orderId[0]["routefromnumber"]);
    $routeArr['to'] = WebOrderController::geodataSearch($orderId[0]["routeto"], $orderId[0]["routetonumber"]);
    $routeArr["driver"]["lat"] = null;
    return view('taxi.homeblankMap', [ 'orderId' => $orderId, 'id' => $id, 'routeArr' => $routeArr]);
})->name('home-id-afterorder');

Route::get('/homeorder/afterorder/uid/{id}', function ($id) {

    $orderId = json_decode(Orderweb::where('id', $id)->get(), true);

    $routeArr['from'] = WebOrderController::geodataSearch($orderId[0]["routefrom"], $orderId[0]["routefromnumber"]);
    $routeArr['to'] = WebOrderController::geodataSearch($orderId[0]["routeto"], $orderId[0]["routetonumber"]);
    $routeArr['driver'] = WebOrderController::driversPositionUid($orderId[0]['dispatching_order_uid']);

    return view('taxi.homeblankMap', [ 'orderId' => $orderId, 'id' => $id, 'routeArr' => $routeArr]);
})->name('home-id-afterorder-uid');


Route::get('/homeorder/afterorder/web/{id}', function ($id) {
    $orderId = json_decode(Orderweb::where('id', $id)->get(), true);
    return view('taxi.homeblank', ['orderId' => $orderId, 'id' => $id]);
})->name('home-id-afterorder-web');


Route::get('/homeblank', function () {
    return view('taxi.homeblank');
})->name('homeblank');

Route::get('/homeblank2', function () {
    return view('taxi.homeblank2');
})->name('homeblank2');

Route::get('/homeblank/{id}', function ($id) {
    return view('taxi.homeblank', ['id' => $id]);
})->name('homeblank-id');

/**
 * Профиль
 */
Route::get('/login-taxi', function () {
    IPController::getIP('/login-taxi');
    return view('auth.login');
})->name('login-taxi');

Route::get('/login-taxi{info}', function ($info) {
    return view('auth.login', ['info' => $info]);
})->name('login-taxi-info');


Route::get('/login-taxi/{phone}', function ($phone) {
    return view('taxi.login-phone', ['phone' => $phone]);
})->name('login-taxi-phone');

Route::get('/profile', [WebOrderController::class, 'profile'])->name('profile');
Route::get('/profileApi', [WebOrderController::class, 'profileApi'])->name('profileApi');

Route::get('/profile/view/{authorization}', function ($authorization) {
    $response = new WebOrderController();
    $response = $response->account($authorization);
    return view('taxi.profile', ['authorization' => $authorization, 'response' => $response]);
})->name('profile-view');

Route::get('/profile/edit/form/{authorization}', [WebOrderController::class, 'profileEditForm'])
    ->name('profile-edit-form');

Route::get('/profile/edit', [WebOrderController::class, 'profileput'])->name('profile-edit');

/**
 * Регистрация
 */
Route::get('/registerSocial', function () {
    IPController::getIP('/register');
    return view('auth.registerSocial');
})->name('registerSocial');


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

Route::get('/restore/sms/{phone}', function ($phone) {
    return view('taxi.restoreSMS-phone', ['phone' => $phone]);
})->name('restore-sms-phone');

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


Route::get('/autocompleteSearchComboHidname', [TypeaheadController::class, 'autocompleteSearchComboHidname'])->name('autocompleteSearchComboHid');
Route::get('/search-home', [TypeaheadController::class, 'index'])->name('search-home');
Route::get('/autocomplete-search', [TypeaheadController::class, 'autocompleteSearch']);
Route::get('/autocomplete-search2', [TypeaheadController::class, 'autocompleteSearch2']);
Route::get('/autocomplete-search-combo', [TypeaheadController::class, 'autocompleteSearchCombo']);
Route::get('/autocomplete-search-combo-hid/{name}', [TypeaheadController::class, 'autocompleteSearchComboHid'])
->name('autocomplete-search-combo-hid');

/**
 * Поиск по объектам
 */
Route::get('/search-home-object', [TypeaheadObjectController::class, 'index'])->name('search-home-object');
Route::get('/autocomplete-search-object', [TypeaheadObjectController::class, 'autocompleteSearch']);
Route::get('/autocomplete-search-object-2', [TypeaheadObjectController::class, 'autocompleteSearch2']);

/**
 * Расчет стоимости
 */

Route::middleware('throttle:6,1')->get('/cost', [WebOrderController::class, 'cost'])
    ->name('cost');
Route::middleware('throttle:6,1')->get('/search/cost', [WebOrderController::class, 'cost'])
    ->name('search-cost');
Route::middleware('throttle:6,1')->get('/search/cost-object', [WebOrderController::class, 'costobject'])
    ->name('search-cost-object');
Route::middleware('throttle:6,1')->get('/search/cost-map', [WebOrderController::class, 'costmap'])
    ->name('search-cost-map');
Route::middleware('throttle:6,1')->get('/search/cost-transfer/{page}',
    [WebOrderController::class, 'costtransfer'])
    ->name('search-cost-transfer');
Route::middleware('throttle:6,1')->get('/search/cost-transfer-from/{page}',
    [WebOrderController::class, 'costtransferfrom'])
    ->name('search-cost-transfer-from');


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
 * Редактирование расчета из Истории поездок
 */

Route::get('/costhistory/orders/edit/{id}', [WebOrderController::class, 'costHistory'])
    ->name('costhistory-orders-id');


/**
 * Редактирование расчета из Дома
 */

Route::get('/costhome/{route_address_from}/{route_address_number_from}/{authorization}', [WebOrderController::class, 'costHome'])
    ->name('costhome');

/**
 * Трансфер в аэропорты и вокзалы
 */

Route::get('/transfer/{routeto}/{page}', [WebOrderController::class, 'transfer'])
    ->name('transfer');

Route::get('/transfer/{routeto}/{page}/{user_id}', function ($routeto, $page, $user_id) {
    $finduser = User::where('viber_id', $user_id)->first();
    if ($finduser) {
        Auth::login($finduser);
    }
    return redirect()->route('transfer', ['routeto' => $routeto, 'page' => $page]);
})->name('transferViber');

Route::get('/transfer/{routeto}/{page}/{user_phone}/{user_first_name}/{route_address_from}/{route_address_number_from}',
    [WebOrderController::class, 'transferProfile'])
    ->name('transfer-profile');

/**
 * Встреча в аэропортах и вокзалах
 */

Route::get('/transferfrom/{routefrom}/{page}', [WebOrderController::class, 'transferFrom'])
    ->name('transferFrom');

Route::get('/transferfrom/{routefrom}/{page}/{user_id}', function ($routefrom, $page, $user_id) {
    $finduser = User::where('viber_id', $user_id)->first();
    if ($finduser) {
        Auth::login($finduser);
    }
    return redirect()->route('transferFrom', ['routefrom' => $routefrom, 'page' => $page]);
})->name('transferFromViber');

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
Route::middleware('throttle:6,1')->get('/costhistory/orders/neworder/{id}', function ($id) {
    $req = Order::where('id', $id)->first();

    $user_phone = $req->user_phone;

    $finduser = User::where('user_phone', $user_phone)->first();

    if (!$finduser) {
        if (Confirmation::sendConfirmCode($user_phone) == 200) {
            return view('auth.verifySMS', ['id' => $id, 'user_phone' => $user_phone]);
        } else {
            return view('taxi.feedback', ['info' => 'Помилка відправкі коду. Спробуйте піздніше.']);
        }
    }

    $WebOrder = new WebOrderController();
    return $WebOrder->costWebOrder($id);
})->name('costhistory-orders-neworder');

Route::get('/verifySmsCode', [Confirmation::class, 'verifySmsCode'])->name('verifySmsCode');



/**
 * Запрос на отмену заказа
 */

Route::get('/webordersCancel/{id}', [WebOrderController::class, 'webordersCancel'])
    ->name('webordersCancel');

/**
 * Запрос состояния заказа
 */

Route::get('/webordersUid/{id}', [WebOrderController::class, 'webordersUid'])
    ->name('webordersUid');


Route::get('/costhistory/orders', function (){
    return response()->json(Order::get());
})->name('costhistory-orders');

Route::get('/costhistory/orders/{id}', function ($id){
    $connectAPI = WebOrderController::connectAPInoEmail();
    if ($connectAPI == 400) {
        return redirect()->route('home-news')
            ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
    }
    $json_arr = WebOrderController::tariffs();
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
    IPController::getIP('/feedback');
    return view('taxi.feedback');
})->name('feedback');

Route::get('/feedback/{user_id}', function ($user_id) {
    $finduser = User::where('viber_id', $user_id)->first();
    if ($finduser) {
        Auth::login($finduser);
    }
    return redirect()->route('feedback');
})->name('feedbackViber');

Route::get('/feedbackInfo/{info}', function ($info) {
    IPController::getIP('/feedback');
    return view('taxi.feedback',['info' => $info]);
})->name('feedbackInfo');


Route::get('/feedback/email', [WebOrderController::class, 'feedbackEmail'])->name('feedback-email');

Route::get('/callBackForm', function () {
    IPController::getIP('/callBackForm');
    return view('taxi.callBack');
})->name('callBackForm');

Route::get('/callBackForm/{user_id}', function ($user_id) {
    $finduser = User::where('viber_id', $user_id)->first();
    if ($finduser) {
        Auth::login($finduser);
    }
    return redirect()->route('callBackForm');
})->name('callBackFormViber');


Route::get('/callBack', [WebOrderController::class, 'callBack'])->name('callBack');

/**
 * Работа в такси
 */

Route::get('/callWorkForm', function () {
    $services =  new ServicesController();

    return view('driver.callWork', ['services' => $services->servicesAll()]);
})->name('callWorkForm');

Route::get('/callWorkForm/{user_id}', function ($user_id) {
    $finduser = User::where('viber_id', $user_id)->first();
    if ($finduser) {
        Auth::login($finduser);
    }
    return redirect()->route('callWorkForm');
})->name('callWorkFormViber');

//Route::get('/callWork', [WebOrderController::class, 'callWork'])->name('callWork');

/**
 * Работа в такси (Job)
 */

Route::get('/callWork', [JobController::class, 'index'])->name('callWork');
Route::get('/callWork/getInfo', [JobController::class, 'getInfo'])->name('getInfo');

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


/**
 * Реклама
 */

Route::get('/orderReklama', function () {
    return view('reklama.oderReklama');
})->name('orderReklama');

Route::get('/driverReklama', function () {
    return view('reklama.driverReklama');
})->name('driverReklama');

Route::get('/stationReclama', function () {
    return view('reklama.stationReklama');
})->name('stationReklama');

Route::get('/airportReklama', function () {
    return view('reklama.airportReklama');
})->name('airportReklama');

Route::get('/regionReklama', function () {
    return view('reklama.regionReklama');
})->name('regionReklama');

Route::get('/tableReklama', function () {
    return view('reklama.tableReklama');
})->name('tableReklama');

/**
 * Отчеты
 */
Route::get('/reportIpRoute', [ReportController::class, 'reportIpRoute'])->middleware('role:superadministrator')
    ->name('reportIpRoute');

Route::get('/reportIpPage', [ReportController::class, 'reportIpPage'])->middleware('role:superadministrator')
    ->name('reportIpPage');

Route::get('/reportIpUniq', [ReportController::class, 'reportIpUniq'])->middleware('role:superadministrator')
    ->name('reportIpUniq');

Route::get('/reportIpUniqShort', [ReportController::class, 'reportIpUniqShort'])->middleware('role:superadministrator')
    ->name('reportIpUniqShort');

Route::get('/reportIpOrder', [ReportController::class, 'reportIpOrder'])->middleware('role:superadministrator')
    ->name('reportIpOrder');

Route::get('/siteMap', [ReportController::class, 'siteMap'])->middleware('role:superadministrator')
    ->name('siteMap');


/**
 * Віджети для використання на сайті
 */
Route::get('/widgets', [WidgetsController::class, 'index'])->name('widgets-index');
Route::get('/widgets/job', [WidgetsController::class, 'job'])->name('widgets-job');

Route::get('/widgets/getInfo', [WidgetsController::class, 'getInfo'])->name('widgets-getInfo');

/**
 * Services about
 */

Route::get('/about/{service}', function (string $service) {
    $page = "services." . $service;
    return view($page);
})->name('about-service');


/**
 * BlackList
 */

Route::get('/blacklist', [BlackListController::class,'index'])->name('index-black');

Route::get('/blacklist/addToBlacklist', [BlackListController::class,'addToBlacklist'])->name('addToBlacklist');
Route::get('/blacklist/deleteFromBlacklist', [BlackListController::class,'deleteFromBlacklist'])->name('deleteFromBlacklist');

/**
  * Ip address
  */
Route::get('/ip/city', [IPController::class,'ipCity'])->name('ipCity');
Route::get('/ip/ipCityOne/{ip}', [IPController::class,'ipCityOne'])->name('ipCity');
Route::get('/ip/address', [IPController::class,'address'])->name('address');
 /**
  * City
  */
Route::get('/city/all', [CityController::class,'index']);
Route::get('/city/online/{city}', [CityController::class,'cityOnline']);
Route::get('/city/destroy/{id}', [CityController::class,'destroy']);
Route::get('/city/edit/{id}/{name}/{address}/{login}/{password}/{online}', [CityController::class,'edit']);
Route::get('/city/cityNew', function () {
    return view('admin.cities');
})->name('city-new');
Route::get('/city/cityCreat', [CityController::class,'cityCreat'])->name('city-save');
Route::get('/city/verification', [CityController::class,'checkDomains'])->name('checkDomains');

Route::get('/city/versionAPICitiesUpdate', [CityController::class,'versionAPICitiesUpdate'])
    ->name('versionAPICitiesUpdate');

Route::get('/city/apiVersion/{name}/{address}', [UniversalAndroidFunctionController::class,'apiVersion'])
    ->name('apiVersion');



/**
 * City versionCombo
 */
Route::get('/city/versionComboDniproPas2', [AndroidPas2_Dnipro_Controller::class,'versionComboDnipro'])->name('versionComboDnipro');
Route::get('/city/versionComboDniproPas4', [AndroidPas4001_Dnipro_Controller::class,'versionComboDnipro'])->name('versionComboDnipro4001');
Route::get('/city/versionComboOdessaPas2', [AndroidPas2_Odessa_Controller::class,'versionComboOdessa'])->name('versionComboOdessa2');
Route::get('/city/versionComboOdessaPas2', [AndroidPas2_Odessa_Controller::class,'versionComboOdessa'])->name('versionComboOdessa2');
Route::get('/city/versionComboZaporizhzhiaPas2', [AndroidPas2_Zaporizhzhia_Controller::class,'versionComboZaporizhzhia'])->name('versionComboZaporizhzhia2');
Route::get('/city/versionComboCherkasyPas2', [AndroidPas2_Cherkasy_Controller::class,'versionComboCherkasy'])->name('versionComboCherkasy2');
Route::get('/showLatLng/{Lat}/{lng}', [\App\Http\Controllers\VisicomController::class,'showLatLng'])->name('showLatLng');

/**
 *
 */

Route::get('/reverse/{Lat}/{lng}', [OpenStreetMapController::class,'reverse'])->name('reverse');
 /**
  * UID
  */

Route::get('/android/UIDStatusShow/{user_full_name}', [UIDController::class, 'UIDStatusShow'])
    ->name('UIDStatusShow');

Route::get('/android/UIDStatusShowEmail/{email}', [UIDController::class, 'UIDStatusShowEmail'])
    ->name('UIDStatusShowEmail');

Route::get('/closeReasonData/all', [UIDController::class, 'UIDStatusShowAdmin'])
    ->name('UIDStatusShowAdmin');

Route::get('/UIDStatusReviewAdmin/{dispatching_order_uid}', [UIDController::class, 'UIDStatusReviewAdmin'])
    ->name('UIDStatusShowAdmin');

/**
 * Bonuses
 */
Route::get('/bonus/all', [BonusController::class, 'index']);
Route::get('/bonus/destroy/{id}', [BonusController::class, 'destroy']);
Route::get('/bonus/edit/{id}/{name}/{size}', [BonusController::class, 'edit']);
Route::get('/bonus/store/', [BonusController::class, 'store'])->name('bonus-store');
Route::get('/bonus/newPage/', [BonusController::class, 'new'])->name('bonus-new');
Route::get('/bonus/bonusUserShow/{email}', [BonusController::class, 'bonusUserShow'])->name('bonusUserShow');
Route::get('/bonus/bonusAdd/{email}/{bonusTypeId}/{bonus}', [BonusController::class, 'bonusAdd'])->name('bonusUserAdd');
Route::get('/bonus/bonusDel/{email}/{bonusTypeId}/{bonus}', [BonusController::class, 'bonusDel'])->name('bonusUserDel');

/**
 * Balance Bonus
 */
Route::get('/bonusBalance/recordsAdd/{orderwebs_id}/{users_id}/{bonus_types_id}/{bonus}', [BonusBalanceController::class, 'recordsAdd'])
    ->name('recordsAdd');
Route::get('/bonusBalance/recordsDel/{orderwebs_id}/{users_id}/{bonus_types_id}/{bonus}', [BonusBalanceController::class, 'recordsDel'])
    ->name('recordsDel');
Route::get('/bonusBalance/recordsBloke/{uid}', [BonusBalanceController::class, 'recordsBloke'])
    ->name('recordsBloke');
Route::get('/bonusBalance/userBalance/{users_id}', [BonusBalanceController::class, 'userBalance'])
    ->name('userBalance');
Route::get('/bonusBalance/userBalanceBloke/{users_id}', [BonusBalanceController::class, 'userBalanceBloke'])
    ->name('userBalanceBloke');
Route::get('/bonusBalance/userBalanceHistory/{users_id}', [BonusBalanceController::class, 'userBalanceHistory'])
    ->name('userBalanceHistory');

Route::get('/bonusBalance/blockBonusToDelete/{orderwebs_id}', [BonusBalanceController::class, 'blockBonusToDelete'])
    ->name('blockBonusToDelete');
Route::get('/bonusBalance/blockBonusReturn/{orderwebs_id}', [BonusBalanceController::class, 'blockBonusReturn'])
    ->name('blockBonusReturn');

Route::get('/bonusBalance/historyUID/{id}', [BonusBalanceController::class, 'historyUID'])
    ->name('historyUID');

Route::get('/bonusBalance/historyUIDunBlocked/{uid}', [BonusBalanceController::class, 'historyUIDunBlocked'])
    ->name('historyUIDunBlocked');

Route::get('/bonusBalance/bonusReport/', [ReportController::class, 'bonusReport'])
    ->name('bonusReport');

/**
 * User add
 */
Route::get('/android/addUser/{name}/{email}', [UniversalAndroidFunctionController::class, 'addUser'])->name('addUser');
Route::get('/android/bonusType1ForAll', [WebOrderController::class, 'bonusType1ForAll'])->name('bonusType1ForAll');
Route::get('/fixIncorrectNameEmail', [WebOrderController::class, 'fixIncorrectNameEmail'])->name('fixIncorrectNameEmail');


/**
 * BlackList
 */
Route::get('/android/verifyBlackListUser/{email}/{androidDom}', [UniversalAndroidFunctionController::class, 'verifyBlackListUser'])
    ->name('verifyBlackListUser');

/**
 * Universal
 */
Route::get('/android/Universal/startNewProcessExecutionStatus/{doubleOrder}', [UniversalAndroidFunctionController::class, 'startNewProcessExecutionStatusEmu'])
    ->name('startNewProcessExecutionStatus');
Route::get('/android/Universal/startNewProcessExecutionStatusEmu/{doubleOrder}', [UniversalAndroidFunctionController::class, 'startNewProcessExecutionStatusEmu'])
    ->name('startNewProcessExecutionStatus');


Route::post('/android/Universal/startNewProcessExecutionStatusPost/', [UniversalAndroidFunctionController::class, 'startNewProcessExecutionStatusPost'])
    ->name('startNewProcessExecutionStatusPost');


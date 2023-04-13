<?php

namespace App\Http\Controllers;

use App\Mail\Admin;
use App\Mail\Check;
use App\Mail\Driver;
use App\Mail\Feedback;
use App\Models\Combo;
use App\Models\Config;
use App\Models\Objecttaxi;
use App\Models\Order;
use App\Models\Orderweb;
use App\Models\Quite;
use App\Models\Street;
use App\Mail\Server;
use App\Models\Tarif;
use App\Models\User;
use App\Rules\ComboName;
use App\Rules\PhoneNumber;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class WebOrderController extends Controller
{
    /**
     * Проверка подключения к АПИ
     */
    public function connectAPI()
    {
        $username = config('app.username');
        $password = hash('SHA512', config('app.password'));
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);
        $subject = 'Отсутствует доступ к серверу.';

        try {
            $url = config('app.taxi2012Url_1') . '/api/clients/profile';


            Http::timeout(2)->withHeaders([
                'Authorization' => $authorization,
            ])->get($url);
            return config('app.taxi2012Url_1');
        } catch (Exception $e) {
            try {
                $url = config('app.taxi2012Url_2') . '/api/clients/profile';
                Http::timeout(2)->withHeaders([
                    'Authorization' => $authorization,
                ])->get($url);

                $messageAdmin = "Ошибка подключения к серверу " . config('app.taxi2012Url_1') . ".   " . PHP_EOL .
                    "Произведено подключение к серверу " . config('app.taxi2012Url_2') . ".";
                $paramsAdmin = [
                    'subject' => $subject,
                    'message' => $messageAdmin,
                ];

                $alarmMessage = new TelegramController();
                $alarmMessage->sendAlarmMessage($messageAdmin);

                Mail::to('cartaxi4@gmail.com')->send(new Server($paramsAdmin));
                Mail::to('taxi.easy.ua@gmail.com')->send(new Server($paramsAdmin));

                return config('app.taxi2012Url_2');
            } catch (Exception $e) {
                try {
                    $url = config('app.taxi2012Url_3') . '/api/clients/profile';
                    Http::timeout(2)->withHeaders([
                        'Authorization' => $authorization,
                    ])->get($url);
                    return config('app.taxi2012Url_3');
                } catch (Exception $e) {
                    $messageAdmin = "Ошибка подключения к серверу " . config('app.taxi2012Url_1') . ".   " . PHP_EOL .
                        "Ошибка подключения к серверу " . config('app.taxi2012Url_2') . ".   " . PHP_EOL .
                        "Ошибка подключения к серверу " . config('app.taxi2012Url_3') . ".";
                    $paramsAdmin = [
                        'subject' => $subject,
                        'message' => $messageAdmin,
                    ];

                    $alarmMessage = new TelegramController();
                    $alarmMessage->sendAlarmMessage($messageAdmin);

                    Mail::to('cartaxi4@gmail.com')->send(new Server($paramsAdmin));
                    Mail::to('taxi.easy.ua@gmail.com')->send(new Server($paramsAdmin));

                    return '400';
                }
            }
        }
    }

    public function connectAPInoEmail()
    {
        $username = config('app.username');
        $password = hash('SHA512', config('app.password'));
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        try {
            $url = config('app.taxi2012Url_1') . '/api/clients/profile';
            Http::timeout(2)->withHeaders([
                'Authorization' => $authorization,
            ])->get($url);
            return config('app.taxi2012Url_1');
        } catch (Exception $e) {
            try {
                $url = config('app.taxi2012Url_2') . '/api/clients/profile';
                Http::timeout(2)->withHeaders([
                    'Authorization' => $authorization,
                ])->get($url);
                return config('app.taxi2012Url_2');
            } catch (Exception $e) {
                try {
                    $url = config('app.taxi2012Url_3') . '/api/clients/profile';
                    Http::timeout(2)->withHeaders([
                        'Authorization' => $authorization,
                    ])->get($url);
                    return config('app.taxi2012Url_3');
                } catch (Exception $e) {
                    return '400';
                }
            }
        }
    }


    /**
     * Цитаты
     */

    public function quites_all()
    {
        $quites = Quite::all();
        return $quites;
    }

    public function query_all()
    {
        $querys = Orderweb::all();
        return $querys;
    }

    /**
     *
     */
    public function getIP () {
        $IP_ADDR = getenv("REMOTE_ADDR") ;
        return $IP_ADDR;
    }

    /**
     * Авторизация пользователя
     * @return string
     */
    public function account($authorization)
    {
        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/clients/profile';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url);

        return $response->collect();

    }

    /**
     * @param $req
     * @return string
     */
    public function authorization($req)
    {
        $username = $req->username;
        $password = hash('SHA512', $req->password);
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        return $authorization;
    }

    /**
     * Запрос профиля клиента
     * @return string
     */
    public function profile()
    {
        $username = Auth::user()->user_phone;
        try {
            $password = hash('SHA512', Crypt::decryptString(Auth::user()->password_taxi));

            $authorization = 'Basic ' . base64_encode($username . ':' . $password);

            $connectAPI = WebOrderController::connectApi();
            if ($connectAPI == 400) {
                return redirect()->route('home-news')
                    ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
            }


            $url = $connectAPI . '/api/clients/profile';
            $response = Http::withHeaders([
                'Authorization' => $authorization,
            ])->get($url);
            $response_arr = json_decode($response, true);


            if ($response->status() == "200") {
                $user_first_name = Auth::user()->name;
                return redirect()->route('profile-view', ['authorization' => $authorization])
                    ->with('success', "Ласкаво просимо $user_first_name! Ваші розрахунки маршруту знайдіть натиснувши кнопку \"Мої маршрути\".");
            } else {

                return redirect()->route('login-taxi-phone', ['phone' => Auth::user()->user_phone])
                    ->with('error', 'Перевірте дані та спробуйте ще раз або пройдіть реєстрацію');
            }
        }
        catch (Exception $e) {

            return redirect()->route('login-taxi-phone', ['phone' => Auth::user()->user_phone])
                ->with('error', 'Перевірте дані та спробуйте ще раз або пройдіть реєстрацію');
        }

    }

    /**
     * Запрос профиля клиента при смене пароля
     * @return string
     */
    public function profileApi(Request $req)
    {
        $username = substr($req->username, 3);
        $password = hash('SHA512', $req->password);
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/clients/profile';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url);
        $response_arr = json_decode($response, true);

        if ($response->status() == "200") {
            $finduser = User::where('name', Auth::user()->name)->first();
            $finduser->user_phone = $req->username;
            $finduser->password_taxi = Crypt::encryptString($req->password);
            $finduser->save();

            $user_first_name = Auth::user()->name;
            return redirect()->route('profile-view', ['authorization' => $authorization])
                ->with('success', "Ласкаво просимо $user_first_name! Ваші розрахунки маршруту знайдіть натиснувши кнопку \"Мої маршрути\".");
        } else {
            return redirect()->route('login-taxi-phone', ['phone' => $req->username])
                ->with('error', 'Перевірте дані та спробуйте ще раз або пройдіть реєстрацію');
        }
    }


    /**
     * Форма редактирования профиля клиента
     * @return string
     */
    public function profileEditForm ($authorization)
    {
        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/clients/profile';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url);
        $response_arr = json_decode($response, true);

        return view('taxi.profileEdit', ['authorization' => $authorization, 'response' => $response]);
    }

    /**
     * Обновление профиля клиента
     * @return int
     */
    public function profileput(Request $req)
    {
        $authorization = $req->authorization;
        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/clients/profile';
        $response = Http::withHeaders([
            'Authorization' => $req->authorization])->put($url, [
            'patch' => 'name, address', /*Обновление патчем.- является необязательным параметром и позволяет выполнить частичное обновление (обновить только имя клиента, только адрес клиента, или и то и другое).
                Возможный значения «patch»:
                «name» - будет обновлена только группа полей: user_first_name, user_middle_name и user_last_name;
                «address» - будет обновлена только группа полей: route_address_from, route_address_number_from, route_address_entrance_from и route_address_apartment_from;
                Значения параметра «patch» можно объединять разделителем «,» (запятая);
                Если «patch» не содержит значения — будут обновлены все поля.*/
            'user_first_name' => $req->user_first_name, //Имя
            'user_middle_name' => $req->user_middle_name, //Отчество
            'user_last_name' => $req->user_last_name, //Фамилия
            'route_address_from' => $req->route_address_from, //Адрес
            'route_address_number_from' => $req->route_address_number_from, //Номер дома
            'route_address_entrance_from' => $req->route_address_entrance_from, //Подъезд
            'route_address_apartment_from' => $req->route_address_apartment_from, //Квартира
            ]);
        $finduser = User::where('email', Auth::user()->email)->first();
        $finduser->name = $req->user_first_name;
        $finduser->save();
        Auth::login($finduser);
        return redirect()->route('profile-view', ['authorization' => $authorization])
           ->with('success', 'Особисті дані успішно оновлено');
    }
    /**
     * Регистрация пользователя
     * Получение кода подтверждения
     * @return int
     */
    public function sendConfirmCode(Request $req)
    {
        $error = true;
        $secret = config('app.RECAPTCHA_SECRET_KEY');

        if (!empty($_GET['g-recaptcha-response'])) { //проверка на робота
            $curl = curl_init('https://www.google.com/recaptcha/api/siteverify');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, 'secret=' . $secret . '&response=' . $_GET['g-recaptcha-response']);
            $out = curl_exec($curl);
            curl_close($curl);

            $out = json_decode($out);
            if ($out->success == true) {
                $connectAPI = WebOrderController::connectApi();
                if ($connectAPI == 400) {
                    return redirect()->route('home-news')
                        ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
                }
                $url = $connectAPI . '/api/account/register/sendConfirmCode';
                $response = Http::post($url, [
                'phone' => $req->username, //Обязательный. Номер мобильного телефона, на который будет отправлен код подтверждения.
                'taxiColumnId' => config('app.taxiColumnId'), //Номер колоны, из которой отправляется SMS (0, 1 или 2, по умолчанию 0).
                'appHash' => '' //Хэш Android приложения для автоматической подстановки смс кода. 11 символов.
                ]);

                if ($response->status() == "200") {
                    return redirect()->route('registration-form-phone', ['phone' => $req->username])
                    ->with('success', 'Код підтвердження успішно надіслано на вказаний телефон');
                } else {
                    return redirect()->route('login-taxi-phone', ['phone' => $req->username])
                    ->with('error', 'Пользователь с таким номером телефона уже зарегистрирован');
                }
            }
        }
        if ($error) {
            return redirect()->route('registration-sms-phone', ['phone' => $req->username])->with('error', "Не пройдено перевірку 'Я не робот'");

        }
    }

    /**
     * Регистрация пользователя
     * Регистрация с кодом подтверждения
     * @return string
     */
    public function register(Request $req)
    {
        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/account/register';
        $response = Http::post($url, [
            //Все параметры обязательные
            'phone' => substr($req->phone, 3), //Номер мобильного телефона, на который будет отправлен код подтверждения
            'confirm_code' => $req->confirm_code, //Код подтверждения, полученный в SMS.
            'password' =>  $req->password, //Пароль.
            'confirm_password' => $req-> confirm_password, //Пароль (повтор).
            'user_first_name' => 'Новий користувач', // Необязательный. Имя клиента
        ]);
     //   dd($response->status());
        if ($response->status() == "201") {
            $username = substr($req->phone, 3);
            $password = hash('SHA512', $req->password);
            $authorization = 'Basic ' . base64_encode($username . ':' . $password);
            return redirect()->route('profile-view', ['authorization' => $authorization])
                ->with('success', 'Реєстрація нового користувача успішна');
        } else {
            return redirect()->route('registration-form')->with('error', $response->body());
        }
    }

    /**
     * Восстановление пароля
     * Получение кода подтверждения
     * @return string
     */
    public function restoreSendConfirmCode(Request $req)
    {
        $error = true;
        $secret = config('app.RECAPTCHA_SECRET_KEY');

        if (!empty($_GET['g-recaptcha-response'])) { //проверка на робота
            $curl = curl_init('https://www.google.com/recaptcha/api/siteverify');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, 'secret=' . $secret . '&response=' . $_GET['g-recaptcha-response']);
            $out = curl_exec($curl);
            curl_close($curl);

            $out = json_decode($out);
            if ($out->success == true) {
                $connectAPI = WebOrderController::connectApi();
                if ($connectAPI == 400) {
                    return redirect()->route('home-news')
                        ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
                }
                $url = $connectAPI . '/api/account/restore/sendConfirmCode';
                $response = Http::post($url, [
                    'phone' =>  substr($req->username, 3), //Обязательный. Номер мобильного телефона, на который будет отправлен код подтверждения.
                    'taxiColumnId' => config('app.taxiColumnId'), //Номер колоны, из которой отправляется SMS (0, 1 или 2, по умолчанию 0).
                    'appHash' => '' //Хэш Android приложения для автоматической подстановки смс кода. 11 символов.
                ]);

                if ($response->status() == "200") {
                    return redirect()->route('restore-form-phone', ['phone' => $req->username])
                        ->with('success', 'Код підтвердження успішно надіслано на вказаний телефон.');
                } else {
                    $json_arrWeb = json_decode($response->body(), true);

                    $resp_answer = 'Помилка. ' . $json_arrWeb['Message'];
                    return redirect()->route('profile')
                        ->with('error', $resp_answer);
                }
            }
        }
        if ($error) {
            return redirect()->route('restore-sms-phone', ['phone' => $req->username])->with('error', "Не пройдено перевірку 'Я не робот'");

        }
    }


    /**
     * Восстановление пароля
     * @return string
     */

    public function restorePassword(Request $req)
    {
        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/account/restore';
        $response = Http::post($url, [
            //Все параметры обязательные
            'phone' => substr($req->phone, 3), //Номер мобильного телефона, на который будет отправлен код подтверждения
            'confirm_code' => $req->confirm_code, //Код подтверждения, полученный в SMS.
            'password' =>  $req->password, //Пароль.
            'confirm_password' => $req-> confirm_password, //Пароль (повтор).
        ]);
        if ($response->status() == "200") {
            $username = substr($req->phone, 3);
            $password = hash('SHA512', $req->password);
            $authorization = 'Basic ' . base64_encode($username . ':' . $password);

            $finduser = User::where('user_phone', $req->phone)->first();
            $finduser->password_taxi = Crypt::encryptString($req->password);
            $finduser->save();

            return redirect()->route('profile-view', ['authorization' => $authorization])
                ->with('success', 'Пароль успішно змінено.');
        } else {

            $json_arrWeb = json_decode($response->body(), true);
          //  dd($json_arrWeb);


            $resp_answer = 'Помилка. ' . $json_arrWeb['Message'];

            return redirect()->route('restore-sms-phone', ['phone' => $req->phone])->with('error', $resp_answer);
        }
    }

    /**
     * Проверка телефона на регистрацию в АПИ
     * если телефон не найден отправит на регистрацию
     */
    public function verifyAccount($phone)
    {
        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/account/register/sendConfirmCode';
        $response = Http::post($url, [
            'phone' => $phone, //Обязательный. Номер мобильного телефона, на который будет отправлен код подтверждения.
            'taxiColumnId' => config('app.taxiColumnId'), //Номер колоны, из которой отправляется SMS (0, 1 или 2, по умолчанию 0).
            'appHash' => '' //Хэш Android приложения для автоматической подстановки смс кода. 11 символов.
        ]);
         return $response;
    }

    /**
     * Верификация телефона
     * Получение кода подтверждения
     * @return string
     */
    public function approvedPhonesSendConfirmCode($phone)
    {
        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/approvedPhones/sendConfirmCode';
        $response = Http::post($url, [
            'phone' => $phone, //Обязательный. Номер мобильного телефона, на который будет отправлен код подтверждения.
            'taxiColumnId' => config('app.taxiColumnId') //Номер колоны, из которой отправляется SMS (0, 1 или 2, по умолчанию 0).
        ]);
        return $response->status();
    }

    /**
     * Верификация телефона
     * Подтверждение номера телефона
     * @return string
     */
    public function approvedPhones($phone, $confirm_code)
    {
        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/approvedPhones/';
        $response = Http::post($url, [
            'phone' => $phone, //Обязательный. Номер мобильного телефона
            'confirm_code' => $confirm_code //Обязательный. Код подтверждения.
        ]);
        return $response->status();
    }
    /**
     * Проверка адреса
     */

    public function adressValidate($req)
    {
        /**
         * По городу
         */

        if ($req->route_undefined == 1 || $req->route_undefined == 'on') { //По городу

            $paramsAdress['route_undefined'] = true;
            /**
             * Проверяем только адрес Откуда
             */
            $req->validate([
                'search' => [new ComboName()],
            ]);

            $arrComboFrom = Combo::where('name', $req->search)->first();

            if ($arrComboFrom->street == 1) { //Проверка на признак улицы
                $req->validate([
                    'from_number' => ['required']
                ]);
                $paramsAdress['routefromnumberBlockNone'] = 'block'; // Открываем поле дома для улиц
                $paramsAdress['routetonumberBlockNone'] = 'block'; //Скрываем поле дома
                $paramsAdress['routefrom'] = $req->search; //Обязательный. Улица откуда.
                $paramsAdress['routefromnumber'] = $req->from_number; //Обязательный. Дом откуда.
            } else {
                $paramsAdress['routefromnumberBlockNone'] = 'none'; //Скрываем поле дома
                $paramsAdress['routetonumberBlockNone'] = 'none'; //Скрываем поле дома
                $paramsAdress['routefrom'] = $req->search; //Обязательный. Улица откуда.
                $paramsAdress['routefromnumber'] = null; //Обязательный. Обнуляем поле Дом откуда.
            }

            $paramsAdress['routeto'] = $req->search; //Обязательный. Улица куда.
            $paramsAdress['routetonumber'] =  $req->from_number; //Обязательный. Дом куда.
            $paramsAdress['route_undefined'] = true; //По городу: True, False

        } else { //Не по городу
            $paramsAdress['route_undefined'] = false; //По городу: True, False

            $req->validate([
                'search' => [new ComboName()],
                'search1' => [new ComboName()],
            ]);

            /**
             * Проверяем заполненность номера дома
             */
            $arrComboFrom = Combo::where('name', $req->search)->first();
            $arrComboTo = Combo::where('name', $req->search1)->first();

            if ($arrComboFrom->street == 1) { //Проверка на признак улицы
                $paramsAdress['routefromnumberBlockNone'] = 'block'; // Открываем поле дома для улиц
                $paramsAdress['routefrom'] = $req->search; //Обязательный. Улица откуда.
                $paramsAdress['routefromnumber'] = $req->from_number; //Обязательный. Дом откуда.
            } else {
                $paramsAdress['routefromnumberBlockNone'] = 'none'; //Скрываем поле дома
                $paramsAdress['routefrom'] = $req->search; //Обязательный. Улица откуда.
                $paramsAdress['routefromnumber'] = null; //Обязательный. Обнуляем поле Дом откуда.
            }

            if ($arrComboTo->street == 1) {
                $paramsAdress['routetonumberBlockNone'] = 'block'; // Открываем поле дома для улиц
                $paramsAdress['routeto'] = $arrComboTo->name; //Обязательный. Улица куда.
                $paramsAdress['routetonumber'] = $req->to_number; //Обязательный. Дом куда.
            } else {
                $paramsAdress['routetonumberBlockNone'] = 'none'; // Скрываем поле дома для улиц
                $paramsAdress['routeto'] = $arrComboTo->name; //Обязательный. Улица куда.
                $paramsAdress['routetonumber'] = null; //Обязательный. Обнуляем поле Дом куда.
            }

            if ($arrComboFrom->street == 1 && $arrComboTo->street == 1) {
                $req->validate([
                    'from_number' => ['required'],
                    'to_number' => ['required']
                ]);
            } else {
                if ($arrComboFrom->street == 1 && $arrComboTo->street !== 1) {
                    $req->validate([
                        'from_number' => ['required'],
                    ]);
                }
                if ($arrComboFrom->street !== 1 && $arrComboTo->street == 1) {
                    $req->validate([
                        'to_number' => ['required']
                    ]);
                }
            }
        };
        return $paramsAdress;
    }

    /**
     * Проверка адреса
     */

    public function adressValidateTransfer($req)
    {
        $paramsAdress['route_undefined'] = false; //По городу: True, False

        $req->validate([
            'search' => [new ComboName()],
        ]);
        $paramsAdress['routefrom'] = $req->search; //Обязательный. Улица откуда.
        $paramsAdress['routefromnumberBlockNone'] = 'none'; //Скрываем поле дома
        $paramsAdress['routefromnumber'] = null; //Обязательный. Обнуляем поле Дом откуда.
        /**
         * Проверяем заполненность номера дома
         */
        $arrComboFrom = Combo::where('name', $req->search)->first();

        //Проверка на признак улицы
        if ($arrComboFrom->street == 1) {
            $req->validate([
                'from_number' => ['required'],
            ]);
            $paramsAdress['routefromnumberBlockNone'] = 'block'; // Открываем поле дома для улиц
            $paramsAdress['routefromnumber'] = $req->from_number; //Обязательный. Дом откуда.
        }

        return $paramsAdress;
    }


    /**
     * Работа с заказами
     * Расчет стоимости заказа по улицам
     * @return string
     */
    public function cost(Request $req)
    {
        /**
         * Проверка адресов в базе
         */
        $params = WebOrderController::adressValidate($req);

        $error = true;
        $secret = config('app.RECAPTCHA_SECRET_KEY');
        /**
         * Запоминаем остальные параметры запроса
         */

        $params['user_full_name'] = $req->user_full_name;
        $params['user_phone'] = $req->user_phone;

        $params['client_sub_card'] = null;
        $params['route_address_entrance_from'] = null;

        $params['required_time'] = $req->required_time; //Время подачи предварительного заказа
        $params['reservation'] = false; //Обязательный. Признак предварительного заказа: True, False

        $reservation = $params['reservation'];
        $required_time = $params['required_time'];

        if ($req->wagon == 'on' || $req->wagon == 1) {
            $params['wagon'] = 1; //Универсал: True, False
        } else {
            $params['wagon'] = 0;
        };
        if ($req->minibus == 'on' || $req->minibus == 1) {
            $params['minibus'] = 1; //Микроавтобус: True, False
        } else {
            $params['minibus'] = 0;
        };
        if ($req->premium == 'on' || $req->premium == 1) {
            $params['premium'] = 1; //Машина премиум-класса: True, False
        } else {
            $params['premium'] = 0;
        };

        $params['flexible_tariff_name'] = $req->flexible_tariff_name; //Гибкий тариф
        $params['comment'] = $req->comment; //Комментарий к заказу
        $params['add_cost'] = $req->add_cost; //Добавленная стоимость
        $params['taxiColumnId'] = config('app.taxiColumnId'); //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2

        $params['custom_extra_charges'] = '20'; //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/



        if (!empty($_GET['g-recaptcha-response'])) { //проверка на робота
            $json_arr = WebOrderController::tariffs();
            $curl = curl_init('https://www.google.com/recaptcha/api/siteverify');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, 'secret=' . $secret . '&response=' . $_GET['g-recaptcha-response']);
            $out = curl_exec($curl);
            curl_close($curl);


            $out = json_decode($out);
            if ($out->success == true) {
                $error = false;
                $username = config('app.username');
                $password = hash('SHA512', config('app.password'));
                $authorization = 'Basic ' . base64_encode($username . ':' . $password);


                $user_full_name = $req->user_full_name;
                $user_phone = $req->user_phone;

                $from = $req->search;
                $from_number = $req->from_number;

                if (Combo::where('name', $from)->first()->street == 0) {
                    $from_number_info = '';
                } else {
                    $from_number_info = "(будинок №$from_number)";
                };

                $auto_type = 'Тип авто: ';
                if ($req->wagon == 'on' || $req->wagon == '1') {
                    $wagon = true;
                    $wagon_type = " Універсал ";
                    $auto_type = $auto_type . $wagon_type . " ";
                } else {
                    $wagon = false;
                };
                if ($req->minibus == 'on' || $req->minibus == '1') {
                    $minibus = true;
                    $minibus_type = " Мікроавтобус ";
                    $auto_type = $auto_type . $minibus_type . " ";
                } else {
                    $minibus = false;
                };
                if ($req->premium == 'on' || $req->premium == '1') {
                    $premium = true;
                    $premium_type = " Машина преміум-класса. ";
                    $auto_type = $auto_type . $premium_type;
                } else {
                    $premium = false;
                };
                if ($auto_type == 'Тип авто: ') {
                    $auto_type = 'Тип авто: звичайне. ';
                };
                $flexible_tariff_name = $req->flexible_tariff_name;
                if ($flexible_tariff_name) {
                    $auto_type = $auto_type . "Тариф: $flexible_tariff_name";
                };
                $comment = $req->comment;
                $add_cost = $req->add_cost;
                $taxiColumnId = config('app.taxiColumnId');

                $payment_type_info = "готівка";

                $route_undefined = false;
                $to = $req->search1;
                $to_number = $req->to_number;

                if ($req->route_undefined == 1 || $req->route_undefined == 'on') {
                    $route_undefined = true;
                    $to = $from;
                    $to_number = $from_number;
                };

                if (Combo::where('name', $to)->first()->street == 0) {
                    $to_number_info = '';
                } else {
                    $to_number_info = "(будинок №$to_number)";
                };

                $connectAPI = WebOrderController::connectApi();
                if ($connectAPI == 400) {
                    return redirect()->route('home-news')
                        ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
                }
                $url = $connectAPI . '/api/weborders/cost';

                $response = Http::withHeaders([
                    'Authorization' => $authorization,
                ])->post($url, [
                    'user_full_name' => null, //Полное имя пользователя
                    'user_phone' => null, //Телефон пользователя
                    'client_sub_card' => null,
                    'required_time' => $required_time, //Время подачи предварительного заказа
                    'reservation' => $reservation, //Обязательный. Признак предварительного заказа: True, False
                    'route_address_entrance_from' => null,
                    'comment' => $comment, //Комментарий к заказу
                    'add_cost' => $add_cost,
                    'wagon' => $wagon, //Универсал: True, False
                    'minibus' => $minibus, //Микроавтобус: True, False
                    'premium' => $premium, //Машина премиум-класса: True, False
                    'flexible_tariff_name' => $flexible_tariff_name, //Гибкий тариф
                    'route_undefined' => $route_undefined, //По городу: True, False
                    'route' => [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                        ['name' => $from, 'number' => $from_number],
                        ['name' => $to, 'number' => $to_number],
                    ],
                    'taxiColumnId' => $taxiColumnId, //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
                    'payment_type' => 0, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
                    /*  'extra_charge_codes' => 'ENGLISH', //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
                        'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
                ]);

                if ($response->status() == "200") {
                    /**
                     * Сохранние расчетов в базе
                     */
                    $order = new Order();
                    $order->IP_ADDR = getenv("REMOTE_ADDR") ;//IP пользователя
                    $order->user_full_name = $user_full_name;//Полное имя пользователя
                    $order->user_phone = $user_phone;//Телефон пользователя
                    $order->client_sub_card = null;
                    $order->required_time = $required_time; //Время подачи предварительного заказа
                    $order->reservation = $reservation; //Обязательный. Признак предварительного заказа: True, False
                    $order->route_address_entrance_from = null;
                    $order->comment = $comment;  //Комментарий к заказу
                    $order->add_cost = $add_cost; //Добавленная стоимость
                    $order->wagon = $wagon; //Универсал: True, False
                    $order->minibus = $minibus; //Микроавтобус: True, False
                    $order->premium = $premium; //Машина премиум-класса: True, False
                    $order->flexible_tariff_name = $flexible_tariff_name; //Гибкий тариф
                    $order->route_undefined = $route_undefined; //По городу: True, False
                    $order->routefrom = $from; //Обязательный. Улица откуда.
                    $order->routefromnumber = $from_number; //Обязательный. Дом откуда.
                    $order->routeto = $to; //Обязательный. Улица куда.
                    $order->routetonumber = $to_number; //Обязательный. Дом куда.
                    $order->taxiColumnId = $taxiColumnId; //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
                    $order->payment_type = 0; //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
                    $order->save();
                    $id = $order;
                    $json_arr = json_decode($response, true);
                    $order_cost  = $json_arr['order_cost'];

                    if ($route_undefined === true) {
                        $order = "Вітаємо $user_full_name. Ви зробили розрахунок за маршрутом від
                        $from $from_number_info по місту. Оплата: $payment_type_info. $auto_type";
                    } else {
                        $order = "Вітаємо $user_full_name. Ви зробили розрахунок за маршрутом від
                        $from $from_number_info до $to $to_number_info. Оплата: $payment_type_info. $auto_type";
                    };

                    return redirect()->route('home-id', ['id' => $id])
                        ->with('success', $order)
                        ->with('order_cost', $order_cost);

                } else {
                    $info = "Помилка створення маршруту: Змініть час замовлення та/або адресу
                            відправлення/призначення або не вибрана опція поїздки по місту.
                            Правильно вводьте або зверніться до оператора.";
                    $alarmMessage = new TelegramController();

                    if ($route_undefined === true) {
                        $message = "Увага 🔥! Помилка розрахунку вартості за маршрутом від $from $from_number_info по місту. Оплата: $payment_type_info. $auto_type";
                    } else {
                        $message = "Увага 🔥! Помилка розрахунку вартості за маршрутом від $from $from_number_info до $to $to_number_info. Оплата: $payment_type_info. $auto_type";
                    };
                    $alarmMessage->sendAlarmMessage($message);
                    $json_arr = WebOrderController::tariffs();
                    return view('taxi.homeCombo', ['json_arr' => $json_arr, 'params' => $params,
                        'info' => $info]);
                }
            }
        }
        if ($error) {
            $json_arr = WebOrderController::tariffs();
            return view('taxi.homeCombo', ['json_arr' => $json_arr, 'params' => $params,
                'info' => 'Не пройдено перевірку на робота.']);
        }
    }
    /**
     * Работа с заказами
     * Расчет стоимости заказа по объектам
     * @return string
     */
    public function costobject(Request $req)
    {

        $error = true;
        $secret = config('app.RECAPTCHA_SECRET_KEY');
        /**
         * Параметры запроса
         */
        $params['user_full_name'] = $req->user_full_name;
        $params['user_phone'] = $req->user_phone;

        $params['routefrom'] = $req->search2; //Обязательный. Улица откуда.

        $params['client_sub_card'] = null;
        $params['required_time'] = $req->required_time; //Время подачи предварительного заказа
        $params['reservation'] = false; //Обязательный. Признак предварительного заказа: True, False

        $reservation = $params['reservation'];
        $required_time = $params['required_time'];

        $params['route_address_entrance_from'] = null;
        if ($req->wagon == 'on' || $req->wagon == 1) {
            $params['wagon'] = 1; //Универсал: True, False
        } else {
            $params['wagon'] = 0;
        };
        if ($req->minibus == 'on' || $req->minibus == 1) {
            $params['minibus'] = 1; //Микроавтобус: True, False
        } else {
            $params['minibus'] = 0;
        };
        if ($req->premium == 'on' || $req->premium == 1) {
            $params['premium'] = 1; //Машина премиум-класса: True, False
        } else {
            $params['premium'] = 0;
        };

        $params['flexible_tariff_name'] = $req->flexible_tariff_name; //Гибкий тариф
        $params['comment'] = $req->comment; //Комментарий к заказу
        $params['add_cost'] = $req->add_cost; //Добавленная стоимость
        $params['taxiColumnId'] = config('app.taxiColumnId'); //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2

        $params['routeto'] = $req->search3; //Обязательный. Улица куда.

        $params['route_undefined'] = false; //По городу: True, False
        if ($req->route_undefined == 1 || $req->route_undefined == 'on') {
            $params['routeto'] = $req->search2; //Обязательный. Улица куда.

            $params['route_undefined'] = 1; //По городу: True, False
        };
        $params['custom_extra_charges'] = '20'; //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/

        if (!empty($_GET['g-recaptcha-response'])) { //проверка на робота
            $json_arr = WebOrderController::tariffs();
            $curl = curl_init('https://www.google.com/recaptcha/api/siteverify');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, 'secret=' . $secret . '&response=' . $_GET['g-recaptcha-response']);
            $out = curl_exec($curl);
            curl_close($curl);


            $out = json_decode($out);
            if ($out->success == true) {
                $error = false;
                $username = config('app.username');
                $password = hash('SHA512', config('app.password'));
                $authorization = 'Basic ' . base64_encode($username . ':' . $password);


                $user_full_name = $req->user_full_name;
                $user_phone = $req->user_phone;

                $from = $req->search2;

                $auto_type = 'Тип авто: ';
                if ($req->wagon == 'on' || $req->wagon == '1') {
                    $wagon = true;
                    $wagon_type = " Універсал ";
                    $auto_type = $auto_type . $wagon_type . " ";
                } else {
                    $wagon = false;
                };
                if ($req->minibus == 'on' || $req->minibus == '1') {
                    $minibus = true;
                    $minibus_type = " Мікроавтобус ";
                    $auto_type = $auto_type . $minibus_type . " ";
                } else {
                    $minibus = false;
                };
                if ($req->premium == 'on' || $req->premium == '1') {
                    $premium = true;
                    $premium_type = " Машина преміум-класса. ";
                    $auto_type = $auto_type . $premium_type;
                } else {
                    $premium = false;
                };
                if ($auto_type == 'Тип авто: ') {
                    $auto_type = 'Тип авто: звичайне. ';
                };
                $flexible_tariff_name = $req->flexible_tariff_name;
                if ($flexible_tariff_name) {
                    $auto_type = $auto_type . "Тариф: $flexible_tariff_name";
                };
                $comment = $req->comment;
                $add_cost = $req->add_cost;
                $taxiColumnId = config('app.taxiColumnId');
                $payment_type_info = "готівка";
                $route_undefined = false;
                $to = $req->search3;

                if ($req->route_undefined == 1 || $req->route_undefined == 'on') {
                    $route_undefined = true;
                    $to = $from;

                };
                $connectAPI = WebOrderController::connectApi();
                if ($connectAPI == 400) {
                    return redirect()->route('home-news')
                        ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
                }
                $url = $connectAPI . '/api/weborders/cost';
                $response = Http::withHeaders([
                    'Authorization' => $authorization,
                ])->post($url, [
                    'user_full_name' => null, //Полное имя пользователя
                    'user_phone' => null, //Телефон пользователя
                    'client_sub_card' => null,
                    'required_time' => $required_time, //Время подачи предварительного заказа
                    'reservation' => $reservation, //Обязательный. Признак предварительного заказа: True, False
                    'route_address_entrance_from' => null,
                    'comment' => $comment, //Комментарий к заказу
                    'add_cost' => $add_cost,
                    'wagon' => $wagon, //Универсал: True, False
                    'minibus' => $minibus, //Микроавтобус: True, False
                    'premium' => $premium, //Машина премиум-класса: True, False
                    'flexible_tariff_name' => $flexible_tariff_name, //Гибкий тариф
                    'route_undefined' => $route_undefined, //По городу: True, False
                    'route' => [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                        ['name' => $from],
                        ['name' => $to],
                    ],
                    'taxiColumnId' => $taxiColumnId, //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
                    'payment_type' => 0, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
                    /*  'extra_charge_codes' => 'ENGLISH', //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
                        'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
                ]);
//dd($response->body());
                if ($response->status() == "200") {
                    /**
                     * Сохранние расчетов в базе
                     */
                    $order = new Order();
                    $order->IP_ADDR = getenv("REMOTE_ADDR") ;//IP пользователя
                    $order->user_full_name = $user_full_name;//Полное имя пользователя
                    $order->user_phone = $user_phone;//Телефон пользователя
                    $order->client_sub_card = null;
                    $order->required_time = $required_time; //Время подачи предварительного заказа
                    $order->reservation = $reservation; //Обязательный. Признак предварительного заказа: True, False
                    $order->route_address_entrance_from = null;
                    $order->comment = $comment;  //Комментарий к заказу
                    $order->add_cost = $add_cost; //Добавленная стоимость
                    $order->wagon = $wagon; //Универсал: True, False
                    $order->minibus = $minibus; //Микроавтобус: True, False
                    $order->premium = $premium; //Машина премиум-класса: True, False
                    $order->flexible_tariff_name = $flexible_tariff_name; //Гибкий тариф
                    $order->route_undefined = $route_undefined; //По городу: True, False
                    $order->routefrom = $from; //Обязательный. Улица откуда.

                    $order->routeto = $to; //Обязательный. Улица куда.

                    $order->taxiColumnId = $taxiColumnId; //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
                    $order->payment_type = 0; //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
                    $order->save();
                    $id = $order;
                    $json_arr = json_decode($response, true);
                    $order_cost  = $json_arr['order_cost'];

                    if ($route_undefined === true) {
                        $order = "Вітаємо $user_full_name. Ви зробили розрахунок за маршрутом від
                        $from по місту. Оплата: $payment_type_info. $auto_type
                        Вартість поїздки становитиме: $order_cost грн.";
                    } else {
                        $order = "Вітаємо $user_full_name. Ви зробили розрахунок за маршрутом від
                        $from  до $to. Оплата: $payment_type_info. $auto_type
                       ";
                    };
                    return redirect()->route('home-object-id', ['id' => $id])
                        ->with('success', $order)
                        ->with('order_cost', $order_cost);;
                } else {

                    WebOrderController::version_object();
                    ?>
                    <script type="text/javascript">
                        alert("Помилка створення маршруту: Змініть час замовлення та/або адресу " +
                            "відправлення/призначення або не вибрана опція поїздки по місту. " +
                            "Правильно вводьте або зверніться до оператора.");
                    </script>
                    <?php

                    return view('taxi.homeReqObject', ['json_arr' => $json_arr, 'params' => $params]);
                }
            }
        }
        if ($error) {
            ?>
            <script type="text/javascript">
                alert("Не пройдено перевірку на робота");
            </script>
            <?php
            $json_arr = WebOrderController::tariffs();
            return view('taxi.homeReqObject', ['json_arr' => $json_arr, 'params' => $params]);
        }
    }

    /**
     * Работа с заказами
     * Расчет стоимости заказа по карте
     * @return string
     */
    public function costmap(Request $req)
    {

        $error = true;
        $secret = config('app.RECAPTCHA_SECRET_KEY');
        /**
         * Параметры запроса
         */
        $params['lat'] = $req->lat;
        $params['lng'] = $req->lng;
        $params['lat2'] = $req->lat2;
        $params['lng2'] = $req->lng2;

        $username = config('app.username');
        $password = hash('SHA512', config('app.password'));
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);
        /**
         * Откуда
         */
        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/geodata/nearest';
        $response_from = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url, [
            'lat' => $params['lat'], //Обязательный. Широта
            'lng' => $params['lng'], //Обязательный. Долгота
            /*'r' => '50' //необязательный. Радиус поиска. Значение от 0 до 1000 м. Если не указано — 500м.*/
        ]);
        $response_arr_from = json_decode($response_from, true);
        if ($response_arr_from['geo_streets']['geo_street'] == null) {
            return redirect()->route('homeMapCombo')->with('error', 'Помилка створення маршруту:
                Перевірьте адресу відправлення або зверніться до оператора.');
        }
        $params['routefrom'] = $response_arr_from['geo_streets']['geo_street'][0]['name']; //Обязательный. Улица откуда.
        $params['routefromnumber'] = $response_arr_from['geo_streets']['geo_street'][0]['houses'][0]['house']; //Обязательный. Дом откуда.
        /**
         * Куда
         */
        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/geodata/nearest';
        $response_to = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url, [
            'lat' => $params['lat2'], //Обязательный. Широта
            'lng' => $params['lng2'], //Обязательный. Долгота
            /*'r' => '50' //необязательный. Радиус поиска. Значение от 0 до 1000 м. Если не указано — 500м.*/
        ]);
        $response_arr_to = json_decode($response_to, true);

        if ($response_arr_to['geo_streets']['geo_street'] != null) {
            $params['routeto'] = $response_arr_to['geo_streets']['geo_street'][0]['name']; //Обязательный. Улица куда.
            $params['routetonumber'] = $response_arr_to['geo_streets']['geo_street'][0]['houses'][0]['house']; //Обязательный. Дом куда.
        } else {
            $params['routeto'] = null;
            $params['routetonumber'] = null;
        }

        $params['user_full_name'] = $req->user_full_name;
        $params['user_phone'] = $req->user_phone;

        $params['client_sub_card'] = null;
        $params['required_time'] = $req->required_time; //Время подачи предварительного заказа
        $params['reservation'] = false; //Обязательный. Признак предварительного заказа: True, False

        $reservation = $params['reservation'];
        $required_time = $params['required_time'];

        $params['route_address_entrance_from'] = null;
        if ($req->wagon == 'on' || $req->wagon == 1) {
            $params['wagon'] = 1; //Универсал: True, False
        } else {
            $params['wagon'] = 0;
        };
        if ($req->minibus == 'on' || $req->minibus == 1) {
            $params['minibus'] = 1; //Микроавтобус: True, False
        } else {
            $params['minibus'] = 0;
        };
        if ($req->premium == 'on' || $req->premium == 1) {
            $params['premium'] = 1; //Машина премиум-класса: True, False
        } else {
            $params['premium'] = 0;
        };

        $params['flexible_tariff_name'] = $req->flexible_tariff_name; //Гибкий тариф
        $params['comment'] = $req->comment; //Комментарий к заказу
        $params['add_cost'] = $req->add_cost; //Добавленная стоимость
        $params['taxiColumnId'] = config('app.taxiColumnId'); //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2

        $payment_type_info = 'готівка';

        $params['route_undefined'] = $req->route_undefined; //По городу: True, False

        if ($req->route_undefined == 1 || $req->route_undefined == 'on') {
            $params['routeto'] =  $params['routefrom']; //Обязательный. Улица куда.
            $params['routetonumber'] = $params['routefromnumber']; //Обязательный. Дом куда.
            $params['route_undefined'] = 1; //По городу: True, False
        };
        $params['custom_extra_charges'] = '20'; //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/

        $json_arr = WebOrderController::tariffs();
        /**
         * Проверка адреса назначения
         */

        if ($response_arr_to['geo_streets']['geo_street'] == null) {
            return redirect()->route('homeMapCombo')->with('error', "Помилка створення маршруту:
                Перевірьте адресу призначення або не вибрана опція поїздки по місту.
                Правильно вводьте або зверніться до оператора.");
        }

        /**
         * проверка на робота
         */

        if (!empty($_GET['g-recaptcha-response'])) {
            $curl = curl_init('https://www.google.com/recaptcha/api/siteverify');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, 'secret=' . $secret . '&response=' . $_GET['g-recaptcha-response']);
            $out = curl_exec($curl);
            curl_close($curl);


            $out = json_decode($out);
            if ($out->success == true) {
                $error = false;
                $username = config('app.username');
                $password = hash('SHA512', config('app.password'));
                $authorization = 'Basic ' . base64_encode($username . ':' . $password);


                $user_full_name = $req->user_full_name;
                $user_phone = $req->user_phone;

                $from = $params['routefrom'];
                $from_number = $params['routefromnumber'];

                if (Combo::where('name', $from)->first()->street == 0) {
                    $from_number_info = '';
                } else {
                    $from_number_info = "(будинок №$from_number)";
                };

                $auto_type = 'Тип авто: ';
                if ($req->wagon == 'on' || $req->wagon == '1') {
                    $wagon = true;
                    $wagon_type = " Універсал ";
                    $auto_type = $auto_type . $wagon_type . " ";
                } else {
                    $wagon = false;
                };
                if ($req->minibus == 'on' || $req->minibus == '1') {
                    $minibus = true;
                    $minibus_type = " Мікроавтобус ";
                    $auto_type = $auto_type . $minibus_type . " ";
                } else {
                    $minibus = false;
                };
                if ($req->premium == 'on' || $req->premium == '1') {
                    $premium = true;
                    $premium_type = " Машина преміум-класса. ";
                    $auto_type = $auto_type . $premium_type;
                } else {
                    $premium = false;
                };
                if ($auto_type == 'Тип авто: ') {
                    $auto_type = 'Тип авто: звичайне. ';
                };
                $flexible_tariff_name = $req->flexible_tariff_name;
                if ($flexible_tariff_name) {
                    $auto_type = $auto_type . "Тариф: $flexible_tariff_name";
                };
                $comment = $req->comment;
                $add_cost = $req->add_cost;
                $taxiColumnId = config('app.taxiColumnId');

                $route_undefined = false;
                $to = $params['routeto'];

                $to_number = $params['routetonumber'];
                if ($params['route_undefined'] == 1) {
                    $route_undefined = true;
                    $to = $from;
                    $to_number = $from_number;
                };

                if (Combo::where('name', $to)->first()->street == 0) {
                    $to_number_info = '';
                } else {
                    $to_number_info = "(будинок №$to_number)";
                };


                $connectAPI = WebOrderController::connectApi();
                if ($connectAPI == 400) {
                    return redirect()->route('home-news')
                        ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
                }
                $url = $connectAPI . '/api/weborders/cost';
                $response = Http::withHeaders([
                    'Authorization' => $authorization,
                ])->post($url, [
                    'user_full_name' => null, //Полное имя пользователя
                    'user_phone' => null, //Телефон пользователя
                    'client_sub_card' => null,
                    'required_time' => $required_time, //Время подачи предварительного заказа
                    'reservation' => $reservation, //Обязательный. Признак предварительного заказа: True, False
                    'route_address_entrance_from' => null,
                    'comment' => $comment, //Комментарий к заказу
                    'add_cost' => $add_cost,
                    'wagon' => $wagon, //Универсал: True, False
                    'minibus' => $minibus, //Микроавтобус: True, False
                    'premium' => $premium, //Машина премиум-класса: True, False
                    'flexible_tariff_name' => $flexible_tariff_name, //Гибкий тариф
                    'route_undefined' => $route_undefined, //По городу: True, False
                    'route' => [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                        ['name' => $from, 'number' => $from_number],
                        ['name' => $to, 'number' => $to_number],
                    ],
                    'taxiColumnId' => $taxiColumnId, //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
                    'payment_type' => 0, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
                    /*  'extra_charge_codes' => 'ENGLISH', //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
                        'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
                ]);

                if ($response->status() == "200") {
                    /**
                     * Сохранние расчетов в базе
                     */
                    $order = new Order();
                    $order->IP_ADDR = getenv("REMOTE_ADDR") ;;//IP пользователя
                    $order->user_full_name = $user_full_name;//Полное имя пользователя
                    $order->user_phone = $user_phone;//Телефон пользователя
                    $order->client_sub_card = null;
                    $order->required_time = $required_time; //Время подачи предварительного заказа
                    $order->reservation = $reservation; //Обязательный. Признак предварительного заказа: True, False
                    $order->route_address_entrance_from = null;
                    $order->comment = $comment;  //Комментарий к заказу
                    $order->add_cost = $add_cost; //Добавленная стоимость
                    $order->wagon = $wagon; //Универсал: True, False
                    $order->minibus = $minibus; //Микроавтобус: True, False
                    $order->premium = $premium; //Машина премиум-класса: True, False
                    $order->flexible_tariff_name = $flexible_tariff_name; //Гибкий тариф
                    $order->route_undefined = $route_undefined; //По городу: True, False
                    $order->routefrom = $from; //Обязательный. Улица откуда.
                    $order->routefromnumber = $from_number; //Обязательный. Дом откуда.
                    $order->routeto = $to; //Обязательный. Улица куда.
                    $order->routetonumber = $to_number; //Обязательный. Дом куда.
                    $order->taxiColumnId = $taxiColumnId; //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
                    $order->payment_type = 0; //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
                    $order->save();
                    $id = $order;
                    $json_arr = json_decode($response, true);

                    $order_cost  = $json_arr['order_cost'];

                    if ($route_undefined === true) {
                        $order = "Вітаємо $user_full_name. Ви зробили розрахунок за маршрутом від
                        $from $from_number_info по місту. Оплата: $payment_type_info. $auto_type";
                    } else {
                        $order = "Вітаємо $user_full_name. Ви зробили розрахунок за маршрутом від
                        $from $from_number_info до $to $to_number_info. Оплата: $payment_type_info. $auto_type";
                    };


                    return redirect()->route('home-id', ['id' => $id])
                        ->with('success', $order)
                        ->with('order_cost', $order_cost);

                } else {
                    $params['routefromnumberBlockNone'] = 'block';
                    $params['routetonumberBlockNone'] = 'block';
                    $info = "Помилка створення маршруту: Змініть час замовлення та/або адресу
                            відправлення/призначення або не вибрана опція поїздки по місту.
                            Правильно вводьте або зверніться до оператора.";
                    $json_arr = WebOrderController::tariffs();
                    return view('taxi.homeCombo', ['json_arr' => $json_arr, 'params' => $params,
                        'info' => $info]);
                }
            }
        }
        if ($error) {
            $params['routefromnumberBlockNone'] = 'block';
            $params['routetonumberBlockNone'] = 'block';
            $json_arr = WebOrderController::tariffs();
            return view('taxi.homeCombo', ['json_arr' => $json_arr, 'params' => $params,
                'info' => 'Не пройдено перевірку на робота.']);
        }
    }

    /**
     * Работа с заказами
     * Расчет стоимости заказа трансферов на вокзалы и в аэропорты
     * @return string
     */
    public function costtransfer($page, Request $req)
    {
        $params = WebOrderController::adressValidateTransfer($req);
     // dd($params);
        $error = true;
        $secret = config('app.RECAPTCHA_SECRET_KEY');
        /**
         * Параметры запроса
         */
        $params['user_full_name'] = "Новий замовник";
        $params['user_phone'] = '000' ;

        $params['client_sub_card'] = null;
        $params['route_address_entrance_from'] = null;

        $params['required_time'] = $req->required_time; //Время подачи предварительного заказа
        $params['reservation'] = false; //Обязательный. Признак предварительного заказа: True, False

        $reservation = $params['reservation'];
        $required_time = $params['required_time'];

        if ($req->wagon == 'on' || $req->wagon == 1) {
            $params['wagon'] = 1; //Универсал: True, False
        } else {
            $params['wagon'] = 0;
        };
        if ($req->minibus == 'on' || $req->minibus == 1) {
            $params['minibus'] = 1; //Микроавтобус: True, False
        } else {
            $params['minibus'] = 0;
        };
        if ($req->premium == 'on' || $req->premium == 1) {
            $params['premium'] = 1; //Машина премиум-класса: True, False
        } else {
            $params['premium'] = 0;
        };

        $params['flexible_tariff_name'] = $req->flexible_tariff_name; //Гибкий тариф
        $params['comment'] = $req->comment; //Комментарий к заказу
        $params['add_cost'] = $req->add_cost; //Добавленная стоимость
        $params['taxiColumnId'] = config('app.taxiColumnId'); //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2



        $params['routeto'] = $req->search1; //Обязательный. Улица куда.
        $params['routetonumber'] = $req->to_number; //Обязательный. Дом куда.
        $params['route_undefined'] = false; //По городу: True, False

        $params['custom_extra_charges'] = '20'; //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/

        if (!empty($_GET['g-recaptcha-response'])) { //проверка на робота
            $json_arr = WebOrderController::tariffs();
            $curl = curl_init('https://www.google.com/recaptcha/api/siteverify');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, 'secret=' . $secret . '&response=' . $_GET['g-recaptcha-response']);
            $out = curl_exec($curl);
            curl_close($curl);


            $out = json_decode($out);
            if ($out->success == true) {
                $error = false;
                $username = config('app.username');
                $password = hash('SHA512', config('app.password'));
                $authorization = 'Basic ' . base64_encode($username . ':' . $password);


                $user_full_name = $req->user_full_name;
                $user_phone = $req->user_phone;

                $from = $req->search;
                $from_number = $req->from_number;

                if (Combo::where('name', $from)->first()->street == 0) {
                    $from_number_info = '';
                } else {
                    $from_number_info = "(будинок №$from_number)";
                };

                $auto_type = 'Тип авто: ';
                if ($req->wagon == 'on' || $req->wagon == '1') {
                    $wagon = true;
                    $wagon_type = " Універсал ";
                    $auto_type = $auto_type . $wagon_type . " ";
                } else {
                    $wagon = false;
                };
                if ($req->minibus == 'on' || $req->minibus == '1') {
                    $minibus = true;
                    $minibus_type = " Мікроавтобус ";
                    $auto_type = $auto_type . $minibus_type . " ";
                } else {
                    $minibus = false;
                };
                if ($req->premium == 'on' || $req->premium == '1') {
                    $premium = true;
                    $premium_type = " Машина преміум-класса. ";
                    $auto_type = $auto_type . $premium_type;
                } else {
                    $premium = false;
                };
                if ($auto_type == 'Тип авто: ') {
                    $auto_type = 'Тип авто: звичайне. ';
                };
                $flexible_tariff_name = $req->flexible_tariff_name;
                if ($flexible_tariff_name) {
                    $auto_type = $auto_type . "Тариф: $flexible_tariff_name";
                };
                $comment = $req->comment;
                $add_cost = $req->add_cost;
                $taxiColumnId = config('app.taxiColumnId');

                $route_undefined = false;
                $to = $req->search1;

                $to_number = '';
                $payment_type_info = 'готівка';

                $connectAPI = WebOrderController::connectApi();
                if ($connectAPI == 400) {
                    return redirect()->route('home-news')
                        ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
                }
                $url = $connectAPI . '/api/weborders/cost';
                $response = Http::withHeaders([
                    'Authorization' => $authorization,
                ])->post($url, [
                    'user_full_name' => null, //Полное имя пользователя
                    'user_phone' => null, //Телефон пользователя
                    'client_sub_card' => null,
                    'required_time' => $required_time, //Время подачи предварительного заказа
                    'reservation' => $reservation, //Обязательный. Признак предварительного заказа: True, False
                    'route_address_entrance_from' => null,
                    'comment' => $comment, //Комментарий к заказу
                    'add_cost' => $add_cost,
                    'wagon' => $wagon, //Универсал: True, False
                    'minibus' => $minibus, //Микроавтобус: True, False
                    'premium' => $premium, //Машина премиум-класса: True, False
                    'flexible_tariff_name' => $flexible_tariff_name, //Гибкий тариф
                    'route_undefined' => $route_undefined, //По городу: True, False
                    'route' => [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                        ['name' => $from, 'number' => $from_number],
                        ['name' => $to],
                    ],
                    'taxiColumnId' => $taxiColumnId, //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
                    'payment_type' => 0, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
                    /*  'extra_charge_codes' => 'ENGLISH', //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
                        'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
                ]);

                if ($response->status() == "200") {
                    /**
                     * Сохранние расчетов в базе
                     */
                    $order = new Order();
                    $order->IP_ADDR = getenv("REMOTE_ADDR") ;//IP пользователя
                    $order->user_full_name = $user_full_name;//Полное имя пользователя
                    $order->user_phone = $user_phone;//Телефон пользователя
                    $order->client_sub_card = null;
                    $order->required_time = $required_time; //Время подачи предварительного заказа
                    $order->reservation = $reservation; //Обязательный. Признак предварительного заказа: True, False
                    $order->route_address_entrance_from = null;
                    $order->comment = $comment;  //Комментарий к заказу
                    $order->add_cost = $add_cost; //Добавленная стоимость
                    $order->wagon = $wagon; //Универсал: True, False
                    $order->minibus = $minibus; //Микроавтобус: True, False
                    $order->premium = $premium; //Машина премиум-класса: True, False
                    $order->flexible_tariff_name = $flexible_tariff_name; //Гибкий тариф
                    $order->route_undefined = $route_undefined; //По городу: True, False
                    $order->routefrom = $from; //Обязательный. Улица откуда.
                    $order->routefromnumber = $from_number; //Обязательный. Дом откуда.
                    $order->routeto = $to; //Обязательный. Улица куда.
                    $order->routetonumber = $to_number; //Обязательный. Дом куда.
                    $order->taxiColumnId = $taxiColumnId; //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
                    $order->payment_type = 0; //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
                    $order->save();
                    $id = $order;
                    $json_arr = json_decode($response, true);
                    $order_cost  = $json_arr['order_cost'];

                    switch ($to) {
                        case 'Аэропорт Борисполь терминал Д':
                            $order = "Вітаємо $user_full_name. Ви зробили розрахунок за маршрутом від
                            $from $from_number_info
                             до аеропорту \"Бориспіль\". Оплата: $payment_type_info. $auto_type";
                            break;
                        case 'Аэропорт Жуляны новый (ул.Медовая 2)':
                            $order = "Вітаємо $user_full_name. Ви зробили розрахунок за маршрутом від
                            $from $from_number_info
                             до аеропорту \"Киів\" (Жуляни). Оплата: $payment_type_info. $auto_type";
                            break;
                        case 'ЖД Южный':
                            $order = "Вітаємо $user_full_name. Ви зробили розрахунок за маршрутом від
                            $from $from_number_info
                             до залізничного вокзалу \"Південний \". Оплата: $payment_type_info. $auto_type";
                            break;
                        case 'Центральный автовокзал (у шлагбаума пл.Московская 3)':
                            $order = "Вітаємо $user_full_name. Ви зробили розрахунок за маршрутом від
                            $from $from_number_info
                             до автовокзалу. Оплата: $payment_type_info. $auto_type";
                            break;
                    }

                    return redirect()->route('home-id', ['id' => $id])
                        ->with('success', $order)
                        ->with('order_cost', $order_cost);

                } else {
                    $info = "Помилка створення маршруту: Змініть час замовлення та/або адресу
                           відправлення/призначення або не вибрана опція поїздки по місту.
                           Правильно вводьте або зверніться до оператора.";
                    $alarmMessage = new TelegramController();

                    if ($route_undefined === true) {
                        $message = "Увага 🔥! Помилка розрахунку вартості за маршрутом від $from $from_number_info по місту. Оплата: $payment_type_info. $auto_type";
                    } else {
                        $message = "Увага 🔥! Помилка розрахунку вартості за маршрутом від $from $from_number_info до $to. Оплата: $payment_type_info. $auto_type";
                    };
                    $alarmMessage->sendAlarmMessage($message);
                    return view($page, ['json_arr' => $json_arr, 'params' => $params, 'info' => $info]);
                }
            }
        }
        if ($error) {
            $json_arr = WebOrderController::tariffs();
            //dd($params);
            return view($page, ['json_arr' => $json_arr, 'params' => $params,
                'info' => "Не пройдено перевірку на робота."]);
        }
    }

    /**
     * Работа с заказами
     * Расчет стоимости заказа трансферов с вокзалов и аэропортов
     * @return string
     */
    public function costtransferfrom($page, Request $req)
    {
        /**
         * Проверка адресов в базе
         */
        $req->validate([
            'search' => new ComboName(),
            'routetonumber' => ['nullable'],
        ]);
        /**
         * Если адреса есть, проверяем заполненность номера дома "откуда"
         */

        $arrCombo = Combo::where('name', $req->search)->first();
        $params['routetonumberBlockNone'] = 'none;'; //Скрываем поле дома
        $params['routetonumber'] = null; //Обязательный. Дом куда.
        if ($arrCombo->street == 1) {
            $req->validate([
                'routetonumber' => ['required']
            ]);
            $params['routetonumberBlockNone'] = 'block;'; // Открываем поле дома для улиц
            $params['routetonumber'] = $req->routetonumber; //Обязательный. Дом куда.
        }

        $params['routeto'] = $req->search; //Обязательный. Улица куда.

        $error = true;
        $secret = config('app.RECAPTCHA_SECRET_KEY');
        /**
         * Параметры запроса
         */
        $params['user_full_name'] = "Новий замовник";
        $params['user_phone'] = '000' ;

        $params['routefrom'] = $req->routefrom; //Обязательный. Улица куда.
        $params['routefromnumber'] = null; //Обязательный. Дом куда.
        $params['client_sub_card'] = null;
        $params['route_address_entrance_from'] = null;

        $params['required_time'] = $req->required_time; //Время подачи предварительного заказа
        $params['reservation'] = false; //Обязательный. Признак предварительного заказа: True, False

        $reservation = $params['reservation'];
        $required_time = $params['required_time'];

        if ($req->wagon == 'on' || $req->wagon == 1) {
            $params['wagon'] = 1; //Универсал: True, False
        } else {
            $params['wagon'] = 0;
        };
        if ($req->minibus == 'on' || $req->minibus == 1) {
            $params['minibus'] = 1; //Микроавтобус: True, False
        } else {
            $params['minibus'] = 0;
        };
        if ($req->premium == 'on' || $req->premium == 1) {
            $params['premium'] = 1; //Машина премиум-класса: True, False
        } else {
            $params['premium'] = 0;
        };

        $params['flexible_tariff_name'] = $req->flexible_tariff_name; //Гибкий тариф
        $params['comment'] = $req->comment; //Комментарий к заказу
        $params['add_cost'] = $req->add_cost; //Добавленная стоимость
        $params['taxiColumnId'] = config('app.taxiColumnId'); //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
        $params['route_undefined'] = false; //По городу: True, False

        $params['custom_extra_charges'] = '20'; //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/



        if (!empty($_GET['g-recaptcha-response'])) { //проверка на робота
            $json_arr = WebOrderController::tariffs();
            $curl = curl_init('https://www.google.com/recaptcha/api/siteverify');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, 'secret=' . $secret . '&response=' . $_GET['g-recaptcha-response']);
            $out = curl_exec($curl);
            curl_close($curl);


            $out = json_decode($out);
            if ($out->success == true) {
                $error = false;
                $username = config('app.username');
                $password = hash('SHA512', config('app.password'));
                $authorization = 'Basic ' . base64_encode($username . ':' . $password);


                $user_full_name = $req->user_full_name;
                $user_phone = $req->user_phone;

                $from = $req->routefrom;
                $from_number = $req->from_number;

                $auto_type = 'Тип авто: ';
                if ($req->wagon == 'on' || $req->wagon == '1') {
                    $wagon = true;
                    $wagon_type = " Універсал ";
                    $auto_type = $auto_type . $wagon_type . " ";
                } else {
                    $wagon = false;
                };
                if ($req->minibus == 'on' || $req->minibus == '1') {
                    $minibus = true;
                    $minibus_type = " Мікроавтобус ";
                    $auto_type = $auto_type . $minibus_type . " ";
                } else {
                    $minibus = false;
                };
                if ($req->premium == 'on' || $req->premium == '1') {
                    $premium = true;
                    $premium_type = " Машина преміум-класса. ";
                    $auto_type = $auto_type . $premium_type;
                } else {
                    $premium = false;
                };
                if ($auto_type == 'Тип авто: ') {
                    $auto_type = 'Тип авто: звичайне. ';
                };
                $flexible_tariff_name = $req->flexible_tariff_name;
                if ($flexible_tariff_name) {
                    $auto_type = $auto_type . "Тариф: $flexible_tariff_name";
                };
                $comment = $req->comment;
                $add_cost = $req->add_cost;
                $taxiColumnId = config('app.taxiColumnId');


                $route_undefined = false;
                $to = $req->search;

                $to_number = $req->routetonumber;

                if (Combo::where('name', $to)->first()->street == 0) {
                    $to_number_info = '';
                } else {
                    $to_number_info = "(будинок №$to_number)";
                };
                $payment_type_info = "готівка";

                $connectAPI = WebOrderController::connectApi();
                if ($connectAPI == 400) {
                    return redirect()->route('home-news')
                        ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
                }
                $url = $connectAPI . '/api/weborders/cost';
                $response = Http::withHeaders([
                    'Authorization' => $authorization,
                ])->post($url, [
                    'user_full_name' => null, //Полное имя пользователя
                    'user_phone' => null, //Телефон пользователя
                    'client_sub_card' => null,
                    'required_time' => $required_time, //Время подачи предварительного заказа
                    'reservation' => $reservation, //Обязательный. Признак предварительного заказа: True, False
                    'route_address_entrance_from' => null,
                    'comment' => $comment, //Комментарий к заказу
                    'add_cost' => $add_cost,
                    'wagon' => $wagon, //Универсал: True, False
                    'minibus' => $minibus, //Микроавтобус: True, False
                    'premium' => $premium, //Машина премиум-класса: True, False
                    'flexible_tariff_name' => $flexible_tariff_name, //Гибкий тариф
                    'route_undefined' => $route_undefined, //По городу: True, False
                    'route' => [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                        ['name' => $from],
                        ['name' => $to, 'number' => $to_number],
                    ],
                    'taxiColumnId' => $taxiColumnId, //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
                    'payment_type' => 0, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
                    /*  'extra_charge_codes' => 'ENGLISH', //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
                        'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
                ]);
                if ($response->status() == "200") {
                    /**
                     * Сохранние расчетов в базе
                     */
                    $order = new Order();
                    $order->IP_ADDR = getenv("REMOTE_ADDR") ;//IP пользователя
                    $order->user_full_name = $user_full_name;//Полное имя пользователя
                    $order->user_phone = $user_phone;//Телефон пользователя
                    $order->client_sub_card = null;
                    $order->required_time = $required_time; //Время подачи предварительного заказа
                    $order->reservation = $reservation; //Обязательный. Признак предварительного заказа: True, False
                    $order->route_address_entrance_from = null;
                    $order->comment = $comment;  //Комментарий к заказу
                    $order->add_cost = $add_cost; //Добавленная стоимость
                    $order->wagon = $wagon; //Универсал: True, False
                    $order->minibus = $minibus; //Микроавтобус: True, False
                    $order->premium = $premium; //Машина премиум-класса: True, False
                    $order->flexible_tariff_name = $flexible_tariff_name; //Гибкий тариф
                    $order->route_undefined = $route_undefined; //По городу: True, False
                    $order->routefrom = $from; //Обязательный. Улица откуда.
                    $order->routefromnumber = $from_number; //Обязательный. Дом откуда.
                    $order->routeto = $to; //Обязательный. Улица куда.
                    $order->routetonumber = $to_number; //Обязательный. Дом куда.
                    $order->taxiColumnId = $taxiColumnId; //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
                    $order->payment_type = 0; //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
                    $order->save();
                    $id = $order;
                    $json_arr = json_decode($response, true);
                    $order_cost  = $json_arr['order_cost'];

                    switch ($from) {
                        case 'Аэропорт Борисполь терминал Д':
                            $order = "Вітаємо $user_full_name. Ви зробили розрахунок за маршрутом від аеропорту \"Бориспіль\"
                            до $to $to_number_info. Оплата: $payment_type_info. $auto_type";
                            break;
                        case 'Аэропорт Жуляны новый (ул.Медовая 2)':
                            $order = "Вітаємо $user_full_name. Ви зробили розрахунок за маршрутом від \"Киів\" (Жуляни)
                            до $to $to_number_info. Оплата: $payment_type_info. $auto_type";
                            break;
                        case 'ЖД Южный':
                            $order = "Вітаємо $user_full_name. Ви зробили розрахунок за маршрутом від залізничного вокзалу \"Південний \"
                            до $to $to_number_info. Оплата: $payment_type_info. $auto_type";
                            break;
                        case 'Центральный автовокзал (у шлагбаума пл.Московская 3)':
                            $order = "Вітаємо $user_full_name. Ви зробили розрахунок за маршрутом від автовокзалу
                            до $to $to_number_info. Оплата: $payment_type_info. $auto_type";
                            break;
                    }

                    return redirect()->route('home-id', ['id' => $id])
                        ->with('success', $order)
                        ->with('order_cost', $order_cost);

                } else {
                    $info = "Помилка створення маршруту: Змініть час замовлення та/або адресу
                           відправлення/призначення або не вибрана опція поїздки по місту.
                           Правильно вводьте або зверніться до оператора.";
                    $alarmMessage = new TelegramController();

                    if ($route_undefined === true) {
                        $message = "Увага 🔥! Помилка розрахунку вартості за маршрутом від $from по місту. Оплата: $payment_type_info. $auto_type";
                    } else {
                        $message = "Увага 🔥! Помилка розрахунку вартості за маршрутом від $from  до $to $to_number_info. Оплата: $payment_type_info. $auto_type";
                    };
                    $alarmMessage->sendAlarmMessage($message);
                    return view($page, ['json_arr' => $json_arr, 'params' => $params, 'info' => $info]);
                }
            }
        }
        if ($error) {
            $json_arr = WebOrderController::tariffs();
            return view($page, ['json_arr' => $json_arr, 'params' => $params,
                'info' => 'Не пройдено перевірку на робота.']);
        }
    }


    /**
     * Работа с заказами
     * Редактирование и расчет стоимости заказа
     * @return string
     */
    public function costEdit($id, Request $req)
    {
        $username = config('app.username');
        $password = hash('SHA512', config('app.password'));
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);


        $user_full_name = $req->user_full_name;
        $user_phone = $req->user_phone;

        $from = $req->search;
        $from_number = $req->from_number;

        if (Combo::where('name', $from)->first()->street == 0) {
            $from_number_info = '';
        } else {
            $from_number_info = "(будинок №$from_number)";
        };

        $required_time = $req->required_time;
        $reservation = false;

        $auto_type = 'Тип авто: ';
        if ($req->wagon == 'on' || $req->wagon == '1') {
            $wagon = true;
            $wagon_type = " Універсал";
            $auto_type = $auto_type . $wagon_type . " ";
        } else {
            $wagon = false;
        };
        if ($req->minibus == 'on' || $req->minibus == '1') {
            $minibus = true;
            $minibus_type = " Мікроавтобус";
            $auto_type = $auto_type . $minibus_type . " ";
        } else {
            $minibus = false;
        };
        if ($req->premium == 'on' || $req->premium == '1') {
            $premium = true;
            $premium_type = " Машина преміум-класса. ";
            $auto_type = $auto_type . $premium_type;
        } else {
            $premium = false;
        };
        if ($auto_type == 'Тип авто: ') {
            $auto_type = 'Тип авто: звичайне. ';
        };
        $flexible_tariff_name = $req->flexible_tariff_name;
        if ($flexible_tariff_name) {
            $auto_type = $auto_type . "Тариф: $flexible_tariff_name";
        };
        $comment = $req->comment;
        $add_cost = $req->add_cost;
        $taxiColumnId = config('app.taxiColumnId');

        $route_undefined = false;
        $to = $req->search1;
        $to_number = $req->to_number;

        if ($req->route_undefined == 1) {
            $route_undefined = true;
            $to = $from;
            $to_number = $from_number;
        };

        if (Combo::where('name', $to)->first()->street == 0) {
            $to_number_info = '';
        } else {
            $to_number_info = "(будинок №$to_number)";
        };

        $payment_type_info = "готівка";

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/weborders/cost';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->post($url, [
            'user_full_name' => $user_full_name, //Полное имя пользователя
            'user_phone' => null, //Телефон пользователя
            'client_sub_card' => null,
            'required_time' => $required_time, //Время подачи предварительного заказа
            'reservation' => $reservation, //Обязательный. Признак предварительного заказа: True, False
            'route_address_entrance_from' => null,
            'comment' => $comment, //Комментарий к заказу
            'add_cost' => $add_cost,
            'wagon' => $wagon, //Универсал: True, False
            'minibus' => $minibus, //Микроавтобус: True, False
            'premium' => $premium, //Машина премиум-класса: True, False
            'flexible_tariff_name' => $flexible_tariff_name, //Гибкий тариф
            'route_undefined' => $route_undefined, //По городу: True, False
            'route' => [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                ['name' => $from, 'number' => $from_number],
                ['name' => $to, 'number' => $to_number],
            ],
            'taxiColumnId' => $taxiColumnId, //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
            'payment_type' => 0, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
        ]);

        if ($response->status() == "200") {
            $order = Order::where ('id', $id)->first();
            $order->user_full_name = $user_full_name;//Полное имя пользователя
            $order->user_phone = $user_phone;//Телефон пользователя
            $order->client_sub_card = null;
            $order->required_time = $required_time; //Время подачи предварительного заказа
            $order->reservation = $reservation; //Обязательный. Признак предварительного заказа: True, False
            $order->route_address_entrance_from = null;
            $order->comment = $comment;  //Комментарий к заказу
            $order->add_cost = $add_cost; //Добавленная стоимость
            $order->wagon = $wagon; //Универсал: True, False
            $order->minibus = $minibus; //Микроавтобус: True, False
            $order->premium = $premium; //Машина премиум-класса: True, False
            $order->flexible_tariff_name = $flexible_tariff_name; //Гибкий тариф
            $order->route_undefined = $route_undefined; //По городу: True, False
            $order->routefrom = $from; //Обязательный. Улица откуда.
            $order->routefromnumber = $from_number; //Обязательный. Дом откуда.
            $order->routeto = $to; //Обязательный. Улица куда.
            $order->routetonumber = $to_number; //Обязательный. Дом куда.
            $order->taxiColumnId = $taxiColumnId; //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
            $order->payment_type = 0; //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
            $order->save();

            $json_arr = json_decode($response, true);
            if ($route_undefined === true) {
                $order = "Вітаємо $user_full_name. Ви зробили розрахунок за маршрутом від $from $from_number_info
                            по місту. Оплата $payment_type_info. $auto_type";
            } else {
                $order = "Вітаємо $user_full_name. Ви зробили розрахунок за маршрутом
                            від $from $from_number_info до $to $to_number_info.
                             Оплата $payment_type_info. $auto_type";
                switch ($to) {
                    case 'Аэропорт Борисполь терминал Д':
                        $order = "Вітаємо $user_full_name. Ви зробили розрахунок за маршрутом від $from $from_number_info
                             до аеропорту \"Бориспіль\". Оплата $payment_type_info. $auto_type";
                        break;
                    case 'Аэропорт Жуляны новый (ул.Медовая 2)':
                        $order = "Вітаємо $user_full_name. Ви зробили розрахунок за маршрутом від $from $from_number_info
                             до аеропорту \"Киів\" (Жуляни). Оплата $payment_type_info. $auto_type";
                        break;
                    case 'ЖД Южный':
                        $order = "Вітаємо $user_full_name. Ви зробили розрахунок за маршрутом від $from $from_number_info
                             до залізничного вокзалу \"Південний \". Оплата $payment_type_info. $auto_type";
                        break;
                    case 'Центральный автовокзал (у шлагбаума пл.Московская 3)':
                        $order = "Вітаємо $user_full_name. Ви зробили розрахунок за маршрутом від $from $from_number_info
                             до автовокзалу. Оплата $payment_type_info. $auto_type";
                        break;
                }

                switch ($from) {
                    case 'Аэропорт Борисполь терминал Д':
                        $order = "Вітаємо $user_full_name. Ви зробили розрахунок за маршрутом від аеропорту \"Бориспіль\"
                            до $to $to_number_info. Оплата $payment_type_info. $auto_type";
                        break;
                    case 'Аэропорт Жуляны новый (ул.Медовая 2)':
                        $order = "Вітаємо $user_full_name. Ви зробили розрахунок за маршрутом від \"Киів\" (Жуляни)
                            до $to $to_number_info. Оплата $payment_type_info. $auto_type";
                        break;
                    case 'ЖД Южный':
                        $order = "Вітаємо $user_full_name. Ви зробили розрахунок за маршрутом від залізничного вокзалу \"Південний \"
                            до $to $to_number_info. Оплата $payment_type_info. $auto_type";
                        break;
                    case 'Центральный автовокзал (у шлагбаума пл.Московская 3)':
                        $order = "Вітаємо $user_full_name. Ви зробили розрахунок за маршрутом від автовокзалу
                            до $to $to_number_info. Оплата $payment_type_info. $auto_type";
                        break;
                }
            };
            $cost = "Вартість поїздки становитиме: " . $json_arr['order_cost'] . 'грн. Для замовлення натисніть тут.';
            return redirect()->route('home-id-afterorder', ['id' => $id])->with('success', $order)->with('cost', $cost);

        } else {
            return  view('taxi.feedback', ['info' => 'Помилка створення маршруту.']);
        }
    }

    /**
     * Работа с заказами
     * Редактирование и расчет стоимости заказа по объектам
     * @return string
     */
    public function costobjectEdit($id, Request $req)
    {
        $username = config('app.username');
        $password = hash('SHA512', config('app.password'));
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);


        $user_full_name = $req->user_full_name;
        $user_phone = $req->user_phone;

        $from = $req->search2;
        $required_time = $req->required_time; //Время подачи предварительного заказа
        $reservation = false;

        $auto_type = 'Тип авто: ';
        if ($req->wagon == 'on' || $req->wagon == '1') {
            $wagon = true;
            $wagon_type = " Універсал";
            $auto_type = $auto_type . $wagon_type . " ";
        } else {
            $wagon = false;
        };
        if ($req->minibus == 'on' || $req->minibus == '1') {
            $minibus = true;
            $minibus_type = " Мікроавтобус";
            $auto_type = $auto_type . $minibus_type . " ";
        } else {
            $minibus = false;
        };
        if ($req->premium == 'on' || $req->premium == '1') {
            $premium = true;
            $premium_type = " Машина преміум-класса. ";
            $auto_type = $auto_type . $premium_type;
        } else {
            $premium = false;
        };
        if ($auto_type == 'Тип авто: ') {
            $auto_type = 'Тип авто: звичайне. ';
        };
        $flexible_tariff_name = $req->flexible_tariff_name;
        if ($flexible_tariff_name) {
            $auto_type = $auto_type . "Тариф: $flexible_tariff_name";
        };

        $payment_type_info = "готівка";

        $comment = $req->comment;
        $add_cost = $req->add_cost;
        $taxiColumnId = config('app.taxiColumnId');

        $route_undefined = false;
        $to = $req->search3;


        if ($req->route_undefined == 1) {
            $route_undefined = true;
            $to = $req->search2;
        };
        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/weborders/cost';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->post($url, [
            'user_full_name' => $user_full_name, //Полное имя пользователя
            'user_phone' => null, //Телефон пользователя
            'client_sub_card' => null,
            'required_time' => $required_time, //Время подачи предварительного заказа
            'reservation' => $reservation, //Обязательный. Признак предварительного заказа: True, False
            'route_address_entrance_from' => null,
            'comment' => $comment, //Комментарий к заказу
            'add_cost' => $add_cost,
            'wagon' => $wagon, //Универсал: True, False
            'minibus' => $minibus, //Микроавтобус: True, False
            'premium' => $premium, //Машина премиум-класса: True, False
            'flexible_tariff_name' => $flexible_tariff_name, //Гибкий тариф
            'route_undefined' => $route_undefined, //По городу: True, False
            'route' => [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                ['name' => $from],
                ['name' => $to],
            ],
            'taxiColumnId' => $taxiColumnId, //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
            'payment_type' => 0, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
        ]);

        if ($response->status() == "200") {

            $order = Order::where ('id', $id)->first();
            $order->user_full_name = $user_full_name;//Полное имя пользователя
            $order->user_phone = $user_phone;//Телефон пользователя
            $order->client_sub_card = null;
            $order->required_time = $required_time; //Время подачи предварительного заказа
            $order->reservation = $reservation; //Обязательный. Признак предварительного заказа: True, False
            $order->route_address_entrance_from = null;
            $order->comment = $comment;  //Комментарий к заказу
            $order->add_cost = $add_cost; //Добавленная стоимость
            $order->wagon = $wagon; //Универсал: True, False
            $order->minibus = $minibus; //Микроавтобус: True, False
            $order->premium = $premium; //Машина премиум-класса: True, False
            $order->flexible_tariff_name = $flexible_tariff_name; //Гибкий тариф
            $order->route_undefined = $route_undefined; //По городу: True, False
            $order->routefrom = $from; //Обязательный. Улица откуда.

            $order->routeto = $to; //Обязательный. Улица куда.

            $order->taxiColumnId = $taxiColumnId; //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
            $order->payment_type = 0; //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
            $order->save();

            $json_arr = json_decode($response, true);
            if ($route_undefined === true) {
                $order = "Вітаємо $user_full_name. Ви зробили розрахунок за маршрутом від $from по місту. Оплата: $payment_type_info. $auto_type";
            } else {
                $order = "Вітаємо $user_full_name. Ви зробили розрахунок за маршрутом від $from  до $to.
                Оплата: $payment_type_info. $auto_type";
            };

            $cost = "Вартість поїздки становитиме: " . $json_arr['order_cost'] . 'грн. Для замовлення натисніть тут.';
            return redirect()->route('home-id-afterorder', ['id' => $id])->with('success', $order)->with('cost', $cost);

        } else {
            return redirect()->route('home-id-object', ['id' => $id])->with('error', "Помилка створення маршруту.");
        }
    }

    /**
     * Работа с заказами
     * Редактирование и расчет стоимости заказа из Истории
     * @return string
     */
    public function costHistory($id)
    {
        $req = Order::where('id', $id)->first();

        if (Combo::where('name', $req->routefrom)->first()->street == 1) {
            $req['routefromnumberBlockNone'] = 'block';
        } else {
            $req['routefromnumberBlockNone'] = 'none';
        }

        if (Combo::where('name', $req->routeto)->first()->street == 1) {
            $req['routetonumberBlockNone'] = 'block';
        } else {
            $req['routetonumberBlockNone'] = 'none';
        }
        $json_arr = WebOrderController::tariffs();
            return view('taxi.homeCombo', ['json_arr' => $json_arr, 'params' => $req]);
    }


    /**
     * Работа с заказами
     * Редактирование и расчет стоимости заказа из Дома
     * @return string
     */
    public function costHome($route_address_from, $route_address_number_from, $authorization)
    {
        $json_arr = WebOrderController::tariffs();

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/clients/profile';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url);

        $response_arr = json_decode($response, true);

        $params['user_phone'] = substr($response["user_phone"], 3);
        $params['user_full_name'] = $response_arr ['user_first_name'];
        $params['routefrom'] = $route_address_from;
        $params['routefromnumber'] = $route_address_number_from;
        $params['routefromnumberBlockNone'] = 'block';
        $params['route_undefined'] = 0;
        $params['routeto'] = null;
        $params['routetonumber'] = null;
        $params['routetonumberBlockNone'] = 'block';
        $params['required_time'] = null;
        $params['wagon'] = 0;
        $params['minibus'] = 0;
        $params['premium'] = 0;
        $params['flexible_tariff_name'] = null;
        $params['payment_type'] = 0;

        return view('taxi.homeCombo', ['json_arr' => $json_arr, 'params' => $params]);

    }

    /**
     * Работа с заказами
     * Редактирование и расчет стоимости заказа до вокзала и аэропортов
     * @return string
     */
    public function transfer($routeto, $page)
    {
        $json_arr = WebOrderController::tariffs();

        $params['user_phone'] = '000';
        $params['user_full_name'] = 'Новий замовник';
        $params['routefrom'] = null;
        $params['routefromnumber'] =  null;
        $params['route_undefined'] = 0;
        $params['routeto'] = $routeto;
        $params['routetonumber'] = null;
        $params['required_time'] = null;
        $params['wagon'] = 0;
        $params['minibus'] = 0;
        $params['premium'] = 0;
        $params['flexible_tariff_name'] = null;
        $params['payment_type'] = 0;

        return view($page, ['json_arr' => $json_arr, 'params' => $params]);
    }

    /**
     * Работа с заказами
     * Редактирование и расчет стоимости заказа до вокзала и аэропортов из кабинета пользователя
     * @return string
     */
    public function transferProfile($routeto, $page, $user_phone, $user_first_name, $route_address_from, $route_address_number_from)
    {
        $json_arr = WebOrderController::tariffs();

        $params['user_phone'] = $user_phone;
        $params['user_full_name'] = $user_first_name;
        $params['routefrom'] = $route_address_from;
        $params['routefromnumber'] = $route_address_number_from;
        $params['route_undefined'] = 0;
        $params['routeto'] = $routeto;
        $params['routetonumber'] = null;
        $params['required_time'] = null;
        $params['wagon'] = 0;
        $params['minibus'] = 0;
        $params['premium'] = 0;
        $params['flexible_tariff_name'] = null;
        $params['payment_type'] = 0;

        return view($page, ['json_arr' => $json_arr, 'params' => $params]);
    }

    /**
     * Работа с заказами
     * Редактирование и расчет стоимости заказа c вокзала и c аэропортов
     * @return string
     */
    public function transferFrom($routefrom, $page)
    {
        $json_arr = WebOrderController::tariffs();

        $params['user_phone'] = '000';
        $params['user_full_name'] = 'Новий замовник';
        $params['routefrom'] = $routefrom;
        $params['routefromnumber'] =  null;
        $params['route_undefined'] = 0;
        $params['routeto'] = null;
        $params['routetonumber'] = null;
        $params['required_time'] = null;
        $params['wagon'] = 0;
        $params['minibus'] = 0;
        $params['premium'] = 0;
        $params['flexible_tariff_name'] = null;
        $params['payment_type'] = 0;

        return view($page, ['json_arr' => $json_arr, 'params' => $params]);
    }

    /**
     * Работа с заказами
     * Создание заказа
     * @return string
     */
    public function costWebOrder($id)
    {
        $req = Order::where('id', $id)->first();
        $user_full_name = $req->user_full_name;
        $user_phone = $req->user_phone;

        $username = config('app.username');
        $password = hash('SHA512', config('app.password'));
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $from = $req->routefrom;
        $from_number = $req->routefromnumber;
        $required_time = $req->required_time; //Время подачи предварительного заказа
        $reservation = false; //Обязательный. Признак предварительного заказа: True, False

        $auto_type = 'Тип авто: ';

        if ($req->wagon == 1) {
            $wagon = true;
            $wagon_type = " Універсал";
            $auto_type = $auto_type . $wagon_type . " ";
        } else {
            $wagon = false;
        };
        if ($req->minibus == 1) {
            $minibus = true;
            $minibus_type = " Мікроавтобус";
            $auto_type = $auto_type . $minibus_type . " ";
        } else {
            $minibus = false;
        };
        if ($req->premium == 1) {
            $premium = true;
            $premium_type = " Машина преміум-класса";
            $auto_type = $auto_type . $premium_type;
        } else {
            $premium = false;
        };
        if ($auto_type == 'Тип авто: ') {
            $auto_type = 'Тип авто: звичайне';
        };

        $flexible_tariff_name = $req->flexible_tariff_name;
        if ($flexible_tariff_name) {
            $auto_type = $auto_type . "Тариф: $flexible_tariff_name";
        };
        $comment = $req->comment .  " через смс";
        $add_cost = $req->add_cost;
        $taxiColumnId = config('app.taxiColumnId');

        $payment_type_info = "готівка";

        $route_undefined = false;
        $to = $req->routeto;
        $to_number = $req->routetonumber;

        if ($req->route_undefined == "1") {
            $route_undefined = true;
            $to = $req->routefrom;
            $to_number = $req->routefromnumber;
        };

        /**
         * Запрос стоимости
         */

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/weborders/cost';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->post($url, [
            'user_full_name' => $user_full_name, //Полное имя пользователя
            'user_phone' => null, //Телефон пользователя
            'client_sub_card' => null,
            'required_time' => $required_time, //Время подачи предварительного заказа
            'reservation' => $reservation, //Обязательный. Признак предварительного заказа: True, False
            'route_address_entrance_from' => null,
            'comment' => $comment, //Комментарий к заказу
            'add_cost' => $add_cost,
            'wagon' => $wagon, //Универсал: True, False
            'minibus' => $minibus, //Микроавтобус: True, False
            'premium' => $premium, //Машина премиум-класса: True, False
            'flexible_tariff_name' => $flexible_tariff_name, //Гибкий тариф
            'route_undefined' => $route_undefined, //По городу: True, False
            'route' => [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                ['name' => $from, 'number' => $from_number],
                ['name' => $to, 'number' => $to_number],
            ],
            'taxiColumnId' => $taxiColumnId, //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
            'payment_type' => 0, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
            /*  'extra_charge_codes' => 'ENGLISH', //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
                'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
        ]);

        /**
         * Заказ поездки
         */

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/weborders';
        $responseWeb = Http::withHeaders([
            'Authorization' => $authorization,
        ])->post($url, [
            'user_full_name' => $user_full_name, //Полное имя пользователя
            'user_phone' => $user_phone, //Телефон пользователя
            'client_sub_card' => null,
            'required_time' => $required_time, //Время подачи предварительного заказа
            'reservation' => $reservation, //Обязательный. Признак предварительного заказа: True, False
            'route_address_entrance_from' => null,
            'comment' => $comment, //Комментарий к заказу
            'add_cost' => $add_cost,
            'wagon' => $wagon, //Универсал: True, False
            'minibus' => $minibus, //Микроавтобус: True, False
            'premium' => $premium, //Машина премиум-класса: True, False
            'flexible_tariff_name' => $flexible_tariff_name, //Гибкий тариф
            'route_undefined' => $route_undefined, //По городу: True, False
            'route' => [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                ['name' => $from, 'number' => $from_number],
                ['name' => $to, 'number' => $to_number],
            ],
            'taxiColumnId' => $taxiColumnId, //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
            'payment_type' => 0, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
        ]);

        if ($responseWeb->status()  == "200") {
            /**
             * Сохранние расчетов в базе
             */
            $orderweb = new Orderweb();
            $orderweb->user_full_name = $user_full_name;//Полное имя пользователя
            $orderweb->user_phone = $user_phone;//Телефон пользователя
            $orderweb->client_sub_card = null;
            $orderweb->required_time = $required_time; //Время подачи предварительного заказа
            $orderweb->reservation = $reservation; //Обязательный. Признак предварительного заказа: True, False
            $orderweb->route_address_entrance_from = null;
            $orderweb->comment = $comment;  //Комментарий к заказу
            $orderweb->add_cost = $add_cost; //Добавленная стоимость
            $orderweb->wagon = $wagon; //Универсал: True, False
            $orderweb->minibus = $minibus; //Микроавтобус: True, False
            $orderweb->premium = $premium; //Машина премиум-класса: True, False
            $orderweb->flexible_tariff_name = $flexible_tariff_name; //Гибкий тариф
            $orderweb->route_undefined = $route_undefined; //По городу: True, False
            $orderweb->routefrom = $from; //Обязательный. Улица откуда.
            $orderweb->routefromnumber = $from_number; //Обязательный. Дом откуда.
            $orderweb->routeto = $to; //Обязательный. Улица куда.
            $orderweb->routetonumber = $to_number; //Обязательный. Дом куда.
            $orderweb->taxiColumnId = $taxiColumnId; //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
            $orderweb->payment_type = 0; //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
            $json_arr = json_decode($response, true);

            $orderweb->web_cost = $json_arr['order_cost'];// Стоимость поездки
            $json_arrWeb = json_decode($responseWeb, true);
            $orderweb->dispatching_order_uid = $json_arrWeb['dispatching_order_uid']; //Идентификатор заказа БД ТН
            $orderweb->save();

            if ($route_undefined !== false) {
                $order = "Вітаємо $user_full_name
                    . Ви успішно зробили замовлення за маршрутом від $from (будинок $from_number) по місту.
                    Оплата: $payment_type_info. $auto_type. Вартість поїздки становитиме: " . $json_arr['order_cost'] .
                    "грн. Номер: " .  $json_arrWeb['dispatching_order_uid'];
            } else {
                $order = "Вітаємо $user_full_name. Ви успішно зробили замовлення за маршрутом
                    від $from (будинок $from_number) до $to (будинок $to_number). Оплата: $payment_type_info
                     $auto_type. Вартість поїздки становитиме: " . $json_arr['order_cost'] . "грн. Номер: " .
                    $json_arrWeb['dispatching_order_uid'];

                switch ($to) {
                    case 'Аэропорт Борисполь терминал Д':
                                    $order = "Вітаємо $user_full_name. Ви успішно зробили замовлення за маршрутом
                                    від $from (будинок $from_number) до аеропорту \"Бориспіль\".
                                    Оплата: $payment_type_info. $auto_type. Вартість поїздки становитиме: " .
                                    $json_arr['order_cost'] . "грн. Номер: " . $json_arrWeb['dispatching_order_uid'];
                        break;
                    case 'Аэропорт Жуляны новый (ул.Медовая 2)':
                                    $order = "Вітаємо $user_full_name. Ви успішно зробили замовлення за маршрутом від
                                    $from (будинок $from_number) до аеропорту \"Киів\" (Жуляни). Оплата: $payment_type_info.
                                    $auto_type. Вартість поїздки становитиме: " . $json_arr['order_cost'] .
                                        "грн. Номер: " . $json_arrWeb['dispatching_order_uid'];
                        break;
                    case 'ЖД Южный':
                                    $order = "Вітаємо $user_full_name. Ви успішно зробили замовлення за маршрутом від
                                    $from (будинок $from_number) до залізничного вокзалу \"Південний \".
                                    Оплата: $payment_type_info.. $auto_type. Вартість поїздки становитиме: " .
                                    $json_arr['order_cost'] . "грн. Номер: " . $json_arrWeb['dispatching_order_uid'];
                        break;
                    case 'Центральный автовокзал (у шлагбаума пл.Московская 3)':
                                    $order = "Вітаємо $user_full_name. Ви успішно зробили замовлення за маршрутом від
                                    $from (будинок $from_number) до автовокзалу.  Оплата: $payment_type_info. $auto_type.
                                    Вартість поїздки становитиме: " . $json_arr['order_cost'] . "грн. Номер: " .
                                        $json_arrWeb['dispatching_order_uid'];
                        break;
                }

                switch ($from) {
                    case 'Аэропорт Борисполь терминал Д':
                                    $order = "Вітаємо $user_full_name. Ви успішно зробили замовлення за маршрутом від
                                    аеропорту \"Бориспіль\" до $to (будинок $to_number).
                                    Оплата: $payment_type_info. $auto_type. Вартість поїздки становитиме: " .
                                        $json_arr['order_cost'] . "грн. Номер: " .
                                        $json_arrWeb['dispatching_order_uid'];
                        break;
                    case 'Аэропорт Жуляны новый (ул.Медовая 2)':
                                    $order = "Вітаємо $user_full_name. Ви успішно зробили замовлення за маршрутом від
                                    аеропорту \"Киів\" (Жуляни) до $to (будинок $to_number).
                                    Оплата: $payment_type_info. $auto_type. Вартість поїздки становитиме: " .
                                        $json_arr['order_cost'] . "грн. Номер: " .
                                        $json_arrWeb['dispatching_order_uid'];
                        break;
                    case 'ЖД Южный':
                                    $order = "Вітаємо $user_full_name. Ви успішно зробили замовлення за маршрутом від
                                    залізничного вокзалу \"Південний \" до $to (будинок $to_number).
                                    Оплата: $payment_type_info. $auto_type. Вартість поїздки становитиме: " .
                                        $json_arr['order_cost'] . "грн. Номер: " .
                                        $json_arrWeb['dispatching_order_uid'];
                        break;
                    case 'Центральный автовокзал (у шлагбаума пл.Московская 3)':
                                    $order = "Вітаємо $user_full_name. Ви успішно зробили замовлення за маршрутом від
                                    автовокзалу до $to (будинок $to_number). Оплата: $payment_type_info. $auto_type.
                                    Вартість поїздки становитиме: " . $json_arr['order_cost'] . "грн. Номер: " .
                                        $json_arrWeb['dispatching_order_uid'];
                        break;
                }

            };
            /**
             * Сообщение на почту о заказе
             */

            $subject = 'Інформація про вашу поїздку:';
            $paramsCheck = [
                'subject' => $subject,
                'message' => $order,
            ];
            $user = User::where('user_phone', $user_phone)->first();

            if ($user) {
                Mail::to($user->email)->send(new Check($paramsCheck));
            }
            Mail::to('taxi.easy.ua@gmail.com')->send(new Check($paramsCheck));
            Mail::to('cartaxi4@gmail.com')->send(new Check($paramsCheck));

            return redirect()->route('home-id-afterorder-uid', $orderweb)->with('success', $order)
                ->with('tel', "Очікуйте на інформацію від оператора з обробки замовлення. Скасувати або внести зміни можна за номером оператора:")
                ->with('back', 'Зробити нове замовлення')
                ->with('cancel', 'Скасувати замовлення.')
                ->with('uid', 'Отримати інформацію');
        } else {
            $json_arr = json_decode($responseWeb, true);
            $message_error = $json_arr['Message'];
            return view('taxi.feedback', ['info' => "Помилка замовлення. $message_error"]);
        }
    }

    /**
    * Заказ звонка
    * @return string
    */
    public function callBack(Request $req)
    {
        $error = true;
        $secret = config('app.RECAPTCHA_SECRET_KEY');

        if (!empty($_GET['g-recaptcha-response'])) { //проверка на робота
            $curl = curl_init('https://www.google.com/recaptcha/api/siteverify');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, 'secret=' . $secret . '&response=' . $_GET['g-recaptcha-response']);
            $out = curl_exec($curl);
            curl_close($curl);

            $out = json_decode($out);
            if ($out->success == true) {
                $username = config('app.username');
                $password = hash('SHA512', config('app.password'));
                $authorization = 'Basic ' . base64_encode($username . ':' . $password);

                $user_phone = $req->user_phone;
                $comment =  "Набрать Клиента для оформления заказа Оператору";
                $taxiColumnId = config('app.taxiColumnId');

                $connectAPI = WebOrderController::connectApi();
                if ($connectAPI == 400) {
                    return redirect()->route('home-news')
                        ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
                }
                $url = $connectAPI . '/api/weborders';
                $responseWeb = Http::withHeaders([
                    'Authorization' => $authorization,
                ])->post($url, [
                    'user_full_name' => 'Новий замовник',
                    'user_phone' => $user_phone, //Телефон пользователя
                    'comment' => $comment, //Комментарий к заказу
                    'reservation' => false, //Обязательный. Признак предварительного заказа: True,
                    'route_undefined' => true, //По городу: True, False
                    'add_cost' => '-35', //Добавленная стоимость
                    'route' => [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                         ['name' => 'ОПЕРАТОР! НАБЕРИТЕ КЛИЕНТА на этот номер', 'lat' => '50.376733115795', 'lng' => '30.609379358341' ],
                    ],
                    'taxiColumnId' => $taxiColumnId, //Обязательный. Номер колоны, в которую будут приходить заказы.
                ]);
                if ($responseWeb->status() == "200") {
                    return redirect()->route('home-news')->with('success', 'Ваш телефон успішно надіслано. ');
                } else {
                    $json_arr = json_decode($responseWeb, true);
                    $message_error = $json_arr['Message'];
                    return redirect()->route('home-news')->with('error', "Помілка. $message_error")
                        ->with('tel2', "Для уточнення деталей наберіть оператора та дотримуйтесь його інструкцій:");
                };

            }
        }
        if ($error) {
            return view('taxi.callBack', ['user_phone' => $req->user_phone,
                'info' => 'Не пройдено перевірку на робота.']);
        }
    }

    /**
     * Работа в такси
     * @return string
     */
    public function callWork(Request $req)
    {
        $req->validate([
            'user_full_name' => ['string'],
            'user_phone' => [new PhoneNumber()],
            'email' => ['email'],
        ]);

        $params['user_full_name'] = $req->user_full_name;
        $params['user_phone'] = $req->user_phone;
        $params['time_work'] = $req->time_work;
        $params['email'] = $req->email;

        $error = true;
        $secret = config('app.RECAPTCHA_SECRET_KEY');



        if (!empty($_GET['g-recaptcha-response'])) { //проверка на робота
            $curl = curl_init('https://www.google.com/recaptcha/api/siteverify');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, 'secret=' . $secret . '&response=' . $_GET['g-recaptcha-response']);
            $out = curl_exec($curl);
            curl_close($curl);

            $out = json_decode($out);
            if ($out->success == true) {
                $username = config('app.username');
                $password = hash('SHA512', config('app.password'));
                $authorization = 'Basic ' . base64_encode($username . ':' . $password);

                $user_full_name = $req->user_full_name;
                $user_phone = $req->user_phone;
                $time_work = $req->time_work;
                $email = $req->email;
                $subject = 'Анкета водія';
                $message = "Доброго часу доби, $user_full_name!
                    Якщо Вам потрібна робота водієм таксі в Києві та Київській області заповніть анткету у вкладенні та
                    надішліть за адресою cartaxi4@gmail.com. Будемо раді бачити Вас у нашій команді професіоналів.";
                $params = [
                        'email' => $email,
                        'subject' => $subject,
                        'message' => $message,
                    ];

                Mail::to($email)->send(new Driver($params));

                $IP_ADDR = getenv("REMOTE_ADDR") ;//IP пользователя
                $subject = 'Новий кандидат у водії.';
                $messageAdmin = "ОПЕРАТОР! Зв'яжіться з новим кандидатом-водієм на ім'я $user_full_name. Йому потрібна робота.
                            Водійський стаж: $time_work років. Анкету надіслано йому на пошту: $email. IP кандидата: $IP_ADDR.
                            Телефон: $user_phone.";
                $paramsAdmin = [
                    'email' => $email,
                    'subject' => $subject,
                    'message' => $messageAdmin,
                ];

                Mail::to('taxi.easy.ua@gmail.com')->send(new Admin($paramsAdmin));

                Mail::to('cartaxi4@gmail.com')->send(new Admin($paramsAdmin));
                $comment =  "ОПЕРАТОР! Перезвоните новому водителю по имени $user_full_name. Ему нужна работа.
                            Водительский стаж $time_work лет. Анкета отправлена ему на почту";
                $taxiColumnId = config('app.taxiColumnId');

                $connectAPI = WebOrderController::connectApi();
                if ($connectAPI == 400) {
                    return redirect()->route('home-news')
                        ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
                }
                $url = $connectAPI . '/api/weborders';
                $responseWeb = Http::withHeaders([
                    'Authorization' => $authorization,
                ])->post($url, [
                    'user_full_name' => $user_full_name,
                    'user_phone' => $user_phone, //Телефон пользователя
                    'comment' => $comment, //Комментарий к заказу
                    'reservation' => false, //Обязательный. Признак предварительного заказа: True,
                    'route_undefined' => true, //По городу: True, False
                    'add_cost' => '-35', //Добавленная стоимость
                    'route' => [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                        ['name' => $comment, 'lat' => '50.376733115795', 'lng' => '30.609379358341' ],
                    ],
                    'taxiColumnId' => $taxiColumnId, //Обязательный. Номер колоны, в которую будут приходить заказы.
                ]);

                if ($responseWeb->status() == "200") {
                    return redirect()->route('home-news')->with('success', "$user_full_name, Ваш телефон успішно надіслано у нашу службу.
                                Анкету чекайте на Вашій пошті. Заповніть її та надішліть за вказаною адресою.")
                        ->with('tel', "Для уточнення чекайте/або наберіть диспетчера:");

                } else {
                    $json_arr = json_decode($responseWeb, true);

                    $message_error = $json_arr['description'];
                    return view('driver.callWork', ['params' => $params,
                        'info' => "Помілка. $message_error"]);
                }
            }
        }
        if ($error) {
            return view('driver.callWork', ['params' => $params,
                'info' => 'Не пройдено перевірку на робота.']);
        }
    }
    /**
     *Отправка почты с сайта
     */
    public function feedbackEmail(Request $req)
    {
        $error = true;
        $secret = config('app.RECAPTCHA_SECRET_KEY');

        if (!empty($_GET['g-recaptcha-response'])) { //проверка на робота
            $curl = curl_init('https://www.google.com/recaptcha/api/siteverify');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, 'secret=' . $secret . '&response=' . $_GET['g-recaptcha-response']);
            $out = curl_exec($curl);
            curl_close($curl);

            $out = json_decode($out);
            if ($out->success == true) {
                $params = [
                    'email' => $req->email,
                    'subject' => $req->subject,
                    'message' => $req->message,
                ];

                Mail::to('taxi.easy.ua@gmail.com')->send(new Feedback($params));
                return redirect()->route('homeCombo')
                    ->with('success',
                    "Повідомлення успішно надіслано адміністратору сайту. Чекайте на відповідь на свій email.");
            }
        }
        if ($error) {
            $params = [
                'email' => $req->email,
                'subject' => $req->subject,
                'message' => $req->message,
            ];
            return view('taxi.feedback', ['params' => $params,
                'info' => 'Не пройдено перевірку на робота.']);
        }

}

    /**
     * Получение списка тарифов
     * @return string
     */
    public function tariffs()
    {
        $response_arr = Tarif::all()->collect();
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

        return $json_arr;
    }

    /**
     * Контроль версии улиц
     */
    public function version_street()
    {
        $username = config('app.username');
        $password = hash('SHA512', config('app.password'));
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        //Обновление списка тарифов
        $url = $connectAPI . '/api/tariffs';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url);

        $response_arr = json_decode($response, true);
        DB::table('tarifs')->truncate();
        for ($i = 0; $i < count($response_arr); $i++) {
            $new_tarif = new Tarif();
            $new_tarif->name = $response_arr[$i]['name'];
            $new_tarif->save();
        }

        $url = $connectAPI . '/api/geodata/streets';
        $json_str = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url, [
            'versionDateGratherThan' => '', //Необязательный. Дата версии гео-данных полученных ранее. Если параметр пропущен — возвращает  последние гео-данные.
        ]);

        $json_arr = json_decode($json_str, true);

        /**
         * Проверка версии геоданных и обновление или создание базы адресов
         * $json_arr['version_date'] - текущая версия улиц в базе
         * config('app.streetVersionDate') - дата версии в конфиге
         */

        $svd = Config::where('id', '1')->first();
        //Проверка версии геоданных и обновление или создание базы адресов
        if (config('app.server') == 'Киев') {
            if ($json_arr['version_date'] !== $svd->streetVersionDate || Street::all()->count() === 0) {
                $svd->streetVersionDate = $json_arr['version_date'];
                $svd->save();
                DB::table('streets')->truncate();
                $i = 0;
                do {
                    $street = new Street();
                    $street->name = $json_arr['geo_street'][$i]["name"];
                    $street->save();

                    $streets = $json_arr['geo_street'][$i]["localizations"];
                    foreach ($streets as $val) {
                        if ($val["locale"] == "UK") {
                            $street = new Street();
                            $street->name = $val['name'];
                            $street->save();
                        }
                    }
                    $i++;
                } while ($i < count($json_arr['geo_street']));
            }
        }
        if (config('app.server') == 'Одесса') {
            if ($json_arr['version_date'] !== $svd->streetVersionDate || Street::all()->count() === 0) {
                $svd->streetVersionDate = $json_arr['version_date'];
                $svd->save();
                DB::table('streets')->truncate();
                $i = 0;

                do {
                    $street = new Street();
                    $street->name = $json_arr['geo_street'][$i]["name"];
                    $street->save();

                    $i++;
                } while ($i < count($json_arr['geo_street']));

            }
        }
    }

    /**
     * Контроль версии объектов
     */
    public function version_object()
    {
        $username = config('app.username');
        $password = hash('SHA512', config('app.password'));
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);


        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        //Обновление списка тарифов
        $url = $connectAPI . '/api/tariffs';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url);

        $response_arr = json_decode($response, true);
        DB::table('tarifs')->truncate();
        for ($i = 0; $i < count($response_arr); $i++) {
            $new_tarif = new Tarif();
            $new_tarif->name = $response_arr[$i]['name'];
            $new_tarif->save();
        }
        $url = $connectAPI . '/api/geodata/objects';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url, [
            'versionDateGratherThan' => '', //Необязательный. Дата версии гео-данных полученных ранее. Если параметр пропущен — возвращает  последние гео-данные.
        ]);

        $json_arr = json_decode($response, true);

        $svd = Config::where('id', '1')->first();
        //Проверка версии геоданных и обновление или создание базы адресов
        if (config('app.server') == 'Киев') {
            if ($json_arr['version_date'] !== $svd->objectVersionDate || Objecttaxi::all()->count() === 0) {
                $svd->objectVersionDate = $json_arr['version_date'];
                $svd->save();

                DB::table('objecttaxis')->truncate();
                $i = 0;
                do {
                    $objects = new Objecttaxi();
                    $objects->name = $json_arr['geo_object'][$i]["name"];
                    $objects->save();
                    $streets = $json_arr['geo_object'][$i]["localizations"];
                    foreach ($streets as $val) {

                        if ($val["locale"] == "UK") {
                            $objects = new Objecttaxi();
                            $objects->name = $val['name'];
                            $objects->save();

                        }
                    }
                    $i++;
                } while ($i < count($json_arr['geo_object']));
  /*              $i = 0;

                do {
                    $objects = new Objecttaxi();
                    $objects->name = $json_arr['geo_object'][$i]["name"];
                    $objects->save();

                    $i++;
                } while ($i < count($json_arr['geo_object']));*/
            }
        }
        if (config('app.server') == 'Одесса') {
            if ($json_arr['version_date'] !== $svd->objectVersionDate || Objecttaxi::all()->count() === 0) {
                $svd->objectVersionDate = $json_arr['version_date'];
                $svd->save();
                DB::table('objecttaxis')->truncate();
                $i = 0;

                do {
                    $objects = new Objecttaxi();
                    $objects->name = $json_arr['geo_object'][$i]["name"];
                    $objects->save();

                    $i++;
                } while ($i < count($json_arr['geo_object']));

            }
        }
    }

    /**
     * Контроль версии улиц и объектов
     */
    public function version_combo()
    {
        $base = env('DB_DATABASE');
        $marker_update = false;

        $username = config('app.username');
        $password = hash('SHA512', config('app.password'));
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);
        /**
         * Проверка подключения к серверам
         */
        $connectAPI = WebOrderController::connectApi();

        if ($connectAPI == 400) {
            if ($base === 'taxi2012_test') {
                return redirect()->route('home-admin')->with('error', "Ошибка подключения к серверу.");
            } else {
                return redirect()->route('home-news')
                    ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
            }
        }

        /**
         * Проверка даты геоданных в АПИ
         */

        $url = $connectAPI . '/api/geodata/streets';
        $json_str = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url, [
            'versionDateGratherThan' => '', //Необязательный. Дата версии гео-данных полученных ранее. Если параметр пропущен — возвращает  последние гео-данные.
        ]);

        $json_arr = json_decode($json_str, true);
        $url_ob = $connectAPI . '/api/geodata/objects';
        $response_ob = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url_ob);

        $json_arr_ob = json_decode($response_ob, true);

        /**
         * Проверка версии геоданных и обновление или создание базы адресов
         * $json_arr['version_date'] - текущая версия улиц в базе
         * config('app.streetVersionDate') - дата версии в конфиге
         * $json_arr_ob['version_date'] - текущая версия объектов в базе
         * config('app.objectVersionDate') - дата версии в конфиге
         */

        $svd = Config::where('id', '1')->first();
        if ($svd) {
            if ($json_arr['version_date'] !==  $svd->streetVersionDate || $json_arr_ob['version_date'] !== $svd->objectVersionDate) {
                $marker_update = true;
            }
        } else {
            $marker_update = true;
        }

        //Проверка версии геоданных и обновление или создание базы адресов

        if ($marker_update || Combo::all()->count() === 0) {
            //Обновление списка тарифов
            $url = $connectAPI . '/api/tariffs';
            $response = Http::withHeaders([
                'Authorization' => $authorization,
            ])->get($url);

            $response_arr = json_decode($response, true);
            DB::table('tarifs')->truncate();
            for ($i = 0; $i < count($response_arr); $i++) {
                $new_tarif = new Tarif();
                $new_tarif->name = $response_arr[$i]['name'];
                $new_tarif->save();
            }

            DB::table('combos')->truncate();

            foreach ($json_arr['geo_street'] as $arrStreet) { //Улицы
                $combo = new Combo();
                $combo->name = $arrStreet["name"];
                $combo->street = 1;
                $combo->save();

                $geo_street = $arrStreet["localizations"];
                if ($geo_street !== null) {
                    foreach ($geo_street as $val) {
                        if ($val["locale"] == "UK") {
                            $combo = new Combo();
                            $combo->name = $val['name'];
                            $combo->street = 1;
                            $combo->save();
                        }
                    }
                }
            }

            foreach ($json_arr_ob['geo_object'] as $arrObject) { // Объекты
                $combo = new Combo();
                $combo->name = $arrObject["name"];
                $combo->street = 0;
                $combo->save();

                $geo_object = $arrObject["localizations"];
                if ($geo_object !== null) {
                    foreach ($geo_object as $val) {
                        if ($val["locale"] == "UK") {
                            $combo = new Combo();
                            $combo->name = $val['name'];
                            $combo->street = 0;
                            $combo->save();
                        }
                    }
                }
            }

            DB::table('configs')->truncate(); //Запись даты обновления версии
            $svd = new Config();
            $svd->streetVersionDate = $json_arr['version_date'];
            $svd->objectVersionDate = $json_arr_ob['version_date'];
            $svd->save();

            return redirect()->route('home-admin')->with('success', "База $base обновлена.");
        } else {
            return redirect()->route('home-admin')->with('success', "База $base актуальна.");
        }
    }

    /**
     * Проверка адресов
     * $params - массив название улицы + дом (или просто объект) + по городу
     */
    public function nameCombo($params)
    {
        $username = config('app.username');
        $password = hash('SHA512', config('app.password'));
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);
        /**
         * Проверка подключения к серверам
         */
        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        /**
         * Проверка адреса в базе
         */
        $comboArr = Combo::where('name', $params['routefrom'])->first();
        dd($comboArr);

        /**
         * Проверка даты геоданных в АПИ
         */

        $url = $connectAPI . '/api/geodata/streets';
        $json_str = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url);

        $json_arr = json_decode($json_str, true);

        $url_ob = $connectAPI . '/api/geodata/objects';
        $response_ob = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url_ob);

        $json_arr_ob = json_decode($response_ob, true);
 /**
         * Проверка версии геоданных и обновление или создание базы адресов
         * $json_arr['version_date'] - текущая версия улиц в базе
         * config('app.streetVersionDate') - дата версии в конфиге
         * $json_arr_ob['version_date'] - текущая версия объектов в базе
         * config('app.objectVersionDate') - дата версии в конфиге
         */

        $svd = Config::where('id', '1')->first();
        //Проверка версии геоданных и обновление или создание базы адресов
        if (config('app.server') == 'Киев') {
            if ($json_arr['version_date'] !== $svd->streetVersionDate ||
                $json_arr_ob['version_date'] !== $svd->objectVersionDate || Combo::all()->count() === 0)
            {
                //Обновление списка тарифов
                $url = $connectAPI . '/api/tariffs';
                $response = Http::withHeaders([
                    'Authorization' => $authorization,
                ])->get($url);

                $response_arr = json_decode($response, true);
                DB::table('tarifs')->truncate();
                for ($i = 0; $i < count($response_arr); $i++) {
                    $new_tarif = new Tarif();
                    $new_tarif->name = $response_arr[$i]['name'];
                    $new_tarif->save();
                }

                $svd->streetVersionDate = $json_arr['version_date'];
                $svd->objectVersionDate = $json_arr_ob['version_date'];
                $svd->save();

                DB::table('combos')->truncate();
                $i = 0;
                do {
                    $combo = new Combo();
                    $combo->name = $json_arr['geo_street'][$i]["name"];
                    $combo->street = 1;
                    $combo->save();

                    $geo_street = $json_arr['geo_street'][$i]["localizations"];
                    foreach ($geo_street as $val) {
                        if ($val["locale"] == "UK") {
                            $combo = new Combo();
                            $combo->name = $val['name'];
                            $combo->street = 1;
                            $combo->save();
                        }
                    }

                    $combo = new Combo();
                    $combo->name = $json_arr_ob['geo_object'][$i]["name"];
                    $combo->street = 0;
                    $combo->save();
                    $geo_object = $json_arr_ob['geo_object'][$i]["localizations"];
                    foreach ($geo_object as $val) {
                        if ($val["locale"] == "UK") {
                            $combo = new Combo();
                            $combo->name = $val['name'];
                            $combo->street = 0;
                            $combo->save();
                        }
                    }
                    $i++;
                } while ($i < count($json_arr['geo_street']));
            }
        }
        /*   if (config('app.server') == 'Одесса') {
               if ($json_arr['version_date'] !== $svd->streetVersionDate || Combo::all()->count() === 0) {
                   $svd->streetVersionDate = $json_arr['version_date'];
                   $svd->save();
                   DB::table('combos')->truncate();
                   $i = 0;

                   do {
                       $street = new Street();
                       $street->name = $json_arr['geo_street'][$i]["name"];
                       $street->save();

                       $i++;
                   } while ($i < count($json_arr['geo_street']));

               }
           }

           *******************************

           $svd = Config::where('id', '1')->first();
           //Проверка версии геоданных и обновление или создание базы адресов
           if (config('app.server') == 'Киев') {
               if ($json_arr['version_date'] !== $svd->objectVersionDate || Combo::all()->count() === 0) {
                   $svd->objectVersionDate = $json_arr['version_date'];
                   $svd->save();

                   DB::table('objecttaxis')->truncate();
                   $i = 0;
                   do {
                       $objects = new Objecttaxi();
                       $objects->name = $json_arr['geo_object'][$i]["name"];
                       $objects->save();
                       $streets = $json_arr['geo_object'][$i]["localizations"];
                       foreach ($streets as $val) {

                           if ($val["locale"] == "UK") {
                               $objects = new Objecttaxi();
                               $objects->name = $val['name'];
                               $objects->save();

                           }
                       }
                       $i++;
                   } while ($i < count($json_arr['geo_object']));
               }
           }
           if (config('app.server') == 'Одесса') {
               if ($json_arr['version_date'] !== $svd->objectVersionDate || Objecttaxi::all()->count() === 0) {
                   $svd->objectVersionDate = $json_arr['version_date'];
                   $svd->save();
                   DB::table('objecttaxis')->truncate();
                   $i = 0;

                   do {
                       $objects = new Objecttaxi();
                       $objects->name = $json_arr['geo_object'][$i]["name"];
                       $objects->save();

                       $i++;
                   } while ($i < count($json_arr['geo_object']));

               }
           }


           */

    }


    /**
     * Гео данные
     * Запрос гео-данных (всех объектов)
     * @return string
     */
    public function objects()
    {
        $username = config('app.username');
        $password = hash('SHA512', config('app.password'));
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/geodata/objects';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url, [
            'versionDateGratherThan' => '', //Дата версии гео-данных полученных ранее. Если параметр пропущен — возвращает  последние гео-данные.
        ]);

        return $response->body() ;
    }

    /**
     * Запрос отмены заказа клиентом
     * @return string
     */
    public function webordersCancel($id)
    {
        $username = config('app.username');
        $password = hash('SHA512', config('app.password'));
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $orderweb = Orderweb::where('id', $id)->first();

        $uid =  $orderweb->dispatching_order_uid; //идентификатор заказа'5b1e13c458514781881da701583c8ccd'

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/weborders/cancel/' . $uid;
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->put($url);


        $json_arrWeb = json_decode($response, true);

        $resp_answer = "Запит на скасування замовлення $uid надіслано. ";

        switch ($json_arrWeb['order_client_cancel_result']) {
            case '0':
                $resp_answer = $resp_answer . "Замовлення не вдалося скасувати. ";
                break;
            case '1':
                $resp_answer = $resp_answer . "Замовлення скасоване. ";
                break;
            case '2':
                $resp_answer = $resp_answer . "Вимагає підтвердження клієнтом скасування диспетчерської. ";
                break;
        }

        return redirect()->route('home-id-afterorder-web', ['id' => $id])->with('success', $resp_answer)
            ->with('tel', "Очікуйте на інформацію від оператора з обробки замовлення. Інформацію можна отримати за номером оператора:")
            ->with('back', 'Зробити нове замовлення');
    }

    /**
     * Запрос состояния заказа
     * @return string
     */
    public function webordersUid($id)
    {
        $username = config('app.username');
        $password = hash('SHA512', config('app.password'));
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $orderweb = Orderweb::where('id', $id)->first();

        $uid =  $orderweb->dispatching_order_uid; //идентификатор заказа

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        try {
            $url = $connectAPI . '/api/weborders/' . $uid;
            $response = Http::withHeaders([
                'Authorization' => $authorization,
            ])->put($url);
            $json_arrWeb = json_decode($response, true);
            $dispatching_order_uid = $json_arrWeb['dispatching_order_uid'];
            $order_cost = $json_arrWeb['order_cost'];
            $order_car_info = $json_arrWeb['order_car_info'];
            $message_success = "Замовлення №$dispatching_order_uid. Вартість:$order_cost грн. Автомобіль:$order_car_info.";

            return redirect()->route('home-id-afterorder', $id)->with('success', $message_success)
                ->with('tel', "Очікуйте на інформацію від оператора з обробки замовлення. Інформацію можна отримати за номером оператора:")
                ->with('tel_driver', $json_arrWeb['driver_phone'])
                ->with('cancel', 'Скасувати замовлення.');
        } catch (Exception $e) {
            $message_error = 'Вибачте. Машину ще не знайдено. Спробуйте трохи згодом.';
            return redirect()->route('home-id-afterorder', $id)->with('error', $message_error)
                ->with('tel', "Очікуйте на інформацію від оператора з обробки замовлення.
                Інформацію можна отримати за номером оператора:")
                ->with('uid', 'Отримати інформацію')
                ->with('cancel', 'Скасувати замовлення.');
        }
    }

    /**
     * Получение координат автомобиля в заказе
     * @return string
     */
    public function driversPositionUid($uid)
    {

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        try {
            $username = config('app.username');
            $password = hash('SHA512', config('app.password'));
            $authorization = 'Basic ' . base64_encode($username . ':' . $password);

            $url = $connectAPI . '/api/weborders/' . $uid;
            $response = Http::withHeaders([
                'Authorization' => $authorization,
            ])->put($url);
            $json_arrWeb = json_decode($response, true);

            return $json_arrWeb["drivercar_position"];
        } catch (Exception $e) {
            return null;
        }
    }
    /**
     * Получение координат автомобилей в радиусе
     * @return string
     */
    public function driversPosition()
    {

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/drivers/position';
        $username = config('app.username');
        $password = hash('SHA512', config('app.password'));
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url, [

            'lat' => '50.418843668133', //Обязательный. Широта
            'lng' => '30.539846933016', //Обязательный. Долгота
            'radius' => '10' //Обязательный. Радиус поиска автомобилей (в км.)
        ]);
       $json_arrWeb = json_decode($response, true);
       dd($json_arrWeb);
        /*  position, title
       /* $tourStops = [
              [ 'lat' => 50.416525, 'lng' => 30.520825 }, "Мікроавтобус"],
                  [{ lat: 50.43962, lng: 30.51525 }, "Мінібус"],

         const tourStops = [
                [{ lat: 50.416525, lng: 30.520825 }, "Мікроавтобус"],
                [{ lat: 50.43962, lng: 30.51525 }, "Мінібус"],

            ];


        */
        return
            $tourStops;
    }



    /**
     * Гео данные
     * Поиск гео-данных (улиц и объектов) по нескольким буквам
     * @return string
     */
    public function geodataSearch($q, $house)
    {

        $username = config('app.username');
        $password = hash('SHA512', config('app.password'));
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/geodata/search';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url, [
            'q' => $q, //Обязательный. Несколько букв для поиска объекта.
            'offset' => 0, //Смещение при выборке (сколько пропустить).
            'limit' => 1000, //Кол-во возвращаемых записей (предел).
            'transliteration' => true, //Разрешить транслитерацию запроса при поиске.
            'qwertySwitcher' => true, //Разрешить преобразование строки запроса в случае ошибочного набора с неверной раскладкой клавиатуры (qwerty). Например, «ghbdtn» - это «привет».
            'fields' => '*', /*Данным параметром можно указать перечень требуемых параметров, которые будут возвращаться в ответе. Разделяются запятой.
                Возможные значения:
                * (возвращает все поля)
                name
                old_name
                houses
                lat
                lng
                locale*/
        ]);
        $response_arr = json_decode($response, true);
  //dd($response_arr["geo_streets"]["geo_street"][0]["houses"][$house]);

        if ($house !== null) {
            if (isset($response_arr["geo_streets"]["geo_street"][0]["houses"][$house])) {
                $LatLng["lat"] = $response_arr["geo_streets"]["geo_street"][0]["houses"][$house]["lat"];
                $LatLng["lng"] = $response_arr["geo_streets"]["geo_street"][0]["houses"][$house]["lng"];
            } else {
                $LatLng["lat"] = 0;
                $LatLng["lng"] = 0;
            }

        }
        else {
         //   dd($response_arr["geo_objects"]["geo_object"]);
            if ($response_arr["geo_objects"]["geo_object"] != null) {
                $LatLng["lat"] = $response_arr["geo_objects"]["geo_object"][0]["lat"];
                $LatLng["lng"] = $response_arr["geo_objects"]["geo_object"][0]["lng"];
            } else {
                $LatLng["lat"] = 0;
                $LatLng["lng"] = 0;
            }
//            $LatLng["lat"] = $response_arr["geo_objects"]["geo_object"][0]["lat"];
//            $LatLng["lng"] = $response_arr["geo_objects"]["geo_object"][0]["lng"];
        }

        return $LatLng;
    }







    /**
     * Смена пароля
     * @return string
     */
    public function changePassword()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/account/changepassword';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->put($url, [
            //Все поля обязательные
            'oldPassword' => '11223344', //Старый пароль
            'newPassword' => '22223344', //Новый пароль
            'repeatNewPassword' => '22223344' //Repeat Новый пароль
        ]);
        return $response->status();
    }




    /**
     * Запрос версии
     * @return string
     */
    public function version()
    {
        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/version';
        $response = Http::get($url);
        return $response->body();
    }








    /**
     * Работа с заказами
     * Создание заказа
     * @return string
     */


    /**
     * Запрос информации о позывном
     * @return string
     */
    public function webordersUidDriver()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);
        $uid = '9a1051aaf1654cd28d97a87c7ff8398a'; //идентификатор заказа

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/weborders/' . $uid . '/driver';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url);

        return $response->body() ;
    }

    /**
     * Добавочная стоимость
     * Get -проверить
     * @return string
     */
    public function webordersUidCostAdditionalGet()
    {
        $username = config('app.username');
        $password = hash('SHA512', config('app.password'));
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);
        $uid = '9a1051aaf1654cd28d97a87c7ff8398a'; //идентификатор заказа

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/weborders/' . $uid . '/cost/additional';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url);

        return $response->body() ;
    }
    /**
     * Добавочная стоимость
     * Post - добавить
     * @return string
     */
    public function webordersUidCostAdditionalPost()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);
        $uid = '9a1051aaf1654cd28d97a87c7ff8398a'; //идентификатор заказа

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/weborders/' . $uid . '/cost/additional';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->post($url, [
            'amount' => 100
        ]);

        return $response->body() ;
    }
    /**
     * Добавочная стоимость
     * Put - изменить
     * @return string
     */
    public function webordersUidCostAdditionalPut()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);
        $uid = '9a1051aaf1654cd28d97a87c7ff8398a'; //идентификатор заказа

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/weborders/' . $uid . '/cost/additional';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->put($url, [
            'amount' => 50
        ]);

        return $response->body() ;
    }

    /**
     * Добавочная стоимость
     * Delete - Удалить
     * @return string
     */
    public function webordersUidCostAdditionalDelete()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);
        $uid = '9a1051aaf1654cd28d97a87c7ff8398a'; //идентификатор заказа

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/weborders/' . $uid . '/cost/additional';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->delete($url);

        return $response->body() ;
    }
    /**
     * Запрос GPS положения машины, выполняющей заказ
     * @return string
     */
    public function webordersDrivercarPosition()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);
        $uid = '9a1051aaf1654cd28d97a87c7ff8398a'; //идентификатор заказа

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/weborders/drivercarposition/' . $uid;
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url);

        return $response->body() ;
    }


    /**
     * Оценка поездки
     * @return int
     */
    public function webordersRate()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);
        $uid = '5b1e13c458514781881da701583c8ccd'; //идентификатор заказа

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/weborders/rate/' . $uid;
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->post($url, [
            'rating' => 5, // Обязательный.	1, 2, 3, 4, 5	Оценка поездки
            'rating_comment' => 'Ok' //Комментарий к оценке. Максимальная длина 120 символов.
        ]);

        return $response->status() ;
    }

    /**
     * Запрос на скрытие заказа (удалить поездку)
     * @return int
     */
    public function webordersHide()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);
        $uid = 'f719e712ad0545a38ab5650ce71d5138'; //идентификатор заказа

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/weborders/hide/' . $uid;
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->put($url);

        return $response->status() ;
    }

    /**
     * Запрос отчета по заказам клиентом
     * @return string
     */
    public function ordersReport()
    {
        $username = '0936734455';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/clients/ordersreport';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url, [
            'dateFrom' => '2022-01-01', //Обязательный. Начальный интервал для отчета
            'dateTo' => '2022-12-31', //Обязательный. Конечный интервал для отчета
        ]);
        return $response->body();
    }

    /**
     * Запрос истории по заказам клиента
     * @return string
     */
    public function ordersHistory()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/clients/ordershistory';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url, [
          //  'limit' => '10', //Необязательный. Вернуть количество записей
            'offset' => '0', //Необязательный. Пропустить количество записей
            'executionStatus' => '*', /* Необязательный.
                Критерий выборки заказов в зависимости от статуса выполнения заказа (см. далее execution_status). В качестве параметра можно передавать перечень статусов выполнения заказа (Примечание 2) разделенных запятой, которые необходимо получить. Например:
                executionStatus=WaitingCarSearch,SearchesForCar,CarFound,Running,Canceled,Executed
                или executionStatus=* - возвращает все заказы
                отсутствующий параметр  executionStatus — эквивалентен executionStatus=Executed*/
        ]);
        return $response->body();
    }

    /**
     * Запрос истории по изменениям бонусов клиента
     * @return string
     */
    public function ordersBonusreport()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/clients/bonusreport';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url, [
            //  'limit' => '10', //Необязательный. Вернуть количество записей
            'offset' => '0', //Необязательный. Пропустить количество записей
           ]);
        return $response->body();
    }


    /**
     * Запрос пяти самых новых адресов клиента
     * @return string
     */
    public function lastaddresses()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/clients/lastaddresses';
        $response = Http::withHeaders([
            'Authorization' => $authorization, ])->get($url);
        return $response->body();
    }


    /**
     * Обновление информации для отправки push
     * @return string
     */
    public function credential()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/clients/credential';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
            //X-WO-API-APP-ID: App_name
        ])->put($url, [
            'app_registration_token' => 'string' //токен (*) Если значения X-WO-API-APP-ID нет в БД сервера или он пустой, он записан в профиль клиента не будет.
        ]);
        return $response->status();
    }

    /**
     * Смена телефона клиента
     * Получение кода подтверждения
     * @return int
     */
    public function changePhoneSendConfirmCode()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/clients/changePhone/sendConfirmCode';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->post($url, [
            'phone' => '380936734488', //Обязательный. Номер мобильного телефона, на который будет отправлен код подтверждения.
            'taxiColumnId' => 0 //Номер колоны, из которой отправляется SMS (0, 1 или 2, по умолчанию 0).
        ]);
        return $response->status();
    }
    /**
     * Смена телефона клиента
     * Смена телефона
     * @return int
     */
    public function clientsChangePhone()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/clients/changePhone/';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->put($url, [
            'phone' => '380936734488', //Обязательный. Номер мобильного телефона, на который будет отправлен код подтверждения.
            'confirm_code' => '1130' //Обязательный. Код подтверждения.
        ]);
        return $response->status();
    }

    /**
     * Виртуальный баланс
     * Пополнение баланса клиента (прием платежей) через платежные системы
     * @return int
     * Алгоритм приема платежей через платежную систему LiqPay.
        1. Клиентское приложение, после успешной авторизации пользователя, присылает запрос на создание платежной транзакции.
        2. После прохождения проверки на возможность создать транзакцию (пополнения баланс пользователя через платежную систему) - возвращается:
            Уникальный идентификатор транзакции;
            Сумма платежа;
            Валюта платежа;
            Описание платежа;
            Уникальный идентификатор пользователя;
            URL для получения изменений статуса платежа.
        3. Клиентское приложение формирует запрос на проведение платежа через платежную систему LiqPay, указав все обязательные параметры.
        4. Поле проведения оплаты через платежную систему, сервер ИДС получает от платежной системы информацию о статусе транзакции.
        5. При успешном статусе транзакции - автоматически меняется статус транзакции и на баланс клиента зачисляется оплаченная сумма платежа.
        6. Клиентское приложение опрашивает сервер для получения текущего статуса транзакции.
        ВАЖНО! Необходимо обязательно указать параметр "server_url", иначе транзакция не будет завершена, и средства не будут автоматически начислены на баланс клиента.
        Для LiqPay: http://<ipaddress>:<port>/api/liqpay/status/
     */
    public function clientsBalanceTransactions()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/clients/balance/transactions/';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->post($url, [
            'amount' => '100.21', //Обязательный. Сумма платежа
        ]);
        return $response->body();
    }

    /**
     * Получение транзакции оплаты
     * @return string
     */
    public function clientsBalanceTransactionsGet()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);
        $id = 37867;

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/clients/balance/transaction/' . $id;
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url);
        return $response->body();
    }

    /**
     * История изменения баланса
     * @return string
     */
    public function clientsBalanceTransactionsGetHistory()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/clients/balance/transactions/';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url, [
            /*Необязательные
             * 'limit' => '10', //Вернуть количество записей
             * 'offset' => '0', //Пропустить количество записей
             */
        ]);
        return $response->body();
    }
    /**
     * Получение избранных адресов
     * @return string
     */
    public function addresses()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/client/addresses';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url);
        return $response->body();
    }

    /**
     * Сохранение избранного адреса
     * @return string
     */
    public function addressesPost()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/client/addresses';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->post($url, [
           'alias' => 'Мой дом', //Название. Максимальный размер 100.
            'comment' => 'Домофон не работает', //Комментарий для создания заказа. Максимальный размер 1024.
            'type' => '1', //Тип адреса: 1 - home, 2 - job, 3 - other.
            'entrance' => '1', //Подъезд
            'address' => [
                'name' => 'Одесская киностудия - Французский бул,33', //Улица или Объект. Если number пустое, то name это Объект, иначе Улица. Максимальный размер 200.
                'number' => '',//Номер дома. Максимальный размер 10.
                'lat' => 46.4595370800332,//Широта
                'lng' => 30.7571053560882//Долгота
            ]
        ]);
        return $response->body();
    }

    /**
     * Изменение избранного адреса
     * @return string
     */
    public function addressesPut()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/client/addresses';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->put($url, [
            'favorite_address_uid' => '092f5cce-715c-4a6a-8aa7-bf54f434c3cf',//Идентификатор избранного адреса, который необходимо обновить.
            'alias' => 'Мой дом', //Название. Максимальный размер 100.
            'comment' => 'Домофон не работает', //Комментарий для создания заказа. Максимальный размер 1024.
            'type' => '1', //Тип адреса: 1 - home, 2 - job, 3 - other.
            'entrance' => '1', //Подъезд
            'address' => [
                'name' => 'Г Одесский Дворик (Успенская 19)', //Улица или Объект. Если number пустое, то name это Объект, иначе Улица. Максимальный размер 200.
                'number' => '',//Номер дома. Максимальный размер 10.
                'lat' => 46.4746977985894,//Широта
                'lng' => 30.7506863475721//Долгота
            ]
        ]);
        return $response->status();
    }

    /**
     * Удаление избранного адреса
     * @return int
     */
    public function addressesDelete()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);
        $favorite_address_uid = '7deb3fed-767e-4fe6-b8d8-2f8ad4b0fd14';

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/client/addresses/' . $favorite_address_uid;
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->delete($url);
        return $response->status();
    }


    /**
     * Гео данные
     * Поиск гео-данных (объектов) по нескольким буквам
     * @return string
     */
    public function objectsSearch()
    {
        $username = '0936734455';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/geodata/objects/search';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url, [
            'q' => 'Оде', //Обязательный. Несколько букв для поиска объекта.
            'offset' => 0, //Смещение при выборке (сколько пропустить).
            'limit' => 10, //Кол-во возвращаемых записей (предел).
            'transliteration' => true, //Разрешить транслитерацию запроса при поиске.
            'qwertySwitcher' => true,  //Разрешить преобразование строки запроса в случае ошибочного набора с неверной раскладкой клавиатуры (qwerty). Например, «ghbdtn» - это «привет».
            'fields' => '*' /*Данным параметром можно указать перечень требуемых параметров, которые будут возвращаться в ответе. Разделяются запятой.
                Возможные значения:
                * (возвращает все поля)
                name
                lat
                lng
                locale*/
        ]);

        return $response->body() ;
    }

    /**
     * Гео данные
     * Запрос гео-данных (всех улиц)
     * @return string
     */
    public function streets()
    {
        $username = '0936734455';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/geodata/streets';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url, [
            'versionDateGratherThan' => '', //Необязательный. Дата версии гео-данных полученных ранее. Если параметр пропущен — возвращает  последние гео-данные.
        ]);

        return $response->body() ;
    }

    /**
     * Гео данные
     * Поиск гео-данных (улиц) по нескольким буквам
     * @return string
     */
    public function streetsSearch()
    {
        $username = '0936734455';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/geodata/streets/search';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url, [
            'q' => 'Оде', //Обязательный. Несколько букв для поиска объекта.
            'offset' => 0, //Смещение при выборке (сколько пропустить).
            'limit' => 10, //Кол-во возвращаемых записей (предел).
            'transliteration' => true, //Разрешить транслитерацию запроса при поиске.
            'qwertySwitcher' => true, //Разрешить преобразование строки запроса в случае ошибочного набора с неверной раскладкой клавиатуры (qwerty). Например, «ghbdtn» - это «привет».
            'fields' => '*', /*Данным параметром можно указать перечень требуемых параметров, которые будут возвращаться в ответе. Разделяются запятой.
                Возможные значения:
                * (возвращает все поля)
                name
                old_name
                houses
                lat
                lng
                locale*/
        ]);

        return $response->body() ;
    }



    /**
     * Гео данные
     * Поиск ближайших гео-данных (улиц и объектов) по  географическим координатам (долгота-широта)
     * @return string
     */
    public function geodataSearchLatLng()
    {
        $username = '0936734455';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/geodata/search';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url, [
            'lat' => '46.4834363079238', //Обязательный. Широта
            'lng' => '30.6886028410144', //Обязательный. Долгота
            'r' => '100' //необязательный. Радиус поиска. Значение от 0 до 1000 м. Если не указано — 500м.
        ]);

        return $response->body() ;
    }

    /**
     * Гео данные
     * Поиск ближайшей геоточки (улицы или объекта) по  географическим координатам (долгота-широта).
     * @return string
     */
    public function geodataNearest()
    {
        $username = '0936734455';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/geodata/nearest';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url, [
            'lat' => '46.4834363079238', //Обязательный. Широта
            'lng' => '30.6886028410144', //Обязательный. Долгота
            'r' => '50' //необязательный. Радиус поиска. Значение от 0 до 1000 м. Если не указано — 500м.
        ]);

        return $response->body() ;
    }

    /**
     * Запрос настроек
     * @return string
     */
    public function settings()
    {
        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/settings';
        $response = Http::get($url);

        return $response->body() ;
    }

    /**
     * Запрос настроек шага добавочной стоимости
     * @return string
     */
    public function addCostIncrementValue()
    {
        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/settings/addCostIncrementValue';
        $response = Http::get($url);

        return $response->body() ;
    }

    /**
     * Запрос серверного времени
     * @return string
     */
    public function time()
    {
        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/time';
        $response = Http::get($url);

        return $response->body() ;
    }

    /**
     * Запрос версии TaxiNavigator
     * @return string
     */
    public function tnVersion($connectAPI)
    {
       /* $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }*/
        $url = $connectAPI . '/api/tnVersion';
        $response = Http::get($url);

        return $response->body() ;
    }


}

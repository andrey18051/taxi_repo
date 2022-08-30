<?php

namespace App\Http\Controllers;

use App\Mail\Feedback;
use App\Models\Order;
use App\Models\Orderweb;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class WebOrderController extends Controller
{
    /**
     * Авторизация пользователя
     * @return string
     */
    public function account($authorization)
    {
        $url = config('app.taxi2012Url') . '/api/clients/profile';
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
    public function profile(Request $req)
    {
        $username = $req->username;
        $password = hash('SHA512', $req->password);
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $url = config('app.taxi2012Url') . '/api/clients/profile';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url);

        if ($response->status() == "200") {
            return redirect()->route('profile-view', ['authorization' => $authorization])
                ->with('success', 'Ласкаво просимо');
        } else {
            return redirect()->route('taxi-login')
                ->with('error', 'Перевірте дані та спробуйте ще раз або пройдіть реєстрацію');
        }
    }
    /**
     * Обновление профиля клиента
     * @return int
     */
    public function profileput(Request $req)
    {
        $authorization = $req->authorization;
        $url = config('app.taxi2012Url') . '/api/clients/profile';
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
                $url = config('app.taxi2012Url') . '/api/account/register/sendConfirmCode';
                $response = Http::post($url, [
                'phone' => $req->username, //Обязательный. Номер мобильного телефона, на который будет отправлен код подтверждения.
                'taxiColumnId' => '0', //Номер колоны, из которой отправляется SMS (0, 1 или 2, по умолчанию 0).
                'appHash' => '' //Хэш Android приложения для автоматической подстановки смс кода. 11 символов.
                ]);

                if ($response->status() == "200") {
                    return redirect()->route('registration-form')
                    ->with('success', 'Код підтвердження успішно надіслано на вказаний телефон');
                } else {
                    return redirect()->route('registration-sms')
                    ->with('error', 'Пользователь с таким номером телефона уже зарегистрирован');
                }
            }
        }
        if ($error) {
            return redirect()->route('registration-sms')->with('error', "Не пройдено перевірку 'Я не робот'");

        }
    }

    /**
     * Регистрация пользователя
     * Регистрация с кодом подтверждения
     * @return string
     */
    public function register(Request $req)
    {
        $url = config('app.taxi2012Url') . '/api/account/register';
        $response = Http::post($url, [
            //Все параметры обязательные
            'phone' => $req->phone, //Номер мобильного телефона, на который будет отправлен код подтверждения
            'confirm_code' => $req->confirm_code, //Код подтверждения, полученный в SMS.
            'password' =>  $req->password, //Пароль.
            'confirm_password' => $req-> confirm_password, //Пароль (повтор).
            'user_first_name' => 'Новий користувач', // Необязательный. Имя клиента
        ]);
        if ($response->status() == "200") {
            $username = $req->phone;
            $password = hash('SHA512', $req->password);
            $authorization = 'Basic ' . base64_encode($username . ':' . $password);
            return redirect()->route('profile-view', ['authorization' => $authorization])
                ->with('success', 'Реєстрація нового користувача успішна');
        } else {
            return redirect()->route('registration-form')->with('error', $response->body());
        }
    }

    /**
     * Работа с заказами
     * Расчет стоимости заказа
     * @return string
     */
    public function cost(Request $req)
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
                $error = false;
                $username = config('app.username');
                $password = hash('SHA512', config('app.password'));
                $authorization = 'Basic ' . base64_encode($username . ':' . $password);

                $url = config('app.taxi2012Url') . '/api/weborders/cost';
                $user_full_name = $req->user_full_name;
                $user_phone = $req->user_phone;
                $from = $req->search;
                $from_number = $req->from_number;
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
                    $premium_type = " Машина преміум-класса";
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

                if ($req->payment_type == 'готівка') {
                    $payment_type = '0';
                } else {
                    $payment_type = '1';
                };

                $route_undefined = false;
                $to = $req->search1;
                $to_number = $req->to_number;
              //  return $req->route_undefined;
                if ($req->route_undefined == 1) {
                    $route_undefined = true;
                    $to = $from;
                    $to_number = $from_number;
                };

                $response = Http::withHeaders([
                    'Authorization' => $authorization,
                ])->post($url, [
                    'user_full_name' => $user_full_name, //Полное имя пользователя
                    'user_phone' => $user_phone, //Телефон пользователя
                    'client_sub_card' => null,
                    'required_time' => null, //Время подачи предварительного заказа
                    'reservation' => false, //Обязательный. Признак предварительного заказа: True, False
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
                    'payment_type' => $payment_type, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
                    /*  'extra_charge_codes' => 'ENGLISH', //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
                        'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
                ]);

                if ($response->status() == "200") {
                    /**
                     * Сохранние расчетов в базе
                     */
                    $order = new Order();

                    $order->user_full_name = $user_full_name;//Полное имя пользователя
                    $order->user_phone = $user_phone;//Телефон пользователя
                    $order->client_sub_card = null;
                    $order->required_time = null; //Время подачи предварительного заказа
                    $order->reservation = false; //Обязательный. Признак предварительного заказа: True, False
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
                    $order->payment_type = $payment_type; //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
                    $order->save();
                    $id = $order;
                    $json_arr = json_decode($response, true);
                    $order_cost  = $json_arr['order_cost'];
                    if ($route_undefined === true) {
                        $order = "Вітаємо $user_full_name на нашому сайті. Ви зробили розрахунок за маршрутом від $from (будинок $from_number) по місту. Оплата $req->payment_type. $auto_type Вартість поїздки становитиме: $order_cost грн.";
                    } else {
                        $order = "Вітаємо $user_full_name на нашому сайті. Ви зробили розрахунок за маршрутом від $from (будинок $from_number) до $to (будинок $to_number). Оплата $req->payment_type. $auto_type Вартість поїздки становитиме: $order_cost грн.";
                    };


                    return redirect()->route('home-id', ['id' => $id])->with('success', $order);

                } else {
                    return redirect()->route('home')->with('error', "Помилка створення маршруту: Не вірна адреса призначення або не вибрана опція поїздки по місту");
                }
            }
        }
        if ($error) {
            $params['user_full_name'] = $req->user_full_name;
            $params['user_phone'] = $req->user_phone;
            $params['routefrom'] = $req->search; //Обязательный. Улица откуда.
            $params['routefromnumber'] = $req->from_number; //Обязательный. Дом откуда.
            $params['client_sub_card'] = null;
            $params['required_time'] = null; //Время подачи предварительного заказа
            $params['reservation'] = false; //Обязательный. Признак предварительного заказа: True, False
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

            if ($req->payment_type == 'готівка') {
                $params['payment_type'] = '0'; //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
            } else {
                $params['payment_type'] = '1';
            };

            $params['routeto'] = $req->search1; //Обязательный. Улица куда.
            $params['routetonumber'] = $req->to_number; //Обязательный. Дом куда.
            $params['route_undefined'] = false; //По городу: True, False
            if ($req->route_undefined === '1') {
                $params['routeto'] = $req->search; //Обязательный. Улица куда.
                $params['routetonumber'] =  $req->from_number; //Обязательный. Дом куда.
                $params['route_undefined'] = true; //По городу: True, False
            };
            $params['custom_extra_charges'] = '20'; //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/

            ?>
            <script type="text/javascript">
                alert("Не пройдено перевірку на робота");
            </script>
            <?php
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
                    case 'Манго':
                    case 'Онлайн платный':
                        break;
                    case 'Базовый':
                    case 'Бизнес-класс':
                    case 'Эконом-класс':

                        $json_arr[$ii]['name'] = $response_arr[$i]['name'];
                        $ii++;
                }
            }
            return view('taxi.homeReq', ['json_arr' => $json_arr, 'params' => $params]);
          //  return redirect()->route('home')->with('error', "Не пройдено перевірку 'Я не робот'");

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

        $url = config('app.taxi2012Url') . '/api/weborders/cost';
        $user_full_name = $req->user_full_name;
        $user_phone = $req->user_phone;
        $from = $req->search;
        $from_number = $req->from_number;

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
            $premium_type = " Машина преміум-класса";
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
        if ($req->payment_type == 'готівка') {
            $payment_type = '0';
        } else {
            $payment_type = '1';
        };
        $route_undefined = false;
        $to = $req->search1;
        $to_number = $req->to_number;

        if ($req->route_undefined == 1) {
            $route_undefined = true;
            $to = $from;
            $to_number = $from_number;
        };

        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->post($url, [
            'user_full_name' => $user_full_name, //Полное имя пользователя
            'user_phone' => $user_phone, //Телефон пользователя
            'client_sub_card' => null,
            'required_time' => null, //Время подачи предварительного заказа
            'reservation' => false, //Обязательный. Признак предварительного заказа: True, False
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
            'payment_type' => $payment_type, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
        ]);

        if ($response->status() == "200") {

            $order = Order::where ('id', $id)->first();
            $order->user_full_name = $user_full_name;//Полное имя пользователя
            $order->user_phone = $user_phone;//Телефон пользователя
            $order->client_sub_card = null;
            $order->required_time = null; //Время подачи предварительного заказа
            $order->reservation = false; //Обязательный. Признак предварительного заказа: True, False
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
            $order->payment_type = $payment_type; //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
            $order->save();

            $json_arr = json_decode($response, true);
            if ($route_undefined === true) {
                $order = "Вітаємо $user_full_name. Ви зробили розрахунок за маршрутом від $from (будинок $from_number) по місту. Оплата $req->payment_type. $auto_type";
            } else {
                $order = "Вітаємо $user_full_name. Ви зробили розрахунок за маршрутом від $from (будинок $from_number) до $to (будинок $to_number). Оплата $req->payment_type. $auto_type";
            };
            $cost = "Вартість поїздки становитиме: " . $json_arr['order_cost'] . 'грн. Для замовлення натисніть тут.';
            return redirect()->route('home-id-afterorder', ['id' => $id])->with('success', $order)->with('cost', $cost);

        } else {
            return redirect()->route('home-id', ['id' => $id])->with('error', "Помилка створення маршруту.");
        }

    }
    /**
     * Работа с заказами
     * Создание заказа
     * @return string
     */
    public function costWebOrder($id)
    {
        $username = config('app.username');
        $password = hash('SHA512', config('app.password'));
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $req = Order::where('id', $id)->first();
        $user_full_name = $req->user_full_name;
        $user_phone = $req->user_phone;
        $from = $req->routefrom;
        $from_number = $req->routefromnumber;

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
        $comment = $req->comment;
        $add_cost = $req->add_cost;
        $taxiColumnId = config('app.taxiColumnId');
        $payment_type = $req->payment_type;

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

        $url = config('app.taxi2012Url') . '/api/weborders/cost';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->post($url, [
            'user_full_name' => $user_full_name, //Полное имя пользователя
            'user_phone' => $user_phone, //Телефон пользователя
            'client_sub_card' => null,
            'required_time' => null, //Время подачи предварительного заказа
            'reservation' => false, //Обязательный. Признак предварительного заказа: True, False
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
            'payment_type' => $payment_type, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
            /*  'extra_charge_codes' => 'ENGLISH', //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
                'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
        ]);

        /**
         * Заказ поездки
        */

        $url = config('app.taxi2012Url') . '/api/weborders';
        $responseWeb = Http::withHeaders([
            'Authorization' => $authorization,
        ])->post($url, [
            'user_full_name' => $user_full_name, //Полное имя пользователя
            'user_phone' => $user_phone, //Телефон пользователя
            'client_sub_card' => null,
            'required_time' => null, //Время подачи предварительного заказа
            'reservation' => false, //Обязательный. Признак предварительного заказа: True, False
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
            'payment_type' => $payment_type, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
            /*  'extra_charge_codes' => 'ENGLISH', //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
                'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
        ]);

        if ($responseWeb->status() == "200") {
            /**
             * Сохранние расчетов в базе
             */
            $orderweb = new Orderweb();
            $orderweb->user_full_name = $user_full_name;//Полное имя пользователя
            $orderweb->user_phone = $user_phone;//Телефон пользователя
            $orderweb->client_sub_card = null;
            $orderweb->required_time = null; //Время подачи предварительного заказа
            $orderweb->reservation = false; //Обязательный. Признак предварительного заказа: True, False
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
            $orderweb->payment_type = $payment_type; //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
            $json_arr = json_decode($response, true);

            $orderweb->web_cost = $json_arr['order_cost'];// Стоимость поездки
            $json_arrWeb = json_decode($responseWeb, true);
            $orderweb->dispatching_order_uid = $json_arrWeb['dispatching_order_uid']; //Идентификатор заказа, присвоенный в БД ТН
            $orderweb->save();
            /**
             *
             */
            if ($req->payment_type == '0') {
                $payment_type = 'готівка';
            } else {
                $payment_type = 'безготівка';
            };

            if ($route_undefined !== false) {
                $order = "Вітаємо $user_full_name
                . Ви успішно зробили замовлення за маршрутом від $from (будинок $from_number) по місту. Оплата $payment_type. $auto_type. Вартість поїздки становитиме: " . $json_arr['order_cost'] . "грн. Номер: " .  $json_arrWeb['dispatching_order_uid'];
            } else {
                $order = "Вітаємо $user_full_name
                . Ви успішно зробили замовлення за маршрутом від $from (будинок $from_number) до $to (будинок $to_number). Оплата $payment_type. $auto_type. Вартість поїздки становитиме: " . $json_arr['order_cost'] . "грн. Номер: " .  $json_arrWeb['dispatching_order_uid'];
            };
            return redirect()->route('homeblank')->with('success', $order)
                ->with('tel', "Очікуйте на інформацію від оператора з обробки замовлення. Скасувати або внести зміни можна за номером оператора:")
                ->with('back', 'Зробити нове замовлення.');

        } else {
            return redirect()->route('home')->with('error', "Помілка створення заказу")
                ->with('back', 'Зробити нове замовлення.');
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

                Mail::to('andrey18051@gmail.com')->send(new Feedback($params));
                return redirect()->route('home')
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
            ?>
            <script type="text/javascript">
            alert("Не пройдено перевірку на робота");
            </script>
            <?php
            return view('taxi.feedbackReq', ['params' => $params]);
        }

}

    /**
     * Получение списка тарифов
     * @return string
     */
    public function tariffs()
    {
        $username = config('app.username');
        $password = hash('SHA512', config('app.password'));
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $url = config('app.taxi2012Url') . '/api/tariffs';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url);

        return $response->body();
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

        $url = config('app.taxi2012Url') . '/api/account/changepassword';
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
     * Восстановление пароля
     * Получение кода подтверждения
     * @return string
     */
    public function restoreSendConfirmCode()
    {
        $url = config('app.taxi2012Url') . '/api/account/restore/sendConfirmCode';
        $response = Http::post($url, [
            'phone' => '0936734488', //Обязательный. Номер мобильного телефона, на который будет отправлен код подтверждения.
            'taxiColumnId' => 0 //Номер колоны, из которой отправляется SMS (0, 1 или 2, по умолчанию 0).
        ]);
        return $response->status();
    }

    /**
     * Восстановление пароля
     * Получение кода подтверждения
     * @return string
     */
    public function restoreСheckConfirmCode()
    {
        $url = config('app.taxi2012Url') . '/api/account/restore/checkConfirmCode';
        $response = Http::post($url, [
            'phone' => '0936734488', //Обязательный. Номер мобильного телефона
            'confirm_code' => '6024' //Обязательный. Код подтверждения.
        ]);
        return $response->status();
    }

    /**
     * Восстановление пароля
     * @return string
     */
    public function restorePassword()
    {
        $url = config('app.taxi2012Url') . '/api/account/restore';
        $response = Http::post($url, [
            'phone' => '0936734488', //Обязательный. Номер мобильного телефона
            'confirm_code' => '6024', //Обязательный. Код подтверждения.
            'password' => '11223344', //Новый пароль
            'confirm_password' => '11223344' //Repeat Новый пароль
        ]);
        return $response->status();
    }


    /**
     * Верификация телефона
     * Получение кода подтверждения
     * @return string
     */
    public function approvedPhonesSendConfirmCode()
    {
        $url = config('app.taxi2012Url') . '/api/approvedPhones/sendConfirmCode';
        $response = Http::post($url, [
            'phone' => '0936734488', //Обязательный. Номер мобильного телефона, на который будет отправлен код подтверждения.
            'taxiColumnId' => 0 //Номер колоны, из которой отправляется SMS (0, 1 или 2, по умолчанию 0).
        ]);
        return $response->status();
    }

    /**
     * Верификация телефона
     * Получение кода подтверждения
     * @return string
     */
    public function approvedPhones()
    {
        $url = config('app.taxi2012Url') . '/api/approvedPhones/';
        $response = Http::post($url, [
            'phone' => '0936734488', //Обязательный. Номер мобильного телефона
            'confirm_code' => '5945' //Обязательный. Код подтверждения.
        ]);
        return $response->status();
    }

    /**
     * Запрос версии
     * @return string
     */
    public function version()
    {
        $url = config('app.taxi2012Url') . '/api/version';
        $response = Http::get($url);
        return $response->body();
    }








    /**
     * Работа с заказами
     * Создание заказа
     * @return string
     */
    public function webordersUid()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);
        $uid = '9a1051aaf1654cd28d97a87c7ff8398a'; //идентификатор заказа

        $url = config('app.taxi2012Url') . '/api/weborders/' . $uid;
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url);

        return $response->body() ;
    }

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

        $url = config('app.taxi2012Url') . '/api/weborders/' . $uid . '/driver';
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

        $url = config('app.taxi2012Url') . '/api/weborders/' . $uid . '/cost/additional';
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

        $url = config('app.taxi2012Url') . '/api/weborders/' . $uid . '/cost/additional';
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

        $url = config('app.taxi2012Url') . '/api/weborders/' . $uid . '/cost/additional';
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

        $url = config('app.taxi2012Url') . '/api/weborders/' . $uid . '/cost/additional';
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

        $url = config('app.taxi2012Url') . '/api/weborders/drivercarposition/' . $uid;
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url);

        return $response->body() ;
    }

    /**
     * Запрос отмены заказа клиентом
     * @return string
     */
    public function webordersCancel()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);
        $uid = '5b1e13c458514781881da701583c8ccd'; //идентификатор заказа

        $url = config('app.taxi2012Url') . '/api/weborders/cancel/' . $uid;
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->put($url);

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

        $url = config('app.taxi2012Url') . '/api/weborders/rate/' . $uid;
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

        $url = config('app.taxi2012Url') . '/api/weborders/hide/' . $uid;
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

        $url = config('app.taxi2012Url') . '/api/clients/ordersreport';
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

        $url = config('app.taxi2012Url') . '/api/clients/ordershistory';
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

        $url = config('app.taxi2012Url') . '/api/clients/bonusreport';
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

        $url = config('app.taxi2012Url') . '/api/clients/lastaddresses';
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

        $url = config('app.taxi2012Url') . '/api/clients/credential';
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

        $url = config('app.taxi2012Url') . '/api/clients/changePhone/sendConfirmCode';
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

        $url = config('app.taxi2012Url') . '/api/clients/changePhone/';
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

        $url = config('app.taxi2012Url') . '/api/clients/balance/transactions/';
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

        $url = config('app.taxi2012Url') . '/api/clients/balance/transaction/' . $id;
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

        $url = config('app.taxi2012Url') . '/api/clients/balance/transactions/';
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

        $url = config('app.taxi2012Url') . '/api/client/addresses';
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

        $url = config('app.taxi2012Url') . '/api/client/addresses';
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

        $url = config('app.taxi2012Url') . '/api/client/addresses';
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

        $url = config('app.taxi2012Url') . '/api/client/addresses/' . $favorite_address_uid;
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->delete($url);
        return $response->status();
    }
    /**
     * Гео данные
     * Запрос гео-данных (всех объектов)
     * @return string
     */
    public function objects()
    {
        $username = '0936734455';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $url = config('app.taxi2012Url') . '/api/geodata/objects';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url, [
            'versionDateGratherThan' => '', //Дата версии гео-данных полученных ранее. Если параметр пропущен — возвращает  последние гео-данные.
        ]);

        return $response->body() ;
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

        $url = config('app.taxi2012Url') . '/api/geodata/objects/search';
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

        $url = config('app.taxi2012Url') . '/api/geodata/streets';
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

        $url = config('app.taxi2012Url') . '/api/geodata/streets/search';
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
     * Поиск гео-данных (улиц и объектов) по нескольким буквам
     * @return string
     */
    public function geodataSearch()
    {
        $username = '0936734455';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $url = config('app.taxi2012Url') . '/api/geodata/search';
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

        $url = config('app.taxi2012Url') . '/api/geodata/search';
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

        $url = config('app.taxi2012Url') . '/api/geodata/nearest';
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
        $url = config('app.taxi2012Url') . '/api/settings';
        $response = Http::get($url);

        return $response->body() ;
    }

    /**
     * Запрос настроек шага добавочной стоимости
     * @return string
     */
    public function addCostIncrementValue()
    {
        $url = config('app.taxi2012Url') . '/api/settings/addCostIncrementValue';
        $response = Http::get($url);

        return $response->body() ;
    }

    /**
     * Запрос серверного времени
     * @return string
     */
    public function time()
    {
        $url = config('app.taxi2012Url') . '/api/time';
        $response = Http::get($url);

        return $response->body() ;
    }

    /**
     * Запрос версии TaxiNavigator
     * @return string
     */
    public function tnVersion()
    {
        $url = config('app.taxi2012Url') . '/api/tnVersion';
        $response = Http::get($url);

        return $response->body() ;
    }

    /**
     * Получение координат автомобилей в радиусе
     * @return string
     */
    public function driversPosition()
    {

        $url = config('app.taxi2012Url') . '/api/drivers/position';
        $username = '0936734455';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url, [
            'lat' => '46.4834363079238', //Обязательный. Широта
            'lng' => '30.6886028410144', //Обязательный. Долгота
            'radius' => '100' //Обязательный. Радиус поиска автомобилей (в км.)
        ]);

        return $response->body() ;
    }
}

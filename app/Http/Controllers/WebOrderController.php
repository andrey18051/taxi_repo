<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WebOrderController extends Controller
{
    /**
     * Авторизация пользователя
     * @return string
     */
    public function account()
    {
        $url = config('app.taxi2012Url') . '/api/account';
        $response = Http::post($url, [
            //Все поля обязательные
            'login' => '0936734488', //Логин (или телефонный номер) для авторизации пользователя
            'password' => hash('SHA512', '22223344') //SHA512 Hash пароля пользователя.*
            // WebOrdersApiClientAppToken	Да	Токен для отправки пушей.
        ]);
        return $response;
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
     * Регистрация пользователя
     * Получение кода подтверждения
     * @return int
     */
    public function sendConfirmCode()
    {
        $url = config('app.taxi2012Url') . '/api/account/register/sendConfirmCode';
        $response = Http::post($url, [
            'phone' => '0936734455', //Обязательный. Номер мобильного телефона, на который будет отправлен код подтверждения.
            'taxiColumnId' => '0', //Номер колоны, из которой отправляется SMS (0, 1 или 2, по умолчанию 0).
            'appHash' => '' //Хэш Android приложения для автоматической подстановки смс кода. 11 символов.
        ]);
        return $response->body();
    }

    /**
     * Регистрация пользователя
     * Регистрация с кодом подтверждения
     * @return string
     */
    public function register()
    {
        $url = config('app.taxi2012Url') . '/api/account/register';
        $response = Http::post($url, [
            //Все параметры обязательные
            'phone' => '0936734455', //Номер мобильного телефона, на который будет отправлен код подтверждения
            'confirm_code' => '9183', //Код подтверждения, полученный в SMS.
            'password' => '11223344', //Пароль.
            'confirm_password' => '11223344', //Пароль (повтор).
            'user_first_name' => 'Sergii', // Необязательный. Имя клиента
        ]);
        return $response->body();
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
     * Рассчет стоимости заказа
     * @return string
     */
    public function cost()
    {
        $username = '0936734455';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $url = config('app.taxi2012Url') . '/api/weborders/cost';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->post($url, [
            'user_full_name' => 'Иванов Александр', //Полное имя пользователя
            'user_phone' => '', //Телефон пользователя
            'client_sub_card' => null,
            'required_time' => null, //Время подачи предварительного заказа
            'reservation' => false, //Обязательный. Признак предварительного заказа: True, False
            'route_address_entrance_from' => null,
            'comment' => '', //Комментарий к заказу
            'add_cost' => 0,
            'wagon' => false, //Универсал: True, False
            'minibus' => false, //Микроавтобус: True, False
            'premium' => false, //Машина премиум-класса: True, False
            'flexible_tariff_name' => 'Базовый', //Гибкий тариф
            'baggage' => false, //Загрузка салона. Параметр доступен при X-API-VERSION < 1.41.0: True, False
            'animal' => false, //Перевозка животного. Параметр доступен при X-API-VERSION < 1.41.0: True, False
            'conditioner' => true, //Кондиционер. Параметр доступен при X-API-VERSION < 1.41.0: True, False
            'courier_delivery' => false, //Курьер. Параметр доступен при X-API-VERSION < 1.41.0: True, False
            'route_undefined' => false, //По городу: True, False
            'terminal' => false, //Терминал. Параметр доступен при X-API-VERSION < 1.41.0: True, False
            'receipt' => false, //Требование чека за поездку. Параметр доступен при X-API-VERSION < 1.41.0: True, False
            'route' => [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                ['name' => 'Казино Афина Плаза (Греческая пл. 3/4)'/*, 'number' => 1*/],
                ['name' => 'Казино Кристал (ДЕВОЛАНОВСКИЙ СПУСК 11)'],
            ],
            'taxiColumnId' => 0, //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
            'payment_type' => 0, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
            /*  'extra_charge_codes' => 'ENGLISH', //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
                'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
        ]);

        return $response->body() ;
    }

    /**
     * Работа с заказами
     * Создание заказа
     * @return string
     */
    public function weborders()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $url = config('app.taxi2012Url') . '/api/weborders';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->post($url, [
            'user_full_name' => 'Иванов Александр', //Полное имя пользователя
            'user_phone' => '0936734455', //Телефон пользователя
            'client_sub_card' => null,
            'required_time' => null, //Время подачи предварительного заказа
            'reservation' => false, //Обязательный. Признак предварительного заказа: True, False
            'route_address_entrance_from' => null,
            'comment' => '', //Комментарий к заказу
            'add_cost' => 0,
            'wagon' => false, //Универсал: True, False
            'minibus' => false, //Микроавтобус: True, False
            'premium' => false, //Машина премиум-класса: True, False
            'flexible_tariff_name' => 'Базовый', //Гибкий тариф
            'baggage' => false, //Загрузка салона. Параметр доступен при X-API-VERSION < 1.41.0: True, False
            'animal' => false, //Перевозка животного. Параметр доступен при X-API-VERSION < 1.41.0: True, False
            'conditioner' => true, //Кондиционер. Параметр доступен при X-API-VERSION < 1.41.0: True, False
            'courier_delivery' => false, //Курьер. Параметр доступен при X-API-VERSION < 1.41.0: True, False
            'route_undefined' => false, //По городу: True, False
            'terminal' => false, //Терминал. Параметр доступен при X-API-VERSION < 1.41.0: True, False
            'receipt' => false, //Требование чека за поездку. Параметр доступен при X-API-VERSION < 1.41.0: True, False
            'route' => [ //Обязательный. Маршрут заказа. (См. Таблицу описания маршрута)
                ['name' => 'КАВКАЗСКАЯ УЛ.', 'number' => '2'],
                ['name' => 'КРАЙНЯЯ УЛ.', 'number' => '2'],
            ],
            'taxiColumnId' => 0, //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
            'payment_type' => 0, //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
            /*  'extra_charge_codes' => 'ENGLISH', //Список кодов доп. услуг (api/settings). Параметр доступен при X-API-VERSION >= 1.41.0. ["ENGLISH", "ANIMAL"]
                'custom_extra_charges' => '20' //Список идентификаторов пользовательских доп. услуг (api/settings). Параметр добавлен в версии 1.46.0. 	[20, 12, 13]*/
        /*{"dispatching_order_uid":"af5857857f9c420f84773cda79698304","discount_trip":false,"find_car_timeout":3600,"find_car_delay":0,"order_cost":"55","currency":" грн.","route_address_from":{"name":"Казино Афина Плаза (Греческая пл. 3/4)","number":null,"lat":46.483063297443,"lng":30.7356095407788},"route_address_to":{"name":"Казино Кристал (ДЕВОЛАНОВСКИЙ СПУСК 11)","number":null,"lat":46.4815271604416,"lng":30.7462156083731}}
        */]);

        return $response->body() ;
    }


    /**
     * Получение списка тарифов
     * @return string
     */
    public function tariffs()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $url = config('app.taxi2012Url') . '/api/tariffs';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url);
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
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
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
     * Запрос профиля клиента
     * @return string
     */
    public function profile()
    {
        $username = '0936734455';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $url = config('app.taxi2012Url') . '/api/clients/profile';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
            ])->get($url);
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
     * Обновление профиля клиента
     * @return int
     */
    public function profileput()
    {
        $username = '0936734455';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $url = config('app.taxi2012Url') . '/api/clients/profile';
        $response = Http::withHeaders([
            'Authorization' => $authorization,])->put($url, [
            'patch' => 'name, address', /*Обновление патчем.- является необязательным параметром и позволяет выполнить частичное обновление (обновить только имя клиента, только адрес клиента, или и то и другое).
                Возможный значения «patch»:
                «name» - будет обновлена только группа полей: user_first_name, user_middle_name и user_last_name;
                «address» - будет обновлена только группа полей: route_address_from, route_address_number_from, route_address_entrance_from и route_address_apartment_from;
                Значения параметра «patch» можно объединять разделителем «,» (запятая);
                Если «patch» не содержит значения — будут обновлены все поля.*/
            'user_first_name' => 'Hanna', //Имя
            'user_middle_name' => 'Anatoliyvna', //Отчество
            'user_last_name' => 'Korzhova', //Фамилия
            'route_address_from' => 'Scince avenu', //Адрес
            'route_address_number_from' => '4B', //Номер дома
            'route_address_entrance_from' => '12', //Подъезд
            'route_address_apartment_from' => '1', //Квартира
        ]);
        return $response->status();
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

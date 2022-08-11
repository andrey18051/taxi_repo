<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TaxiController extends Controller
{
    /**
     * Запрос версии
     * @return string
     */
    public function version()
    {
        $response = Http::get('http://31.43.107.151:7303/api/version');
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

        $response = Http::withHeaders([
            'Authorization' => $authorization,
            ])->get('http://31.43.107.151:7303/api/clients/profile');
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

        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get('http://31.43.107.151:7303/api/client/addresses');
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

        $response = Http::withHeaders([
            'Authorization' => $authorization, ])->get('http://31.43.107.151:7303/api/clients/lastaddresses');
        return $response->body();
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

        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get('http://31.43.107.151:7303/api/tariffs');
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

        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get('http://31.43.107.151:7303/api/clients/ordershistory', [
            'limit' => '10', //Необязательный. Вернуть количество записей
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
     * Запрос отчета по заказам клиентом
     * @return string
     */
    public function ordersReport()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $response = Http::withHeaders([
            'Authorization' => $authorization,
            ])-> accept('application/json')->asForm()->get('http://31.43.107.151:7303/api/clients/ordersreport', [
             'dateFrom' => '2013.08.13', //Обязательный. Начальный интервал для отчета
             'dateTo' => '2013.08.13', //Обязательный. Конечный интервал для отчета
        ]);
        return $response->body();
    }

    /**
     * Запрос истории по изменениям бонусов клиента
     * @return string
     */
    public function bonusReport()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $response = Http::withHeaders([
            'Authorization' => $authorization,
            ])->get('http://31.43.107.151:7303/api/clients/bonusreport', [
            'limit' => '10', //Необязательный. Вернуть количество записей
            'offset'=> '0', //Необязательный. Пропустить количество записей
        ]);
        return $response->body();
    }

    /**
     * Обновление профиля клиента
     * @return int
     */
    public function profileput()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $response = Http::withHeaders([
            'Authorization' => $authorization,])->put('http://31.43.107.151:7303/api/clients/profile', [
            'patch' => 'name, address', /*Обновление патчем.- является необязательным параметром и позволяет выполнить частичное обновление (обновить только имя клиента, только адрес клиента, или и то и другое).
                Возможный значения «patch»:
                «name» - будет обновлена только группа полей: user_first_name, user_middle_name и user_last_name;
                «address» - будет обновлена только группа полей: route_address_from, route_address_number_from, route_address_entrance_from и route_address_apartment_from;
                Значения параметра «patch» можно объединять разделителем «,» (запятая);
                Если «patch» не содержит значения — будут обновлены все поля.*/
            'user_first_name' => 'Mykyta', //Имя
            'user_middle_name' => 'Andriyovich', //Отчество
            'user_last_name' => 'Korzhov', //Фамилия
            'route_address_from' => 'Scince avenu', //Адрес
            'route_address_number_from' => '4B', //Номер дома
            'route_address_entrance_from' => '12', //Подъезд
            'route_address_apartment_from' => '1', //Квартира
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

        $response = Http::post('http://31.43.107.151:7303/api/account/register/sendConfirmCode', [
                'phone' => '0936734455', //Обязательный. Номер мобильного телефона, на который будет отправлен код подтверждения.
                'taxiColumnId' => '0', //Необязательный. Номер колоны, из которой отправляется SMS (0, 1 или 2, по умолчанию 0).
                 'appHash' => '' //Необязательный. Хэш Android приложения для автоматической подстановки смс кода. 11 символов.
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
             $response = Http::post('http://31.43.107.151:7303/api/account/register', [
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
     * Работа с заказами
     * Рассчет стоимости заказа
     * @return string
     */
    public function cost()
    {
        $username = '0936734455';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $response = Http::withHeaders([
            'Authorization' => $authorization,

            ])->post('http://31.43.107.151:7303/api/weborders/cost', [
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
     * Гео данные
     * Запрос гео-данных (всех объектов)
     * @return string
     */
    public function objects()
    {
        $username = '0936734455';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get('http://31.43.107.151:7303/api/geodata/objects', [
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

        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get('http://31.43.107.151:7303/api/geodata/objects/search', [
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

        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get('http://31.43.107.151:7303/api/geodata/streets', [
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

        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get('http://31.43.107.151:7303/api/geodata/streets/search', [
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

        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get('http://31.43.107.151:7303/api/geodata/search', [
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

        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get('http://31.43.107.151:7303/api/geodata/search', [
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

        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get('http://31.43.107.151:7303/api/geodata/nearest', [
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
        $response = Http::get('http://31.43.107.151:7303/api/settings');

        return $response->body() ;
    }

    /**
     * Запрос настроек шага добавочной стоимости
     * @return string
     */
    public function addCostIncrementValue()
    {
        $response = Http::get('http://31.43.107.151:7303/api/settings/addCostIncrementValue');

        return $response->body() ;
    }

    /**
     * Запрос серверного времени
     * @return string
     */
    public function time()
    {
        $response = Http::get('http://31.43.107.151:7303/api/time');

        return $response->body() ;
    }

    /**
     * Запрос версии TaxiNavigator
     * @return string
     */
    public function tnVersion()
    {
        $response = Http::get('http://31.43.107.151:7303/api/tnVersion');

        return $response->body() ;
    }

    /**
     * Получение координат автомобилей в радиусе
     * @return string
     */
    public function driversPosition()
    {
        $username = '0936734455';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get('http://31.43.107.151:7303/api/drivers/position', [
            'lat' => '46.4834363079238', //Обязательный. Широта
            'lng' => '30.6886028410144', //Обязательный. Долгота
            'radius' => '100' //Обязательный. Радиус поиска автомобилей (в км.)
        ]);

        return $response->body() ;
    }
}

<?php

namespace App\Http\Controllers;

use App\Mail\Check;
use App\Models\BlackList;
use App\Models\City;
use App\Models\Order;
use App\Models\Orderweb;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use SebastianBergmann\Diff\Exception;

class UniversalAndroidFunctionController extends Controller
{
    public function postRequestHTTP(
        $url,
        $parameter,
        $authorization,
        $identificationId,
        $apiVersion
    ) {
        return Http::withHeaders([
//            $response = Http::dd()->withHeaders([
            "Authorization" => $authorization,
            "X-WO-API-APP-ID" => $identificationId,
            "X-API-VERSION" => $apiVersion
        ])->post($url, $parameter);
    }

    public function postRequestHTTPDouble(
        $url,
        $parameter,
        $authorizationBonus,
        $authorizationDouble,
        $identificationId,
        $apiVersion,
        $connectAPI
    ) {
        $responseBonus = Http::withHeaders([
            "Authorization" => $authorizationBonus,
            "X-WO-API-APP-ID" => $identificationId,
            "X-API-VERSION" => $apiVersion
        ])->post($url, $parameter);
        $responseBonus["url"] = $url;
        $responseBonus["parameter"] = $parameter;


        $originalString = $parameter['phone'];
        $parameter['phone'] = substr($originalString, 0, -1);
        $parameter['comment'] = $parameter['comment'] . "(тел." . substr($originalString, -1) . ')';


        $responseDouble = Http::withHeaders([
            "Authorization" => $authorizationDouble,
            "X-WO-API-APP-ID" => $identificationId,
            "X-API-VERSION" => $apiVersion
        ])->post($url, $parameter);
        $responseDouble["url"] = $url;
        $responseDouble["parameter"] = $parameter;


        $this->startNewProcessExecutionStatus(
            $responseBonus,
            $responseDouble,
            $authorizationBonus,
            $authorizationDouble,
            $connectAPI,
            $identificationId,
            $apiVersion
        );

        return $responseBonus;
    }
    public function startNewProcessExecutionStatus(
        $responseBonus,
        $responseDouble,
        $authorizationBonus,
        $authorizationDouble,
        $connectAPI,
        $identificationId,
        $apiVersion
    ) {
        $startTime = time();
        $upDateTimeBonus = $startTime;
        $upDateTimeDouble = $startTime;

        $maxExecutionTime = 4 * 60 * 60; // Максимальное время выполнения - 4 часа
        $cancelUID = null;

        $newStatusBonus['execution_status'] = $responseBonus['execution_status'];
        $newStatusDouble['execution_status'] = $responseDouble['execution_status'];

        $newStatusBonus['dispatching_order_uid'] = $responseBonus['dispatching_order_uid'];
        $newStatusDouble['dispatching_order_uid'] = $responseDouble['dispatching_order_uid'];


        while (time() - $startTime < $maxExecutionTime) {
            if ($newStatusBonus['execution_status'] == "CarFound" || $newStatusBonus['execution_status'] == "Running") {
                if ($newStatusDouble['execution_status'] == "CarFound" || $newStatusDouble['execution_status'] == "Running") {
                    break;
                }
            }

            switch ($newStatusBonus['execution_status']) {
                case "CarFound":
                case "Running":
                    $this->webordersCancel(
                        $newStatusDouble['dispatching_order_uid'],
                        $connectAPI,
                        $authorizationDouble,
                        $identificationId,
                        $apiVersion
                    );
                    $cancelUID = $responseDouble;
                    if ((time() - $upDateTimeBonus) >= 30) {
                        $newStatusBonus = $this->getExecutionStatus(
                            $authorizationBonus,
                            $identificationId,
                            $apiVersion,
                            $responseBonus["url"],
                            $responseBonus["parameter"]
                        );
                        $upDateTimeBonus = time();
                    }
                    break;
                case "WaitingCarSearch":
                case "SearchesForCar":
                    if ($cancelUID == $responseDouble) {
                        Http::withHeaders([
                            "Authorization" => $authorizationDouble,
                            "X-WO-API-APP-ID" => $identificationId,
                            "X-API-VERSION" => $apiVersion
                        ])->post($responseDouble['url'], $responseDouble['parameter']);
                    }
                    if ((time() - $upDateTimeBonus) >= 5) {
                        $newStatusBonus = $this->getExecutionStatus(
                            $authorizationBonus,
                            $identificationId,
                            $apiVersion,
                            $responseBonus["url"],
                            $responseBonus["parameter"]
                        );
                        $upDateTimeBonus = time();
                    }
                    break;
            }
            switch ($newStatusDouble['execution_status']) {
                case "CarFound":
                case "Running":
                    $this->webordersCancel(
                        $newStatusBonus['dispatching_order_uid'],
                        $connectAPI,
                        $authorizationBonus,
                        $identificationId,
                        $apiVersion
                    );
                    $cancelUID = $responseBonus;
                    if ((time() - $upDateTimeDouble) >= 30) {
                        $newStatusDouble = $this->getExecutionStatus(
                            $authorizationDouble,
                            $identificationId,
                            $apiVersion,
                            $responseDouble["url"],
                            $responseDouble["parameter"]
                        );
                        $upDateTimeDouble = time();
                    }
                    break;
                case "WaitingCarSearch":
                case "SearchesForCar":
                    if ($cancelUID == $responseBonus) {
                        Http::withHeaders([
                            "Authorization" => $authorizationBonus,
                            "X-WO-API-APP-ID" => $identificationId,
                            "X-API-VERSION" => $apiVersion
                        ])->post($responseBonus['url'], $responseBonus['parameter']);
                    }
                    if ((time() - $upDateTimeDouble) >= 5) {
                        $newStatusDouble = $this->getExecutionStatus(
                            $authorizationDouble,
                            $identificationId,
                            $apiVersion,
                            $responseDouble["url"],
                            $responseDouble["parameter"]
                        );
                        $upDateTimeDouble = time();
                    }
                    break;
            }
        }
    }

    public function getExecutionStatus(
        $authorization,
        $identificationId,
        $apiVersion,
        $url,
        $parameter
    ) {
        // Здесь реализуйте код для получения статуса execution_status по UID
        // Верните фактический статус для последующей проверки
        return  Http::withHeaders([
            "Authorization" => $authorization,
            "X-WO-API-APP-ID" => $identificationId,
            "X-API-VERSION" => $apiVersion
        ])->post($url, $parameter);
    }

    /**
     * Запрос отмены заказа клиентом
     * @return string
     */
    public function webordersCancel(
        $uid,
        $connectAPI,
        $authorization,
        $identificationId,
        $apiVersion
    ) {
        $url = $connectAPI . '/api/weborders/cancel/' . $uid;
        Http::withHeaders([
            "Authorization" => $authorization,
            "X-WO-API-APP-ID" => $identificationId,
            "X-API-VERSION" => $apiVersion
        ])->put($url);
    }

    public function saveCost($params)
    {
        /**
         * Сохранние расчетов в базе
         */

        $order = new Order();
        $order->IP_ADDR = getenv("REMOTE_ADDR") ;//IP пользователя
        $order->user_full_name = $params['user_full_name'];//Полное имя пользователя
        $order->user_phone = $params['user_phone'];//Телефон пользователя
        $order->client_sub_card = null;
        $order->required_time = $params['required_time']; //Время подачи предварительного заказа
        $order->reservation = $params['reservation']; //Обязательный. Признак предварительного заказа: True, False
        $order->route_address_entrance_from = null;
        $order->comment = $params['comment'];  //Комментарий к заказу
        $order->add_cost = $params['add_cost']; //Добавленная стоимость
        $order->wagon = $params['wagon']; //Универсал: True, False
        $order->minibus = $params['minibus']; //Микроавтобус: True, False
        $order->premium = $params['premium']; //Машина премиум-класса: True, False
        $order->flexible_tariff_name = $params['flexible_tariff_name']; //Гибкий тариф
        $order->route_undefined = $params['route_undefined']; //По городу: True, False
        $order->routefrom = $params['from']; //Обязательный. Улица откуда.
        $order->routefromnumber = $params['from_number']; //Обязательный. Дом откуда.
        $order->routeto = $params['to']; //Обязательный. Улица куда.
        $order->routetonumber = " "; //Обязательный. Дом куда.
        $order->taxiColumnId = $params['taxiColumnId']; //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
        $order->payment_type = "0"; //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
        $order->save();
    }

    public function saveOrder($params, $identificationId)
    {

        /**
         * Сохранние расчетов в базе
         */
        $order = new Orderweb();

        $order->user_full_name = $params["user_full_name"];//Полное имя пользователя
        $order->user_phone = $params["user_phone"];//Телефон пользователя
        $order->email = $params['email'];//Телефон пользователя
        $order->client_sub_card = null;
        $order->required_time = $params["required_time"]; //Время подачи предварительного заказа
        $order->reservation = $params["reservation"]; //Обязательный. Признак предварительного заказа: True, False
        $order->route_address_entrance_from = null;
        $order->comment = $identificationId;  //Комментарий к заказу
        $order->add_cost = $params["add_cost"]; //Добавленная стоимость
        $order->wagon = $params["wagon"]; //Универсал: True, False
        $order->minibus = $params["minibus"]; //Микроавтобус: True, False
        $order->premium = $params["premium"]; //Машина премиум-класса: True, False
        $order->flexible_tariff_name = $params["flexible_tariff_name"]; //Гибкий тариф
        $order->route_undefined = $params["route_undefined"]; //По городу: True, False
        $order->routefrom = $params["from"]; //Обязательный. Улица откуда.
        $order->routefromnumber = $params["from_number"]; //Обязательный. Дом откуда.
        $order->routeto = $params["to"]; //Обязательный. Улица куда.
        $order->routetonumber = $params["to_number"]; //Обязательный. Дом куда.
        $order->taxiColumnId = $params["taxiColumnId"]; //Обязательный. Номер колоны, в которую будут приходить заказы. 0, 1 или 2
        $order->payment_type = "0"; //Тип оплаты заказа (нал, безнал) (см. Приложение 4). Null, 0 или 1
        $order->web_cost = $params['order_cost'];
        $order->dispatching_order_uid = $params['dispatching_order_uid'];
        $order->closeReason = $params['closeReason'];
        $order->closeReasonI = 1;
        $order->server = $params['server'];

        $order->save();

        /**
         * Сообщение о заказе
         */
//        dd($params);

        if (!$params["route_undefined"]) {
            $order = "Нове замовлення від " . $params['user_full_name'] .
                " за маршрутом від " . $params['from'] . " " . $params['from_number'] .
                " до "  . $params['to'] . " " . $params['to_number'] .
                ". Вартість поїздки становитиме: " . $params['order_cost'] . "грн. Номер замовлення: " .
                $params['dispatching_order_uid'];
        } else {
            $order = "Нове замовлення від " . $params['user_full_name'] .
                " за маршрутом від " . $params['from'] . " " . $params['from_number'] .
                " по місту. Вартість поїздки становитиме: " . $params['order_cost'] . "грн. Номер замовлення: " .
                $params['dispatching_order_uid'];
        }

        $subject = 'Інформація про нову поїздку:';
        $paramsCheck = [
            'subject' => $subject,
            'message' => $order,
        ];
        Mail::to('taxi.easy.ua@gmail.com')->send(new Check($paramsCheck));
        $message = new TelegramController();
        try {
            $message->sendMeMessage($order);
        } catch (Exception $e) {
            $subject = 'Ошибка в телеграмм';
            $paramsCheck = [
                'subject' => $subject,
                'message' => $e,
            ];
            Mail::to('taxi.easy.ua@gmail.com')->send(new Check($paramsCheck));
        };
    }
    public function addUser($name, $email)
    {
        $newUser = User::whereRaw('BINARY email = ?', [$email])->first();
        if ($newUser == null) {
            $newUser = new User();
            $newUser->name = $name;
            $newUser->email = $email;
            $newUser->password = "123245687";

            $newUser->facebook_id = null;
            $newUser->google_id = null;
            $newUser->linkedin_id = null;
            $newUser->github_id = null;
            $newUser->twitter_id = null;
            $newUser->telegram_id = null;
            $newUser->viber_id = null;
            $newUser->save();

            $user = User::where('email', $email)->first();
            (new BonusBalanceController)->recordsAdd(0, $user->id, 1, 1);
        }
    }

    public function verifyBlackListUser($email, $androidDom)
    {
        IPController::getIP("/android/$androidDom/startPage");
        $user =  BlackList::where('email', $email)->first();

        if ($user == null) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Не черном списке";

            return response($response_error, 200)
                ->header('Content-Type', 'json');
        } else {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "В черном списке";

            return response($response_error, 200)
                ->header('Content-Type', 'json');
        }
    }

    public function geoDataSearch(
        $to,
        $to_number,
        $autorization,
        $identificationId,
        $apiVersion,
        $connectAPI
    ): array {
        if ($to_number != " ") {
            $LatLng = self::geoDataSearchStreet(
                $to,
                $to_number,
                $autorization,
                $identificationId,
                $apiVersion,
                $connectAPI
            );
        } else {
            $LatLng = self::geoDataSearchObject(
                $to,
                $autorization,
                $identificationId,
                $apiVersion,
                $connectAPI
            );
        }

        return $LatLng;
    }
    public function geoDataSearchStreet(
        $to,
        $to_number,
        $autorization,
        $identificationId,
        $apiVersion,
        $connectAPI
    ): array {
        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Ошибка соединения с сервером.";

            return $response_error;
        }

        $url = $connectAPI . '/api/geodata/search';

        $response = Http::withHeaders([
            "Authorization" => $autorization,
            "X-WO-API-APP-ID" => $identificationId,
            "X-API-VERSION" => $apiVersion
        ])->get($url, [
            'q' => $to, //Обязательный. Несколько букв для поиска объекта.
            'offset' => 0, //Смещение при выборке (сколько пропустить).
            'limit' => 1, //Кол-во возвращаемых записей (предел).
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

        $LatLng["lat"] = 0;
        $LatLng["lng"] = 0;
        if ((strncmp($to_number, " ", 1) != 0)) {
            if (isset($response_arr["geo_streets"]["geo_street"][0]["houses"])) {
                foreach ($response_arr["geo_streets"]["geo_street"][0]["houses"] as $value) {
                    if ($value['house'] ==  trim($to_number)) {
                        $LatLng["lat"] = $value["lat"];
                        $LatLng["lng"] = $value["lng"];
                        break;
                    }
                }
            }
        }

        return $LatLng;
    }

    public function geoDataSearchObject(
        $to,
        $autorization,
        $identificationId,
        $apiVersion,
        $connectAPI
    ): array {
        if ($connectAPI == 400) {
            $response_error["order_cost"] = 0;
            $response_error["Message"] = "Ошибка соединения с сервером.";

            return $response_error;
        }

        $url = $connectAPI . '/api/geodata/objects/search';

        $response = Http::withHeaders([
            "Authorization" => $autorization,
            "X-WO-API-APP-ID" => $identificationId,
            "X-API-VERSION" => $apiVersion
        ])->get($url, [
            'q' => $to, //Обязательный. Несколько букв для поиска объекта.
            'offset' => 0, //Смещение при выборке (сколько пропустить).
            'limit' => 1, //Кол-во возвращаемых записей (предел).
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
        $LatLng["lat"] = 0;
        $LatLng["lng"] = 0;

        if (isset($response_arr["geo_object"][0]["name"])) {
            $LatLng["lat"] = $response_arr["geo_object"][0]["lat"];
            $LatLng["lng"] = $response_arr["geo_object"][0]["lng"];
        }
        return $LatLng;
    }

    public function historyUIDStatus(
        $uid,
        $connectAPI,
        $authorization,
        $identificationId,
        $apiVersion
    ) {
        $url = $connectAPI . '/api/weborders/' . $uid;

        return Http::withHeaders([
            "Authorization" => $authorization,
            "X-WO-API-APP-ID" => $identificationId,
            "X-API-VERSION" => $apiVersion
        ])->get($url);
    }

    public function authorization($cityString): string
    {
        $city = City::where('name', $cityString)->first();
        $username = $city->login;
        $password = hash('SHA512', $city->password);
        return 'Basic ' . base64_encode($username . ':' . $password);
    }

}

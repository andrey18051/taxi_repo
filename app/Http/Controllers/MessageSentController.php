<?php

namespace App\Http\Controllers;

use App\Models\Orderweb;
use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use SebastianBergmann\Diff\Exception;

class MessageSentController extends Controller
{
    /**
     * @throws \Exception
     */
    public function sentCancelInfo($orderweb)
    {

        $user_full_name = $orderweb->user_full_name;
        $user_phone = $orderweb->user_phone;
        $email = $orderweb->email;
        $routefrom = $orderweb->routefrom;
        $routeto = $orderweb->routeto;
        $add_cost = $orderweb->add_cost;
        $web_cost = $orderweb->web_cost +  $add_cost;
        $dispatching_order_uid = $orderweb->dispatching_order_uid;
        $server = $orderweb->server;
        switch ($orderweb->comment) {
            case "taxi_easy_ua_pas1":
                $pas = "ПАС_1";
                break;
            case "taxi_easy_ua_pas2":
                $pas = "ПАС_2";
                break;
            case "taxi_easy_ua_pas3":
                $pas = "ПАС_3";
                break;
            case "taxi_easy_ua_pas4":
                $pas = "ПАС_4";
                break;
        }

        $kievTimeZone = new DateTimeZone('Europe/Kiev');

        $dateTime = new DateTime($orderweb->updated_at);


        $dateTime->setTimezone($kievTimeZone);
        $formattedTime = $dateTime->format('d.m.Y H:i:s');

        $updated_at = $formattedTime;
        Log::debug("updated_at " .$updated_at);

        $subject = "Отмена заказа";

        $messageAdmin = "Клиент $user_full_name (телефон $user_phone, email $email) отменил заказ по маршруту $routefrom -> $routeto стоимостью $web_cost грн. Номер заказа $dispatching_order_uid. Сервер $server. Приложение  $pas. Время отмены $updated_at";

        $alarmMessage = new TelegramController();

        try {
            $alarmMessage->sendAlarmMessage($messageAdmin);
            $alarmMessage->sendMeMessage($messageAdmin);
        } catch (Exception $e) {
            Log::debug("sentCancelInfo Ошибка в телеграмм $messageAdmin");
        }
        Log::debug("sentCancelInfo  $messageAdmin");
    }

    /**
     * @throws \Exception
     */
    public function sentCarTakingInfo($orderweb)
    {

        $user_full_name = $orderweb->user_full_name;
        $user_phone = $orderweb->user_phone;
        $email = $orderweb->email;
        $routefrom = $orderweb->routefrom;
        $routeto = $orderweb->routeto;
        $add_cost = $orderweb->add_cost;
        $web_cost = $orderweb->web_cost +  $add_cost;

        $storedData = $orderweb->auto;
        $dataDriver = json_decode($storedData, true);
//        $name = $dataDriver["name"];
        $color = $dataDriver["color"];
        $brand = $dataDriver["brand"];
        $model = $dataDriver["model"];
        $number = $dataDriver["number"];
        $phoneNumber = $dataDriver["phoneNumber"];

        $dispatching_order_uid = $orderweb->dispatching_order_uid;
        $server = $orderweb->server;
        switch ($orderweb->comment) {
            case "taxi_easy_ua_pas1":
                $pas = "ПАС_1";
                break;
            case "taxi_easy_ua_pas2":
                $pas = "ПАС_2";
                break;
            case "taxi_easy_ua_pas3":
                $pas = "ПАС_3";
                break;
            case "taxi_easy_ua_pas4":
                $pas = "ПАС_4";
                break;
        }

        $kievTimeZone = new DateTimeZone('Europe/Kiev');

        $dateTime = new DateTime($orderweb->updated_at);


        $dateTime->setTimezone($kievTimeZone);
        $formattedTime = $dateTime->format('d.m.Y H:i:s');

        $updated_at = $formattedTime;
        Log::debug("updated_at " .$updated_at);

        $subject = "Найдена авто";

        $messageAdmin = "$subject. Клиент $user_full_name (телефон $user_phone, email $email)
         Получил авто $number (цвет $color  $brand $model телефон водителя $phoneNumber)
         на  заказ по маршруту $routefrom -> $routeto стоимостью $web_cost грн.
         Номер заказа $dispatching_order_uid. Сервер $server. Приложение  $pas.
         Время $updated_at";

        $alarmMessage = new TelegramController();

        try {
            $alarmMessage->sendAlarmMessage($messageAdmin);
            $alarmMessage->sendMeMessage($messageAdmin);
        } catch (Exception $e) {
            Log::debug("sentCancelInfo Ошибка в телеграмм $messageAdmin");
        }
        Log::debug("sentCancelInfo  $messageAdmin");
    }

    /**
     * @throws \Exception
     */
    public function sentCarRestoreOrder($orderweb)
    {

        $user_full_name = $orderweb->user_full_name;
        $user_phone = $orderweb->user_phone;
        $email = $orderweb->email;
        $routefrom = $orderweb->routefrom;
        $routeto = $orderweb->routeto;
        $add_cost = $orderweb->add_cost;
        $web_cost = $orderweb->web_cost +  $add_cost;

        $dispatching_order_uid = $orderweb->dispatching_order_uid;
        $server = $orderweb->server;
        switch ($orderweb->comment) {
            case "taxi_easy_ua_pas1":
                $pas = "ПАС_1";
                break;
            case "taxi_easy_ua_pas2":
                $pas = "ПАС_2";
                break;
            case "taxi_easy_ua_pas3":
                $pas = "ПАС_3";
                break;
            case "taxi_easy_ua_pas4":
                $pas = "ПАС_4";
                break;
        }

        $kievTimeZone = new DateTimeZone('Europe/Kiev');

        $dateTime = new DateTime($orderweb->updated_at);


        $dateTime->setTimezone($kievTimeZone);
        $formattedTime = $dateTime->format('d.m.Y H:i:s');

        $updated_at = $formattedTime;
        Log::debug("updated_at " .$updated_at);

        $subject = "Восстановлен заказ после отказа водителя ";

        $messageAdmin = "$subject. Клиент $user_full_name (телефон $user_phone, email $email)
         заказ по маршруту $routefrom -> $routeto стоимостью $web_cost грн.
         Номер заказа $dispatching_order_uid. Сервер $server. Приложение  $pas.
         Время $updated_at";

        $alarmMessage = new TelegramController();

        try {
            $alarmMessage->sendAlarmMessage($messageAdmin);
            $alarmMessage->sendMeMessage($messageAdmin);
        } catch (Exception $e) {
            Log::debug("sentCancelInfo Ошибка в телеграмм $messageAdmin");
        }
        Log::debug("sentCancelInfo  $messageAdmin");
    }
    /**
     * @throws \Exception
     */
    public function sentCarRestoreOrderAfterAddCost($orderweb)
    {

        $user_full_name = $orderweb->user_full_name;
        $user_phone = $orderweb->user_phone;
        $email = $orderweb->email;
        $routefrom = $orderweb->routefrom;
        $routeto = $orderweb->routeto;
        $add_cost = $orderweb->add_cost;
        $web_cost = $orderweb->web_cost +  $add_cost;

        $dispatching_order_uid = $orderweb->dispatching_order_uid;
        $server = $orderweb->server;
        switch ($orderweb->comment) {
            case "taxi_easy_ua_pas1":
                $pas = "ПАС_1";
                break;
            case "taxi_easy_ua_pas2":
                $pas = "ПАС_2";
                break;
            case "taxi_easy_ua_pas3":
                $pas = "ПАС_3";
                break;
            case "taxi_easy_ua_pas4":
                $pas = "ПАС_4";
                break;
        }

        $kievTimeZone = new DateTimeZone('Europe/Kiev');

        $dateTime = new DateTime($orderweb->updated_at);


        $dateTime->setTimezone($kievTimeZone);
        $formattedTime = $dateTime->format('d.m.Y H:i:s');

        $updated_at = $formattedTime;
        Log::debug("updated_at " .$updated_at);

        $subject = "Восстановлен заказ после добавления стоимости  клиентом ";

        $messageAdmin = "$subject. Клиент $user_full_name (телефон $user_phone, email $email)
         заказ по маршруту $routefrom -> $routeto стоимостью $web_cost грн.
         Номер заказа $dispatching_order_uid. Сервер $server. Приложение  $pas.
         Время $updated_at";

        $alarmMessage = new TelegramController();

        try {
            $alarmMessage->sendAlarmMessage($messageAdmin);
            $alarmMessage->sendMeMessage($messageAdmin);
        } catch (Exception $e) {
            Log::debug("sentCancelInfo Ошибка в телеграмм $messageAdmin");
        }
        Log::debug("sentCancelInfo  $messageAdmin");
    }

    /**
     * @throws \Exception
     */
    public function sentDriverUnTakeOrder($uid)
    {
        $orderweb = Orderweb::where("dispatching_order_uid", $uid)->first();
        $user_full_name = $orderweb->user_full_name;
        $user_phone = $orderweb->user_phone;
        $email = $orderweb->email;
        $routefrom = $orderweb->routefrom;
        $routeto = $orderweb->routeto;
        $add_cost = $orderweb->add_cost;
        $web_cost = $orderweb->web_cost +  $add_cost;

        $storedData = $orderweb->auto;
        $dataDriver = json_decode($storedData, true);
//        $name = $dataDriver["name"];
        $color = $dataDriver["color"];
        $brand = $dataDriver["brand"];
        $model = $dataDriver["model"];
        $number = $dataDriver["number"];
        $phoneNumber = $dataDriver["phoneNumber"];


        $dispatching_order_uid = $orderweb->dispatching_order_uid;
        $server = $orderweb->server;
        switch ($orderweb->comment) {
            case "taxi_easy_ua_pas1":
                $pas = "ПАС_1";
                break;
            case "taxi_easy_ua_pas2":
                $pas = "ПАС_2";
                break;
            case "taxi_easy_ua_pas3":
                $pas = "ПАС_3";
                break;
            case "taxi_easy_ua_pas4":
                $pas = "ПАС_4";
                break;
        }

        $kievTimeZone = new DateTimeZone('Europe/Kiev');

        $dateTime = new DateTime($orderweb->updated_at);


        $dateTime->setTimezone($kievTimeZone);
        $formattedTime = $dateTime->format('d.m.Y H:i:s');

        $updated_at = $formattedTime;
        Log::debug("updated_at " .$updated_at);

        $subject = "Водитель отказался от поездки.
        авто $number (цвет $color  $brand $model телефон водителя $phoneNumber)";

        $messageAdmin = "$subject. Клиент $user_full_name (телефон $user_phone, email $email)
         заказ по маршруту $routefrom -> $routeto стоимостью $web_cost грн.
         Номер заказа $dispatching_order_uid. Сервер $server. Приложение  $pas.
         Время $updated_at";

        $alarmMessage = new TelegramController();

        try {
            $alarmMessage->sendAlarmMessage($messageAdmin);
            $alarmMessage->sendMeMessage($messageAdmin);
        } catch (Exception $e) {
            Log::debug("sentCancelInfo Ошибка в телеграмм $messageAdmin");
        }
        Log::debug("sentCancelInfo  $messageAdmin");
    }

    /**
     * @throws \Exception
     */
    public function sentDriverInStartPoint($uid, $uidDriver)
    {
        $orderweb = Orderweb::where("dispatching_order_uid", $uid)->first();

        $orderweb->closeReason = "102";
        $orderweb->save();


        $user_full_name = $orderweb->user_full_name;
        $user_phone = $orderweb->user_phone;
        $email = $orderweb->email;
        $routefrom = $orderweb->routefrom;
        $routeto = $orderweb->routeto;
        $add_cost = $orderweb->add_cost;
        $web_cost = $orderweb->web_cost +  $add_cost;
        $storedData = $orderweb->auto;
//        $dataDriver = FCMController::readUserInfoFromFirestore($uidDriver);
        $dataDriver = json_decode($storedData, true);
        $name = $dataDriver["name"];
        $color = $dataDriver["color"];
        $brand = $dataDriver["brand"];
        $model = $dataDriver["model"];
        $number = $dataDriver["number"];
        $phoneNumber = $dataDriver["phoneNumber"];


        $dispatching_order_uid = $orderweb->dispatching_order_uid;
        $server = $orderweb->server;
        switch ($orderweb->comment) {
            case "taxi_easy_ua_pas1":
                $pas = "ПАС_1";
                break;
            case "taxi_easy_ua_pas2":
                $pas = "ПАС_2";
                break;
            case "taxi_easy_ua_pas3":
                $pas = "ПАС_3";
                break;
            case "taxi_easy_ua_pas4":
                $pas = "ПАС_4";
                break;
        }

        $kievTimeZone = new DateTimeZone('Europe/Kiev');

        $dateTime = new DateTime($orderweb->updated_at);


        $dateTime->setTimezone($kievTimeZone);
        $formattedTime = $dateTime->format('d.m.Y H:i:s');

        $updated_at = $formattedTime;
        Log::debug("updated_at " .$updated_at);

        $subject = "Водитель приехал в первую точку.
        авто $number (цвет $color  $brand $model телефон водителя $phoneNumber)";

        $messageAdmin = "$subject. Клиент $user_full_name (телефон $user_phone, email $email)
         заказ по маршруту $routefrom -> $routeto стоимостью $web_cost грн.
         Номер заказа $dispatching_order_uid. Сервер $server. Приложение  $pas.
         Время $updated_at";

        $alarmMessage = new TelegramController();

        try {
            $alarmMessage->sendAlarmMessage($messageAdmin);
            $alarmMessage->sendMeMessage($messageAdmin);
        } catch (Exception $e) {
            Log::debug("sentCancelInfo Ошибка в телеграмм $messageAdmin");
        }
        Log::debug("sentCancelInfo  $messageAdmin");
    }

    /**
     * @throws \Exception
     */
    public function sentDriverInRout($uid, $uidDriver)
    {
        $orderweb = Orderweb::where("dispatching_order_uid", $uid)->first();

        $orderweb->closeReason = "103";
        $orderweb->save();


        $user_full_name = $orderweb->user_full_name;
        $user_phone = $orderweb->user_phone;
        $email = $orderweb->email;
        $routefrom = $orderweb->routefrom;
        $routeto = $orderweb->routeto;
        $add_cost = $orderweb->add_cost;
        $web_cost = $orderweb->web_cost +  $add_cost;
        $storedData = $orderweb->auto;

        $dataDriver = json_decode($storedData, true);
        $name = $dataDriver["name"];
        $color = $dataDriver["color"];
        $brand = $dataDriver["brand"];
        $model = $dataDriver["model"];
        $number = $dataDriver["number"];
        $phoneNumber = $dataDriver["phoneNumber"];


        $dispatching_order_uid = $orderweb->dispatching_order_uid;
        $server = $orderweb->server;
        switch ($orderweb->comment) {
            case "taxi_easy_ua_pas1":
                $pas = "ПАС_1";
                break;
            case "taxi_easy_ua_pas2":
                $pas = "ПАС_2";
                break;
            case "taxi_easy_ua_pas3":
                $pas = "ПАС_3";
                break;
            case "taxi_easy_ua_pas4":
                $pas = "ПАС_4";
                break;
        }

        $kievTimeZone = new DateTimeZone('Europe/Kiev');

        $dateTime = new DateTime($orderweb->updated_at);


        $dateTime->setTimezone($kievTimeZone);
        $formattedTime = $dateTime->format('d.m.Y H:i:s');

        $updated_at = $formattedTime;
        Log::debug("updated_at " .$updated_at);

        $subject = "Водитель едет в первую точку.
        авто $number (цвет $color  $brand $model телефон водителя $phoneNumber)";

        $messageAdmin = "$subject. Клиент $user_full_name (телефон $user_phone, email $email)
         заказ по маршруту $routefrom -> $routeto стоимостью $web_cost грн.
         Номер заказа $dispatching_order_uid. Сервер $server. Приложение  $pas.
         Время $updated_at";

        $alarmMessage = new TelegramController();

        try {
            $alarmMessage->sendAlarmMessage($messageAdmin);
            $alarmMessage->sendMeMessage($messageAdmin);
        } catch (Exception $e) {
            Log::debug("sentCancelInfo Ошибка в телеграмм $messageAdmin");
        }
        Log::debug("sentCancelInfo  $messageAdmin");
    }

    /**
     * @throws \Exception
     */
    public function sentDriverCloseOrder($uid)
    {
        $orderweb = Orderweb::where("dispatching_order_uid", $uid)->first();

        $orderweb->closeReason = "1";
        $orderweb->save();


        $user_full_name = $orderweb->user_full_name;
        $user_phone = $orderweb->user_phone;
        $email = $orderweb->email;
        $routefrom = $orderweb->routefrom;
        $routeto = $orderweb->routeto;
        $add_cost = $orderweb->add_cost;
        $web_cost = $orderweb->web_cost +  $add_cost;

        $storedData = $orderweb->auto;
        $dataDriver = json_decode($storedData, true);
//        $name = $dataDriver["name"];
        $color = $dataDriver["color"];
        $brand = $dataDriver["brand"];
        $model = $dataDriver["model"];
        $number = $dataDriver["number"];
        $phoneNumber = $dataDriver["phoneNumber"];


        $dispatching_order_uid = $orderweb->dispatching_order_uid;
        $server = $orderweb->server;
        switch ($orderweb->comment) {
            case "taxi_easy_ua_pas1":
                $pas = "ПАС_1";
                break;
            case "taxi_easy_ua_pas2":
                $pas = "ПАС_2";
                break;
            case "taxi_easy_ua_pas3":
                $pas = "ПАС_3";
                break;
            case "taxi_easy_ua_pas4":
                $pas = "ПАС_4";
                break;
        }

        $kievTimeZone = new DateTimeZone('Europe/Kiev');

        $dateTime = new DateTime($orderweb->updated_at);


        $dateTime->setTimezone($kievTimeZone);
        $formattedTime = $dateTime->format('d.m.Y H:i:s');

        $updated_at = $formattedTime;
        Log::debug("updated_at " .$updated_at);

        $subject = "Водитель выполнил заказ.
        авто $number (цвет $color  $brand $model телефон водителя $phoneNumber)";

        $messageAdmin = "$subject. Клиент $user_full_name (телефон $user_phone, email $email)
         заказ по маршруту $routefrom -> $routeto стоимостью $web_cost грн.
         Номер заказа $dispatching_order_uid. Сервер $server. Приложение  $pas.
         Время $updated_at";

        $alarmMessage = new TelegramController();

        try {
            $alarmMessage->sendAlarmMessage($messageAdmin);
            $alarmMessage->sendMeMessage($messageAdmin);
        } catch (Exception $e) {
            Log::debug("sentCancelInfo Ошибка в телеграмм $messageAdmin");
        }
        Log::debug("sentCancelInfo  $messageAdmin");
    } /**
     * @throws \Exception
     */
    public function sentDriverNoDelCommission($uid)
    {
        $orderweb = Orderweb::where("dispatching_order_uid", $uid)->first();

        $orderweb->closeReason = "1";
        $orderweb->save();


        $user_full_name = $orderweb->user_full_name;
        $user_phone = $orderweb->user_phone;
        $email = $orderweb->email;
        $routefrom = $orderweb->routefrom;
        $routeto = $orderweb->routeto;
        $add_cost = $orderweb->add_cost;
        $web_cost = $orderweb->web_cost +  $add_cost;

        $storedData = $orderweb->auto;
        $dataDriver = json_decode($storedData, true);
//        $name = $dataDriver["name"];
        $color = $dataDriver["color"];
        $brand = $dataDriver["brand"];
        $model = $dataDriver["model"];
        $number = $dataDriver["number"];
        $phoneNumber = $dataDriver["phoneNumber"];


        $dispatching_order_uid = $orderweb->dispatching_order_uid;
        $server = $orderweb->server;
        switch ($orderweb->comment) {
            case "taxi_easy_ua_pas1":
                $pas = "ПАС_1";
                break;
            case "taxi_easy_ua_pas2":
                $pas = "ПАС_2";
                break;
            case "taxi_easy_ua_pas3":
                $pas = "ПАС_3";
                break;
            case "taxi_easy_ua_pas4":
                $pas = "ПАС_4";
                break;
        }

        $kievTimeZone = new DateTimeZone('Europe/Kiev');

        $dateTime = new DateTime($orderweb->updated_at);


        $dateTime->setTimezone($kievTimeZone);
        $formattedTime = $dateTime->format('d.m.Y H:i:s');

        $updated_at = $formattedTime;
        Log::debug("updated_at " .$updated_at);

        $subject = "Водителю не списалась комиссия за поездку.
        авто $number (цвет $color  $brand $model телефон водителя $phoneNumber)";

        $messageAdmin = "$subject. Клиент $user_full_name (телефон $user_phone, email $email)
         заказ по маршруту $routefrom -> $routeto стоимостью $web_cost грн.
         Номер заказа $dispatching_order_uid. Сервер $server. Приложение  $pas.
         Время $updated_at";

        $alarmMessage = new TelegramController();

        try {
            $alarmMessage->sendAlarmMessage($messageAdmin);
            $alarmMessage->sendMeMessage($messageAdmin);
        } catch (Exception $e) {
            Log::debug("sentCancelInfo Ошибка в телеграмм $messageAdmin");
        }
        Log::debug("sentCancelInfo  $messageAdmin");
    }

    /**
     * @throws \Exception
     */
    public function sentNoCancelInfo($orderweb)
    {

        if ($orderweb->user_full_name) {
            $user_full_name = $orderweb->user_full_name;
        } else {
            $user_full_name = "no_name";
        }
        $user_phone = $orderweb->user_phone;
        $email = $orderweb->email;
        $routefrom = $orderweb->routefrom;
        $routeto = $orderweb->routeto;
        $add_cost = $orderweb->add_cost;
        $web_cost = $orderweb->web_cost +  $add_cost;
        $dispatching_order_uid = $orderweb->dispatching_order_uid;
        $server = $orderweb->server;
        switch ($orderweb->comment) {
            case "taxi_easy_ua_pas1":
                $pas = "ПАС_1";
                break;
            case "taxi_easy_ua_pas2":
                $pas = "ПАС_2";
                break;
            case "taxi_easy_ua_pas3":
                $pas = "ПАС_3";
                break;
            case "taxi_easy_ua_pas4":
                $pas = "ПАС_4";
                break;
        }

        $kievTimeZone = new DateTimeZone('Europe/Kiev');

        $dateTime = new DateTime($orderweb->updated_at);


        $dateTime->setTimezone($kievTimeZone);
        $formattedTime = $dateTime->format('d.m.Y H:i:s');

        $updated_at = $formattedTime;
        Log::debug("updated_at " .$updated_at);

        $messageAdmin = "Клиент $user_full_name (телефон $user_phone, email $email) НЕ МОЖЕТ ОТМЕНИТЬ заказ по маршруту $routefrom -> $routeto стоимостью $web_cost грн. Номер заказа $dispatching_order_uid. Сервер $server. Приложение  $pas. Время отмены $updated_at";

        $alarmMessage = new TelegramController();

        try {
            $alarmMessage->sendAlarmMessage($messageAdmin);
            $alarmMessage->sendMeMessage($messageAdmin);
        } catch (Exception $e) {
            Log::debug("sentNoCancelInfo Ошибка в телеграмм $messageAdmin");
        };
        Log::debug("sentNoCancelInfo  $messageAdmin");
    }

    /**
     * @throws \Exception
     */
    public function sentDriverPayToBalance($uidDriver, $amount)
    {
        try {
            // Получите экземпляр клиента Firestore из сервис-провайдера
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            // Получите ссылку на коллекцию и документ
            $collection = $firestore->collection('users');
            $document = $collection->document($uidDriver);

            // Получите снимок документа
            $snapshot = $document->snapshot();

            if ($snapshot->exists()) {
                // Получите данные из документа
                $dataDriver = $snapshot->data();

                if (is_array($dataDriver)) {
                    $name = $dataDriver['name'] ?? 'Unknown';
                    $color = $dataDriver['color'] ?? 'Unknown';
                    $brand = $dataDriver['brand'] ?? 'Unknown';
                    $model = $dataDriver['model'] ?? 'Unknown';
                    $number = $dataDriver['number'] ?? 'Unknown';
                    $phoneNumber = $dataDriver['phoneNumber'] ?? 'Unknown';


                    $currentDateTime = Carbon::now();
                    $kievTimeZone = new DateTimeZone('Europe/Kiev');
                    $dateTime = new DateTime($currentDateTime);
                    $dateTime->setTimezone($kievTimeZone);
                    $formattedTime = $dateTime->format('d.m.Y H:i:s');

                    $subject = "Водитель $name картой пополнил свой баланс . Авто $number (цвет $color  $brand $model телефон водителя $phoneNumber)";

                    $messageAdmin = "$subject. Сумма $amount грн. Время $formattedTime";

                    $alarmMessage = new TelegramController();

                    try {
                        $alarmMessage->sendAlarmMessage($messageAdmin);
                        $alarmMessage->sendMeMessage($messageAdmin);
                    } catch (Exception $e) {
                        Log::debug("sentCancelInfo Ошибка в телеграмм $messageAdmin");
                    }
                    Log::debug("sentCancelInfo  $messageAdmin");
                }
            } else {
                Log::info("Document does not exist!");
                return "Document does not exist!";
            }
        } catch (\Exception $e) {
            Log::error("Error reading document from Firestore: " . $e->getMessage());
            return "Error reading document from Firestore.";
        }

    }
    /**
     * @throws \Exception
     */
    public function sentDriverUpdateAccount($uidDriver)
    {
        try {
            // Получите экземпляр клиента Firestore из сервис-провайдера
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            // Получите ссылку на коллекцию и документ
            $collection = $firestore->collection('users');
            $document = $collection->document($uidDriver);

            // Получите снимок документа
            $snapshot = $document->snapshot();

            if ($snapshot->exists()) {
                // Получите данные из документа
                $dataDriver = $snapshot->data();

                if (is_array($dataDriver)) {
                    $name = $dataDriver['name'] ?? 'Unknown';
                    $phoneNumber = $dataDriver['phoneNumber'] ?? 'Unknown';


                    $currentDateTime = Carbon::now();
                    $kievTimeZone = new DateTimeZone('Europe/Kiev');
                    $dateTime = new DateTime($currentDateTime);
                    $dateTime->setTimezone($kievTimeZone);
                    $formattedTime = $dateTime->format('d.m.Y H:i:s');

                    $subject = "Водитель google_id: $uidDriver обновил свои данные и ожидает подтверждения.
Проверьте:
ФИО $name
телефон $phoneNumber
Время обновления $formattedTime
Подтвердить данные https://m.easy-order-taxi.site/driver/verifyDriverUpdateInfo/$uidDriver";

                    $messageAdmin = "$subject. Время $formattedTime";

                    $alarmMessage = new TelegramController();

                    try {
                        $alarmMessage->sendAlarmMessage($messageAdmin);
                        $alarmMessage->sendMeMessage($messageAdmin);
                    } catch (Exception $e) {
                        Log::debug("sentCancelInfo Ошибка в телеграмм $messageAdmin");
                    }
                    Log::debug("sentCancelInfo  $messageAdmin");
                }
            } else {
                Log::info("Document does not exist!");
                return "Document does not exist!";
            }
        } catch (\Exception $e) {
            Log::error("Error reading document from Firestore: " . $e->getMessage());
            return "Error reading document from Firestore.";
        }
    }

    public function sentDriverUpdateCar($uidDriver, $carId)
    {
        try {
            // Получите экземпляр клиента Firestore из сервис-провайдера
            $serviceAccountPath = env('FIREBASE_CREDENTIALS_DRIVER_TAXI');
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $firestore = $firebase->createFirestore()->database();

            // Получите ссылку на коллекцию и документ
            $collection = $firestore->collection('users');
            $document = $collection->document($uidDriver);

            // Получите снимок документа
            $snapshot = $document->snapshot();

            if ($snapshot->exists()) {
                // Получите данные из документа
                $dataDriver = $snapshot->data();

                if (is_array($dataDriver)) {
                    $name = $dataDriver['name'] ?? 'Unknown';
                    $phoneNumber = $dataDriver['phoneNumber'] ?? 'Unknown';


                    $currentDateTime = Carbon::now();
                    $kievTimeZone = new DateTimeZone('Europe/Kiev');
                    $dateTime = new DateTime($currentDateTime);
                    $dateTime->setTimezone($kievTimeZone);
                    $formattedTime = $dateTime->format('d.m.Y H:i:s');


                    $collectionCar = $firestore->collection('cars');
                    $documentCar = $collectionCar->document($carId);
                    $snapshotCar = $documentCar->snapshot();
                    if ($snapshotCar->exists()) {
                        // Получите данные из документа
                        $dataCar = $snapshotCar->data();

                        $brand = $dataCar['brand'] ?? 'Unknown';
                        $color = $dataCar['color'] ?? 'Unknown';
                        $model = $dataCar['model'] ?? 'Unknown';
                        $number = $dataCar['number'] ?? 'Unknown';
                        $type = $dataCar['type'] ?? 'Unknown';
                        $year = $dataCar['year'] ?? 'Unknown';

                        $subject = "Водитель
ФИО $name
телефон $phoneNumber
google_id: $uidDriver отправил данные авто и ожидает подтверждения
Проверьте данные авто:
Марка  $brand
модель $model
тип кузова $type
цвет $color
номер $number
год $year
Время обновления $formattedTime
Подтвердить данные https://m.easy-order-taxi.site/driver/verifyDriverUpdateCarInfo/$carId";

                        $messageAdmin = "$subject";

                        $alarmMessage = new TelegramController();

                        try {
                            $alarmMessage->sendAlarmMessage($messageAdmin);
                            $alarmMessage->sendMeMessage($messageAdmin);
                        } catch (Exception $e) {
                            Log::debug("sentCancelInfo Ошибка в телеграмм $messageAdmin");
                        }
                        Log::debug("sentCancelInfo  $messageAdmin");
                    }
                }
            } else {
                Log::info("Document does not exist!");
                return "Document does not exist!";
            }
        } catch (\Exception $e) {
            Log::error("Error reading document from Firestore: " . $e->getMessage());
            return "Error reading document from Firestore.";
        }
    }

}

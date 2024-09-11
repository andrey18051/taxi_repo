<?php

namespace App\Http\Controllers;

use App\Models\Orderweb;
use DateTime;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        $web_cost = $orderweb->web_cost;
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
        $web_cost = $orderweb->web_cost;

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
        $web_cost = $orderweb->web_cost;

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
    public function sentDriverUnTakeOrder($uid)
    {
        $orderweb = Orderweb::where("dispatching_order_uid", $uid)->first();
        $user_full_name = $orderweb->user_full_name;
        $user_phone = $orderweb->user_phone;
        $email = $orderweb->email;
        $routefrom = $orderweb->routefrom;
        $routeto = $orderweb->routeto;
        $web_cost = $orderweb->web_cost;

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
    public function sentDriverInStartPoint($uid)
    {
        $orderweb = Orderweb::where("dispatching_order_uid", $uid)->first();

        $orderweb->closeReason = "102";
        $orderweb->save();


        $user_full_name = $orderweb->user_full_name;
        $user_phone = $orderweb->user_phone;
        $email = $orderweb->email;
        $routefrom = $orderweb->routefrom;
        $routeto = $orderweb->routeto;
        $web_cost = $orderweb->web_cost;

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
        $web_cost = $orderweb->web_cost;

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
        $web_cost = $orderweb->web_cost;
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
}

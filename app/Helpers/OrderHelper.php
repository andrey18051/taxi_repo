<?php

namespace App\Helpers;

use App\Http\Controllers\AndroidTestOSMController;
use App\Http\Controllers\MessageSentController;
use App\Http\Controllers\UniversalAndroidFunctionController;
use Illuminate\Support\Facades\Log;

class OrderHelper
{
    /**
     * Рассчитывает корректировку стоимости заказа после смены часа.
     *
     * @param string $url URL базового API
     * @param array $parameter Параметры запроса
     * @param string $authorization Авторизационный токен
     * @param string $identificationId Идентификатор
     * @param string $apiVersion Версия API
     * @param object $order Объект заказа
     * @return int Корректировка стоимости
     */
    public static function calculateCostBalanceAfterHourChange(
        string $url,
        array $parameter,
        string $authorization,
        string $identificationId,
        string $apiVersion,
        string $newAddCost,
        object $order
    ): int {
        $addCostBalance = 0;

        $urlCost = $url . "/cost";

        $attempt_20 = $order->attempt_20;
        $add_cost = $order->add_cost;

        switch ($order->comment) {
            case 'taxi_easy_ua_pas1':
                $application = "PAS1";
                break;
            case 'taxi_easy_ua_pas2':
                $application = "PAS2";
                break;
            default:
                $application = "PAS4";
        }
        switch ($order->city) {
            case "city_kiev":
                $city = "Kyiv City";
                break;
            case "city_cherkassy":
                $city = "Cherkasy Oblast";
                break;
            case "city_odessa":
                if($order->server == "http://188.190.245.102:7303") {
                    $city = "OdessaTest";
                } else {
                    $city = "Odessa";
                }


                break;
            case "city_zaporizhzhia":
                $city = "Zaporizhzhia";
                break;
            case "city_dnipro":
                $city = "Dnipropetrovsk Oblast";
                break;
            case "city_lviv":
                $city = "Lviv";
                break;
            case "city_ivano_frankivsk":
                $city = "Ivano_frankivsk";
                break;
            case "city_vinnytsia":
                $city = "Vinnytsia";
                break;
            case "city_poltava":
                $city = "Poltava";
                break;
            case "city_sumy":
                $city = "Sumy";
                break;
            case "city_kharkiv":
                $city = "Kharkiv";
                break;
            case "city_chernihiv":
                $city = "Chernihiv";
                break;
            case "city_rivne":
                $city = "Rivne";
                break;
            case "city_ternopil":
                $city = "Ternopil";
                break;
            case "city_khmelnytskyi":
                $city = "Khmelnytskyi";
                break;
            case "city_zakarpattya":
                $city = "Zakarpattya";
                break;
            case "city_zhytomyr":
                $city = "Zhytomyr";
                break;
            case "city_kropyvnytskyi":
                $city = "Kropyvnytskyi";
                break;
            case "city_mykolaiv":
                $city = "Mykolaiv";
                break;
            case "city_chernivtsi":
                $city = "Chernivtsi";
                break;
            case "city_lutsk":
                $city = "Lutsk";
                break;
            default:
                $city = "all";
        }
        $cost_correction = (new AndroidTestOSMController)->costCorrectionValue(
            $order->pay_system,
            $city,
            $order->server,
            $application
        );


        $parameter['add_cost'] =  (int)  $attempt_20 + (int)  $add_cost + (int) $newAddCost;
        $messageAdmin = "
            $order

            order->pay_system $order->pay_system,
            city $city,
            order->connectAPI $order->server,
            application $application

        (int)  attempt_20    $attempt_20
        (int)  add_cost    $add_cost
        (int)  newAddCost  $newAddCost
        (int)  cost_correction  $cost_correction


        Параметры расчета нового заказа:" .
                   json_encode($parameter, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        (new MessageSentController)->sentMessageAdmin($messageAdmin);

        $responseCost = (new UniversalAndroidFunctionController)->postRequestHTTP(
            $urlCost,
            $parameter,
            $authorization,
            $identificationId,
            $apiVersion
        );


        if ($responseCost->successful() && $responseCost->status() == 200) {
            Log::info("Успешный ответ API с кодом 200");

            $responseCostArr = $responseCost->json();
            $oldCost = $order->web_cost;

            $orderNewCost = $responseCostArr["order_cost"];

            $addCostBalanceClear = (int) $oldCost - (int) $orderNewCost + (int) $newAddCost;
            $addCostBalance =  $addCostBalanceClear -(int) $cost_correction;

            $responseCostArrAnswer = "Полный ответ:" . json_encode($responseCostArr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            $messageAdmin = "После перепроверки базовая стоимость:
                    Старая $oldCost ,
                    Новая $orderNewCost,
                    addCostBalanceClear $addCostBalanceClear
                    cost_correction $cost_correction
                    Корректировка нового заказа $addCostBalance
                    $responseCostArrAnswer";


        } else {
            $errorMessage = $responseCost->failed() ? $responseCost->body() : "Неизвестная ошибка";
            $messageAdmin = "Ошибка запроса новой стоимости. Детали: " . $errorMessage;

            Log::error($messageAdmin);
        }
        (new MessageSentController)->sentMessageAdmin($messageAdmin);



        return $addCostBalance;
    }



}

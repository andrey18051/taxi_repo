<?php

namespace App\Helpers;

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

        $parameter['add_cost'] =  (int)  $attempt_20 + (int)  $add_cost + (int) $newAddCost;
        $messageAdmin = "Параметры расчета нового заказа:" .
                   json_encode($parameter, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);

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

            $addCostBalance = (int) $oldCost - (int) $orderNewCost + (int) $newAddCost;

            $responseCostArrAnswer = "Полный ответ:" . json_encode($responseCostArr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            $messageAdmin = "После перепроверки базовая стоимость:
                    Старая $oldCost ,
                    Новая $orderNewCost,
                    Корректировка нового заказа $addCostBalance
                    $responseCostArrAnswer";


        } else {
            $errorMessage = $responseCost->failed() ? $responseCost->body() : "Неизвестная ошибка";
            $messageAdmin = "Ошибка запроса новой стоимости. Детали: " . $errorMessage;

            Log::error($messageAdmin);
        }
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);



        return $addCostBalance;
    }



}

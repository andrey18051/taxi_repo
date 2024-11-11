<?php

namespace App\Http\Controllers;

use App\Models\OrdersRefusal;
use App\Models\Orderweb;
use App\Models\Uid_history;
use Illuminate\Http\Request;

class CleanerTableController extends Controller
{
    public function cleanOrderRefusalTable()
    {
        $orderRefusals = OrdersRefusal::all();
        if ($orderRefusals != null) {
            foreach ($orderRefusals as $value) {
                $order = Orderweb::where("dispatching_order_uid", $value->order_uid)
                    ->whereIn('closeReason', ['-1', '101', '102'])
                    ->first();

                if ($order == null) {
                    OrdersRefusal::where("order_uid", $value->order_uid)->delete();
                }
            }
        }
    }

    public function cleanUidHistoriesTable()
    {
        $uidHistories = Uid_history::cursor();
        foreach ($uidHistories as $value) {
            // Находим запись в Orderweb
            $bonusOrder = Orderweb::where("dispatching_order_uid", $value->uid_bonusOrder)->first();

            if (!$bonusOrder) {
                continue; // Пропускаем, если заказ не найден
            }

            $connectAPI = $bonusOrder->server;
            $autorization = (new UIDController)->autorization($connectAPI);
            $identificationId = $bonusOrder->comment;

            // Проверяем close_reason для бонусного и дублирующего заказа
            $close_reason_bonusOrder = (new UIDController)->closeReasonUIDStatusFirst(
                $value->uid_bonusOrder,
                $connectAPI,
                $autorization,
                $identificationId
            );
            $close_reason_doubleOrder = (new UIDController)->closeReasonUIDStatusFirst(
                $value->uid_uid_doubleOrder,
                $connectAPI,
                $autorization,
                $identificationId
            );

            // Удаляем запись, если оба заказа не имеют причины "-1"
            if ($close_reason_bonusOrder != "-1" && $close_reason_doubleOrder != "-1") {
                Uid_history::where("uid_bonusOrder", $value->uid_bonusOrder)->delete();
            }
        }
    }

}

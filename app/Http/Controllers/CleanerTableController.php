<?php

namespace App\Http\Controllers;

use App\Models\OrdersRefusal;
use App\Models\Orderweb;
use App\Models\Uid_history;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CleanerTableController extends Controller
{
    public function cleanOrderRefusalTable()
    {
        Log::info('Начата очистка таблицы OrdersRefusal.');

        $orderRefusals = OrdersRefusal::pluck('order_uid');

        if ($orderRefusals->isNotEmpty()) {
            Log::info('Найдено записей в OrdersRefusal: ' . $orderRefusals->count());

            $existingOrders = Orderweb::whereIn('dispatching_order_uid', $orderRefusals)
                ->whereIn('closeReason', ['-1', '101', '102'])
                ->pluck('dispatching_order_uid');

            $ordersToDelete = OrdersRefusal::whereNotIn('order_uid', $existingOrders)->pluck('order_uid');

            if ($ordersToDelete->isNotEmpty()) {
                Log::info('Будет удалено записей из OrdersRefusal: ' . $ordersToDelete->count());
                OrdersRefusal::whereIn('order_uid', $ordersToDelete)->delete();
            } else {
                Log::info('Записи для удаления не найдены.');
            }
        } else {
            Log::info('Таблица OrdersRefusal пуста.');
        }

        Log::info('Очистка таблицы OrdersRefusal завершена.');
    }

    public function cleanUidHistoriesTable()
    {
        Log::info('Начата очистка таблицы Uid_history.');

        $uidHistories = Uid_history::cursor();
        $processedCount = 0;
        $deletedCount = 0;

        foreach ($uidHistories as $value) {
            $processedCount++;
            Log::info("Обработка записи Uid_history с uid_bonusOrder: {$value->uid_bonusOrder}");

            $bonusOrder = Orderweb::where("dispatching_order_uid", $value->uid_bonusOrder)->first();

            if (!$bonusOrder) {
                Log::info("Связанный заказ не найден для uid_bonusOrder: {$value->uid_bonusOrder}");
                Uid_history::where("uid_bonusOrder", $value->uid_bonusOrder)->delete();
                $deletedCount++;
                continue;
            }
            $updatedAt = Carbon::parse($bonusOrder->updated_at);

            // Сравниваем с текущей датой
            if ($updatedAt->diffInMonths(Carbon::now()) >= 1) {
                Uid_history::where("uid_bonusOrder", $value->uid_bonusOrder)->delete();
                $deletedCount++;
                Log::info("Запись Uid_history с uid_bonusOrder: {$value->uid_bonusOrder} удалена.");
                continue;
            }
            $connectAPI = $bonusOrder->server;
            $autorization = (new UIDController)->autorization($connectAPI);
            if ($autorization == null) {
                Uid_history::where("uid_bonusOrder", $value->uid_bonusOrder)->delete();
                $deletedCount++;
                continue;
            }
            $identificationId = $bonusOrder->comment;

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
            Log::info("uid_bonusOrder: {$value->uid_bonusOrder}");
            Log::info("close_reason_bonusOrder: {$close_reason_bonusOrder}");
            Log::info("uid_doubleOrder: {$value->uid_uid_doubleOrder}");
            Log::info("close_reason_doubleOrder: {$close_reason_doubleOrder}");

            if ($close_reason_bonusOrder != "-1" && $close_reason_doubleOrder != "-1") {
                Uid_history::where("uid_bonusOrder", $value->uid_bonusOrder)->delete();
                $deletedCount++;
                Log::info("Запись Uid_history с uid_bonusOrder: {$value->uid_bonusOrder} удалена.");
            } else {

                Log::info("Запись Uid_history с uid_bonusOrder: {$value->uid_bonusOrder} оставлена.");
            }
        }

        Log::info("Очистка таблицы Uid_history завершена. Обработано записей: $processedCount. Удалено записей: $deletedCount.");
    }
}


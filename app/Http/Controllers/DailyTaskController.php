<?php

namespace App\Http\Controllers;

use App\Jobs\StartNewProcessExecution;
use App\Jobs\StartOrderReview;
use App\Models\DoubleOrder;
use App\Models\Orderweb;
use App\Models\Uid_history;
use App\Models\WfpInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class DailyTaskController extends Controller
{

    /**
     * Retry a database query multiple times with a delay.
     *
     * @param callable $callback
     * @return mixed
     * @throws Exception
     */
    private function retryQuery(callable $callback)
    {
        $attempt = 0;
        do {
            try {
                // Проверяем соединение перед выполнением запроса
                DB::connection()->getPdo();
                return $callback();
            } catch (Exception $e) {
                if (++$attempt >= 30) {
                    throw $e;
                }
                usleep(5000 * 1000); // задержка в миллисекундах

                // Пытаемся повторно подключиться к базе данных
                DB::connection('mysql')->reconnect();
            }
        } while (true);
    }

    public function sentTaskMessage($message)
    {
        $alarmMessage = new TelegramController();

        try {
            $alarmMessage->sendAlarmMessage($message);
            $alarmMessage->sendMeMessage($message);
            Log::info("sentTaskMessage: $message");
        } catch (Exception $e) {
            Log::error("sentTaskMessage: Ошибка отправки в телеграмм");
        };
    }

    /**
     *
     */

    public function restartProcessExecutionStatus()
    {
        // Используем retryQuery для повторения запроса в случае неудачи
        sleep(30);
        try {
            $doubleOrder = $this->retryQuery(function () {
                return DoubleOrder::all();
            });

            $message = "Перезапуск сервера";
            Log::info("restartProcessExecutionStatus: Перезапуск сервера");
            self::sentTaskMessage($message);

            if ($doubleOrder) {
                foreach ($doubleOrder as $value) {
                    $responseBonusStrArr = json_decode($value->responseBonusStr, true);
                    $message = "Запущен заново процесс опроса статусов заказа: "
                        . $responseBonusStrArr['dispatching_order_uid'];
                    self::sentTaskMessage($message);
                    Log::info("restartProcessExecutionStatus: $message");
                    StartNewProcessExecution::dispatch($value->id);
                }
            } else {
                $message = "Нет активных задач опроса для перезапуска";
                Log::info("restartProcessExecutionStatus: Нет активных задача опроса для перезапуска");
                self::sentTaskMessage($message);
            }
        } catch (Exception $e) {
            Log::error("Ошибка при выполнении запроса: " . $e->getMessage());
            self::sentTaskMessage("Ошибка при выполнении запроса: " . $e->getMessage());
        }
    }

    /**
     * Пересмотр холдов
     */
    public function orderCardWfpReviewTask()
    {
//        $orderwebs = Orderweb::where(function ($query) {
//            $query->where('wfp_status_pay', 'WaitingAuthComplete')
//                ->orWhere('wfp_status_pay', 'InProcessing');
//        })->get();
//
//        if (!$orderwebs->isEmpty()) {
//            Log::info("orderCardWfpReviewTask Orderweb", $orderwebs->toArray());
//
//            foreach ($orderwebs->toArray() as $value) {
//                $uid = $value['dispatching_order_uid'];
////                $uid = (new MemoryOrderChangeController)->show($uid);
//                $uid_history = Uid_history::where("uid_bonusOrderHold", $uid)->first();
//                if ($uid_history != null) {
//                    Log::info("uid_history $uid_history");
//                    $bonusOrder = $uid_history->uid_bonusOrder;
//                    $doubleOrder = $uid_history->uid_doubleOrder;
//                    $bonusOrderHold  = $uid_history->uid_bonusOrder;
//                    Log::info("uid_history bonusOrder $bonusOrder");
//                    Log::info("uid_history doubleOrder $doubleOrder");
//                    Log::info("uid_history bonusOrderHold $bonusOrderHold");
////                    StartOrderReview::dispatch($bonusOrder, $doubleOrder, $bonusOrderHold);
//                    (new UniversalAndroidFunctionController)->orderReview(
//                        $bonusOrder,
//                        $doubleOrder,
//                        $bonusOrderHold
//                    );
//                } else {
//                    $message = "Оператор проверьте холд по счету WFP: " .  $value['wfp_order_id'] . "для пересмотра";
//                    $order = Orderweb::where('wfp_order_id', $value['wfp_order_id'])->first();
//                    $order->wfp_status_pay = 'Declined';
//                    $order->save();
//
//                    self::sentTaskMessage($message);
//                    Log::info("orderCardWfpReviewTask $message");
//                }
//            }
//        } else {
//            $message = "orderCardWfpReviewTask нет холдов WFP для пересмотра";
////            self::sentTaskMessage($message);
//            Log::info("orderCardWfpReviewTask $message");
//        }
        $orderwebs = WfpInvoice::where(function ($query) {
            $query->where('transactionStatus', 'WaitingAuthComplete')
                ->orWhere('transactionStatus', 'InProcessing');
        })->get();
        $messageAdmin = "orderCardWfpReviewTask: " . $orderwebs;
        (new MessageSentController)->sentMessageAdmin($messageAdmin);

        if (!$orderwebs->isEmpty()) {
            Log::info("orderCardWfpReviewTask WfpInvoice", $orderwebs->toArray());

            foreach ($orderwebs->toArray() as $value) {
                $uid = $value['dispatching_order_uid'];
                $uid = (new MemoryOrderChangeController)->show($uid);
                $uid_history = Uid_history::where("uid_bonusOrderHold", $uid)->first();
                if ($uid_history != null) {
                    Log::info("uid_history $uid_history");
                    $bonusOrder = $uid_history->uid_bonusOrder;
                    $doubleOrder = $uid_history->uid_doubleOrder;
                    $bonusOrderHold  = $uid_history->uid_bonusOrderHold;
                    Log::info("uid_history bonusOrder $bonusOrder");
                    Log::info("uid_history doubleOrder $doubleOrder");
                    Log::info("uid_history bonusOrderHold $bonusOrderHold");
//                    StartOrderReview::dispatch($bonusOrder, $doubleOrder, $bonusOrderHold);
                    (new UniversalAndroidFunctionController)->orderReview(
                        $bonusOrder,
                        $doubleOrder,
                        $bonusOrderHold
                    );
                } else {
                    (new UniversalAndroidFunctionController)->orderReview(
                        $uid,
                        $uid,
                        $uid
                    );
//                    $message = "Оператор проверьте холд по счету WFP: " .  $value['orderReference'] . "для пересмотра";
//                    $order = WfpInvoice::where('orderReference', $value['orderReference'])->first();
//
//                    $order->save();
//
//                    self::sentTaskMessage($message);
//                    Log::info("orderCardWfpReviewTask $message");
                }
            }
        } else {
            $message = "orderCardWfpReviewTask нет холдов WFP для пересмотра";
//            self::sentTaskMessage($message);
            Log::info("orderCardWfpReviewTask $message");
        }
    }
    public function orderBonusReviewTask()
    {
        $orderwebs = Orderweb::where('pay_system', 'bonus_payment')
                ->where('bonus_status', 'hold')->get();

        if (!$orderwebs->isEmpty()) {
            Log::info("orderBonusReviewTask", $orderwebs->toArray());

            foreach ($orderwebs->toArray() as $value) {
                $uid = $value['dispatching_order_uid'];

                $uid_history = Uid_history::where("uid_bonusOrderHold", $uid)->first();
                if ($uid_history != null) {
                    Log::info("uid_history $uid_history");
                    $bonusOrder = $uid_history->uid_bonusOrder;
                    $doubleOrder = $uid_history->uid_doubleOrder;
                    $bonusOrderHold  = $uid_history->uid_bonusOrder;
                    Log::info("uid_history bonusOrder $bonusOrder");
                    Log::info("uid_history doubleOrder $doubleOrder");
                    Log::info("uid_history bonusOrderHold $bonusOrderHold");

                } else {
                    $message = "Оператор проверьте холд бонусов: " .  $uid . "для пересмотра";

                    self::sentTaskMessage($message);

                    Log::info("orderCardWfpReviewTask $message");
                    $bonusOrder = $uid;
                    $doubleOrder = $uid;
                    $bonusOrderHold  = $uid;
                    Log::info("uid_history bonusOrder $bonusOrder");
                    Log::info("uid_history doubleOrder $doubleOrder");
                    Log::info("uid_history bonusOrderHold $bonusOrderHold");
                }
                (new UniversalAndroidFunctionController)->orderReview(
                    $bonusOrder,
                    $doubleOrder,
                    $bonusOrderHold
                );
            }
        } else {
            $message = "orderBonusReviewTask нет холдов бонусов для пересмотра";
//            self::sentTaskMessage($message);
            Log::info("orderReviewTask $message");
        }
    }

}

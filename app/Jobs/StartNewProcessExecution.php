<?php

namespace App\Jobs;

use App\Http\Controllers\AndroidTestOSMController;
use App\Http\Controllers\MemoryOrderChangeController;
use App\Http\Controllers\MessageSentController;
use App\Http\Controllers\UIDController;
use App\Http\Controllers\UniversalAndroidFunctionController;
use App\Models\DoubleOrder;
use App\Models\Orderweb;
use App\Models\Uid_history;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;



class StartNewProcessExecution implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $orderId;
    protected $jobId; // Поле для сохранения ID задачи

    /**
     * Create a new job instance.
     *
     * @param int $orderId
     */
    public function __construct($orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        $this->jobId = $this->job->getJobId();

        $messageAdmin = "!!!+++13032025 Запущена вилка для заказа $this->orderId Job ID: {$this->jobId} started for order ID: {$this->orderId}";
        (new MessageSentController)->sentMessageAdmin($messageAdmin);

        $doubleOrderRecord = DoubleOrder::find($this->orderId);
        if($doubleOrderRecord) {
            $responseBonusStr = $doubleOrderRecord->responseBonusStr;

            $authorizationBonus = $doubleOrderRecord->authorizationBonus;
            $authorizationDouble = $doubleOrderRecord->authorizationDouble;

            $connectAPI = $doubleOrderRecord->connectAPI;
            $identificationId = $doubleOrderRecord->identificationId;
            $responseBonus = json_decode($responseBonusStr, true);
            $bonusOrder = $responseBonus['dispatching_order_uid'];

            try {


                Log::info("Запуск startNewProcessExecutionStatusJob для orderId: {$this->orderId}, jobId: {$this->jobId}");
                $result = (new UniversalAndroidFunctionController)->startNewProcessExecutionStatusJob($this->orderId, $this->jobId);
                Log::info("Результат startNewProcessExecutionStatusJob: " . ($result ?? 'null'));


                if ($result === "exit") {
                    $messageAdmin = "Задача завершена для заказа $this->orderId (Job ID: {$this->jobId})";
                    (new MessageSentController)->sentMessageAdmin($messageAdmin);

                    $uid = (new MemoryOrderChangeController)->show($bonusOrder);

                    $orderweb = Orderweb::where("dispatching_order_uid", $uid)->first();

                    if($orderweb) {
                        $application = $orderweb->comment;
                    } else {
                        $application = "taxi_easy_ua_pas2";
                    }
                    $city ='';

                    $uid_history = Uid_history::where("uid_bonusOrderHold", $bonusOrder)->first();

                    if ($uid_history !== null) {
                        // Действие, если запись найдена
                        $ui = $uid_history->uid_bonusOrder;
                        $ui_Double = $uid_history->uid_doubleOrder;

                        $messageAdmin = "Найдена запись Uid_history: " . $ui . " - " . $ui_Double;
                        (new MessageSentController)->sentMessageAdminLog($messageAdmin);
                        //// bonus section
                        // to cancel


                        $header = [
                            "Authorization" => $authorizationBonus,
                            "X-WO-API-APP-ID" => $identificationId,
                        ];
                        // status bonus
                        $url = $connectAPI . '/api/weborders/' . $ui;

                        $responseArr = (new UniversalAndroidFunctionController)->getStatus(
                            $header,
                            $url
                        );

                        if (isset($responseArr["close_reason"]) && $responseArr["close_reason"] != 1) {
                            $url_cancel = $connectAPI . '/api/weborders/cancel/' . $ui;
                            $result = AndroidTestOSMController::repeatCancel(
                                $url_cancel,
                                $authorizationBonus,
                                $application,
                                $city,
                                $connectAPI,
                                $ui
                            );
                            if ($result === 1) {
                                $messageAdmin = "123 Bonus (безнал) отменен по выходу из вилки, номер заказа: $ui";
                                (new MessageSentController)->sentMessageAdmin($messageAdmin);
                            }
                        } else {
                            $messageAdmin = "321 Bonus (безнал) отменен ранее до выхода из вилки, номер заказа: $ui";
                            (new MessageSentController)->sentMessageAdmin($messageAdmin);
                        }


                        ///// double section
                        // to cancel



                        $header = [
                            "Authorization" => $authorizationDouble,
                            "X-WO-API-APP-ID" => $identificationId,
                        ];

                        $url = $connectAPI . '/api/weborders/' . $ui_Double;
                        $responseArr = (new UniversalAndroidFunctionController)->getStatus(
                            $header,
                            $url
                        );

                        if (isset($responseArr["close_reason"]) && $responseArr["close_reason"] != 1) {
                            $url_cancel = $connectAPI . '/api/weborders/cancel/' . $ui_Double;
                            $result = AndroidTestOSMController::repeatCancel(
                                $url_cancel,
                                $authorizationDouble,
                                $application,
                                $city,
                                $connectAPI,
                                $ui_Double
                            );
                            if ($result === 1) {
                                $messageAdmin = "123 Double (нал) отменен по выходу из вилки, номер заказа: $ui_Double";
                                (new MessageSentController)->sentMessageAdmin($messageAdmin);
                            }

                        } else {
                            $messageAdmin = "321 Double (нал) отменен ранее ранее до выхода из вилки, номер заказа: $ui_Double";
                            (new MessageSentController)->sentMessageAdmin($messageAdmin);
                        }
                    }

                    (new UIDController())->UIDStatusReviewDaily();

                    return;
                }
            } catch (\Exception $e) {
                Log::error("Ошибка в startNewProcessExecutionStatusJob для orderId: {$this->orderId}, jobId: {$this->jobId}: " . $e->getMessage());
                throw $e; // Повторно выбросить исключение, чтобы задание пометилось как неудачное
            }

        }


    }



}


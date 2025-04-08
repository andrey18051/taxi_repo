<?php

namespace App\Jobs;

use App\Http\Controllers\AndroidTestOSMController;
use App\Http\Controllers\MessageSentController;
use App\Http\Controllers\UniversalAndroidFunctionController;
use App\Http\Controllers\WfpController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StartCancelOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $uid;
    protected $uid_Double;
    protected $pay_method;
    protected $orderReference;
    protected $application;
    protected $city;
    protected $transactionStatus;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        $uid,
        $uid_Double,
        $pay_method,
        $city,
        $application
    ) {
        $this->uid = $uid;
        $this->uid_Double = $uid_Double;
        $this->pay_method = $pay_method;
        $this->city = $city;
        $this->application = $application;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        $messageAdmin = "Запущен процесс удаления заказа $this->uid и $this->uid_Double";
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);
        $result = (new AndroidTestOSMController)->webordersCancelDoubleWithoutReviewHold(
            $this->uid,
            $this->uid_Double,
            $this->pay_method,
            $this->city,
            $this->application,
        );


        if ($result === null) {
            Log::info("Задача StartCancelOrder $this->uid завершена");
            $messageAdmin = "Задача StartCancelOrder $this->uid завершена";
            (new MessageSentController)->sentMessageAdmin($messageAdmin);
            return;
        }

        Log::info("Задача StartCancelOrder $this->uid завершена");
    }
}

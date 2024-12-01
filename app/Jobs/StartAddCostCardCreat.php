<?php

namespace App\Jobs;

use App\Http\Controllers\MessageSentController;
use App\Http\Controllers\UniversalAndroidFunctionController;
use App\Http\Controllers\WfpController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StartAddCostCardCreat implements ShouldQueue
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
        $orderReference,
        $application,
        $city,
        $transactionStatus
    ) {
        $this->uid = $uid;
        $this->uid_Double = $uid_Double;
        $this->pay_method = $pay_method;
        $this->orderReference = $orderReference;
        $this->application = $application;
        $this->city = $city;
        $this->transactionStatus = $transactionStatus;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        $messageAdmin = "Запущен процесс создания нового безнального заказа с добавкой +20грн к стоимости заказа $this->uid ";
        (new MessageSentController)->sentMessageAdmin($messageAdmin);
        (new UniversalAndroidFunctionController)->startAddCostCardCreat(
            $this->uid,
            $this->uid_Double,
            $this->pay_method,
            $this->orderReference,
            $this->transactionStatus,
            $this->city
        );
    }
}

<?php

namespace App\Console\Commands;

use App\Http\Controllers\BonusBalanceController;
use App\Http\Controllers\DailyTaskController;
use App\Models\Orderweb;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RestartTask extends Command
{
    protected $signature = 'restart-task:run';
    protected $description = 'Run the restart task';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $message = "Это тестовое сообщение прихожит при перезапуске сервера";
        (new \App\Http\Controllers\DailyTaskController)->sentTaskMessage($message);

               // Выполняйте вашу логику здесь
//        $orderwebs = Orderweb::whereNotNull('fondy_order_id')
//            ->where('fondy_status_pay', 'approved')
//            ->get();
//
//        Log::debug($this->description);
//
//        foreach ($orderwebs as $orderweb) {
//            // Ваш код для обработки каждой записи
//            (new \App\Http\Controllers\FondyController)->fondyStatusReviewAdmin($orderweb['fondy_order_id']);
//        }
//
//        $orderwebs = Orderweb::whereNotNull('wfp_order_id')
//            ->where('wfp_status_pay', 'Approved')
//            ->where('wfp_status_pay', 'WaitingAuthComplete')
//            ->get();
//
//        foreach ($orderwebs as $orderweb) {
//            // Ваш код для обработки каждой записи
//            (new \App\Http\Controllers\WfpController())->wfpStatusReviewAdmin($orderweb['wfp_order_id']);
//        }
//        (new \App\Http\Controllers\BonusBalanceController)->balanceReviewDaily();
    }
}


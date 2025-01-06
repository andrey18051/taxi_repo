<?php

namespace App\Console\Commands;

use App\Http\Controllers\BonusBalanceController;
use App\Http\Controllers\DailyTaskController;
use App\Http\Controllers\UIDController;
use App\Models\Orderweb;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DailyTask extends Command
{
    protected $signature = 'daily-task:run';
    protected $description = 'Run the daily task for eligible orderwebs';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {

//        $message = "Запуск задачи проверки холдов и перепроверки статусов заказов в работе на рабочем сервере";
//        (new DailyTaskController)->sentTaskMessage($message);
        (new UIDController())->UIDStatusReviewDaily();
        (new DailyTaskController)->orderCardWfpReviewTask();
        (new DailyTaskController)->orderBonusReviewTask();
    }
}

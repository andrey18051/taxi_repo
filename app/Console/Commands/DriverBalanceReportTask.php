<?php

namespace App\Console\Commands;

use App\Http\Controllers\OrdersRefusalController;
use App\Http\Controllers\ReportController;
use Illuminate\Console\Command;

class DriverBalanceReportTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'driver-balance-report-task:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Отправка отчета по балансу водителей';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        (new ReportController)->reportBalanceDriver();

        //Очистка
        (new OrdersRefusalController())->cleanOrderRefusalTable();

        return 0;
    }
}

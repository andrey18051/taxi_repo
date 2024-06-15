<?php

namespace App\Console\Commands;

use App\Http\Controllers\DailyTaskController;
use Illuminate\Console\Command;

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
        (new DailyTaskController)->restartProcessExecutionStatus();
    }
}

<?php

namespace App\Console\Commands;

use App\Http\Controllers\DailyTaskController;
use Illuminate\Console\Command;

class VersionApiTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'versionApiTask:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'versionApiTask verify version taxi servers';

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
        (new DailyTaskController)->verifyVersionApiTaskPas1();
        (new DailyTaskController)->verifyVersionApiTaskPas2();
        (new DailyTaskController)->verifyVersionApiTaskPas4();
        return 0;
    }
}

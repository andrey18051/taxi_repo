<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SendIPReportJob;

class SendIPReportCommand extends Command
{
    protected $signature = 'report:send-ip';
    protected $description = 'Send IP report for records with PAS in page';

    public function handle()
    {
        $email = 'andrey18051@gmail.com'; // Замените на нужный email
        $filter = 'PAS';

        SendIPReportJob::dispatch($email, $filter);

        $this->info("IP report job dispatched at " . now());

        return Command::SUCCESS;
    }
}

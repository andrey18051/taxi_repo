<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\IPReportService;

class SendIPReportCommand extends Command
{
    protected $signature = 'report:send-ip';
    protected $description = 'Send IP report for records with PAS in page';

    public function handle()
    {
        try {
            $service = new IPReportService('andrey18051@gmail.com', 'PAS');
            $service->send();

            $this->info("✅ IP report sent successfully at " . now());

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

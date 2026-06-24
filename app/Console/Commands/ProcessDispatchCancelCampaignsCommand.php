<?php

namespace App\Console\Commands;

use App\Services\DispatchOrderCancelService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessDispatchCancelCampaignsCommand extends Command
{
    protected $signature = 'dispatch-cancel:process-due';

    protected $description = 'Run due dispatch order cancel background retries (queue fallback)';

    public function handle(DispatchOrderCancelService $service): int
    {
        $processed = $service->processDueCampaigns();

        if ($processed > 0) {
            Log::info('ProcessDispatchCancelCampaigns: processed=' . $processed);
        }

        return self::SUCCESS;
    }
}

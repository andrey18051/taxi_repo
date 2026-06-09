<?php

namespace App\Console\Commands;

use App\Services\OrderAutoCancelService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScheduledAutoCancelCommand extends Command
{
    protected $signature = 'orders:auto-cancel-scheduled';

    protected $description = 'Auto-cancel pre-orders with no car 15 minutes after required_time';

    public function handle(OrderAutoCancelService $autoCancelService): int
    {
        $candidates = $autoCancelService->findScheduledOrdersPendingCancel();
        $cancelled = 0;

        foreach ($candidates as $order) {
            if ($autoCancelService->tryCancelScheduledOrder($order)) {
                $cancelled++;
            }
        }

        Log::info("ScheduledAutoCancel: checked={$candidates->count()} cancelled={$cancelled}");

        return self::SUCCESS;
    }
}

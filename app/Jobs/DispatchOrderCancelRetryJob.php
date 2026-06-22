<?php

namespace App\Jobs;

use App\Services\DispatchOrderCancelService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchOrderCancelRetryJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    private string $primaryUid;

    public function __construct(string $primaryUid)
    {
        $this->primaryUid = $primaryUid;
    }

    public function uniqueId(): string
    {
        return 'dispatch_cancel_' . $this->primaryUid;
    }

    public function handle(DispatchOrderCancelService $service): void
    {
        $service->runAttempt($this->primaryUid);
    }
}

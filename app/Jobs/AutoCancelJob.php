<?php

namespace App\Jobs;

use App\Services\OrderAutoCancelService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AutoCancelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $uid;

    public function __construct($uid)
    {
        $this->uid = $uid;
    }

    /**
     * @throws \Exception
     */
    public function handle(OrderAutoCancelService $autoCancelService)
    {
        $autoCancelService->tryCancelImmediateOrder($this->uid);
    }
}

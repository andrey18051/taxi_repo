<?php

namespace App\Jobs;

use App\Services\DispatchOrderCancelService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchOrderCancelRetryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public $timeout = 120;

    /** @var string */
    protected $primaryUid;

    public function __construct($primaryUid)
    {
        $this->primaryUid = $primaryUid;
    }

    public function handle(DispatchOrderCancelService $service)
    {
        $service->runBackgroundAttempt($this->primaryUid);
    }
}

<?php

namespace App\Jobs;

use App\Http\Controllers\AndroidTestOSMController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessCostSearchMarkersTime implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $params;

    /**
     * Create a new job instance.
     */
    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * Execute the job.
     * @throws \Exception
     */
    public function handle(): void
    {
        $controller = new AndroidTestOSMController();
        $controller->costSearchMarkersTime(
            $this->params['originLatitude'] ?? null,
            $this->params['originLongitude'] ?? null,
            $this->params['toLatitude'] ?? null,
            $this->params['toLongitude'] ?? null,
            $this->params['tarif'] ?? null,
            $this->params['phone'] ?? null,
            $this->params['user'] ?? null,
            $this->params['time'] ?? null,
            $this->params['date'] ?? null,
            $this->params['services'] ?? null,
            $this->params['city'] ?? null,
            $this->params['application'] ?? null
        );
    }
}

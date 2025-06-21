<?php

namespace App\Jobs;

use App\Http\Controllers\AndroidTestOSMController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class WebordersCancelAndRestorNalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $uid;
    protected $city;
    protected $application;
    protected $order;

    /**
     * Create a new job instance.
     */
    public function __construct(string $uid, string $city, string $application, $order)
    {
        $this->uid = $uid;
        $this->city = $city;
        $this->application = $application;
        $this->order = $order;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $controller = new AndroidTestOSMController();
            $result = $controller->webordersCancelRestorAddCostNal(
                $this->uid,
                $this->city,
                $this->application,
                $this->order
            );

            // Check the response to determine if the job should be considered failed
            if (isset($result['response'])) {
                Log::info('WebordersCancelAndRestorNalJob completed successfully: ' . $result['response']);
                return;
            } else {
                // Job will complete if no naturally  exception is thrown
                Log::error('WebordersCancelAndRestorDoubleJob failed: ' . $result['response']);
                $this->fail(new \Exception($result['response']));
            }
        } catch (\Exception $e) {
            Log::error('Error in WebordersCancelAndRestorNalJob: ' . $e->getMessage());
            $this->fail($e);
        }
    }
}

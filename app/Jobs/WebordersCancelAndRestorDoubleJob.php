<?php

namespace App\Jobs;

use App\Http\Controllers\AndroidTestOSMController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class WebordersCancelAndRestorDoubleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $uid;
    protected $uidDouble;
    protected $city;
    protected $application;
    protected $order;

    public $timeout = 300;
    public $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(string $uid, string $uidDouble, string $city, string $application, $order)
    {
        $this->uid = $uid;
        $this->uidDouble = $uidDouble;
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
            $result = $controller->webordersCancelAndRestorDouble(
                $this->uid,
                $this->uidDouble,
                $this->city,
                $this->application,
                $this->order
            );

            // Check the response to determine if the job should be considered failed
            if (isset($result['response']) && strpos($result['response'], 'не вдалося скасувати') !== false) {
                Log::error('WebordersCancelAndRestorDoubleJob failed: ' . $result['response']);
                $this->fail(new \Exception($result['response']));
            } else {
                Log::info('WebordersCancelAndRestorDoubleJob completed successfully: ' . $result['response']);
                // Job will complete naturally if no exception is thrown
                return;
            }
        } catch (\Exception $e) {
            Log::error('Error in WebordersCancelAndRestorDoubleJob: ' . $e->getMessage());
            $this->fail($e);
        }
    }
}

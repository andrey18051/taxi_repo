<?php

namespace App\Jobs;

use App\Http\Controllers\MessageSentController;
use App\Http\Controllers\WfpController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $application;
    protected $city;
    protected $orderReference;

    /**
     * Create a new job instance.
     */
    public function __construct($application, $city, $orderReference)
    {
        $this->application = $application;
        $this->city = $city;
        $this->orderReference = $orderReference;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            $messageAdmin = "Запущен процесс CheckStatusJob $this->orderReference";
            (new MessageSentController)->sentMessageAdminLog($messageAdmin);

            $paymentController = new WfpController();
            $result = $paymentController->checkStatusJob($this->application, $this->city, $this->orderReference);


            Log::info("CheckStatusJob завершён", [
                "application" => $this->application,
                "city" => $this->city,
                "orderReference" => $this->orderReference,
                "result" => $result
            ]);

            $messageAdmin = "CheckStatusJob завершён" . "application " . $this->application . "city " .$this->city . "orderReference " .$this->orderReference . " result " . $result;
            (new MessageSentController)->sentMessageAdminLog($messageAdmin);
        } catch (\Exception $e) {
            Log::error("Ошибка в CheckStatusJob: " . $e->getMessage(), [
                "application" => $this->application,
                "city" => $this->city,
                "orderReference" => $this->orderReference
            ]);
        }
    }
}

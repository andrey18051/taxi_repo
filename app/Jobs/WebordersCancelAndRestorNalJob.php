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

    public $tries = 1; // Максимум 1 попыток
    public $timeout = 30; // Таймаут 30 секунд


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
        Log::info("Starting WebordersCancelAndRestorNalJob", [
            'uid' => $this->uid,
            'city' => $this->city,
            'application' => $this->application,
            'order_id' => $this->order->id ?? 'N/A',
            'attempt' => $this->attempts(),
        ]);

        try {
            $controller = new AndroidTestOSMController();
            $result = $controller->webordersCancelRestorAddCostNal(
                $this->uid,
                $this->city,
                $this->application,
                $this->order
            );

            // Проверяем результат выполнения
            if (isset($result['response']) && $result['response'] === '200') {
                Log::info("WebordersCancelAndRestorNalJob completed successfully", [
                    'uid' => $this->uid,
                    'response' => $result['response'],
                ]);
                return;
            }

            // Если response отсутствует или некорректен
            Log::error("WebordersCancelAndRestorNalJob failed: Invalid or missing response", [
                'uid' => $this->uid,
                'result' => $result,
            ]);
            $this->fail(new \Exception('Invalid or missing response: ' . json_encode($result)));
        } catch (\Exception $e) {
            Log::error("Error in WebordersCancelAndRestorNalJob", [
                'uid' => $this->uid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->fail($e);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Exception $exception): void
    {
        Log::critical("WebordersCancelAndRestorNalJob failed permanently", [
            'uid' => $this->uid,
            'city' => $this->city,
            'application' => $this->application,
            'order_id' => $this->order->id ?? 'N/A',
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Отправка уведомления администратору
        try {
            \Illuminate\Support\Facades\Notification::route('mail', 'taxi.easy.ua.sup@gmail.com')
                ->notify(new \App\Notifications\FailedJobsAlert(1));
        } catch (\Exception $e) {
            Log::error("Failed to send notification for WebordersCancelAndRestorNalJob", [
                'uid' => $this->uid,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

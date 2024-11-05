<?php

namespace App\Jobs;

use App\Http\Controllers\DriverController;
use App\Http\Controllers\FCMController;
use App\Http\Controllers\UniversalAndroidFunctionController;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeleteOrderPersonal implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $driver_uid;
    protected $dispatching_order_uid;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        $dispatching_order_uid,
        $driver_uid
    ) {
        $this->dispatching_order_uid = $dispatching_order_uid;
        $this->driver_uid = $driver_uid;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("DeleteOrderPersonal job started for order UID: {$this->dispatching_order_uid} and driver UID: {$this->driver_uid}");

        // Подождем 20 секунд
        sleep(20);

        // Запускаем метод
        try {
            (new FCMController)->deleteOrderPersonalDocumentFromFirestore(
                $this->dispatching_order_uid,
                $this->driver_uid
            );
            Log::info("Successfully called deleteOrderPersonalDocumentFromFirestore.");
        } catch (\Exception $e) {
            Log::error("Error executing deleteOrderPersonalDocumentFromFirestore: " . $e->getMessage());
        }
    }

}

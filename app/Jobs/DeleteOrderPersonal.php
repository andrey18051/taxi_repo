<?php

namespace App\Jobs;

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
    protected $created_at;
    protected $order;
    protected $driver_uid;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        $created_at,
        $order,
        $driver_uid
    ) {
        $this->created_at = $created_at;
        $this->order= $order;
        $this->driver_uid = $driver_uid;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("DeleteOrderPersonal
            $this->created_at,
            $this->order,
            $this->driver_uid");


        do {
            $time2 = (new FCMController())->currentKievDateTime();
            $carbonTime1 = Carbon::createFromFormat('d.m.Y H:i:s', $this->created_at, 'Europe/Kiev');
            $carbonTime2 = Carbon::createFromFormat('d.m.Y H:i:s', $time2, 'Europe/Kiev');

            sleep(1);
        } while ($carbonTime1->diffInSeconds($carbonTime2)<20);

        //Запускаем через 20 секунд

        if (!(new FCMController())->verifyRefusal($this->order->id, $this->driver_uid)) {
            (new FCMController())->autoDeleteOrderPersonal(
                $this->created_at,
                $this->order,
                $this->driver_uid
            );
        }
    }
}

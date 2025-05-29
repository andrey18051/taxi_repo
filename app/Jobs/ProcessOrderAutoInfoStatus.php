<?php

namespace App\Jobs;

use App\Http\Controllers\OrderStatusController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessOrderAutoInfoStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $uidBonusOrderHold;

    /**
     * Create a new job instance.
     *
     * @param string $uidBonusOrderHold
     * @return void
     */
    public function __construct(string $uidBonusOrderHold)
    {
        $this->uidBonusOrderHold = $uidBonusOrderHold;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Вызов метода контроллера
        (new OrderStatusController)->getOrderAutoInfoStatus($this->uidBonusOrderHold);
    }
}

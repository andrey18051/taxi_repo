<?php

namespace App\Jobs;

use App\Http\Controllers\UniversalAndroidFunctionController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StartOrderReview implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $bonusOrder;
    protected $doubleOrder;
    protected $bonusOrderHold;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        (new UniversalAndroidFunctionController)->orderReview(
            $this->bonusOrder,
            $this->doubleOrder,
            $this->bonusOrderHold
        );
    }
}

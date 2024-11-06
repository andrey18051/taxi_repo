<?php

namespace App\Jobs;

use App\Http\Controllers\AndroidTestOSMController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SearchOrderToDeleteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    protected $originLatitude;
    protected $originLongitude;
    protected $toLatitude;
    protected $toLongitude;
    protected $email;
    protected $start;
    protected $finish;
    protected $payment_type;
    protected $city;
    protected $application;

    public function __construct(
        $originLatitude,
        $originLongitude,
        $toLatitude,
        $toLongitude,
        $email,
        $start,
        $finish,
        $payment_type,
        $city,
        $application
    ) {
        $this->originLatitude = $originLatitude;
        $this->originLongitude  = $originLongitude;
        $this->toLatitude = $toLatitude;
        $this->toLongitud = $toLongitude;
        $this->email = $email;
        $this->start = $start;
        $this->finish = $finish;
        $this->payment_type = $payment_type;
        $this->city = $city;
        $this->application = $application;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        (new AndroidTestOSMController)->searchOrderToDelete(
            $this->originLatitude,
            $this->originLongitude,
            $this->toLatitude,
            $this->toLongitude,
            $this->email,
            $this->start,
            $this->finish,
            $this->payment_type,
            $this->city,
            $this->application
        );
    }
}

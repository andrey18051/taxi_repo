<?php

namespace App\Jobs;

use App\Http\Controllers\UniversalAndroidFunctionController;
use App\Http\Controllers\WfpController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TokenPayJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $application, $city, $orderReference, $amount, $productName, $clientEmail, $clientPhone, $order_id, $pay_system;

    public function __construct($application, $city, $orderReference, $amount, $productName, $clientEmail, $clientPhone, $order_id, $pay_system)
    {
        $this->application = $application;
        $this->city = $city;
        $this->orderReference = $orderReference;
        $this->amount = $amount;
        $this->productName = $productName;
        $this->clientEmail = $clientEmail;
        $this->clientPhone = $clientPhone;
        $this->order_id = $order_id;
        $this->pay_system = $pay_system;
    }

    public function handle()
    {
        (new UniversalAndroidFunctionController)->orderIdMemoryToken($this->orderReference, $this->order_id, $this->pay_system);
        (new WfpController)->chargeActiveToken(
            $this->application,
            $this->city,
            $this->orderReference,
            $this->amount,
            $this->productName,
            $this->clientEmail,
            $this->clientPhone
        );
        (new WfpController)->checkStatus(
            $this->application,
            $this->city,
            $this->orderReference
        );
    }
}

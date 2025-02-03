<?php

namespace App\Jobs;

use App\Http\Controllers\UniversalAndroidFunctionController;
use App\Http\Controllers\WfpController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
        Log::info("🚀 [TokenPayJob] Начало обработки платежа", [
            'orderReference' => $this->orderReference,
            'order_id'       => $this->order_id,
            'pay_system'     => $this->pay_system,
            'amount'         => $this->amount,
            'city'           => $this->city,
        ]);

        try {
            Log::debug("📝 Сохранение токена платежа...");
            (new UniversalAndroidFunctionController)->orderIdMemoryToken(
                $this->orderReference,
                $this->order_id,
                $this->pay_system
            );
            Log::info("✅ Токен успешно сохранён для заказа {$this->orderReference}");

            Log::debug("💳 Запуск chargeActiveToken...");
            (new WfpController)->chargeActiveToken(
                $this->application,
                $this->city,
                $this->orderReference,
                $this->amount,
                $this->productName,
                $this->clientEmail,
                $this->clientPhone
            );
            Log::info("✅ Платёж успешно инициирован для заказа {$this->orderReference}");

            Log::debug("🔍 Проверка статуса платежа...");
            (new WfpController)->checkStatus(
                $this->application,
                $this->city,
                $this->orderReference
            );
            Log::info("✅ Статус платежа проверен для заказа {$this->orderReference}");

        } catch (\Exception $e) {
            Log::error("❌ Ошибка при обработке платежа для заказа {$this->orderReference}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // чтобы Laravel мог повторить задачу в случае ошибки
        }

        Log::info("🏁 [TokenPayJob] Обработка платежа завершена для заказа {$this->orderReference}");
    }
}

<?php

namespace App\Jobs;

use App\Http\Controllers\PusherController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\WfpInvoice;

class SimplePollStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $orderReference;
    protected $dispatching_order_uid;
    protected $application;
    protected $email;
    protected $attempts = 0; // Начальное значение
    protected $maxTime = 50; // секунды
    protected $checkInterval = 3; // секунды

    // Изменяем конструктор - добавляем параметр для attempts
    public function __construct(
        $orderReference,
        $dispatching_order_uid,
        $application,
        $email,
        $attempts = 0  // ← Новый параметр для передачи счетчика
    )
    {
        $this->orderReference = $orderReference;
        $this->dispatching_order_uid = $dispatching_order_uid;
        $this->application = $application;
        $this->email = $email;
        $this->attempts = $attempts; // Устанавливаем счетчик

        Log::info("📡 Задача опроса статуса #{$this->attempts} запущена для: {$orderReference}");
        Log::info("📡 SimplePollStatusJob конструктор вызван", [
            'orderReference' => $orderReference,
            'attempts' => $attempts,
            'file' => __FILE__,
            'line' => __LINE__
        ]);
    }

    /**
     * @throws \Pusher\PusherException
     * @throws \Pusher\ApiErrorException
     */
    public function handle()
    {
        // Увеличиваем счетчик
        $this->attempts++;
        $currentTime = $this->attempts * $this->checkInterval;

        Log::info("🔄 Проверка #{$this->attempts} (секунда {$currentTime}/{$this->maxTime})");

        $invoice = WfpInvoice::where("orderReference", $this->orderReference)->first();

        if (!$invoice) {
            Log::warning("Invoice не найден");
            $this->rescheduleOrExit($currentTime);
            return;
        }

        $transactionStatus = $invoice->transactionStatus;
         if(!$transactionStatus) {
             Log::warning("transactionStatus не найден");
             return;
         }
        switch ($transactionStatus) {
            case 'Declined':
                Log::warning("❌ Платеж отклонен");
                $this->sendPushNotification($transactionStatus);
                return;

            case 'WaitingAuthComplete':
            case 'Approved':
                Log::info("✅ Успешный статус: {$transactionStatus}");
                return;

            default:
                Log::debug("⏳ Статус: " . ($transactionStatus ?? 'ожидание'));
                $this->rescheduleOrExit($currentTime);
        }
    }

    private function rescheduleOrExit($currentTime): void
    {
        if ($currentTime < $this->maxTime) {
            Log::debug("⏱️ Повтор через {$this->checkInterval} секунд (попытка {$this->attempts})");

            // Передаем ВСЕ параметры, включая текущий счетчик
            self::dispatch(
                $this->orderReference,
                $this->dispatching_order_uid,
                $this->application,
                $this->email,
                $this->attempts  // ← Ключевое изменение!
            )->delay(now()->addSeconds($this->checkInterval));
        } else {
            Log::warning("⏰ Время опроса истекло (50 сек)");

        }
    }

    /**
     * @throws \Pusher\PusherException
     * @throws \Pusher\ApiErrorException
     */
    private function sendPushNotification(
        $transactionStatus = null  // Делаем необязательным
    ): void
    {
        // Для отладки
        if ($transactionStatus === null) {
            Log::error("sendPushNotification вызван без аргумента!");
            $transactionStatus = 'Unknown';
        }

        Log::info("📲 Отправлен пуш об отклоненном платеже");
        (new PusherController)->sentStatusWfp(
            $transactionStatus,
            $this->dispatching_order_uid,
            $this->application,
            $this->email
        );
    }
}

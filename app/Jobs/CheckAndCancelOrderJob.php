<?php

namespace App\Jobs;

use App\Http\Controllers\PusherController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Orderweb;
use App\Http\Controllers\WfpController;
use App\Models\WfpInvoice;
use App\Http\Controllers\MemoryOrderChangeController;
use App\Http\Controllers\FCMController;

class CheckAndCancelOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $uid;
    protected $app;
    protected $email;

    public function __construct($uid, $app, $email)
    {
        $this->uid = $uid;
        $this->app = $app;
        $this->email = $email;
        Log::info("🔄 Задача отмены заказа создана. Входной UID: {$uid} app: {$app} email: {$email}");
    }

    public function handle()
    {
        Log::info("🚀 Начало обработки отмены заказа. UID: {$this->uid}");

        try {
            $this->processOrderCancellation();
            Log::info("✅ Завершение обработки заказа. UID: {$this->uid}");

        } catch (\Exception $e) {
            Log::error("💥 Критическая ошибка при обработке заказа {$this->uid}: " . $e->getMessage());
            Log::error("📋 Stack trace: " . $e->getTraceAsString());
        }
    }

    private function processOrderCancellation(): void
    {
        // 1. Получаем order UID
        Log::debug("📋 Получение order UID из MemoryOrderChangeController");
        $uid = (new MemoryOrderChangeController)->show($this->uid);
        Log::info("🔑 Получен order UID: {$uid}");

        // 2. Ищем заказ в базе
        Log::debug("🔍 Поиск заказа в базе данных по UID: {$uid}");
        $order = Orderweb::where("dispatching_order_uid", $uid)->first();

        if (is_null($order)) {
            Log::error("❌ Заказ не найден в базе данных. UID: {$uid}");
            return;
        }

        Log::info("✅ Заказ найден. ID: {$order->id}, создан: {$order->created_at}");

        // 3. Проверяем merchant account
        Log::debug("🏦 Проверка данных мерчанта");
        $merchantInfo = (new WfpController)->checkMerchantInfo($order);

        if (isset($merchantInfo["merchantAccount"]) && $merchantInfo["merchantAccount"] == "errorMerchantAccount") {
            $order->transactionStatus = "errorMerchantAccount";
            $order->save();

            Log::warning("⚠️ Мерчант не найден для заказа {$uid}. Устанавливаем статус errorMerchantAccount");
            $this->cleanupOrder($uid);
            return;
        }

        Log::debug("✅ Данные мерчанта проверены");

        // 4. Проверяем invoice статус
        Log::debug("💰 Проверка статуса оплаты заказа");
        $orderReference = $order->wfp_order_id;

        // Если orderReference null - отменяем
        if ($orderReference === null) {
            Log::warning("⚠️ Номер транзакции (orderReference) отсутствует. Отменяем заказ {$uid}");
            $this->cleanupOrder($uid);
            return;
        }

        Log::info("💳 Номер транзакции заказа: {$orderReference}");

        // Ищем invoice
        Log::debug("🔍 Поиск информации о транзакции в таблице WfpInvoice");
        $invoice = WfpInvoice::where("orderReference", $orderReference)->first();

        // Если invoice не найден - отменяем
        if (!$invoice) {
            Log::warning("⚠️ Информация о транзакции не найдена в WfpInvoice для orderReference: {$orderReference}");
            $this->cleanupOrder($uid);
            return;
        }

        Log::debug("✅ Транзакция найдена. Invoice ID: {$invoice->id}");

        // 5. Проверяем статус транзакции
        $transactionStatus = $invoice->transactionStatus;
        Log::info("📊 Текущий статус транзакции: " . ($transactionStatus ?? 'NULL'));

        // Разрешенные статусы (НЕ отменяем)
        $allowedStatuses = ['WaitingAuthComplete', 'Approved'];

        if ($transactionStatus === null) {
            Log::warning("⚠️ Статус транзакции не установлен (NULL). Отменяем заказ {$uid}");
            $this->cleanupOrder($uid);
            return;
        }

        // Проверяем разрешенные статусы
        if (in_array($transactionStatus, $allowedStatuses)) {
            Log::info("✅ Статус '{$transactionStatus}' разрешен. Заказ {$uid} сохраняется");
            return;
        }

        // Проверяем отклоненные статусы
        if ($transactionStatus === 'Declined') {
            Log::warning("❌ Платеж отклонен (Declined). Отменяем заказ {$uid}");
        } else {
            Log::warning("⚠️ Неразрешенный статус '{$transactionStatus}'. Отменяем заказ {$uid}");
        }

        $this->cleanupOrder($uid);
    }

    private function cleanupOrder(
        $uid
    ): void
    {
        Log::info("🧹 Начало очистки данных заказа {$uid}");

        try {
            $fcmController = new FCMController();

            // Удаление из Firestore
            Log::debug("🔥 Удаление документа из основного Firestore");
            $result1 = $fcmController->deleteDocumentFromFirestore($uid);
            Log::info(($result1 ? "✅" : "❌") . " Удаление из основного Firestore");

            Log::debug("🔥 Удаление документа из Firestore OrdersTakingCancel");
            $result2 = $fcmController->deleteDocumentFromFirestoreOrdersTakingCancel($uid);
            Log::info(($result2 ? "✅" : "❌") . " Удаление из Firestore OrdersTakingCancel");

            Log::debug("🔥 Удаление документа из Sector Firestore");
            $result3 = $fcmController->deleteDocumentFromSectorFirestore($uid);
            Log::info(($result3 ? "✅" : "❌") . " Удаление из Sector Firestore");

            // Запись в историю
            Log::debug("📝 Запись отмененного заказа в историю Firestore");
            $result4 = $fcmController->writeDocumentToHistoryFirestore($uid, "cancelled");
            Log::info(($result4 ? "✅" : "❌") . " Запись в историю Firestore");

            Log::debug("📝 Запись отменены заказа в таблицу заказов");

            $order = Orderweb::where("dispatching_order_uid", $uid)->first();
            $order->closeReason = "1";
            $order->save();
            //Пуш об отмене заказа
            (new PusherController)->sentCanceledStatus(
                $this->app,
                $this->email,
                $uid
            );
            Log::info("🧹 Очистка данных заказа {$uid} завершена");

        } catch (\Exception $e) {
            Log::error("💥 Ошибка при очистке данных заказа {$uid}: " . $e->getMessage());
        }
    }
}

// При создании заказа или в нужном месте
//CheckAndCancelOrderJob::dispatch($uid)
//    ->delay(now()->addSeconds(50))
//    ->onQueue('high'); // Укажите очередь, если нужно

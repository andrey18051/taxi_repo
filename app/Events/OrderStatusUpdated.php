<?php

namespace App\Events;

use App\Http\Controllers\MessageSentController;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OrderStatusUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order_uid;
    private $channelName;
    private $eventName;

    /**
     * Создание нового экземпляра события.
     *
     * @param string $order_uid  UID заказа
     * @param string $cityCode   Код города (например, "PAS1")
     * @param string $channelName Название канала (по умолчанию "teal-towel-48")
     */
    public function __construct(string $order_uid, string $cityCode, string $channelName = 'teal-towel-48')
    {
        $this->order_uid = $order_uid;
        $this->channelName = $channelName;
        $this->eventName = "order-status-updated-" . strtoupper($cityCode); // Формирование имени события

        Log::info("OrderStatusUpdated event __construct: order_uid={$order_uid}, channelName={$channelName}, eventName={$this->eventName}");
    }

    /**
     * Определяет, на каких каналах должно транслироваться событие.
     *
     * @return array
     */
    public function broadcastOn(): array
    {
        $messageAdmin = "Broadcasting event '{$this->eventName}' on channel '{$this->channelName}': " . $this->order_uid;
        Log::info("OrderStatusUpdated event: " . $messageAdmin);

        // Вызываем метод для записи в лог через контроллер
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);

        return [$this->channelName];
    }

    /**
     * Определяет имя события для трансляции.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return $this->eventName;
    }
}

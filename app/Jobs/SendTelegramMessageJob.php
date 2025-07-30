<?php

namespace App\Jobs;

use App\Helpers\Telegram;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class SendTelegramMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $bot;
    protected $chatId;
    protected $message;
    public $tries = 1; // Увеличено до 3 попыток
    public $timeout = 5;

    /**
     * Create a new job instance.
     */
    public function __construct($bot, $chatId, $message)
    {
        $this->bot = $bot;
        $this->chatId = $chatId;
        $this->message = $message;
        // Очередь по умолчанию можно задать здесь, но мы будем указывать при диспетчеризации
    }

    /**
     * Execute the job.
     */
    public function handle(): bool
    {
        // Блокировка для изоляции задач по chatId
        $lock = Cache::lock('telegram_' . $this->chatId, 10);
        if ($lock->get()) {
            try {
                $response = Http::post(Telegram::url . $this->bot . '/sendMessage', [
                    'chat_id' => $this->chatId,
                    'text' => $this->message,
                    'parse_mode' => 'html',
                ]);

                if ($response->successful()) {
                    Log::info("Message sent to chat {$this->chatId}: {$this->message}");
                    $lock->release();
                    return true;
                }

                throw new \Exception('Telegram API error: ' . $response->body());
            } catch (Exception $e) {
                $lock->release();
                if (str_contains($e->getMessage(), '429')) {
                    Log::warning("Rate limit hit for chat {$this->chatId}, retrying in 60 seconds");
                    $this->release(60); // Задержка 60 секунд при 429
                } else {
                    Log::error("Failed to send message to chat {$this->chatId}: {$e->getMessage()}");
                    $this->fail($e); // Пометить задачу как неуспешную
                }
            }
        } else {
            Log::warning("Lock not acquired for chat {$this->chatId}, retrying in 10 seconds");
            $this->release(10); // Повторная попытка через 10 секунд
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception)
    {
        Log::error("SendTelegramMessageJob failed for chat {$this->chatId}: {$exception->getMessage()}");
        // Дополнительная логика, например, уведомление через Slack
    }
}

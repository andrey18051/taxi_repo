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
use Illuminate\Http\Client\RequestException;

class SendTelegramMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $bot;
    protected $chatId;
    protected $message;
    public $tries = 1; // Только одна попытка для предотвращения повторных отправок
    public $timeout = 30; // Таймаут 30 секунд
    public $uniqueId; // Уникальный идентификатор задачи

    /**
     * Create a new job instance.
     */
    public function __construct($bot, $chatId, $message)
    {
        $this->bot = $bot;
        $this->chatId = $chatId;
        $this->message = $message;
        $this->queue = 'telegram'; // Специальная очередь для Telegram
        $this->uniqueId = md5($chatId . $message . time()); // Уникальный ID для блокировки
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->uniqueId;
    }

    /**
     * Execute the job.
     */
    public function handle(): bool
    {
        Log::debug("Starting SendTelegramMessageJob: chatId={$this->chatId}, message=" . substr($this->message, 0, 50) . "...");

        // Используем уникальную блокировку для этой задачи
        $lockKey = 'telegram_' . $this->uniqueId;
        $lock = Cache::lock($lockKey, 10);

        if (!$lock->get()) {
            Log::warning("Could not acquire lock for SendTelegramMessageJob: chatId={$this->chatId}, uniqueId={$this->uniqueId}");
            $this->delete(); // Удаляем задачу, чтобы не пытаться снова
            return false;
        }

        try {
            $response = Http::timeout(30)
                ->post(Telegram::url . $this->bot . '/sendMessage', [
                    'chat_id' => $this->chatId,
                    'text' => $this->message,
                    'parse_mode' => 'html',
                ]);

            if ($response->successful()) {
                Log::info("Message sent successfully to chatId={$this->chatId}");
                $lock->release();
                return true;
            }

            throw new RequestException($response);
        } catch (RequestException $e) {
            $lock->release();
            if ($e->response->status() === 429) {
                Log::warning("Rate limit hit for chatId={$this->chatId}, message not sent");
            } else {
                Log::error("Failed to send Telegram message to chatId={$this->chatId}: status={$e->response->status()}, error=" . $e->getMessage());
            }
            $this->fail($e); // Пометить задачу как неуспешную
            return false;
        } catch (\Exception $e) {
            $lock->release();
            Log::error("Unexpected error in SendTelegramMessageJob for chatId={$this->chatId}: " . $e->getMessage());
            $this->fail($e); // Пометить задачу как неуспешную
            return false;
        }
    }
}

<?php

namespace App\Jobs;

use App\Helpers\Telegram;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendTelegramMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $bot;
    protected $chatId;
    protected $message;

    public $tries = 1;    // Только одна попытка
    public $timeout = 60; // Таймаут 60 секунд


    /**
     * Create a new job instance.
     */
    public function __construct($bot, $chatId, $message)
    {
        $this->bot = $bot;
        $this->chatId = $chatId;
        $this->message = $message;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = now();
        Log::info("Starting SendTelegramMessageJob", [
            'bot' => $this->bot,
            'chat_id' => $this->chatId,
            'message' => substr($this->message, 0, 100) . (strlen($this->message) > 100 ? '...' : ''),
            'attempt' => $this->attempts(),
        ]);

        try {
            $response = retry(3, function () {
                $response = Http::timeout(10)->post(Telegram::url . $this->bot . '/sendMessage', [
                    'chat_id' => $this->chatId,
                    'text' => $this->message,
                    'parse_mode' => 'html',
                ]);

                if (!$response->successful()) {
                    throw new \Exception("Telegram API request failed: {$response->body()}");
                }

                return $response;
            }, 2000);

            $responseBody = $response->json();
            if (!isset($responseBody['ok']) || $responseBody['ok'] !== true) {
                throw new \Exception("Telegram API returned error: " . json_encode($responseBody));
            }

            Log::info("SendTelegramMessageJob completed successfully", [
                'chat_id' => $this->chatId,
                'response' => $responseBody,
            ]);
        } catch (\Exception $e) {
            Log::error("Error in SendTelegramMessageJob", [
                'chat_id' => $this->chatId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Если задача выполняется дольше 1 минуты, удаляем её
            if ($startTime->diffInMinutes(now()) > 1) {
                Log::warning("SendTelegramMessageJob timed out, deleting job", [
                    'chat_id' => $this->chatId,
                ]);
                $this->delete();
            }

            throw $e; // Пробрасываем исключение для повторной попытки
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Exception $exception): void
    {
        Log::critical("SendTelegramMessageJob failed permanently", [
            'bot' => $this->bot,
            'chat_id' => $this->chatId,
            'message' => substr($this->message, 0, 100) . (strlen($this->message) > 100 ? '...' : ''),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Отправка уведомления администратору
        try {
            \Illuminate\Support\Facades\Notification::route('mail', 'taxi.easy.ua.sup@gmail.com')
                ->notify(new \App\Notifications\FailedJobsAlert(1));
        } catch (Exception $e) {
            Log::error("Failed to send notification for SendTelegramMessageJob", [
                'chat_id' => $this->chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

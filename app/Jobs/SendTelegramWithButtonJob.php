<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendTelegramWithButtonJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $botToken;
    public $chatId;
    public $message;
    public $buttonText;
    public $buttonUrl;
    public $tries = 3;
    public $timeout = 30;

    public function __construct(string $botToken, string $chatId, string $message, string $buttonText, string $buttonUrl)
    {
        $this->botToken = $botToken;
        $this->chatId = $chatId;
        $this->message = $message;
        $this->buttonText = $buttonText;
        $this->buttonUrl = $buttonUrl;
    }

    public function handle()
    {
        try {
            $keyboard = [
                'inline_keyboard' => [
                    [
                        [
                            'text' => $this->buttonText,
                            'url' => $this->buttonUrl
                        ]
                    ]
                ]
            ];

            $response = Http::timeout(30)->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                'chat_id' => $this->chatId,
                'text' => $this->message,
                'parse_mode' => 'Markdown',
                'reply_markup' => json_encode($keyboard),
                'disable_web_page_preview' => true,
            ]);

            if (!$response->successful()) {
                throw new \Exception("Telegram API error: " . $response->body());
            }

            Log::info('Telegram message with button sent via job', [
                'chat_id' => $this->chatId,
                'button_text' => $this->buttonText
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send Telegram message with button', [
                'error' => $e->getMessage(),
                'chat_id' => $this->chatId
            ]);
            $this->fail($e);
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('SendTelegramWithButtonJob failed: ' . $exception->getMessage());
    }
}

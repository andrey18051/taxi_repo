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
use Throwable;

class SendTelegramMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $bot;
    protected $chatId;
    protected $message;
    public $tries = 1;

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
    public function handle()
    {
        try {
            Http::post(Telegram::url . $this->bot . '/sendMessage', [
                'chat_id' => $this->chatId,
                'text' => $this->message,
                'parse_mode' => 'html',
            ]);
            return true;
        } catch (\Exception $e) {
            // Просто логируем ошибку, не выбрасываем исключение
            Log::error('Ошибка отправки сообщения Telegram: ' . $e->getMessage());
            return true;
        }

    }

}

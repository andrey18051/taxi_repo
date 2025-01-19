<?php

namespace App\Jobs;

use App\Helpers\Telegram;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class SendTelegramMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $bot;
    protected $chatId;
    protected $message;

    public $timeout = 60;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($bot, $chatId, $message)
    {
        $this->bot = $bot;
        $this->chatId = $chatId;
        $this->message = $message;
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $startTime = now();

        try {
            retry(3, function () {
                return Http::post(Telegram::url . $this->bot . '/sendMessage', [
                    'chat_id' => $this->chatId,
                    'text' => $this->message,
                    'parse_mode' => 'html'
                ]);
            }, 2000); // 2 секунды
        } catch (\Exception $e) {
            Log::error('Error sending message: ' . $e->getMessage(), ['exception' => $e]);

            // Если задача длится слишком долго, вы можете вручную удалить её
            if ($startTime->diffInMinutes(now()) > 1) {
                // Логика удаления задачи из очереди, если нужно
                 Queue::delete($this->job);
            }
        }

        if ($startTime->diffInMinutes(now()) > 1) {
            // Логика удаления задачи из очереди, если нужно
            Queue::delete($this->job);
        }
    }

}

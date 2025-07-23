<?php

namespace App\Jobs;

use App\Http\Controllers\MessageSentController;
use App\Http\Controllers\UniversalAndroidFunctionController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SearchAutoOrderCardJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const MAX_RETRIES = 12; // 12 попыток * 5 секунд = 60 секунд максимум
    private const RETRY_DELAY_SECONDS = 5;
    /**
     * @var string
     */
    private $uid;

    /**
     * Create a new job instance.
     *
     * @param string $uid
     */
    public function __construct(string $uid)
    {
        $this->uid = $uid;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $messageAdmin = "Запущен процесс создания ожидания авто заказа $this->uid ";
        (new MessageSentController)->sentMessageAdmin($messageAdmin);

        try {
            // Валидация UID
            if (empty($this->uid)) {
                Log::error('SearchAutoOrderCardJob: Invalid UID provided');
                return;
            }

            // Вызов сервиса для обработки
            $result = (new \App\Http\Controllers\UniversalAndroidFunctionController)->searchAutoOrderCardJob($this->uid, "yes_mes");

            // Логирование результата
            Log::info('SearchAutoOrderCardJob completed', [
                'uid' => $this->uid,
                'status' => $result['status'],
                'message' => $result['message'],
            ]);

            $messageAdmin = "SearchAutoOrderCardJob completed: " . $result['message'];
            (new MessageSentController)->sentMessageAdmin($messageAdmin);

        } catch (\Exception $e) {
            Log::error('SearchAutoOrderCardJob failed', [
                'uid' => $this->uid,
                'error' => $e->getMessage(),
            ]);

            // Повторная попытка в случае ошибки (если требуется)
            $this->release(self::RETRY_DELAY_SECONDS);
        }
    }
}

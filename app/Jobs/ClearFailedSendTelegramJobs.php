<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClearFailedSendTelegramJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 10;

    public function __construct()
    {
        //
    }

    public function handle()
    {
        try {
            $deleted = DB::table('failed_jobs')
                ->where('payload', 'like', '%SendTelegramMessageJob%')
                ->delete();

            Log::info("Очистка failed_jobs: удалено {$deleted} записей для SendTelegramMessageJob.");
        } catch (\Exception $e) {
            Log::error("Ошибка очистки failed_jobs: {$e->getMessage()}");
            // Возвращаем true, чтобы задача считалась успешной
            return true;
        }
    }
}


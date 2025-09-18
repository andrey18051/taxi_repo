<?php

namespace App\Jobs;

use App\Http\Controllers\FCMController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class WriteDocumentToFirestore implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1; // Максимум 1 попыток
    protected $dispatchingOrderUid;

    /**
     * Создаём новый экземпляр задачи.
     */
    public function __construct(string $dispatchingOrderUid)
    {
        $this->dispatchingOrderUid = $dispatchingOrderUid;
    }

    /**
     * Логика выполнения задачи.
     */
    public function handle(): void
    {
        (new FCMController)->writeDocumentToFirestore($this->dispatchingOrderUid);
    }
}

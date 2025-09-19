<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\KafkaService;
use Illuminate\Support\Facades\Log;

class KafkaConsumeCommand extends Command
{
    protected $signature = 'kafka:consume {topic=test-topic}';
    protected $description = 'Подписка на Kafka топик и обработка сообщений';

    protected $kafka;

    public function __construct(KafkaService $kafka)
    {
        parent::__construct();
        $this->kafka = $kafka;
    }

    public function handle()
    {
        $topic = $this->argument('topic');
        $this->info("Подписка на топик: {$topic}");

        while (true) {
            $messages = $this->kafka->consumeMessages('my_consumer', 'instance1', $topic);

            if ($messages['status'] === 'ok' && !empty($messages['messages'])) {
                foreach ($messages['messages'] as $msg) {
                    // Обработка каждого сообщения
                    $value = $msg['value'] ?? [];
                    Log::info('Получено сообщение из Kafka', $value);

                    // Тут можно вызывать бизнес-логику, например:
                    // OrderService::processOrder($value['order_id'], $value['status']);
                }
            }

            // Небольшая задержка, чтобы не перегружать REST Proxy
            sleep(1);
        }
    }
}

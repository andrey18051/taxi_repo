<?php

namespace App\Console\Commands;

use App\Http\Controllers\AndroidTestOSMController;
use App\Jobs\ProcessCostSearchMarkersTime;
use Illuminate\Console\Command;
use App\Services\KafkaService;
use Illuminate\Support\Facades\Log;

class KafkaConsumeCommand extends Command
{
    protected $signature = 'kafka:consume {topic=cost-topic}';
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

                    (new \App\Http\Controllers\AndroidTestOSMController)->costSearchMarkersTime(
                        $value['originLatitude'] ?? null,
                        $value['originLongitude'] ?? null,
                        $value['toLatitude'] ?? null,
                        $value['toLongitude'] ?? null,
                        $value['tarif'] ?? null,        // tariff
                        $value['phone'] ?? null,
                        $value['user'] ?? null,
                        $value['time'] ?? null,         // time
                        $value['date'] ?? null,         // date
                        $value['services'] ?? null,     // services
                        $value['city'] ?? null,         // city
                        $value['application'] ?? null   // application
                    );


//                    ProcessCostSearchMarkersTime::dispatch([
//                        'originLatitude' => $value['originLatitude'] ?? null,
//                        'originLongitude' => $value['originLongitude'] ?? null,
//                        'toLatitude' => $value['toLatitude'] ?? null,
//                        'toLongitude' => $value['toLongitude'] ?? null,
//                        'tarif' => $value['tarif'] ?? null,
//                        'phone' => $value['phone'] ?? null,
//                        'user' => $value['user'] ?? null,
//                        'time' => $value['time'] ?? null,
//                        'date' => $value['date'] ?? null,
//                        'services' => $value['services'] ?? null,
//                        'city' => $value['city'] ?? null,
//                        'application' => $value['application'] ?? null
//                    ])->onQueue('high'); // можно указать очередь
                }
            }

            // Небольшая задержка, чтобы не перегружать REST Proxy
            sleep(10);
        }
    }
}

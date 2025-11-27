<?php

namespace App\Console\Commands;

use App\Http\Controllers\AndroidTestOSMController;
use App\Jobs\ProcessCostSearchMarkersTime;
use Illuminate\Console\Command;
use App\Services\KafkaService;
use Illuminate\Support\Facades\Log;

class KafkaConsumeCommand extends Command
{
    // Указываем топики по умолчанию через запятую
    protected $signature = 'kafka:consume
                            {topics=cost-topic,cost-topic-my-api : Список топиков через запятую}
                            {--timeout=30 : Таймаут в секундах для запросов к Kafka}';

    protected $description = 'Постоянное наблюдение за Kafka топиками и обработка сообщений';

    protected $kafka;

    public function __construct(KafkaService $kafka)
    {
        parent::__construct();
        $this->kafka = $kafka;
    }

    public function handle()
    {
        $topics = explode(',', $this->argument('topics'));
        $timeout = $this->option('timeout');

        $this->info("🔄 Запущено постоянное наблюдение за топиками: " . implode(', ', $topics));
        $this->info("⏱️ Таймаут установлен: {$timeout} секунд");

        $iteration = 0;

        // Бесконечный цикл для постоянного наблюдения
        while (true) {
            $iteration++;
            $this->info("🔍 Итерация #{$iteration} - Проверка топиков: " . date('Y-m-d H:i:s'));

            foreach ($topics as $topic) {
                try {
                    $this->processTopic($topic, $timeout);
                } catch (\Exception $e) {
                    Log::error("Ошибка при обработке топика {$topic}: " . $e->getMessage());
                    $this->error("❌ Ошибка в топике {$topic}: " . $e->getMessage());
                }
            }

            // Пауза между итерациями проверки
            $this->comment("⏳ Ожидание 10 секунд до следующей проверки...");
            sleep(3);
        }
    }

    protected function processTopic($topic, $timeout)
    {
        $this->line("📭 Проверка топика: {$topic}");

        // Передаем таймаут в KafkaService
        $messages = $this->kafka->consumeMessages('my_consumer', 'instance1', $topic, $timeout);

        if ($messages['status'] === 'ok') {
            $messageCount = count($messages['messages'] ?? []);

            if ($messageCount > 0) {
                $this->info("✅ Получено {$messageCount} сообщений из топика: {$topic}");

                foreach ($messages['messages'] as $index => $msg) {
                    $value = $msg['value'] ?? [];
                    Log::info("📨 Сообщение из топика {$topic}", $value);
                    $this->routeMessage($topic, $value);
                    $this->line("✔️ Обработано сообщение #" . ($index + 1));
                }
            } else {
                $this->line("📭 В топике {$topic} нет новых сообщений");
            }
        } else {
            $this->error("❌ Ошибка при чтении топика {$topic}: " . ($messages['message'] ?? 'Unknown error'));
        }
    }

    protected function routeMessage($topic, $value)
    {
        switch ($topic) {
            case 'cost-topic':
                $this->processCostTopic($value);
                break;
            case 'cost-topic-my-api':
                $this->processCostTopicMyApi($value);
                break;
            default:
                Log::warning("⚠️ Неизвестный топик: {$topic}", $value);
                $this->warn("⚠️ Получено сообщение из неизвестного топика: {$topic}");
                break;
        }
    }

    protected function processCostTopic($value)
    {
        // Существующая логика для cost-topic
        (new AndroidTestOSMController)->costSearchMarkersTime(
            $value['originLatitude'] ?? null,
            $value['originLongitude'] ?? null,
            $value['toLatitude'] ?? null,
            $value['toLongitude'] ?? null,
            $value['tarif'] ?? null,
            $value['phone'] ?? null,
            $value['user'] ?? null,
            $value['time'] ?? null,
            $value['date'] ?? null,
            $value['services'] ?? null,
            $value['city'] ?? null,
            $value['application'] ?? null
        );

        $this->info("🎯 Обработано сообщение из cost-topic");
    }

    protected function processCostTopicMyApi($value)
    {
        // Логика для cost-topic-my-api
        Log::info("🔔 Обработка сообщения из cost-topic-my-api", $value);

        // Можно использовать ту же логику или добавить специфичную
        (new AndroidTestOSMController)->costSearchMarkersTimeMyApi(
            $value['originLatitude'] ?? null,
            $value['originLongitude'] ?? null,
            $value['toLatitude'] ?? null,
            $value['toLongitude'] ?? null,
            $value['tarif'] ?? null,
            $value['phone'] ?? null,
            $value['user'] ?? null,
            $value['time'] ?? null,
            $value['date'] ?? null,
            $value['services'] ?? null,
            $value['city'] ?? null,
            $value['application'] ?? null
        );

        $this->info("🎯 Обработано сообщение из cost-topic-my-api");
    }
}

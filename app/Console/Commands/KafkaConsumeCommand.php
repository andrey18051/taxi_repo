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
            $this->info("\n🔍 Итерация #{$iteration} - " . date('Y-m-d H:i:s'));

            foreach ($topics as $topic) {
                try {
                    $this->processTopic($topic, $timeout);
                } catch (\Exception $e) {
                    Log::error("Ошибка при обработке топика {$topic}: " . $e->getMessage());
                    $this->error("❌ Ошибка в топике {$topic}: " . $e->getMessage());
                }
            }

            // Пауза между итерациями проверки
            $this->comment("⏳ Ожидание 3 секунд до следующей проверки...");
            sleep(3);
        }
    }

    protected function processTopic($topic, $timeout)
    {
        $this->line("📭 Проверка топика: <fg=cyan>{$topic}</>");

        // Получаем сообщения из Kafka
        $result = $this->kafka->consumeMessages($topic, (int)$timeout, 50);

        if ($result['status'] === 'success') {
            $messageCount = $result['message_count'] ?? 0;

            if ($messageCount > 0) {
                $this->info("✅ Получено {$messageCount} сообщений из топика: {$topic}");

                // ⚠️ ИСПРАВЛЕНИЕ: сообщения находятся в $result['messages'], а не $result['data']
                $messages = $result['messages'] ?? [];

                foreach ($messages as $index => $msg) {
                    // Извлекаем значение сообщения
                    $value = $this->extractMessageValue($msg);

                    if ($value) {
                        $this->info("📨 Обработка сообщения #" . ($index + 1));
                        Log::info("📨 Сообщение из топика {$topic}", is_array($value) ? $value : ['message' => $value]);

                        // Маршрутизируем сообщение
                        $this->routeMessage($topic, $value);
                        $this->line("✔️ Сообщение #" . ($index + 1) . " обработано");
                    } else {
                        $this->warn("⚠️ Не удалось извлечь значение из сообщения #" . ($index + 1));
                        Log::warning("Не удалось извлечь значение из сообщения Kafka", ['raw_message' => $msg]);
                    }
                }

                $this->line("🎯 Обработано сообщений: {$messageCount}");
            } else {
                $this->line("📭 В топике {$topic} нет новых сообщений");
                Log::debug("Топик {$topic} пуст или нет новых сообщений");
            }
        } else {
            $errorMsg = $result['message'] ?? 'Unknown error';
            $this->error("❌ Ошибка при чтении топика {$topic}: " . $errorMsg);

            // Логируем для отладки
            Log::error("Ошибка при чтении топика Kafka", [
                'topic' => $topic,
                'error' => $errorMsg,
                'result' => $result
            ]);
        }
    }

    /**
     * Извлекает значение из сообщения Kafka
     * Поддерживает разные форматы сообщений
     */
    protected function extractMessageValue(array $msg)
    {
        // Формат 1: значение напрямую в поле 'value'
        if (isset($msg['value']) && is_array($msg['value'])) {
            return $msg['value'];
        }

        // Формат 2: значение как строковый JSON в поле 'value'
        if (isset($msg['value']) && is_string($msg['value'])) {
            $decoded = json_decode($msg['value'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
            return ['raw' => $msg['value']];
        }

        // Формат 3: сообщение само является массивом данных
        if (isset($msg['payload'])) {
            return $msg['payload'];
        }

        // Формат 4: весь массив и есть сообщение (исключаем служебные поля)
        $excludeFields = ['topic', 'partition', 'offset', 'key'];
        $filtered = array_diff_key($msg, array_flip($excludeFields));

        if (!empty($filtered)) {
            return $filtered;
        }

        return null;
    }

    protected function routeMessage($topic, $value)
    {
        if (!is_array($value)) {
            Log::warning("Сообщение из топика {$topic} не является массивом", ['value' => $value]);
            $this->warn("⚠️ Сообщение из топика {$topic} не является массивом");
            return;
        }

        $this->info("🔄 Маршрутизация сообщения из топика: {$topic}");

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
        try {
            $this->info("🎯 Обработка сообщения из cost-topic");

            // Вызываем контроллер с извлеченными данными
            (new AndroidTestOSMController)->costSearchMarkersTime(
                $value['origin_lat'] ?? $value['originLatitude'] ?? null,
                $value['origin_lng'] ?? $value['originLongitude'] ?? null,
                $value['to_lat'] ?? $value['toLatitude'] ?? null,
                $value['to_lng'] ?? $value['toLongitude'] ?? null,
                $value['tarif'] ?? null,
                $value['phone'] ?? null,
                $value['user'] ?? null,
                $value['time'] ?? null,
                $value['date'] ?? null,
                $value['services'] ?? null,
                $value['city'] ?? null,
                $value['application'] ?? null
            );

            Log::info("✅ Сообщение из cost-topic успешно обработано");
        } catch (\Exception $e) {
            Log::error("❌ Ошибка обработки сообщения из cost-topic: " . $e->getMessage(), [
                'value' => $value,
                'trace' => $e->getTraceAsString()
            ]);
            $this->error("❌ Ошибка обработки сообщения из cost-topic: " . $e->getMessage());
        }
    }

    protected function processCostTopicMyApi($value)
    {
        try {
            $this->info("🎯 Обработка сообщения из cost-topic-my-api");

            Log::info("🔔 Обработка сообщения из cost-topic-my-api", $value);

            (new AndroidTestOSMController)->costSearchMarkersTimeMyApi(
                $value['origin_lat'] ?? $value['originLatitude'] ?? null,
                $value['origin_lng'] ?? $value['originLongitude'] ?? null,
                $value['to_lat'] ?? $value['toLatitude'] ?? null,
                $value['to_lng'] ?? $value['toLongitude'] ?? null,
                $value['tarif'] ?? null,
                $value['phone'] ?? null,
                $value['user'] ?? null,
                $value['time'] ?? null,
                $value['date'] ?? null,
                $value['services'] ?? null,
                $value['city'] ?? null,
                $value['application'] ?? null
            );

            Log::info("✅ Сообщение из cost-topic-my-api успешно обработано");
        } catch (\Exception $e) {
            Log::error("❌ Ошибка обработки сообщения из cost-topic-my-api: " . $e->getMessage(), [
                'value' => $value,
                'trace' => $e->getTraceAsString()
            ]);
            $this->error("❌ Ошибка обработки сообщения из cost-topic-my-api: " . $e->getMessage());
        }
    }
}

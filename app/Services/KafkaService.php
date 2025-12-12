<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KafkaService
{
    protected $client;
    protected $host;
    protected $defaultTimeout;

    public function __construct()
    {
        $this->host = env('KAFKA_REST_HOST', 'http://127.0.0.1:8082');
        $this->defaultTimeout = 15; // Уменьшено с 30 до 15 секунд

        $this->client = new Client([
            'base_uri' => $this->host,
            'timeout'  => $this->defaultTimeout,
            'connect_timeout' => 5.0,
            'read_timeout' => $this->defaultTimeout,
        ]);

    }
    public function testShellCurl(): array
    {
        $cmd = "curl -s http://localhost:8082/topics";
        $output = shell_exec($cmd);

        return [
            'command' => $cmd,
            'output' => $output,
            'json' => json_decode($output, true),
            'working' => !empty($output)
        ];
    }
    /**
     * Отправка сообщения в Kafka топик через REST Proxy
     */
    public function send(string $topic, array $message, string $key = null): array
    {
        $startTime = microtime(true);

        try {
            $payload = ['records' => []];
            $record = ['value' => $message];

            if ($key !== null) {
                $record['key'] = $key;
            }

            $payload['records'][] = $record;

            $response = $this->client->post("/topics/{$topic}", [
                'headers' => [
                    'Content-Type' => 'application/vnd.kafka.json.v2+json',
                    'Accept' => 'application/vnd.kafka.v2+json'
                ],
                'json' => $payload,
                'timeout' => 10 // Уменьшено с 30 до 10 секунд
            ]);

            $body = json_decode((string) $response->getBody(), true);
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('Kafka message sent', [
                'topic' => $topic,
                'duration_ms' => $duration,
                'response' => $body
            ]);

            return [
                'status' => 'success',
                'topic' => $topic,
                'duration_ms' => $duration,
                'response' => $body,
                'message' => 'Message sent successfully'
            ];
        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('Kafka send error', [
                'topic' => $topic,
                'error' => $e->getMessage(),
                'duration_ms' => $duration
            ]);

            return [
                'status' => 'error',
                'topic' => $topic,
                'duration_ms' => $duration,
                'message' => 'Failed to send message: ' . $e->getMessage(),
                'suggestion' => 'Check Kafka connection and topic existence'
            ];
        }
    }

    /**
     * Получение сообщений из Kafka топика через REST Proxy
     */
    /**
     * Получение сообщений из Kafka топика через REST Proxy (ИСПРАВЛЕННАЯ ВЕРСИЯ)
     */
    public function consumeMessages(string $topic = 'test-topic', int $timeout = 10, int $maxMessages = 50): array
    {
        $startTime = microtime(true);
        $consumerGroup = "php_consumer_" . uniqid();

        try {
            // 1. Создаём consumer
            $response = $this->client->post("/consumers/{$consumerGroup}", [
                'headers' => [
                    'Content-Type' => 'application/vnd.kafka.v2+json',
                    'Accept'       => 'application/vnd.kafka.v2+json',
                ],
                'json' => [
                    'name'                  => 'php_consumer',
                    'format'                => 'json',
                    'auto.offset.reset'     => 'earliest',
                    'auto.commit.enable'    => 'false',
                ],
                'timeout' => 5,
            ]);

            $instance = json_decode($response->getBody(), true)['instance_id'] ?? null;
            if (!$instance) throw new \Exception('No instance_id');

            // 2. Подписываемся
            $this->client->post("/consumers/{$consumerGroup}/instances/{$instance}/subscription", [
                'headers' => ['Content-Type' => 'application/vnd.kafka.v2+json'],
                'json'    => ['topics' => [$topic]],
                'timeout' => 5,
            ]);

            // 3. Читаем сообщения — ВОТ ГЛАВНОЕ ИСПРАВЛЕНИЕ
            $response = $this->client->get("/consumers/{$consumerGroup}/instances/{$instance}/records", [
                'headers' => [
                    'Accept' => 'application/vnd.kafka.json.v2+json',
                ],
                'timeout'     => $timeout + 2,
                'read_timeout' => $timeout + 2,
            ]);

            $messages = json_decode($response->getBody(), true) ?? [];

            // 4. Удаляем consumer
            $this->client->delete("/consumers/{$consumerGroup}/instances/{$instance}", ['timeout' => 3]);

            return [
                'status'       => 'success',
                'message_count'=> count($messages),
                'messages'     => $messages,
                'duration_ms'  => round((microtime(true) - $startTime) * 1000, 2),
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        }
    }
    /**
     * Быстрая проверка статуса топика
     */
    public function checkTopicStatus(string $topic): array
    {
        try {
            $response = Http::timeout(3)
                ->get("{$this->host}/topics/{$topic}");

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'topic' => $topic,
                    'exists' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'status' => 'error',
                'topic' => $topic,
                'exists' => false,
                'message' => 'Topic not found or error: ' . $response->body()
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'topic' => $topic,
                'exists' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Получение списка топиков
     */
    public function getTopics(): array
    {
        try {
            $response = Http::timeout(5)->get("{$this->host}/topics");

            if ($response->successful()) {
                $topics = $response->json();
                return [
                    'status' => 'success',
                    'topics' => is_array($topics) ? $topics : [],
                    'count' => is_array($topics) ? count($topics) : 0
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Failed to get topics: ' . $response->body(),
                'topics' => []
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'topics' => []
            ];
        }
    }

    /**
     * Очистка consumer (приватный метод)
     */
    private function cleanupConsumer(string $consumerGroup, string $instanceName): void
    {
        try {
            Http::timeout(2)
                ->delete("{$this->host}/consumers/{$consumerGroup}/instances/{$instanceName}");

            Log::debug('Consumer cleaned up', [
                'consumer_group' => $consumerGroup,
                'instance' => $instanceName
            ]);
        } catch (\Exception $e) {
            // Тихий fail - это нормально, consumer может уже не существовать
            Log::debug('Consumer cleanup failed (normal)', [
                'consumer_group' => $consumerGroup,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Создание стандартизированного ответа с ошибкой
     */
    private function createErrorResponse(
        string $title,
        string $error,
        string $topic,
        float $startTime
    ): array {
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        // Пытаемся разобрать JSON ошибку
        $errorData = json_decode($error, true);
        $errorMessage = $errorData['message'] ?? $error;
        $errorCode = $errorData['error_code'] ?? null;

        $response = [
            'status' => 'error',
            'topic' => $topic,
            'message' => $title,
            'error' => $errorMessage,
            'error_code' => $errorCode,
            'error_raw' => $error,
            'duration_ms' => $duration,
            'timestamp' => date('Y-m-d H:i:s'),
            'suggestion' => 'Check if topic exists and has messages'
        ];

        // Добавляем дополнительные подсказки в зависимости от ошибки
        if (strpos($errorMessage, 'Unrecognized field') !== false) {
            $response['suggestion'] = 'Invalid consumer configuration. Check Kafka REST Proxy documentation.';
            $response['error_type'] = 'configuration';

            // Пытаемся извлечь название неправильного поля
            if (preg_match('/Unrecognized field: (\w+)/', $errorMessage, $matches)) {
                $response['invalid_field'] = $matches[1];
            }
        } elseif (strpos($errorMessage, 'timeout') !== false) {
            $response['suggestion'] = 'Topic might be empty. Try sending a message first.';
            $response['error_type'] = 'timeout';
        } elseif (strpos($errorMessage, 'connect') !== false) {
            $response['suggestion'] = 'Kafka REST Proxy might be unavailable. Check service status.';
            $response['error_type'] = 'connection';
        } elseif ($errorCode === 404) {
            $response['suggestion'] = 'Topic or consumer group not found. Check if topic exists.';
            $response['error_type'] = 'not_found';
        } elseif ($errorCode === 422) {
            $response['suggestion'] = 'Invalid request parameters. Check consumer configuration.';
            $response['error_type'] = 'validation';
        }

        return $response;
    }
}

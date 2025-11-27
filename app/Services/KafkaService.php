<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KafkaService
{
    protected $client;
    protected $host;

    public function __construct()
    {
        $this->host = env('KAFKA_REST_HOST', 'http://localhost:8082');
        $this->client = new Client([
            'base_uri' => $this->host,
            'timeout'  => 30.0, // Увеличено с 5 до 30 секунд
            'connect_timeout' => 10.0,
        ]);
    }

    /**
     * Отправка сообщения в Kafka топик через REST Proxy
     */
    public function send(string $topic, array $message): array
    {
        try {
            $response = $this->client->post("/topics/{$topic}", [
                'headers' => [
                    'Content-Type' => 'application/vnd.kafka.json.v2+json',
                    'Accept' => 'application/vnd.kafka.v2+json'
                ],
                'json' => [
                    'records' => [
                        ['value' => $message]
                    ]
                ],
                'timeout' => 30 // Увеличено для отправки
            ]);

            $body = json_decode((string) $response->getBody(), true);

            return [
                'status' => 'ok',
                'topic' => $topic,
                'response' => $body
            ];
        } catch (\Exception $e) {
            Log::error('Kafka REST Proxy error (send): ' . $e->getMessage());
            return [
                'status' => 'error',
                'topic' => $topic,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Получение сообщений из Kafka топика через REST Proxy
     */
    public function consumeMessages(
        string $consumerGroup = 'my_consumer',
        string $instanceName = 'instance1',
        string $topic = 'test-topic',
        int $timeout = 30 // Добавлен параметр таймаута
    ): array {
        try {
            // 1. Создаем consumer с увеличенным таймаутом
            $createResponse = Http::timeout($timeout)
                ->withHeaders([
                    'Content-Type' => 'application/vnd.kafka.v2+json',
                    'Accept' => 'application/vnd.kafka.v2+json'
                ])->post("{$this->host}/consumers/{$consumerGroup}", [
                    'name' => $instanceName,
                    'format' => 'json',
                    'auto.offset.reset' => 'earliest',
                    'auto.commit.enable' => 'true'
                ]);
            if ($createResponse->status() === 409) {
                Log::info("Consumer {$consumerGroup}/{$instanceName} already exists");
            } elseif ($createResponse->successful()) {
                Log::info("Consumer {$consumerGroup}/{$instanceName} created successfully");
            }
            if (!$createResponse->successful() && $createResponse->status() !== 409) {
                Log::warning("Consumer creation failed: " . $createResponse->body());
                // Продолжаем выполнение, так как consumer может уже существовать
            }

            // 2. Подписываемся на топик
            $subscribeResponse = Http::timeout($timeout)
                ->withHeaders([
                    'Content-Type' => 'application/vnd.kafka.v2+json',
                    'Accept' => 'application/vnd.kafka.v2+json'
                ])->post("{$this->host}/consumers/{$consumerGroup}/instances/{$instanceName}/subscription", [
                    'topics' => [$topic]
                ]);

            if (!$subscribeResponse->successful() && $subscribeResponse->status() !== 409) {
                Log::warning("Subscription failed: " . $subscribeResponse->body());
                // Продолжаем выполнение, так как подписка может уже существовать
            }

            // 3. Получаем сообщения с увеличенным таймаутом
            $recordsResponse = Http::timeout($timeout)
                ->withHeaders([
                    'Accept' => 'application/vnd.kafka.json.v2+json'
                ])->get("{$this->host}/consumers/{$consumerGroup}/instances/{$instanceName}/records");

            if ($recordsResponse->successful()) {
                $messages = $recordsResponse->json();
                return [
                    'status' => 'ok',
                    'messages' => is_array($messages) ? $messages : []
                ];
            }

            // Если нет сообщений - это не ошибка, возвращаем пустой массив
            if ($recordsResponse->status() === 204) {
                return [
                    'status' => 'ok',
                    'messages' => []
                ];
            }

            Log::warning("Records fetch failed: " . $recordsResponse->body());
            return [
                'status' => 'error',
                'message' => $recordsResponse->body()
            ];

        } catch (\Exception $e) {
            Log::error('Kafka REST Proxy error (consume): ' . $e->getMessage(), [
                'consumer_group' => $consumerGroup,
                'topic' => $topic
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage() ?: 'Timeout or connection error'
            ];
        }
    }

    /**
     * Удаление consumer instance (для очистки)
     */
    public function deleteConsumer(string $consumerGroup = 'my_consumer', string $instanceName = 'instance1'): array
    {
        try {
            $response = Http::timeout(10)
                ->delete("{$this->host}/consumers/{$consumerGroup}/instances/{$instanceName}");

            if ($response->successful() || $response->status() === 404) {
                return ['status' => 'ok'];
            }

            return [
                'status' => 'error',
                'message' => $response->body()
            ];
        } catch (\Exception $e) {
            Log::error('Kafka consumer deletion error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}

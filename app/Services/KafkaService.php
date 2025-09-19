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
        $this->host = env('KAFKA_REST_HOST', 'http://localhost:8082'); // REST Proxy
        $this->client = new Client([
            'base_uri' => $this->host,
            'timeout'  => 5.0,
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
                ]
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
        string $topic = 'test-topic'
    ): array {
        try {
            // 1. Создаем consumer (если уже есть — 409 можно игнорировать)
            $createResponse = Http::withHeaders([
                'Content-Type' => 'application/vnd.kafka.v2+json',
                'Accept' => 'application/vnd.kafka.v2+json'
            ])->post("{$this->host}/consumers/{$consumerGroup}", [
                'name' => $instanceName,
                'format' => 'json',
                'auto.offset.reset' => 'earliest'
            ]);

            if (!$createResponse->successful() && $createResponse->status() !== 409) {
                return [
                    'status' => 'error',
                    'message' => $createResponse->body()
                ];
            }

            // 2. Подписываемся на топик
            $subscribeResponse = Http::withHeaders([
                'Content-Type' => 'application/vnd.kafka.v2+json',
                'Accept' => 'application/vnd.kafka.v2+json'
            ])->post("{$this->host}/consumers/{$consumerGroup}/instances/{$instanceName}/subscription", [
                'topics' => [$topic]
            ]);

            if (!$subscribeResponse->successful() && $subscribeResponse->status() !== 409) {
                return [
                    'status' => 'error',
                    'message' => $subscribeResponse->body()
                ];
            }

            // 3. Получаем сообщения
            $recordsResponse = Http::withHeaders([
                'Accept' => 'application/vnd.kafka.json.v2+json'
            ])->get("{$this->host}/consumers/{$consumerGroup}/instances/{$instanceName}/records");

            if ($recordsResponse->successful()) {
                return [
                    'status' => 'ok',
                    'messages' => $recordsResponse->json()
                ];
            }

            return [
                'status' => 'error',
                'message' => $recordsResponse->body()
            ];

        } catch (\Exception $e) {
            Log::error('Kafka REST Proxy error (consume): ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return [
                'status' => 'error',
                'message' => $e->getMessage() ?: 'Пустое сообщение исключения'
            ];
        }
    }
}

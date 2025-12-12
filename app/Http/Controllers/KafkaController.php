<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\KafkaService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KafkaController extends Controller
{
    protected $kafkaService;

    public function __construct(KafkaService $kafkaService)
    {
        $this->kafkaService = $kafkaService;
    }
    public function testCurlDirect(): JsonResponse
    {
        try {
            // Используем системный curl
            $output = shell_exec('curl -s http://localhost:8082/topics');

            return response()->json([
                'status' => 'success',
                'output' => $output,
                'json_decoded' => json_decode($output, true)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function testGuzzleDirect(): JsonResponse
    {
        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => 5,
                'connect_timeout' => 3,
            ]);

            $response = $client->get('http://localhost:8082/topics');

            return response()->json([
                'status' => $response->getStatusCode(),
                'body' => json_decode($response->getBody(), true),
                'headers' => $response->getHeaders()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'class' => get_class($e)
            ]);
        }
    }

    public function testShell(): JsonResponse
    {
        $result = $this->kafkaService->testShellCurl();
        return response()->json($result);
    }
    public function testNetwork(): JsonResponse
    {
        // Проверяем разные адреса
        $hosts = [
            'http://localhost:8082',
            'http://127.0.0.1:8082',
            'http://host.docker.internal:8082',
            'http://gateway.docker.internal:8082',
        ];

        $results = [];
        foreach ($hosts as $host) {
            $cmd = "curl -s --max-time 3 {$host}/topics 2>&1";
            $output = shell_exec($cmd);
            $results[$host] = [
                'command' => $cmd,
                'output' => $output,
                'success' => !empty($output) && strpos($output, '[') === 0
            ];
        }

        // Проверяем gateway
        $gateway = shell_exec('ip route | grep default | awk \'{print $3}\'');
        if ($gateway) {
            $gateway = trim($gateway);
            $host = "http://{$gateway}:8082";
            $cmd = "curl -s --max-time 3 {$host}/topics 2>&1";
            $output = shell_exec($cmd);
            $results[$host] = [
                'command' => $cmd,
                'output' => $output,
                'success' => !empty($output) && strpos($output, '[') === 0,
                'gateway' => $gateway
            ];
        }

        return response()->json($results);
    }
    public function diagnoseConsumerIssue(): JsonResponse
    {
        $diagnostics = [];

        // 1. Проверка shell_exec
        $diagnostics['shell_exec'] = [
            'enabled' => function_exists('shell_exec'),
            'test_command' => 'echo "test"',
            'test_output' => shell_exec('echo "test"')
        ];

        // 2. Проверка curl
        $diagnostics['curl'] = [
            'test_command' => 'curl --version',
            'test_output' => shell_exec('curl --version 2>&1')
        ];

        // 3. Проверка доступа к Kafka REST Proxy
        $diagnostics['kafka_access'] = [
            'ping_command' => 'curl -s --max-time 3 http://localhost:8082 2>&1',
            'ping_output' => shell_exec('curl -s --max-time 3 http://localhost:8082 2>&1'),
            'topics_command' => 'curl -s --max-time 3 http://localhost:8082/topics 2>&1',
            'topics_output' => shell_exec('curl -s --max-time 3 http://localhost:8082/topics 2>&1')
        ];

        // 4. Попытка создать consumer напрямую
        $consumerGroup = 'test_diagnosis_' . time();
        $createData = [
            'name' => 'diagnosis_instance',
            'format' => 'json',
            'auto.offset.reset' => 'earliest'
        ];

        $createCmd = sprintf(
            "curl -s --max-time 3 -H 'Content-Type: application/vnd.kafka.v2+json' " .
            "-H 'Accept: application/vnd.kafka.v2+json' " .
            "-X POST http://localhost:8082/consumers/%s " .
            "-d '%s' 2>&1",
            $consumerGroup,
            json_encode($createData)
        );

        $diagnostics['consumer_creation'] = [
            'command' => $createCmd,
            'output' => shell_exec($createCmd),
            'consumer_group' => $consumerGroup
        ];

        return response()->json($diagnostics);
    }
    public function testCurlWithTimeout(): JsonResponse
    {
        try {
            // Используем curl с разными таймаутами
            $commands = [
                'curl -s http://localhost:8082/topics',
                'curl -s --max-time 5 http://localhost:8082/topics',
                'curl -s --connect-timeout 3 http://localhost:8082/topics'
            ];

            $results = [];
            foreach ($commands as $cmd) {
                $output = shell_exec($cmd);
                $results[] = [
                    'command' => $cmd,
                    'output' => $output,
                    'json' => json_decode($output, true)
                ];
            }

            return response()->json($results);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    public function testDirectKafkaApi(): JsonResponse
    {
        $host = 'http://localhost:8082';

        try {
            // 1. Проверка базового доступа
            $baseResponse = Http::timeout(3)->get($host);

            // 2. Проверка топиков
            $topicsResponse = Http::timeout(3)->get("{$host}/topics");

            // 3. Попытка создать consumer
            $consumerGroup = "test_group_" . time();
            $consumerResponse = Http::timeout(3)
                ->withHeaders([
                    'Content-Type' => 'application/vnd.kafka.v2+json'
                ])
                ->post("{$host}/consumers/{$consumerGroup}", [
                    'name' => 'test_instance',
                    'format' => 'json',
                    'auto.offset.reset' => 'earliest'
                ]);

            return response()->json([
                'base_api' => [
                    'status' => $baseResponse->status(),
                    'body' => $baseResponse->body()
                ],
                'topics' => [
                    'status' => $topicsResponse->status(),
                    'body' => $topicsResponse->json() ?? $topicsResponse->body()
                ],
                'consumer_creation' => [
                    'status' => $consumerResponse->status(),
                    'success' => $consumerResponse->successful(),
                    'body' => $consumerResponse->json() ?? $consumerResponse->body(),
                    'consumer_group' => $consumerGroup
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    /**
     * Отправка тестового сообщения
     */
    public function sendTestMessage(string $orderId, string $status): JsonResponse
    {
        $result = $this->kafkaService->send('test-topic', [
            'order_id' => $orderId,
            'status' => $status,
            'timestamp' => date('Y-m-d H:i:s'),
            'source' => 'php_api'
        ]);

        return response()->json($result);
    }

    /**
     * Отправка сообщения в cost-topic (совместимость со старым Android)
     */
    public function sendCostMessage(Request $request): JsonResponse
    {
        $message = [
            'origin_lat' => $request->input('originLatitude'),
            'origin_lng' => $request->input('originLongitude'),
            'to_lat' => $request->input('toLatitude'),
            'to_lng' => $request->input('toLongitude'),
            'tarif' => $request->input('tarif'),
            'phone' => $request->input('phone'),
            'client_cost' => (float)($request->input('client_cost', 0)),
            'user' => $request->input('user'),
            'add_cost' => (float)($request->input('add_cost', 0)),
            'time' => $request->input('time', date('H:i:s')),
            'comment' => $request->input('comment', ''),
            'date' => $request->input('date', date('Y-m-d')),
            'start' => $request->input('start', ''),
            'finish' => $request->input('finish', ''),
            'wfp_invoice' => $request->input('wfp_invoice', ''),
            'services' => $request->input('services', ''),
            'city' => $request->input('city', ''),
            'application' => $request->input('application', 'android'),
            '_meta' => [
                'received_at' => date('Y-m-d H:i:s'),
                'api_endpoint' => 'android-legacy',
                'format' => 'formdata'
            ]
        ];

        $result = $this->kafkaService->send('cost-topic', $message);

        return response()->json($result);
    }

    /**
     * Отправка сообщения в cost-topic-my-api (совместимость со старым Android)
     */
    public function sendCostMessageMyApi(Request $request): JsonResponse
    {
        $data = [
            'origin_lat' => $request->input('originLatitude'),
            'origin_lng' => $request->input('originLongitude'),
            'to_lat' => $request->input('toLatitude'),
            'to_lng' => $request->input('toLongitude'),
            'tarif' => $request->input('tarif'), // Android: 'tarif', PHP: 'tariff'
            'phone' => $request->input('phone'),
            'client_cost' => (float)($request->input('client_cost', 0)),
            'user' => $request->input('user'),
            'add_cost' => (float)($request->input('add_cost', 0)),
            'time' => $request->input('time', date('H:i:s')),
            'comment' => $request->input('comment', ''),
            'date' => $request->input('date', date('Y-m-d')),
            'start' => $request->input('start', ''),
            'finish' => $request->input('finish', ''),
            'wfp_invoice' => $request->input('wfp_invoice', ''),
            'services' => $request->input('services', ''),
            'city' => $request->input('city', ''),
            'application' => $request->input('application', 'android')
        ];

        // Логируем входящие данные для отладки
        Log::info('Android Kafka request received', [
            'input_data' => $request->all(),
            'processed_data' => $data,
            'format' => $request->isJson() ? 'json' : 'formdata'
        ]);

        // Добавляем системные поля
        $data['_meta'] = [
            'received_at' => date('Y-m-d H:i:s'),
            'api_endpoint' => 'android-legacy',
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ];

        $result = $this->kafkaService->send('cost-topic-my-api', $data);

        return response()->json($result);
    }

    /**
     * Получение сообщений из топика
     */
    public function getMessages(Request $request): JsonResponse
    {
        $topic = $request->input('topic', 'test-topic');
        $timeout = min((int)$request->input('timeout', 8), 30);
        $limit = min((int)$request->input('limit', 50), 100);

        $result = $this->kafkaService->consumeMessages($topic, $timeout, $limit);

        // ⚠️ ИСПРАВЛЕНИЕ: меняем 'data' на 'messages'
        return response()->json([
            'operation' => 'consume',
            'topic' => $topic,
            'status' => $result['status'],
            'message_count' => $result['message_count'] ?? 0,
            'duration_ms' => $result['duration_ms'] ?? 0,
            'timestamp' => $result['timestamp'] ?? date('Y-m-d H:i:s'),
            'messages' => $result['messages'] ?? [], // ⬅️ МЕНЯЕМ ТУТ
            'metadata' => [
                'timeout_used' => $timeout,
                'limit_used' => $limit,
                'consumer_group' => $result['consumer_group'] ?? 'unknown'
            ],
            'suggestions' => $this->getSuggestions($result, $topic)
        ]);
    }
    public function debugConsumer(Request $request): JsonResponse
    {
        $topic = $request->input('topic', 'cost-topic-my-api');
        $timeout = 5;

        $result = $this->kafkaService->consumeMessages($topic, $timeout, 1);

        return response()->json([
            'topic' => $topic,
            'result_structure' => array_keys($result),
            'raw_result' => $result,
            'has_messages_field' => isset($result['messages']),
            'messages_count' => isset($result['messages']) ? count($result['messages']) : 0,
            'first_message_structure' => isset($result['messages'][0]) ? array_keys($result['messages'][0]) : null,
            'first_message_value' => isset($result['messages'][0]['value']) ? $result['messages'][0]['value'] : null
        ]);
    }
    /**
     * Проверка статуса топика
     */
    public function checkTopic(Request $request): JsonResponse
    {
        $topic = $request->input('topic', 'test-topic');

        $result = $this->kafkaService->checkTopicStatus($topic);

        return response()->json([
            'operation' => 'topic_check',
            'topic' => $topic,
            'exists' => $result['exists'] ?? false,
            'status' => $result['status'],
            'message' => $result['message'] ?? '',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Получение списка всех топиков
     */
    public function listTopics(): JsonResponse
    {
        $result = $this->kafkaService->getTopics();

        return response()->json([
            'operation' => 'list_topics',
            'status' => $result['status'],
            'count' => $result['count'] ?? 0,
            'topics' => $result['topics'] ?? [],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Тестовый endpoint для проверки работы
     */
    public function testEndpoint(): JsonResponse
    {
        return response()->json([
            'service' => 'Kafka API Gateway',
            'status' => 'operational',
            'version' => '2.0',
            'timestamp' => date('Y-m-d H:i:s'),
            'endpoints' => [
                'send_test' => [
                    'method' => 'GET',
                    'url' => '/kafka/send-test/{orderId}/{status}',
                    'description' => 'Send test message'
                ],
                'send_cost' => [
                    'method' => 'POST',
                    'url' => '/kafka/send-cost',
                    'description' => 'Send cost calculation data'
                ],
                'send_cost_myapi' => [
                    'method' => 'POST',
                    'url' => '/kafka/send-cost-myapi',
                    'description' => 'Send my-api cost data'
                ],
                'consume' => [
                    'method' => 'GET',
                    'url' => '/kafka/consume?topic={topic}&timeout={timeout}&limit={limit}',
                    'description' => 'Consume messages from topic'
                ],
                'check_topic' => [
                    'method' => 'GET',
                    'url' => '/kafka/check-topic?topic={topic}',
                    'description' => 'Check topic status'
                ],
                'list_topics' => [
                    'method' => 'GET',
                    'url' => '/kafka/topics',
                    'description' => 'List all topics'
                ]
            ],
            'default_topics' => ['test-topic', 'cost-topic', 'cost-topic-my-api']
        ]);
    }

    /**
     * Генерация подсказок на основе результата
     */
    private function getSuggestions(array $result, string $topic): array
    {
        $suggestions = [];

        if ($result['status'] === 'success') {
            if (($result['message_count'] ?? 0) === 0) {
                $suggestions[] = "Topic '{$topic}' is empty. Send a message first.";
                $suggestions[] = "Use: GET /kafka/send-test/test123/pending";
                $suggestions[] = "Or: POST /kafka/send-cost with JSON data";
            } else {
                $suggestions[] = "Successfully retrieved {$result['message_count']} messages";
            }
        } else {
            $suggestions[] = "Operation failed: " . ($result['message'] ?? 'Unknown error');

            if (strpos($result['error'] ?? '', 'timeout') !== false) {
                $suggestions[] = "Try with shorter timeout: ?timeout=3";
                $suggestions[] = "Check if topic exists: /kafka/check-topic?topic={$topic}";
            }

            $suggestions[] = "Send a test message first to ensure topic has data";
        }

        return $suggestions;
    }
}

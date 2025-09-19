<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\KafkaService;

class TestKafkaController extends Controller
{
    protected $kafka;

    public function __construct(KafkaService $kafka)
    {
        $this->kafka = $kafka;
    }

    /**
     * Отправка сообщения в Kafka топик
     *
     * @param string $orderId
     * @param string $status
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage($orderId, $status)
    {
        $result = $this->kafka->send('test-topic', [
            'order_id' => $orderId,
            'status' => $status
        ]);

        return response()->json($result);
    }

    public function getMessages()
    {
        $result = $this->kafka->consumeMessages();

        return response()->json($result);
    }

}

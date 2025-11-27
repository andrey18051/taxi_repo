<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\KafkaService;

class KafkaController extends Controller
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
     * @return JsonResponse
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

    /**
     * Отправка сообщения в Kafka топик
     *

     * @return JsonResponse
     */
//    public function sendCostMessage(
//         $originLatitude,
//         $originLongitude,
//         $toLatitude,
//         $toLongitude,
//         $tariff,
//         $phone,
//         $clientCost,
//         $user,
//         $add_cost,
//         $time,
//         $comment,
//         $date,
//         $start,
//         $finish,
//         $wfpInvoice,
//         $services,
//         $city,
//         $application
//    ): JsonResponse {
//        $result = $this->kafka->send('cost-topic', [
//            'origin_lat'      => $originLatitude,
//            'origin_lng'      => $originLongitude,
//            'to_lat'          => $toLatitude,
//            'to_lng'          => $toLongitude,
//            'tariff'          => $tariff,
//            'phone'           => $phone,
//            'client_cost'     => $clientCost,
//            'user'            => $user,
//            'add_cost'        => $add_cost,
//            'time'            => $time,
//            'comment'         => $comment,
//            'date'            => $date,
//            'start'           => $start,
//            'finish'          => $finish,
//            'wfp_invoice'     => $wfpInvoice,
//            'services'        => $services,
//            'city'            => $city,
//            'application'     => $application,
//        ]);
//
//        return response()->json($result);
//    }

    public function sendCostMessage(Request $request): JsonResponse
    {
        $data = $request->all();

        $result = $this->kafka->send('cost-topic', $data);

        return response()->json($result);
    }
    public function sendCostMessageMyApi(Request $request): JsonResponse
    {
        $data = $request->all();

        $result = $this->kafka->send('cost-topic-my-api', $data);

        return response()->json($result);
    }

}

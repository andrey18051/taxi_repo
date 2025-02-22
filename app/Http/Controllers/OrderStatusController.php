<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderStatusController extends Controller
{


    public function __construct()
    {
        // В Laravel лучше использовать storage_path() для путей

    }

    private function log(string $message): void
    {
        // Используем встроенный в Laravel механизм логирования
        Log::info($message);

    }

    public function getOrderStatusMessage(string $currentState, string $nextState, int $closeReason = -1): string
    {
        $message = '';

        $this->log("Processing state transition: $currentState -> $nextState (closeReason: $closeReason)");

        switch ($currentState) {
            case 'SearchesForCar':
                switch ($nextState) {
                    case 'WaitingCarSearch':
                    case 'Canceled':
                    case 'CostCalculation':
                        $message = 'Поиск авто';
                        break;
                }
                break;

            case 'WaitingCarSearch':
                switch ($nextState) {
                    case 'Canceled':
                        $message = 'Поиск авто';
                        break;
                    case 'CarFound':
                        $message = 'Авто найдено';
                        break;
                }
                break;

            case 'CarFound':
                switch ($nextState) {
                    case 'CarFound':
                    case 'Running':
                    case 'CostCalculation':
                    case 'Canceled':
                        $message = 'Авто найдено';
                        break;
                }
                break;

            case 'Running':
                switch ($nextState) {
                    case 'CarFound':
                    case 'Running':
                    case 'CostCalculation':
                        $message = 'Авто найдено';
                        break;
                    case 'Executed':
                        $message = 'Заказ выполнен';
                        break;
                }
                break;

            case 'CostCalculation':
                switch ($nextState) {
                    case 'CarFound':
                    case 'Running':
                        $message = 'Авто найдено';
                        break;
                    case 'Canceled':
                    case 'CostCalculation':
                        $message = $closeReason != -1 ? 'Заказ снят' : 'Поиск авто';
                        break;
                    case 'Executed':
                        $message = 'Заказ выполнен';
                        break;
                    case 'SearchesForCar':
                    case 'WaitingCarSearch':
                        $message = 'Поиск авто';
                        break;
                }
                break;

            case 'Canceled':
                switch ($nextState) {
                    case 'SearchesForCar':
                    case 'WaitingCarSearch':
                        $message = 'Поиск авто';
                        break;
                    case 'CarFound':
                    case 'Running':
                        $message = 'Авто найдено';
                        break;
                    case 'CostCalculation':
                    case 'Canceled':
                        $message = $closeReason != -1 ? 'Заказ снят' : 'Поиск авто';
                        break;
                }
                break;

            case 'Executed':
                switch ($nextState) {
                    case 'SearchesForCar':
                    case 'WaitingCarSearch':
                    case 'CarFound':
                    case 'Running':
                    case 'CostCalculation':
                        $message = 'Заказ выполнен';
                        break;
                }
                break;

            default:
                $message = 'Неизвестное состояние';
                $this->log("Warning: Unknown current state: $currentState");
                break;
        }

        if ($message) {
            $this->log("Result: $message");
        } else {
            $this->log("Warning: No message determined for transition $currentState -> $nextState");
        }

        return $message;
    }

//    public function getOrderStatusMessageResult($currentOrderInput, $nextOrderInput): string
//    {
//        $message = '';
//
//        // Преобразуем входные данные в массивы
//        $currentOrder = is_string($currentOrderInput) ? json_decode($currentOrderInput, true) : $currentOrderInput;
//        $nextOrder = is_string($nextOrderInput) ? json_decode($nextOrderInput, true) : $nextOrderInput;
//
//        // Проверяем, что преобразование прошло успешно и данные являются массивами
//        if (!is_array($currentOrder) || !is_array($nextOrder)) {
//            $this->log("Error: Input parameters must be valid JSON strings or arrays");
//            return 'Ошибка обработки состояния';
//        }
//
//        // Извлекаем состояния
//        $currentState = $currentOrder['execution_status'] ?? '';
//        $nextState = $nextOrder['execution_status'] ?? '';
//
//        // Определяем closeReason: берем из того заказа, где status = Canceled, иначе -1
//        $closeReason = ($currentState === 'Canceled') ? ($currentOrder['close_reason'] ?? -1) :
//            ($nextState === 'Canceled') ? ($nextOrder['close_reason'] ?? -1) : -1;
//
//        $this->log("Processing state transition: $currentState -> $nextState (closeReason: $closeReason)");
//
//        // Логика на основе таблицы
//        switch ($currentState) {
//            case 'SearchesForCar':
//                switch ($nextState) {
//                    case 'SearchesForCar':
//                    case 'WaitingCarSearch':
//                    case 'Canceled':
//                    case 'CostCalculation':
//                        $message = 'Поиск авто';
//                        break;
//                    case 'CarFound':
//                    case 'Running':
//                        $message = 'Авто найдено';
//                        break;
//                }
//                break;
//
//            case 'WaitingCarSearch':
//                switch ($nextState) {
//                    case 'SearchesForCar':
//                    case 'WaitingCarSearch':
//                    case 'CostCalculation':
//                    case 'Canceled':
//                        $message = 'Поиск авто';
//                        break;
//                    case 'CarFound':
//                    case 'Running':
//                        $message = 'Авто найдено';
//                        break;
//                }
//                break;
//
//            case 'Running':
//            case 'CarFound':
//                switch ($nextState) {
//                    case 'SearchesForCar':
//                    case 'WaitingCarSearch':
//                    case 'CarFound':
//                    case 'Running':
//                    case 'Canceled':
//                    case 'CostCalculation':
//                        $message = 'Авто найдено';
//                        break;
//                }
//                break;
//
//            case 'CostCalculation':
//                switch ($nextState) {
//                    case 'SearchesForCar':
//                    case 'WaitingCarSearch':
//                        $message = 'Поиск авто';
//                        break;
//                    case 'CarFound':
//                    case 'Running':
//                        $message = 'Авто найдено';
//                        break;
//                    case 'CostCalculation':
//                    case 'Canceled':
//                        $message = ($closeReason != -1) ? 'Заказ снят' : 'Поиск авто';
//                        break;
//                    case 'Executed':
//                        $message = 'Заказ выполнен';
//                        break;
//                }
//                break;
//
//            case 'Canceled':
//                switch ($nextState) {
//                    case 'SearchesForCar':
//                    case 'WaitingCarSearch':
//                        $message = 'Поиск авто';
//                        break;
//                    case 'CarFound':
//                    case 'Running':
//                        $message = 'Авто найдено';
//                        break;
//                    case 'CostCalculation':
//                    case 'Canceled':
//                        $message = ($closeReason != -1) ? 'Заказ снят' : 'Поиск авто';
//                        break;
//                }
//                break;
//
//            case 'Executed':
//                switch ($nextState) {
//                    case 'SearchesForCar':
//                    case 'WaitingCarSearch':
//                    case 'CarFound':
//                    case 'Running':
//                    case 'CostCalculation':
//                        $message = 'Заказ выполнен';
//                        break;
//                }
//                break;
//
//            default:
//                $message = 'Неизвестное состояние';
//                $this->log("Warning: Unknown current state: $currentState");
//                break;
//        }
//
//        if ($message) {
//            $this->log("Result: $message");
//        } else {
//            $this->log("Warning: No message determined for transition $currentState -> $nextState");
//        }
//
//        return $message;
//    }

    public function testOrderStatusMessageResultWithRealData($currentOrderInput, $nextOrderInput): \Illuminate\Http\JsonResponse
    {
        $currentOrderBase = is_string($currentOrderInput) ? json_decode($currentOrderInput, true) : $currentOrderInput;
        $nextOrderBase = is_string($nextOrderInput) ? json_decode($nextOrderInput, true) : $nextOrderInput;

        if (!is_array($currentOrderBase) || !is_array($nextOrderBase)) {
            $this->log("Error: Input parameters must be valid JSON strings or arrays");
            return response()->json(['error' => 'Invalid input'], 400);
        }

        $statusSequence = [
            'SearchesForCar',
            'WaitingCarSearch',
            'CarFound',
            'Running',
            'CostCalculation',
            'Canceled',
            'Executed'
        ];

        $possibleCloseReasons = [-1, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9];

        $this->log("Тест начат с реальными данными:");
        $this->log("Исходный наличный: " . json_encode($currentOrderBase));
        $this->log("Исходный БЕЗНАЛ: " . json_encode($nextOrderBase));

        $results = [];

        foreach ($statusSequence as $currentStatus) {
            foreach ($statusSequence as $nextStatus) {
                foreach ($possibleCloseReasons as $currentCloseReason) {
                    foreach ($possibleCloseReasons as $nextCloseReason) {
                        $currentOrder = $currentOrderBase;
                        $nextOrder = $nextOrderBase;

                        $currentOrder['execution_status'] = $currentStatus;
                        $currentOrder['close_reason'] = $currentCloseReason;
                        $nextOrder['execution_status'] = $nextStatus;
                        $nextOrder['close_reason'] = $nextCloseReason;

                        $this->log("\nТестируем комбинацию:");
                        $this->log("наличный: status={$currentOrder['execution_status']}, close_reason={$currentOrder['close_reason']}");
                        $this->log("БЕЗНАЛ: status={$nextOrder['execution_status']}, close_reason={$nextOrder['close_reason']}");

                        $result = $this->getOrderStatusMessageResult($currentOrder, $nextOrder);

                        if ($result === null) {
                            $this->log("Ошибка: результат обработки null");
                            continue;
                        }

                        // Определяем, какой заказ выбран
                        $selectedOrderSource = ($result === $currentOrder) ? 'наличный' :
                            (($result === $nextOrder) ? 'БЕЗНАЛ' : 'Неизвестный источник');

                        // Логируем результат с указанием источника
                        $this->log("Выбранный заказ: status={$result['execution_status']}, close_reason={$result['close_reason']} (Источник: $selectedOrderSource)");

                        $operatorAction = $this->simulateOperatorAction($result);

                        $results[] = [
                            'current_order' => [
                                'status' => $currentStatus,
                                'close_reason' => $currentCloseReason
                            ],
                            'next_order' => [
                                'status' => $nextStatus,
                                'close_reason' => $nextCloseReason
                            ],
                            'selected_order' => $result,
                            'operator_action' => $operatorAction,
                            'selected_source' => $selectedOrderSource // Добавляем в результат
                        ];
                    }
                }
            }
        }

        $this->log("Тест завершен");
        return response()->json(['results' => $results]);
    }

// Основной метод (без изменений)
    public function getOrderStatusMessageResult($currentOrderInput, $nextOrderInput)
    {
        $currentOrder = is_string($currentOrderInput) ? json_decode($currentOrderInput, true) : $currentOrderInput;
        $nextOrder = is_string($nextOrderInput) ? json_decode($nextOrderInput, true) : $nextOrderInput;

        if (!is_array($currentOrder) || !is_array($nextOrder)) {
            $this->log("Error: Input parameters must be valid JSON strings or arrays");
            return null;
        }

        $currentState = $currentOrder['execution_status'] ?? 'SearchesForCar';
        $nextState = $nextOrder['execution_status'] ?? 'SearchesForCar';
        $closeReasonCurrent = $currentOrder['close_reason'] ?? -1;
        $closeReasonNext = $nextOrder['close_reason'] ?? -1;

        $this->log("Processing state transition: $currentState -> $nextState (closeReasonCurrent: $closeReasonCurrent, closeReasonNext: $closeReasonNext)");

        switch (true) {
            case ($currentState !== 'Canceled' && $nextState === 'Canceled'):
                if ($closeReasonNext != -1) {
                    return $this->prepareResponse($nextOrder);
                }
                return $this->prepareResponse($currentOrder);

            case ($currentState === 'Canceled' && $nextState !== 'Canceled'):
                if ($closeReasonCurrent != -1) {
                    return $this->prepareResponse($currentOrder);
                }
                return $this->prepareResponse($nextOrder);

            case ($currentState !== 'Canceled' && $nextState !== 'Canceled'):
                if ($currentState === 'Running' || $nextState === 'Running') {
                    return $this->prepareResponse($currentState === 'Running' ? $currentOrder : $nextOrder);
                }
                if ($currentState === 'Executed' || $nextState === 'Executed') {
                    return $this->prepareResponse($currentState === 'Executed' ? $currentOrder : $nextOrder);
                }
                if ($currentState === 'CarFound' || $nextState === 'CarFound') {
                    return $this->prepareResponse($currentState === 'CarFound' ? $currentOrder : $nextOrder);
                }
                return $this->prepareResponse($currentOrder);

            case ($currentState === 'Canceled' && $nextState === 'Canceled'):
                if ($closeReasonCurrent != -1 && $closeReasonCurrent !== -1) {
                    return $this->prepareResponse($currentOrder);
                }
                if ($closeReasonNext != -1) {
                    return $this->prepareResponse($nextOrder);
                }
                return $this->prepareResponse($currentOrder);

            default:
                return $this->prepareResponse($currentOrder);
        }
    }

    private function prepareResponse($order)
    {
        return $order; // Возвращаем весь заказ целиком
    }

// Эмуляция действий оператора
    private function simulateOperatorAction($order)
    {
        $closeReason = $order['close_reason'] ?? -1;
        $executionStatus = $order['execution_status'] ?? 'SearchesForCar';
        $driverPhone = $order['driver_phone'] ?? null;
        $timeToStartPoint = $order['time_to_start_point'] ?? null;
        $orderCarInfo = $order['order_car_info'] ?? null;

        switch ($closeReason) {
            case -1:
                switch ($executionStatus) {
                    case 'CarFound':
                    case 'Running':
                        $this->log("Оператор: Авто найдено. Информация: $orderCarInfo, Телефон: $driverPhone, Время: $timeToStartPoint");
                        break;
                    case 'Executed':
                        $this->log("Оператор: Заказ выполнен");
                        break;
                    case 'SearchesForCar':
                    case 'WaitingCarSearch':
                    case 'CostCalculation':
                    default:
                        $this->log("Оператор: Поиск авто");
                        break;
                }
                break;

            case 0:
            case 8:
                if ($executionStatus === 'Executed') {
                    $this->log("Оператор: Заказ выполнен");
                } else if (in_array($executionStatus, ['SearchesForCar', 'WaitingCarSearch']) || $executionStatus === null) {
                    $this->log("Оператор: Поиск авто");
                } else if (in_array($executionStatus, ['CarFound', 'Running'])) {
                    $this->log("Оператор: Авто найдено. Информация: $orderCarInfo, Телефон: $driverPhone, Время: $timeToStartPoint");
                }
                break;

            case 1:
            case 2:
            case 3:
            case 4:
            case 5:
            case 6:
            case 7:
            case 9:
                if ($executionStatus === 'Canceled') {
                    $this->log("Оператор: Заказ отменен клиентом");
                } else if ($executionStatus === 'CostCalculation') {
                    $this->log("Оператор: Заказ снят (CostCalculation)");
                } else if (in_array($executionStatus, ['CarFound', 'Running'])) {
                    $this->log("Оператор: Авто найдено. Информация: $orderCarInfo, Телефон: $driverPhone, Время: $timeToStartPoint");
                } else {
                    $this->log("Оператор: Поиск авто");
                }
                break;

            default:
                $this->log("Оператор: Поиск авто (неизвестный close_reason)");
                break;
        }
    }


// Пример вызова теста с реальными данными
    public function runTestWithRealData()
    {
        $currentOrderExample = [
            'execution_status' => 'Running',
            'close_reason' => 0,
            'driver_phone' => '123-456-7890',
            'time_to_start_point' => '10 минут',
            'order_car_info' => 'Toyota Camry, белый, A123BC',
            'order_id' => 'ORD123'
        ];

        $nextOrderExample = [
            'execution_status' => 'Canceled',
            'close_reason' => 3,
            'driver_phone' => '098-765-4321',
            'time_to_start_point' => '15 минут',
            'order_car_info' => 'Honda Accord, черный, X789YZ',
            'order_id' => 'ORD456'
        ];

        $this->testOrderStatusMessageResultWithRealData($currentOrderExample, $nextOrderExample);
    }

    // Добавим метод для обработки HTTP-запросов
    public function showStatus(string $currentState, string $nextState, ?int $closeReason = -1)
    {
        // Валидация напрямую через встроенные проверки PHP
        if (empty($currentState) || !is_string($currentState)) {
            throw new \InvalidArgumentException('current_state must be a non-empty string');
        }

        if (empty($nextState) || !is_string($nextState)) {
            throw new \InvalidArgumentException('next_state must be a non-empty string');
        }

        $message = $this->getOrderStatusMessage($currentState, $nextState, $closeReason);

        return response()->json([

            'message' => $message,
            'дубль нал' => $currentState,
            'безнал' => $nextState,
            'close_reason' => $closeReason
        ]);
    }
}

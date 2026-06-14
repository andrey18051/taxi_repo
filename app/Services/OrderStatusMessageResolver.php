<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Маппинг пары execution_status (нал + карта) → action для приложения.
 * Правила сверены с таблицей «Действия статусы.xlsx» и OrderStatusController.
 */
class OrderStatusMessageResolver
{
    public const ACTION_SEARCH = 'Поиск авто';

    public const ACTION_CAR_FOUND = 'Авто найдено';

    public const ACTION_AT_ADDRESS = 'На месте';

    public const ACTION_IN_ROUTE = 'В пути';

    public const ACTION_COMPLETED = 'Заказ выполнен';

    public const ACTION_CANCELED = 'Заказ снят';

    private const DISPATCHED_STATES = [
        'CarFound',
        'Running',
        'WaitingAtAddress',
        'AtAddress',
        'InRoute',
    ];

    /**
     * @return array{action: string, response_leg: string, close_reason: int, orderweb_close_reason: string}
     */
    public function resolve(string $nalState, string $cardState, ?array $nalOrder = null, ?array $cardOrder = null): array
    {
        $nalClose = $this->closeReason($nalOrder);
        $cardClose = $this->closeReason($cardOrder);

        $base = $this->resolveBaseAction($nalState, $cardState, $nalClose, $cardClose);
        $refined = $this->refineTripStage($base, $nalState, $cardState, $nalOrder, $cardOrder);

        return $refined;
    }

    /**
     * @return array{action: string, response_leg: string, close_reason: int, orderweb_close_reason: string}
     */
    private function resolveBaseAction(string $nalState, string $cardState, int $nalClose, int $cardClose): array
    {
        if ($this->inStates($nalState, ['SearchesForCar', 'WaitingCarSearch'])
            && $this->inStates($cardState, ['SearchesForCar', 'WaitingCarSearch'])) {
            return $this->pack(self::ACTION_SEARCH, 'nal', -1, '-1');
        }
        if ($nalState === 'SearchesForCar' && $cardState === 'CostCalculation') {
            return $this->pack(self::ACTION_SEARCH, 'nal', -1, '-1');
        }
        if ($nalState === 'CostCalculation' && $cardState === 'SearchesForCar') {
            return $this->pack(self::ACTION_SEARCH, 'card', -1, '-1');
        }
        if ($nalState === 'Canceled' && $cardState === 'SearchesForCar') {
            return $this->pack(self::ACTION_SEARCH, 'card', -1, '-1');
        }
        if ($nalState === 'SearchesForCar' && $cardState === 'Canceled') {
            return $this->pack(self::ACTION_SEARCH, 'nal', -1, '-1');
        }
        if ($nalState === 'Canceled' && $cardState === 'WaitingCarSearch') {
            return $this->pack(self::ACTION_SEARCH, 'card', -1, '-1');
        }
        if ($nalState === 'WaitingCarSearch' && $cardState === 'Canceled') {
            return $this->pack(self::ACTION_SEARCH, 'nal', -1, '-1');
        }
        if ($nalState === 'CostCalculation' && $this->inStates($cardState, ['SearchesForCar', 'WaitingCarSearch'])) {
            return $this->pack(self::ACTION_SEARCH, 'card', -1, '-1');
        }
        if ($this->inStates($nalState, ['SearchesForCar', 'WaitingCarSearch']) && $cardState === 'CostCalculation') {
            return $this->pack(self::ACTION_SEARCH, 'nal', -1, '-1');
        }

        if ($nalState === 'SearchesForCar' && $this->inStates($cardState, ['CarFound', 'Running'])) {
            return $this->pack(self::ACTION_CAR_FOUND, 'card', -1, '101');
        }
        if ($this->inStates($nalState, ['CarFound', 'Running']) && $cardState === 'SearchesForCar') {
            return $this->pack(self::ACTION_CAR_FOUND, 'nal', -1, '101');
        }
        if ($nalState === 'WaitingCarSearch' && $this->inStates($cardState, ['CarFound', 'Running'])) {
            return $this->pack(self::ACTION_CAR_FOUND, 'card', -1, '101');
        }
        if ($this->inStates($nalState, ['CarFound', 'Running']) && $cardState === 'WaitingCarSearch') {
            return $this->pack(self::ACTION_CAR_FOUND, 'nal', -1, '101');
        }
        if ($nalState === 'CarFound' && $this->inStates($cardState, ['CarFound', 'Running'])) {
            return $this->pack(self::ACTION_CAR_FOUND, 'card', -1, '101');
        }
        if ($nalState === 'Running' && $cardState === 'CarFound') {
            return $this->pack(self::ACTION_CAR_FOUND, 'nal', -1, '101');
        }
        if ($nalState === 'Running' && $cardState === 'Running') {
            return $this->pack(self::ACTION_CAR_FOUND, 'card', -1, '101');
        }
        if ($nalState === 'Canceled' && $this->inStates($cardState, ['CarFound', 'Running'])) {
            return $this->pack(self::ACTION_CAR_FOUND, 'card', -1, '101');
        }
        if ($this->inStates($nalState, ['CarFound', 'Running']) && $cardState === 'Canceled') {
            return $this->pack(self::ACTION_CAR_FOUND, 'nal', -1, '101');
        }
        if ($nalState === 'CostCalculation' && $this->inStates($cardState, ['CarFound', 'Running'])) {
            return $this->pack(self::ACTION_CAR_FOUND, 'card', -1, '101');
        }
        if ($this->inStates($nalState, ['CarFound', 'Running']) && $cardState === 'CostCalculation') {
            return $this->pack(self::ACTION_CAR_FOUND, 'nal', -1, '101');
        }

        if ($nalState === 'Executed' && $this->inStates($cardState, ['SearchesForCar', 'WaitingCarSearch', 'CarFound', 'Running'])) {
            return $this->pack(self::ACTION_COMPLETED, 'nal', 104, '104');
        }
        if ($this->inStates($nalState, ['SearchesForCar', 'WaitingCarSearch', 'CarFound', 'Running']) && $cardState === 'Executed') {
            return $this->pack(self::ACTION_COMPLETED, 'card', 104, '104');
        }
        if ($nalState === 'Executed' && $cardState === 'CostCalculation') {
            return $this->pack(self::ACTION_COMPLETED, 'nal', 104, '104');
        }
        if ($nalState === 'CostCalculation' && $cardState === 'Executed') {
            return $this->pack(self::ACTION_COMPLETED, 'card', 104, '104');
        }
        if ($nalState === 'Canceled' && $cardState === 'Executed') {
            return $this->pack(self::ACTION_COMPLETED, 'card', 104, '104');
        }
        if ($nalState === 'Executed' && $cardState === 'Canceled') {
            return $this->pack(self::ACTION_CANCELED, 'card', 1, '1');
        }
        if ($nalState === 'Executed' && $cardState === 'Executed') {
            return $this->pack(self::ACTION_COMPLETED, 'card', 104, '104');
        }

        if ($nalState === 'Canceled' && $cardState === 'CostCalculation') {
            $action = $nalClose !== -1 ? self::ACTION_CANCELED : self::ACTION_SEARCH;

            return $this->pack($action, 'nal', $nalClose, (string) ($nalClose !== -1 ? $nalClose : -1));
        }
        if ($nalState === 'CostCalculation' && $cardState === 'Canceled') {
            $action = $cardClose !== -1 ? self::ACTION_CANCELED : self::ACTION_SEARCH;

            return $this->pack($action, 'card', $cardClose, (string) ($cardClose !== -1 ? $cardClose : -1));
        }
        if ($nalState === 'CostCalculation' && $cardState === 'CostCalculation') {
            if ($nalClose !== -1 && $cardClose !== -1) {
                return $this->pack(self::ACTION_CANCELED, 'card', 1, '1');
            }

            return $this->pack(self::ACTION_SEARCH, 'card', -1, '-1');
        }
        if ($nalState === 'Canceled' && $cardState === 'Canceled') {
            if ($nalClose !== -1 || $cardClose !== -1) {
                return $this->pack(self::ACTION_CANCELED, 'card', 1, '1');
            }

            return $this->pack(self::ACTION_SEARCH, 'card', -1, '-1');
        }

        if ($this->hasDispatchedLeg($nalState, $cardState)) {
            $leg = $this->pickActiveLeg($nalState, $cardState);

            return $this->pack(self::ACTION_CAR_FOUND, $leg, -1, '101');
        }

        Log::warning('OrderStatusMessageResolver fallback', [
            'nalState' => $nalState,
            'cardState' => $cardState,
        ]);

        return $this->pack(self::ACTION_SEARCH, 'nal', -1, '-1');
    }

    /**
     * @return array{action: string, response_leg: string, close_reason: int, orderweb_close_reason: string}
     */
    private function refineTripStage(
        array $base,
        string $nalState,
        string $cardState,
        ?array $nalOrder,
        ?array $cardOrder
    ): array {
        if (!in_array($base['action'], [self::ACTION_CAR_FOUND, self::ACTION_SEARCH], true)) {
            return $base;
        }

        $leg = $base['response_leg'] === 'card' ? $cardOrder : $nalOrder;
        if ($leg === null) {
            return $base;
        }

        $executionStatus = (string) ($leg['execution_status'] ?? '');
        $stage = $this->stageFromExecutionStatus($executionStatus);
        if ($stage === null) {
            return $base;
        }

        $base['action'] = $stage['action'];
        $base['close_reason'] = $stage['close_reason'];
        $base['orderweb_close_reason'] = (string) $stage['close_reason'];

        return $base;
    }

    /**
     * @return array{action: string, close_reason: int}|null
     */
    private function stageFromExecutionStatus(string $executionStatus): ?array
    {
        switch ($executionStatus) {
            case 'WaitingAtAddress':
            case 'AtAddress':
                return ['action' => self::ACTION_AT_ADDRESS, 'close_reason' => 102];
            case 'InRoute':
                return ['action' => self::ACTION_IN_ROUTE, 'close_reason' => 103];
            case 'Running':
                return ['action' => self::ACTION_IN_ROUTE, 'close_reason' => 103];
            case 'CarFound':
                return ['action' => self::ACTION_CAR_FOUND, 'close_reason' => 101];
            default:
                return null;
        }
    }

    private function hasDispatchedLeg(string $nalState, string $cardState): bool
    {
        return in_array($nalState, self::DISPATCHED_STATES, true)
            || in_array($cardState, self::DISPATCHED_STATES, true);
    }

    private function pickActiveLeg(string $nalState, string $cardState): string
    {
        if (in_array($cardState, self::DISPATCHED_STATES, true)
            && !in_array($nalState, self::DISPATCHED_STATES, true)) {
            return 'card';
        }
        if (in_array($nalState, self::DISPATCHED_STATES, true)) {
            return 'nal';
        }

        return 'card';
    }

    private function inStates(string $state, array $list): bool
    {
        return in_array($state, $list, true);
    }

    private function closeReason(?array $order): int
    {
        if ($order === null) {
            return -1;
        }
        $value = $order['close_reason'] ?? -1;

        return is_numeric($value) ? (int) $value : -1;
    }

    /**
     * @return array{action: string, response_leg: string, close_reason: int, orderweb_close_reason: string}
     */
    private function pack(string $action, string $leg, int $closeReason, string $orderwebCloseReason): array
    {
        return [
            'action' => $action,
            'response_leg' => $leg,
            'close_reason' => $closeReason,
            'orderweb_close_reason' => $orderwebCloseReason,
        ];
    }
}

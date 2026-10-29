<?php

namespace App\Services;

/**
 * Когда простой одиночный заказ PAS 2 можно поднять заново после снятия на сервере такси.
 * Решение без БД и HTTP — его проверяют тесты.
 */
final class Pas2SimpleDispatchRecreateGate
{
    public const MAX_RECREATES = 2;

    /** «Не выполнено» на диспетчере: не «снят клиентом» (1, 6, 7, 9). */
    public const FOREIGN_CLOSE_REASONS = [2, 3, 4, 5];

    /** Заказ ещё в поиске, машину клиенту не показывали. */
    public const SEARCH_CLOSE_REASONS = ['-1', '0', '100', ''];

    /**
     * Машину уже показывали. Если диспетчер после этого закрыл заказ
     * причиной 2/3/4/5, это снятое авто, а не живая поездка.
     */
    public const CAR_SHOWN_CLOSE_REASONS = ['101', '102', '103'];

    private const TRIP_EXECUTION = ['CarFound', 'Running', 'Executed'];

    /**
     * @param array{
     *   is_pas2?: bool,
     *   payment_flow?: int,
     *   server?: string,
     *   is_fork?: bool,
     *   close_reason?: int,
     *   order_is_archive?: bool,
     *   execution_status?: string,
     *   previous_close_reason?: string,
     *   recover_stored?: bool,
     *   has_car?: bool,
     *   cancel_requested?: bool,
     *   payment_confirmed?: bool,
     *   recreate_count?: int,
     *   add_cost_in_progress?: bool
     * } $context
     */
    public static function shouldRecreate(array $context): bool
    {
        if (empty($context['is_pas2'])) {
            return false;
        }

        if ((int) ($context['payment_flow'] ?? 0) !== \App\City\PaymentFlow::SIMPLE) {
            return false;
        }

        $server = (string) ($context['server'] ?? '');
        if ($server === '' || $server === 'my_server_api') {
            return false;
        }

        if (!empty($context['is_fork'])) {
            return false;
        }

        $closeReason = (int) ($context['close_reason'] ?? -999);
        if (!in_array($closeReason, self::FOREIGN_CLOSE_REASONS, true)) {
            return false;
        }

        if (empty($context['recover_stored']) && empty($context['order_is_archive'])) {
            return false;
        }

        $archivedForeign = self::isArchivedForeignClose($context, $closeReason);
        $execution = (string) ($context['execution_status'] ?? '');
        if ($execution === 'Executed'
            || (!$archivedForeign && in_array($execution, self::TRIP_EXECUTION, true))) {
            return false;
        }

        if (!$archivedForeign && !empty($context['has_car'])) {
            return false;
        }

        if (!empty($context['cancel_requested']) || !empty($context['add_cost_in_progress'])) {
            return false;
        }

        if (empty($context['payment_confirmed'])) {
            return false;
        }

        if ((int) ($context['recreate_count'] ?? 0) >= self::MAX_RECREATES) {
            return false;
        }

        if (!empty($context['recover_stored'])) {
            return true;
        }

        $previous = (string) ($context['previous_close_reason'] ?? '');
        if (in_array($previous, self::SEARCH_CLOSE_REASONS, true)) {
            return true;
        }

        return $archivedForeign && in_array($previous, self::CAR_SHOWN_CLOSE_REASONS, true);
    }

    /**
     * Архив диспетчера с «нет машины» / «отказ водителя» / «просчет».
     * Сохранённое авто с прошлого опроса здесь уже не живой заказ.
     *
     * @param array<string, mixed> $context
     */
    private static function isArchivedForeignClose(array $context, int $closeReason): bool
    {
        return !empty($context['order_is_archive'])
            && in_array($closeReason, self::FOREIGN_CLOSE_REASONS, true);
    }
}

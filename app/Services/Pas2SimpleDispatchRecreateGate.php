<?php

namespace App\Services;

/**
 * Когда одиночный заказ можно поднять заново после снятия на сервере такси.
 * PAS 2 на простом режиме — нал и безнал. Остальные PAS — одиночный нал,
 * в том числе в городе с вилкой, где наличный заказ один.
 * Решение без БД и HTTP — его проверяют тесты.
 */
final class Pas2SimpleDispatchRecreateGate
{
    /** Диспетчер может несколько раз снять «нет машины»; 2 мало — третья попытка оставляла поиск без заказа. */
    public const MAX_RECREATES = 5;

    /** «Не выполнено» на диспетчере: не «снят клиентом» (1, 6, 7, 9). 4 — «нет машины». */
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
     *   is_client_app?: bool,
     *   pay_system?: string,
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
        if (!self::isEligibleOrder($context)) {
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

        $archivedForeign = self::isForeignDispatchClose($context, $closeReason);
        if (empty($context['recover_stored']) && !$archivedForeign) {
            return false;
        }
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
     * PAS 2 и простой режим — как раньше (нал и оплаченный безнал).
     * Одиночный нал любого PAS — и на простом режиме, и в городе с вилкой.
     *
     * @param array<string, mixed> $context
     */
    private static function isEligibleOrder(array $context): bool
    {
        $knownApp = !empty($context['is_pas2']) || !empty($context['is_client_app']);
        if (!$knownApp) {
            return false;
        }

        $flow = (int) ($context['payment_flow'] ?? 0);
        if (!empty($context['is_pas2']) && $flow === \App\City\PaymentFlow::SIMPLE) {
            return true;
        }

        $singleCash = (string) ($context['pay_system'] ?? '') === 'nal_payment'
            && empty($context['is_fork']);
        if (!$singleCash) {
            return false;
        }

        return in_array($flow, [
            \App\City\PaymentFlow::SIMPLE,
            \App\City\PaymentFlow::FORK,
            \App\City\PaymentFlow::FORK_LEGACY,
        ], true);
    }

    /**
     * Причина 2/3/4/5 уже лежит в orderweb — следующий опрос должен поднять заказ,
     * даже если архив диспетчера ещё не пришёл.
     */
    public static function shouldRecoverStoredCloseReason($closeReason): bool
    {
        return in_array((string) $closeReason, ['2', '3', '4', '5'], true);
    }

    /**
     * Архив диспетчера или Canceled с «нет машины» / «отказ водителя» / «просчет».
     * Сохранённое авто с прошлого опроса здесь уже не живой заказ.
     *
     * @param array<string, mixed> $context
     */
    private static function isForeignDispatchClose(array $context, int $closeReason): bool
    {
        if (!in_array($closeReason, self::FOREIGN_CLOSE_REASONS, true)) {
            return false;
        }
        if (!empty($context['order_is_archive'])) {
            return true;
        }

        $execution = strtolower((string) ($context['execution_status'] ?? ''));

        return $execution === 'canceled' || $execution === 'cancelled';
    }
}

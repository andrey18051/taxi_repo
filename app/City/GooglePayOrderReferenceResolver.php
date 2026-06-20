<?php

namespace App\City;

/**
 * Согласование orderReference приложения с hold, созданным до отправки заказа.
 */
final class GooglePayOrderReferenceResolver
{
    private const HOLD_STATUSES = ['WaitingAuthComplete', 'Approved'];

    /**
     * @param iterable<int, array{orderReference?: string|null, amount?: string|null, transactionStatus?: string|null, dispatching_order_uid?: string|null}> $orphanCandidates
     */
    public static function resolveForOrderBind(
        string $appOrderReference,
        $orderCost,
        iterable $orphanCandidates
    ): string {
        $appInvoiceReady = false;
        foreach ($orphanCandidates as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }
            if (($candidate['orderReference'] ?? null) === $appOrderReference
                && self::isHoldStatus($candidate['transactionStatus'] ?? null)
                && !empty($candidate['dispatching_order_uid'])) {
                $appInvoiceReady = true;
                break;
            }
        }

        if ($appInvoiceReady || self::hasHoldInvoice($appOrderReference, $orphanCandidates, false)) {
            return $appOrderReference;
        }

        $orderCostString = self::normalizeAmount($orderCost);
        foreach ($orphanCandidates as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }
            $reference = $candidate['orderReference'] ?? null;
            if ($reference === null || $reference === '' || $reference === $appOrderReference) {
                continue;
            }
            if (!empty($candidate['dispatching_order_uid'])) {
                continue;
            }
            if (!self::isHoldStatus($candidate['transactionStatus'] ?? null)) {
                continue;
            }
            if ($orderCostString !== null
                && self::normalizeAmount($candidate['amount'] ?? null) !== $orderCostString) {
                continue;
            }

            return $reference;
        }

        return $appOrderReference;
    }

    /**
     * @param iterable<int, array{orderReference?: string|null, transactionStatus?: string|null, dispatching_order_uid?: string|null}> $candidates
     */
    private static function hasHoldInvoice(string $orderReference, iterable $candidates, bool $requireUid): bool
    {
        foreach ($candidates as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }
            if (($candidate['orderReference'] ?? null) !== $orderReference) {
                continue;
            }
            if (!self::isHoldStatus($candidate['transactionStatus'] ?? null)) {
                continue;
            }
            if ($requireUid && empty($candidate['dispatching_order_uid'])) {
                continue;
            }

            return true;
        }

        return false;
    }

    private static function isHoldStatus(?string $status): bool
    {
        return $status !== null && in_array($status, self::HOLD_STATUSES, true);
    }

    private static function normalizeAmount($amount): ?string
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        if (is_numeric($amount)) {
            $value = (float) $amount;

            return abs($value - round($value)) < 0.00001
                ? (string) (int) round($value)
                : (string) $value;
        }

        return (string) $amount;
    }
}

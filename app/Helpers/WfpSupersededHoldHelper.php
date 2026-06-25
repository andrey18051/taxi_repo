<?php

namespace App\Helpers;

/**
 * Определяет, что инвойс — устаревший основной холд (после rebind Google Pay).
 */
class WfpSupersededHoldHelper
{
    public static function isSupersededMainHold(
        string $invoiceReference,
        ?string $orderMainReference,
        ?string $invoiceStatus
    ): bool {
        if ($invoiceStatus !== 'WaitingAuthComplete') {
            return false;
        }
        if ($orderMainReference === null || $orderMainReference === '') {
            return false;
        }

        return $invoiceReference !== $orderMainReference;
    }
}

<?php

namespace App\Support;

use App\City\SimpleCashlessDispatchStatusSync;

/**
 * orderwebs.payment_type is MySQL SET('0','1').
 * A numeric 1 is the bit of the first member, so the row is stored as cash ('0').
 * Card orders must be written as the string '1'.
 */
final class PaymentTypeColumn
{
    public static function store(?string $paySystem, $paymentType): string
    {
        if (SimpleCashlessDispatchStatusSync::isCashlessPaySystem($paySystem)
            || (int) $paymentType === 1
        ) {
            return '1';
        }

        return '0';
    }
}

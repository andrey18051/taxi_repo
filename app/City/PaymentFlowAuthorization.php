<?php

namespace App\City;

final class PaymentFlowAuthorization
{
    /**
     * Adjust dispatch authorization for city payment_flow (fork / simple / off).
     */
    public static function apply(array $authorizationChoiceArr, int $paymentFlow): array
    {
        if ($paymentFlow === PaymentFlow::OFF) {
            $authorizationChoiceArr['payment_type'] = 0;
            $authorizationChoiceArr['authorizationBonus'] = null;
            $authorizationChoiceArr['authorizationDouble'] = null;

            return $authorizationChoiceArr;
        }

        if ($paymentFlow !== PaymentFlow::SIMPLE) {
            return $authorizationChoiceArr;
        }

        if ((int) ($authorizationChoiceArr['payment_type'] ?? 0) !== 1) {
            return $authorizationChoiceArr;
        }

        if (!empty($authorizationChoiceArr['authorizationBonus'])) {
            $authorizationChoiceArr['authorization'] = $authorizationChoiceArr['authorizationBonus'];
        }

        // authorizationBonus оставляем: cost/order по-прежнему берут его при payment_type=1.
        $authorizationChoiceArr['authorizationDouble'] = null;

        return $authorizationChoiceArr;
    }
}

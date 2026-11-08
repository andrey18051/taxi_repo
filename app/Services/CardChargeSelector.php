<?php

namespace App\Services;

/**
 * Какая сохранённая карта идёт в CHARGE.
 * Активная побеждает. Если флаг active не проставлен, берём последнюю карту этого мерчанта.
 */
class CardChargeSelector
{
    public static function pick(array $cards): ?array
    {
        $latest = null;
        $active = null;

        foreach ($cards as $card) {
            if (!is_array($card) || !isset($card['id'])) {
                continue;
            }

            if ($latest === null || (int) $card['id'] > (int) $latest['id']) {
                $latest = $card;
            }

            if (!empty($card['active'])) {
                if ($active === null || (int) $card['id'] > (int) $active['id']) {
                    $active = $card;
                }
            }
        }

        return $active ?? $latest;
    }
}

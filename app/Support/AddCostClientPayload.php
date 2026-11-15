<?php

namespace App\Support;

/**
 * Ответ chargeActiveTokenAddCost, который PAS разбирает как PurchaseResponse.
 * Пустой transactionStatus роняет switch в приложении (NPE getClass на null).
 */
final class AddCostClientPayload
{
    public const STATUS_PROCESSING = 'InProcessing';

    public const STATUS_DECLINED = 'Declined';

    public static function pendingCancel(): array
    {
        return [
            'response' => 'dispatch_cancel_pending',
            'transactionStatus' => self::STATUS_PROCESSING,
        ];
    }

    public static function failed(string $response = '400'): array
    {
        return [
            'response' => $response,
            'transactionStatus' => self::STATUS_DECLINED,
        ];
    }
}

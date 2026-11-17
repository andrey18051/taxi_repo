<?php

namespace App\Support;

use App\Models\Orderweb;

/**
 * The app retries orderClientCostMyApi with the same WayForPay reference
 * when the first request times out. A second dispatcher order must not be opened.
 */
final class RepeatedCardInvoiceOrder
{
    public static function shouldRefuseNewDispatch(?string $invoice, bool $orderAlreadyExists): bool
    {
        $invoice = trim((string) $invoice);
        if ($invoice === '' || $invoice === '*' || $invoice === 'no') {
            return false;
        }

        return $orderAlreadyExists;
    }

    public static function orderAlreadyExists(?string $invoice): bool
    {
        $invoice = trim((string) $invoice);
        if ($invoice === '' || $invoice === '*' || $invoice === 'no') {
            return false;
        }

        return Orderweb::where('wfp_order_id', $invoice)->exists();
    }

    public static function refuseResponse()
    {
        return response([
            'order_cost' => '0',
            'Message' => 'ErrorCardPayment',
        ], 200)->header('Content-Type', 'json');
    }
}

<?php

namespace App\Services;

use App\Helpers\OrderHelper;
use DateTime;
use DateTimeZone;

/**
 * Тексты Telegram об отмене заказа с указанием инициатора.
 */
class OrderCancelNotificationHelper
{
    public const INITIATOR_CLIENT = 'client';

    public const INITIATOR_DRIVER = 'driver';

    public const INITIATOR_DISPATCHER = 'dispatcher';

    public const INITIATOR_SYSTEM = 'system';

    public static function resolveInitiator(?string $explicitInitiator, ?string $channelNote = null): string
    {
        if ($explicitInitiator !== null && $explicitInitiator !== '') {
            return $explicitInitiator;
        }

        $fromNote = self::resolveInitiatorFromChannelNote($channelNote);
        if ($fromNote !== null) {
            return $fromNote;
        }

        return self::INITIATOR_CLIENT;
    }

    public static function resolveInitiatorFromChannelNote(?string $channelNote): ?string
    {
        if ($channelNote === null || trim($channelNote) === '') {
            return null;
        }

        $note = trim($channelNote);
        $lower = mb_strtolower($note);

        if (str_contains($note, 'UidHistory')
            || str_contains($note, 'my_server_api')
            || str_contains($note, 'Vod my_server_api')) {
            return self::INITIATOR_SYSTEM;
        }

        if (str_contains($lower, 'вилка') || str_contains($lower, 'диспетч')) {
            return self::INITIATOR_DISPATCHER;
        }

        return null;
    }

    public static function initiatorLabel(string $initiator): string
    {
        switch ($initiator) {
            case self::INITIATOR_DRIVER:
                return 'водитель';
            case self::INITIATOR_DISPATCHER:
                return 'диспетчер';
            case self::INITIATOR_SYSTEM:
                return 'система';
            case self::INITIATOR_CLIENT:
            default:
                return 'клиент';
        }
    }

    public static function formatDriverDetail(?string $autoJson): ?string
    {
        if ($autoJson === null || trim($autoJson) === '') {
            return null;
        }

        $data = json_decode($autoJson, true);
        if (!is_array($data)) {
            return null;
        }

        $parts = [];
        if (!empty($data['name'])) {
            $parts[] = (string) $data['name'];
        }
        if (!empty($data['phoneNumber'])) {
            $parts[] = 'тел. ' . $data['phoneNumber'];
        }
        if (!empty($data['driverNumber'])) {
            $parts[] = 'позывной ' . $data['driverNumber'];
        }

        return $parts === [] ? null : implode(', ', $parts);
    }

    public static function pasLabelFromComment(?string $comment): string
    {
        switch ($comment) {
            case 'taxi_easy_ua_pas1':
                return 'ПАС_1';
            case 'taxi_easy_ua_pas2':
                return 'ПАС_2';
            case 'taxi_easy_ua_pas3':
                return 'ПАС_3';
            case 'taxi_easy_ua_pas4':
                return 'ПАС_4';
            case 'taxi_easy_ua_pas5':
                return 'ПАС_5';
            default:
                return '';
        }
    }

    public static function formatCancelTime($updatedAt): string
    {
        if ($updatedAt === null || $updatedAt === '') {
            return 'n/a';
        }

        $dateTime = new DateTime((string) $updatedAt);
        $dateTime->setTimezone(new DateTimeZone('Europe/Kiev'));

        return $dateTime->format('d.m.Y H:i:s');
    }

    public static function buildInitiatorLine(string $initiator, object $orderweb, ?string $channelNote = null): string
    {
        $label = self::initiatorLabel($initiator);

        if ($initiator === self::INITIATOR_DRIVER) {
            $driverDetail = self::formatDriverDetail($orderweb->auto ?? null);
            if ($driverDetail !== null) {
                return "Инициатор: {$label} ({$driverDetail}).";
            }

            return "Инициатор: {$label}.";
        }

        if ($initiator === self::INITIATOR_SYSTEM && $channelNote !== null && trim($channelNote) !== '') {
            return 'Инициатор: ' . $label . ' (' . trim($channelNote) . ').';
        }

        return "Инициатор: {$label}.";
    }

    public static function buildTelegramCancelMessage(
        object $orderweb,
        ?string $cancelInitiator = null,
        ?string $channelNote = null
    ): string {
        $initiator = self::resolveInitiator($cancelInitiator, $channelNote);
        $initiatorLine = self::buildInitiatorLine($initiator, $orderweb, $channelNote);

        $userFullName = (string) ($orderweb->user_full_name ?? '');
        $userPhone = (string) ($orderweb->user_phone ?? '');
        $email = (string) ($orderweb->email ?? '');
        $routeFrom = (string) ($orderweb->routefrom ?? '');
        $routeTo = (string) ($orderweb->routeto ?? '');
        $dispatchUid = (string) ($orderweb->dispatching_order_uid ?? '');
        $server = (string) ($orderweb->server ?? '');
        $pas = self::pasLabelFromComment($orderweb->comment ?? null);
        $updatedAt = self::formatCancelTime($orderweb->updated_at ?? null);
        $costForClient = OrderHelper::resolveDisplayCostGrivna($orderweb);

        if ($initiator === self::INITIATOR_CLIENT) {
            $body = "Клиент {$userFullName} (телефон {$userPhone}, email {$email}) отменил заказ";
        } else {
            $body = "Заказ клиента {$userFullName} (телефон {$userPhone}, email {$email}) отменён";
        }

        $message = "{$initiatorLine} {$body} по маршруту {$routeFrom} -> {$routeTo} стоимостью {$costForClient} грн."
            . " Номер заказа {$dispatchUid}. Сервер {$server}. Приложение {$pas}. Время отмены {$updatedAt}";

        return OrderPaymentNotificationHelper::appendWfpReferenceToCancelMessage(
            $message,
            $orderweb->wfp_order_id ?? null
        );
    }

    public static function buildProblemCancelTelegramMessage(
        object $orderweb,
        string $primaryUid,
        ?string $cancelInitiator = null
    ): string {
        $createdLocal = $orderweb->created_at
            ? self::formatCancelTime($orderweb->created_at)
            : 'n/a';
        $server = trim((string) ($orderweb->server ?? ''));
        $from = trim((string) ($orderweb->routefrom ?? ''));
        $initiator = self::initiatorLabel(self::resolveInitiator($cancelInitiator, null));

        return sprintf(
            '%s %s %s — проблема отмены, инициатор: %s',
            $createdLocal,
            $server,
            $from !== '' ? $from : $primaryUid,
            $initiator
        );
    }
}

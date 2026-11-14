<?php

namespace App\Mail;

/**
 * Текст закреплённого оповещения и разбор команды /mailpass.
 */
final class MailAlertText
{
    public static function serverLabel($appUrl): string
    {
        $url = (string) $appUrl;
        if (strpos($url, 't.easy-order-taxi.site') !== false) {
            return 'тест';
        }
        if (strpos($url, 'm.easy-order-taxi.site') !== false) {
            return 'боевой';
        }

        return 'этот';
    }

    public static function peerUrl($appUrl): string
    {
        $url = (string) $appUrl;
        if (strpos($url, 't.easy-order-taxi.site') !== false) {
            return 'https://m.easy-order-taxi.site';
        }
        if (strpos($url, 'm.easy-order-taxi.site') !== false) {
            return 'https://t.easy-order-taxi.site';
        }

        return '';
    }

    public static function pinned($appUrl): string
    {
        return "Почта не уходит: Gmail не принимает пароль приложения.\n"
            .'Сервер: '.self::serverLabel($appUrl).".\n\n"
            ."Напишите боту в личные сообщения, не в общий чат:\n"
            ."/mailpass пароль_приложения\n\n"
            .'Это 16 букв: Google → Безопасность → Пароли приложений.';
    }

    public static function isCommand($text): bool
    {
        return preg_match('#^/mailpass(?:@\S+)?(?:\s|$)#iu', trim((string) $text)) === 1;
    }

    public static function commandPassword($text): ?string
    {
        if (preg_match('#^/mailpass(?:@\S+)?\s+(.+)$#ius', trim((string) $text), $matches) !== 1) {
            return null;
        }

        return MailAppPassword::normalize($matches[1]);
    }
}

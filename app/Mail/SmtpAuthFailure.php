<?php

namespace App\Mail;

/**
 * Gmail закрыл SMTP-вход: пароль аккаунта или отозванный пароль приложения.
 */
final class SmtpAuthFailure
{
    public static function matches($message): bool
    {
        $text = strtolower((string) $message);
        $needles = [
            'failed to authenticate',
            'application-specific password required',
            'username and password not accepted',
            '534-5.7.9',
            '535-5.7.8',
        ];
        foreach ($needles as $needle) {
            if (strpos($text, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}

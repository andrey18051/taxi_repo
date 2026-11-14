<?php

namespace App\Mail;

/**
 * Пароль приложения Gmail: 16 латинских букв, пробелы в команде допустимы.
 */
final class MailAppPassword
{
    /**
     * @param mixed $raw
     */
    public static function normalize($raw): ?string
    {
        $compact = strtolower((string) preg_replace('/\s+/', '', trim((string) $raw)));
        if (preg_match('/^[a-z]{16}$/', $compact) !== 1) {
            return null;
        }

        return $compact;
    }
}

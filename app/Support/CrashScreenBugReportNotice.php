<?php

namespace App\Support;

/**
 * Экран «Упс... Щось пішло не так» создаёт тикет в Mantis,
 * а Telegram из отдельного процесса приложения не доходит.
 */
final class CrashScreenBugReportNotice
{
    /** @var list<string> */
    private const MARKERS = [
        'Щось пішло не так',
        'Что-то пошло не так',
        'Something went wrong',
    ];

    public static function isCrashScreen(string $summary, string $description): bool
    {
        $text = $summary . "\n" . $description;
        foreach (self::MARKERS as $marker) {
            if (mb_stripos($text, $marker) !== false) {
                return true;
            }
        }

        return false;
    }

    public static function messageFromBody(string $rawBody, int $issueId, string $viewUrl): ?string
    {
        $decoded = json_decode($rawBody, true);
        if (!is_array($decoded)) {
            return null;
        }

        $summary = trim((string) ($decoded['summary'] ?? ''));
        $description = trim((string) ($decoded['description'] ?? ''));
        if (!self::isCrashScreen($summary, $description)) {
            return null;
        }

        $problem = $summary !== '' ? $summary : self::firstLine($description);

        return "🐞 БАГ-РЕПОРТ\n"
            . "📝 Проблема: " . $problem . "\n\n"
            . "🔗 Mantis #" . $issueId . ": " . $viewUrl;
    }

    private static function firstLine(string $text): string
    {
        $line = strtok($text, "\r\n");

        return is_string($line) ? trim($line) : '';
    }
}

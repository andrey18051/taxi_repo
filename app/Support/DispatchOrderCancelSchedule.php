<?php

namespace App\Support;

/**
 * Единое расписание PUT /api/weborders/cancel от начала кампании (секунды).
 *
 * 1 — сразу (в HTTP-запросе клиента)
 * 2 — через 5 с после первой неудачи
 * 3 — на 30-й секунде
 * 4 — на 60-й секунде
 * 5+ — каждые 60 с (120, 180, …) пока заказ не в архиве на диспетчере
 *
 * Telegram «проблема отмены» — {@see PROBLEM_TELEGRAM_AFTER_SECONDS} (10 мин от старта кампании).
 */
class DispatchOrderCancelSchedule
{
    /** 10 минут без успешной отмены — уведомление в Telegram, кампания продолжается. */
    public const PROBLEM_TELEGRAM_AFTER_SECONDS = 600;

    public static function offsetSecondsForAttempt(int $attemptNumber): int
    {
        if ($attemptNumber <= 1) {
            return 0;
        }
        if ($attemptNumber === 2) {
            return 5;
        }
        if ($attemptNumber === 3) {
            return 30;
        }

        return 60 + ($attemptNumber - 4) * 60;
    }

    public static function delayUntilNextAttempt(int $campaignStartedAt, int $nextAttemptNumber, ?int $now = null): int
    {
        $now = $now ?? time();
        $target = $campaignStartedAt + self::offsetSecondsForAttempt($nextAttemptNumber);

        return max(0, $target - $now);
    }
}

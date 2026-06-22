<?php

namespace App\Support;

/**
 * Расписание повторных PUT /api/weborders/cancel от начала кампании (секунды).
 * 1: сразу, 2: +5с, 3: +30с, 4: +60с, далее каждые 60с.
 */
class DispatchOrderCancelSchedule
{
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

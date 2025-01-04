<?php

namespace App\Helpers;

use Carbon\Carbon;

class TimeHelper
{
    /**
     * Проверяет, осталось ли до смены часа 15 секунд.
     *
     * @return int
     */
    public static function isFifteenSecondsToNextHour(): bool
    {
        // Получаем текущее время
        $currentTime = Carbon::now();

        // Получаем количество оставшихся секунд до конца текущего часа
        $secondsToNextHour = 3600 - ($currentTime->minute * 60 + $currentTime->second);

        // Проверяем, осталось ли ровно 15 секунд

        return $secondsToNextHour;
    }
}


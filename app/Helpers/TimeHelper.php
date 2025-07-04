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
    public static function isFifteenSecondsToNextHour(): int
    {
        $currentTime = Carbon::now();
        $secondsToNextHour = 3600 - ($currentTime->minute * 60 + $currentTime->second);

        return $secondsToNextHour;
    }

}


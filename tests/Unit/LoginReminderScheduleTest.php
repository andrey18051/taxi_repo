<?php

namespace Tests\Unit;

use App\Http\Controllers\AndroidInstallationController;
use Carbon\Carbon;
use Tests\TestCase;

class LoginReminderScheduleTest extends TestCase
{
    public function test_tomorrow_seven_kyiv_is_converted_to_utc_correctly(): void
    {
        // On some Windows/PHP setups timezone ID may be "Europe/Kiev"
        $tz = in_array('Europe/Kyiv', \DateTimeZone::listIdentifiers(), true)
            ? 'Europe/Kyiv'
            : 'Europe/Kiev';

        Carbon::setTestNow(Carbon::create(2026, 7, 2, 12, 0, 0, $tz));

        $dueUtc = AndroidInstallationController::computeReminderDueAtUtc();

        // Tomorrow 07:00 Kyiv should be tomorrow 04:00 UTC during summer time (UTC+3)
        $this->assertSame('UTC', $dueUtc->getTimezone()->getName());
        $this->assertSame('2026-07-03 04:00:00', $dueUtc->format('Y-m-d H:i:s'));
    }
}


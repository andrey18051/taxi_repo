<?php

namespace Tests\Unit;

use App\Helpers\VisicomKeyCheckHelper;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class VisicomKeyCheckHelperTest extends TestCase
{
    public function test_remaining_days_from_issue_date(): void
    {
        $now = Carbon::parse('2026-08-14 12:00:00', 'Europe/Kiev');

        $this->assertSame(29, VisicomKeyCheckHelper::remainingDays('2026-09-12', $now));
    }

    public function test_remaining_days_expires_today(): void
    {
        $now = Carbon::parse('2026-09-12 23:00:00', 'Europe/Kiev');

        $this->assertSame(0, VisicomKeyCheckHelper::remainingDays('2026-09-12', $now));
    }

    public function test_remaining_days_already_expired(): void
    {
        $now = Carbon::parse('2026-09-15 10:00:00', 'Europe/Kiev');

        $this->assertSame(-3, VisicomKeyCheckHelper::remainingDays('2026-09-12', $now));
    }

    public function test_empty_expires_uses_default(): void
    {
        $now = Carbon::parse('2026-08-14 12:00:00', 'Europe/Kiev');

        $this->assertSame(29, VisicomKeyCheckHelper::remainingDays('', $now));
        $this->assertSame(29, VisicomKeyCheckHelper::remainingDays(null, $now));
    }

    public function test_format_remaining_text_plural(): void
    {
        $now = Carbon::parse('2026-08-14 12:00:00', 'Europe/Kiev');

        $this->assertSame(
            'осталось 29 дней (до 12.09.2026)',
            VisicomKeyCheckHelper::formatRemainingText('2026-09-12', $now)
        );
    }

    public function test_format_remaining_one_day(): void
    {
        $now = Carbon::parse('2026-09-11 12:00:00', 'Europe/Kiev');

        $this->assertSame(
            'остался 1 день (до 12.09.2026)',
            VisicomKeyCheckHelper::formatRemainingText('2026-09-12', $now)
        );
    }

    public function test_days_word(): void
    {
        $this->assertSame('день', VisicomKeyCheckHelper::daysWord(1));
        $this->assertSame('дня', VisicomKeyCheckHelper::daysWord(2));
        $this->assertSame('дня', VisicomKeyCheckHelper::daysWord(22));
        $this->assertSame('дней', VisicomKeyCheckHelper::daysWord(5));
        $this->assertSame('дней', VisicomKeyCheckHelper::daysWord(11));
        $this->assertSame('дней', VisicomKeyCheckHelper::daysWord(29));
    }

    public function test_telegram_message_success(): void
    {
        $message = VisicomKeyCheckHelper::buildTelegramMessage(
            true,
            200,
            'TaxiEasy test, t.easy-order-taxi.site',
            'осталось 29 дней (до 12.09.2026)'
        );

        $this->assertSame(
            'Проверка ключа Visicom (TaxiEasy test, t.easy-order-taxi.site): успешна. осталось 29 дней (до 12.09.2026).',
            $message
        );
    }

    public function test_telegram_message_http_403(): void
    {
        $message = VisicomKeyCheckHelper::buildTelegramMessage(
            false,
            403,
            'work',
            'осталось 29 дней (до 12.09.2026)'
        );

        $this->assertStringContainsString('ошибка HTTP 403', $message);
        $this->assertStringContainsString('осталось 29 дней', $message);
    }

    public function test_environment_label(): void
    {
        $this->assertSame(
            'TaxiEasy, m.easy-order-taxi.site',
            VisicomKeyCheckHelper::environmentLabel('TaxiEasy', 'https://m.easy-order-taxi.site')
        );
    }
}

<?php

namespace Tests\Unit;

use App\Support\KievDateTimeFormatter;
use PHPUnit\Framework\TestCase;

class KievDateTimeFormatterTest extends TestCase
{
    public function test_formatOrderCreatedAt_convertsUtcToKyiv(): void
    {
        // 08:28 UTC → 11:28 EEST (UTC+3, июнь)
        $this->assertSame(
            '13.06.2026 11:28:19',
            KievDateTimeFormatter::formatOrderCreatedAt('2026-06-13 08:28:19')
        );
    }

    public function test_formatOrderCreatedAt_preservesPlaceholder(): void
    {
        $this->assertSame('*', KievDateTimeFormatter::formatOrderCreatedAt('*'));
        $this->assertSame('', KievDateTimeFormatter::formatOrderCreatedAt(''));
    }

    public function test_formatRequiredTime_formatsLocalPickupTime(): void
    {
        $this->assertSame(
            '07.07.2026 21:47',
            KievDateTimeFormatter::formatRequiredTime('2026-07-07T21:47:00')
        );
    }
}

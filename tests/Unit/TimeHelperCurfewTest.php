<?php

namespace Tests\Unit;

use App\Helpers\TimeHelper;
use Carbon\Carbon;
use Tests\TestCase;

class TimeHelperCurfewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.start_time' => '01:00',
            'app.end_time' => '05:00',
            'services.city_app_order.curfew_boundary_minutes' => 30,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_curfew_inactive_before_start(): void
    {
        $at = Carbon::parse('2026-09-13 00:30:00', TimeHelper::KYIV_TIMEZONE);

        $this->assertFalse(TimeHelper::isCurfewActive($at));
    }

    public function test_curfew_active_from_one_to_five(): void
    {
        $this->assertTrue(TimeHelper::isCurfewActive(
            Carbon::parse('2026-09-13 01:00:00', TimeHelper::KYIV_TIMEZONE)
        ));
        $this->assertTrue(TimeHelper::isCurfewActive(
            Carbon::parse('2026-09-13 03:15:00', TimeHelper::KYIV_TIMEZONE)
        ));
        $this->assertTrue(TimeHelper::isCurfewActive(
            Carbon::parse('2026-09-13 05:00:00', TimeHelper::KYIV_TIMEZONE)
        ));
    }

    public function test_curfew_inactive_after_end(): void
    {
        $at = Carbon::parse('2026-09-13 05:01:00', TimeHelper::KYIV_TIMEZONE);

        $this->assertFalse(TimeHelper::isCurfewActive($at));
    }

    public function test_get_curfew_status_uses_config_window(): void
    {
        $status = TimeHelper::getCurfewStatus(
            Carbon::parse('2026-09-13 00:45:00', TimeHelper::KYIV_TIMEZONE)
        );

        $this->assertSame('01:00', $status['start_time']);
        $this->assertSame('05:00', $status['end_time']);
        $this->assertFalse($status['curfew_active']);
    }

    public function test_near_boundary_around_new_start(): void
    {
        $this->assertTrue(TimeHelper::isNearCurfewBoundary(
            Carbon::parse('2026-09-13 00:45:00', TimeHelper::KYIV_TIMEZONE)
        ));
        $this->assertTrue(TimeHelper::isNearCurfewBoundary(
            Carbon::parse('2026-09-13 01:20:00', TimeHelper::KYIV_TIMEZONE)
        ));
        $this->assertFalse(TimeHelper::isNearCurfewBoundary(
            Carbon::parse('2026-09-13 00:20:00', TimeHelper::KYIV_TIMEZONE)
        ));
    }
}

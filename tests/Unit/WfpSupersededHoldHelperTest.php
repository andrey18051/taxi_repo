<?php

namespace Tests\Unit;

use App\Helpers\WfpSupersededHoldHelper;
use PHPUnit\Framework\TestCase;

class WfpSupersededHoldHelperTest extends TestCase
{
    public function test_detects_superseded_main_hold(): void
    {
        $this->assertTrue(WfpSupersededHoldHelper::isSupersededMainHold(
            'V_20260625181525107_Z8X9',
            'V_20260625181836438_KTEP',
            'WaitingAuthComplete'
        ));
    }

    public function test_current_main_hold_is_not_superseded(): void
    {
        $this->assertFalse(WfpSupersededHoldHelper::isSupersededMainHold(
            'V_20260625181836438_KTEP',
            'V_20260625181836438_KTEP',
            'WaitingAuthComplete'
        ));
    }

    public function test_non_hold_status_is_not_superseded(): void
    {
        $this->assertFalse(WfpSupersededHoldHelper::isSupersededMainHold(
            'V_OLD',
            'V_NEW',
            'Voided'
        ));
    }

    public function test_missing_main_reference_is_not_superseded(): void
    {
        $this->assertFalse(WfpSupersededHoldHelper::isSupersededMainHold(
            'V_OLD',
            null,
            'WaitingAuthComplete'
        ));
    }
}

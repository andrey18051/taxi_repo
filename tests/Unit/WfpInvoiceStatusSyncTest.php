<?php

namespace Tests\Unit;

use App\Http\Controllers\WfpController;
use ReflectionMethod;
use Tests\TestCase;

class WfpInvoiceStatusSyncTest extends TestCase
{
    private function shouldUpdate(?string $current, ?string $new): bool
    {
        $method = new ReflectionMethod(WfpController::class, 'shouldUpdateWfpInvoiceStatus');
        $method->setAccessible(true);

        return $method->invoke(new WfpController(), $current, $new);
    }

    public function test_allows_upgrade_from_hold_to_voided(): void
    {
        $this->assertTrue($this->shouldUpdate('WaitingAuthComplete', 'Voided'));
    }

    public function test_blocks_downgrade_from_voided_to_hold(): void
    {
        $this->assertFalse($this->shouldUpdate('Voided', 'WaitingAuthComplete'));
    }

    public function test_allows_voided_to_voided_refresh(): void
    {
        $this->assertTrue($this->shouldUpdate('Voided', 'Voided'));
    }

    public function test_allows_refunded_after_voided(): void
    {
        $this->assertTrue($this->shouldUpdate('Voided', 'Refunded'));
    }

    public function test_rejects_empty_new_status(): void
    {
        $this->assertFalse($this->shouldUpdate('WaitingAuthComplete', ''));
        $this->assertFalse($this->shouldUpdate('WaitingAuthComplete', null));
    }
}

<?php

namespace Tests\Unit;

use App\Models\Uid_history;
use App\Support\ForkOrderCancelLegResolver;
use Tests\TestCase;

class ForkOrderCancelLegResolverTest extends TestCase
{
    public function test_uses_uid_history_bonus_and_double_uids(): void
    {
        $history = new Uid_history();
        $history->uid_bonusOrder = 'bonus-uid';
        $history->uid_doubleOrder = 'double-uid';

        $legs = ForkOrderCancelLegResolver::resolve('any-a', 'any-b', $history);

        $this->assertCount(2, $legs);
        $this->assertSame('bonus-uid', $legs[0]['uid']);
        $this->assertSame('bonus', $legs[0]['auth_role']);
        $this->assertSame('double-uid', $legs[1]['uid']);
        $this->assertSame('double', $legs[1]['auth_role']);
    }

    public function test_fallback_maps_first_param_to_bonus_second_to_double(): void
    {
        $legs = ForkOrderCancelLegResolver::resolve('first-uid', 'second-uid', null);

        $this->assertSame('first-uid', $legs[0]['uid']);
        $this->assertSame('bonus', $legs[0]['auth_role']);
        $this->assertSame('second-uid', $legs[1]['uid']);
        $this->assertSame('double', $legs[1]['auth_role']);
    }
}

<?php

namespace Tests\Unit;

use App\Models\Orderweb;
use App\Models\Uid_history;
use App\Support\ForkOrderCancelLegResolver;
use Tests\TestCase;

class ForkOrderCancelLegResolverTest extends TestCase
{
    public function test_nal_order_uses_dispatching_order_uid_only(): void
    {
        $orderweb = new Orderweb();
        $orderweb->dispatching_order_uid = 'a1950002a8e44e5bbb19476ac5a47ee0';

        $history = new Uid_history();
        $history->uid_bonusOrder = 'afcc3e2ff9824428b0954139755b092c';
        $history->uid_doubleOrder = 'd19a0285529b4084a243315c6bf488d2';

        $legs = ForkOrderCancelLegResolver::resolveDispatchCancelLegs(
            $orderweb,
            $history,
            ['authorization' => 'token-nal']
        );

        $this->assertCount(1, $legs);
        $this->assertSame('a1950002a8e44e5bbb19476ac5a47ee0', $legs[0]['uid']);
        $this->assertSame('default', $legs[0]['auth_role']);
    }

    public function test_card_fork_includes_bonus_and_double_when_auth_present(): void
    {
        $orderweb = new Orderweb();
        $orderweb->dispatching_order_uid = 'hold-uid';

        $history = new Uid_history();
        $history->uid_bonusOrder = 'bonus-uid';
        $history->uid_doubleOrder = 'double-uid';

        $legs = ForkOrderCancelLegResolver::resolveDispatchCancelLegs(
            $orderweb,
            $history,
            [
                'authorization' => 'token-default',
                'authorizationBonus' => 'token-bonus',
                'authorizationDouble' => 'token-double',
            ]
        );

        $this->assertCount(3, $legs);
        $this->assertSame('hold-uid', $legs[0]['uid']);
        $this->assertSame('bonus-uid', $legs[1]['uid']);
        $this->assertSame('double-uid', $legs[2]['uid']);
    }
}

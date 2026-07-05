<?php

namespace Tests\Unit;

use App\Helpers\WfpClientNameHelper;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class WfpClientNameHelperTest extends TestCase
{
    public function test_build_name_fields_uses_phone_as_first_name(): void
    {
        $fields = WfpClientNameHelper::buildNameFields('380501112233');

        $this->assertSame('380501112233', $fields['clientFirstName']);
        $this->assertSame(' ', $fields['clientLastName']);
    }

    public function test_pick_display_phone_prefers_user_phone_from_database(): void
    {
        $user = new User();
        $user->user_phone = '380501112233';

        $phone = WfpClientNameHelper::pickDisplayPhone($user, '380999999999');

        $this->assertSame('380501112233', $phone);
    }

    public function test_pick_display_phone_falls_back_to_request_phone(): void
    {
        $phone = WfpClientNameHelper::pickDisplayPhone(null, '380671234567');

        $this->assertSame('380671234567', $phone);
    }

    public function test_pick_display_phone_unknown_when_missing(): void
    {
        $phone = WfpClientNameHelper::pickDisplayPhone(null, '');

        $this->assertSame('Unknown', $phone);
    }
}

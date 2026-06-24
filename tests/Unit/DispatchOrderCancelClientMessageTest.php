<?php

namespace Tests\Unit;

use App\Http\Controllers\AndroidTestOSMController;
use App\Services\DispatchOrderCancelService;
use Tests\TestCase;

class DispatchOrderCancelClientMessageTest extends TestCase
{
    public function test_confirmed_message_contains_scasovane_for_app_parser(): void
    {
        $message = (new AndroidTestOSMController())->buildClientCancelConfirmedMessage(2);

        $this->assertStringContainsString('надіслано', mb_strtolower($message));
        $this->assertStringContainsString('скасоване', mb_strtolower($message));
        $this->assertStringNotContainsString('очікуємо', mb_strtolower($message));
    }

    public function test_pending_message_matches_service_constant(): void
    {
        $pending = DispatchOrderCancelService::CLIENT_MESSAGE_PENDING;

        $this->assertStringContainsString('надіслано', mb_strtolower($pending));
        $this->assertStringContainsString('очікуємо', mb_strtolower($pending));
        $this->assertStringNotContainsString('скасоване', mb_strtolower($pending));
    }

    public function test_problem_telegram_threshold_is_ten_minutes(): void
    {
        $this->assertSame(600, \App\Support\DispatchOrderCancelSchedule::PROBLEM_TELEGRAM_AFTER_SECONDS);
    }
}

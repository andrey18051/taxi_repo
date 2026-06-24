<?php

namespace Tests\Unit;

use App\Services\DispatchOrderCancelService;
use App\Support\DispatchOrderCancelSchedule;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DispatchOrderCancelProcessDueTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_process_due_campaigns_skips_when_next_attempt_not_due(): void
    {
        $uid = 'due-test-not-yet';
        $this->seedCampaign($uid, time(), 4);

        $service = new DispatchOrderCancelService();

        $this->assertSame(0, $service->processDueCampaigns());
        $this->assertTrue(Cache::has(DispatchOrderCancelService::CACHE_PREFIX . $uid));
    }

    public function test_process_due_campaigns_runs_when_past_schedule_and_cleans_missing_order(): void
    {
        $uid = 'due-test-past';
        $startedAt = time() - DispatchOrderCancelSchedule::offsetSecondsForAttempt(5) - 5;
        $this->seedCampaign($uid, $startedAt, 4);

        $service = new DispatchOrderCancelService();

        $this->assertSame(1, $service->processDueCampaigns());
        $this->assertFalse(Cache::has(DispatchOrderCancelService::CACHE_PREFIX . $uid));
        $this->assertSame([], Cache::get(DispatchOrderCancelService::CACHE_INDEX_KEY, []));
    }

    private function seedCampaign(string $uid, int $startedAt, int $attemptNumber): void
    {
        Cache::put(DispatchOrderCancelService::CACHE_PREFIX . $uid, [
            'primary_uid' => $uid,
            'city' => 'Kyiv City',
            'application' => 'PAS4',
            'payment_type' => 'nal_payment',
            'legs' => [['uid' => $uid, 'auth_role' => 'default']],
            'started_at' => $startedAt,
            'attempt_number' => $attemptNumber,
            'problem_telegram_sent' => false,
        ], now()->addHour());
        Cache::put(DispatchOrderCancelService::CACHE_INDEX_KEY, [$uid], now()->addHour());
    }
}

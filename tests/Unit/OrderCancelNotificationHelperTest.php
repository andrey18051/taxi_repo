<?php

namespace Tests\Unit;

use App\Services\OrderCancelNotificationHelper;
use Tests\TestCase;

class OrderCancelNotificationHelperTest extends TestCase
{
    private function sampleOrderweb(array $overrides = []): object
    {
        $orderweb = (object) [
            'user_full_name' => 'Іван Петренко',
            'user_phone' => '+380501234567',
            'email' => 'client@example.com',
            'routefrom' => 'вул. Сумська, 1',
            'routeto' => 'вул. Pushkinska, 10',
            'dispatching_order_uid' => 'UID123456',
            'server' => 'vod_api',
            'comment' => 'taxi_easy_ua_pas4',
            'web_cost' => 150,
            'updated_at' => '2026-07-02 12:00:00',
            'created_at' => '2026-07-02 11:55:00',
        ];

        foreach ($overrides as $key => $value) {
            $orderweb->{$key} = $value;
        }

        return $orderweb;
    }

    public function test_client_cancel_message_contains_initiator_and_client_wording(): void
    {
        $message = OrderCancelNotificationHelper::buildTelegramCancelMessage(
            $this->sampleOrderweb(),
            OrderCancelNotificationHelper::INITIATOR_CLIENT
        );

        $this->assertStringContainsString('Инициатор: клиент.', $message);
        $this->assertStringContainsString('отменил заказ', $message);
        $this->assertStringContainsString('Іван Петренко', $message);
        $this->assertStringContainsString('UID123456', $message);
    }

    public function test_driver_cancel_message_contains_driver_details(): void
    {
        $message = OrderCancelNotificationHelper::buildTelegramCancelMessage(
            $this->sampleOrderweb([
                'auto' => json_encode([
                    'name' => 'Сергій',
                    'phoneNumber' => '+380671112233',
                    'driverNumber' => '777',
                ], JSON_UNESCAPED_UNICODE),
            ]),
            OrderCancelNotificationHelper::INITIATOR_DRIVER
        );

        $this->assertStringContainsString('Инициатор: водитель (Сергій, тел. +380671112233, позывной 777).', $message);
        $this->assertStringContainsString('отменён', $message);
        $this->assertStringNotContainsString('отменил заказ', $message);
    }

    public function test_dispatcher_initiator_resolved_from_channel_note(): void
    {
        $initiator = OrderCancelNotificationHelper::resolveInitiator(
            null,
            'Вилка скасована (підтверджено на диспетчері)'
        );

        $this->assertSame(OrderCancelNotificationHelper::INITIATOR_DISPATCHER, $initiator);

        $message = OrderCancelNotificationHelper::buildTelegramCancelMessage(
            $this->sampleOrderweb(),
            null,
            'Вилка скасована (підтверджено на диспетчері)'
        );

        $this->assertStringContainsString('Инициатор: диспетчер.', $message);
    }

    public function test_system_initiator_includes_channel_note(): void
    {
        $message = OrderCancelNotificationHelper::buildTelegramCancelMessage(
            $this->sampleOrderweb(),
            OrderCancelNotificationHelper::INITIATOR_SYSTEM,
            'Отмена UidHistory'
        );

        $this->assertStringContainsString('Инициатор: система (Отмена UidHistory).', $message);
    }

    public function test_problem_cancel_message_contains_initiator(): void
    {
        $message = OrderCancelNotificationHelper::buildProblemCancelTelegramMessage(
            $this->sampleOrderweb(),
            'UID123456',
            OrderCancelNotificationHelper::INITIATOR_CLIENT
        );

        $this->assertStringContainsString('проблема отмены, инициатор: клиент', $message);
    }
}

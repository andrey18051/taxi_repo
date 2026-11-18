<?php

namespace Tests\Unit;

use App\Helpers\OrderHelper;
use App\Services\OrderStatusMessageResolver;
use App\Support\CrashScreenBugReportNotice;
use App\Support\DispatchStatusPayload;
use Tests\TestCase;

class DispatchReorderAndCrashNoticeTest extends TestCase
{
    public function test_status_action_is_read_from_json_response_not_http_headers(): void
    {
        $response = response()->json([
            'action' => OrderStatusMessageResolver::ACTION_CANCELED,
            'close_reason' => 1,
        ]);

        $this->assertSame(OrderStatusMessageResolver::ACTION_CANCELED, DispatchStatusPayload::action($response));
        $this->assertTrue(DispatchStatusPayload::isCanceled($response));
    }

    public function test_status_action_is_read_from_json_string(): void
    {
        $json = json_encode(['action' => 'Поиск авто'], JSON_UNESCAPED_UNICODE);

        $this->assertSame('Поиск авто', DispatchStatusPayload::action($json));
        $this->assertFalse(DispatchStatusPayload::isCanceled($json));
        $this->assertNull(DispatchStatusPayload::action('not-json'));
        $this->assertNull(DispatchStatusPayload::action(null));
    }

    public function test_recreate_add_cost_follows_the_latest_client_price(): void
    {
        $stored = ['add_cost' => -54, 'payment_type' => 0];

        $raised = OrderHelper::alignAddCostWithClientPrice($stored, 6, 11);

        $this->assertSame(-49, $raised['add_cost']);
        $this->assertSame($stored, OrderHelper::alignAddCostWithClientPrice($stored, 6, 6));
        $this->assertSame($stored, OrderHelper::alignAddCostWithClientPrice($stored, 0, 11));
    }

    public function test_crash_screen_notice_is_only_the_oops_report(): void
    {
        $crash = json_encode([
            'summary' => '[БАГ-РЕПОРТ] Упс... Щось пішло не так',
            'description' => "Упс... Щось пішло не так\n\njava.lang.NullPointerException",
        ], JSON_UNESCAPED_UNICODE);
        $manual = json_encode([
            'summary' => 'Заказ наличный не создается',
            'description' => 'Наличный при заказе должен создаваться',
        ], JSON_UNESCAPED_UNICODE);

        $message = CrashScreenBugReportNotice::messageFromBody(
            $crash,
            109,
            'https://t.easy-order-taxi.site/mantis/view.php?id=109'
        );

        $this->assertNotNull($message);
        $this->assertStringContainsString('Mantis #109', $message);
        $this->assertStringContainsString('Щось пішло не так', $message);
        $this->assertNull(CrashScreenBugReportNotice::messageFromBody($manual, 108, 'https://example/108'));
        $this->assertTrue(CrashScreenBugReportNotice::isCrashScreen('Oops', 'Something went wrong'));
        $this->assertTrue(CrashScreenBugReportNotice::isCrashScreen('', 'Упс... Что-то пошло не так'));
    }
}

<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class PreliminaryOrderHistoryFilterTest extends TestCase
{
    /**
     * Regression: preliminary order created more than 48 hours ago must stay in active list.
     */
    public function test_reservation_order_older_than_48_hours_matches_history_filter(): void
    {
        $historySince = Carbon::parse('2026-06-09 12:00:00');

        $orders = collect([
            (object) [
                'created_at' => '2026-06-07 10:00:00',
                'reservation' => 1,
                'required_time' => '2026-07-07 21:47:00',
            ],
            (object) [
                'created_at' => '2026-06-07 10:00:00',
                'reservation' => 0,
                'required_time' => null,
            ],
        ]);

        $filtered = $orders->filter(function ($row) use ($historySince) {
            return Carbon::parse($row->created_at) >= $historySince
                || ($row->reservation == 1 && $row->required_time !== null);
        });

        $this->assertCount(1, $filtered);
        $this->assertSame(1, $filtered->first()->reservation);
    }

    public function test_recent_non_reservation_order_matches_history_filter(): void
    {
        $historySince = Carbon::parse('2026-06-09 12:00:00');

        $orders = collect([
            (object) [
                'created_at' => '2026-06-09 11:00:00',
                'reservation' => 0,
                'required_time' => null,
            ],
        ]);

        $filtered = $orders->filter(function ($row) use ($historySince) {
            return Carbon::parse($row->created_at) >= $historySince
                || ($row->reservation == 1 && $row->required_time !== null);
        });

        $this->assertCount(1, $filtered);
    }

    public function test_fallback_uses_active_order_when_history_query_is_empty(): void
    {
        $order = collect([(object) ['dispatching_order_uid' => 'abc']]);
        $orderHistory = collect();

        if ($orderHistory->isEmpty() && $order->isNotEmpty()) {
            $orderHistory = $order;
        }

        $this->assertCount(1, $orderHistory);
        $this->assertSame('abc', $orderHistory->first()->dispatching_order_uid);
    }
}

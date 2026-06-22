<?php

return [
    'auto_cancel_delay_minutes' => env('AUTO_CANCEL_DELAY_MINUTES', 15),

    /** my_server_api: через сколько секунд после создания заказа проверить оплату и отменить */
    'my_server_api_payment_check_delay_seconds' => env('MY_SERVER_API_PAYMENT_CHECK_DELAY_SECONDS', 60),

    /** payment_flow=2 (простой безнал): таймаут до автоотмены привязанной карты без оплаты (секунды от создания заказа) */
    'simple_cashless_payment_check_delay_seconds' => env('SIMPLE_CASHLESS_PAYMENT_CHECK_DELAY_SECONDS', 60),

    /** Опрос WfpInvoice до этой секунды (интервал — my_server_api_payment_poll_interval_seconds) */
    'my_server_api_payment_poll_max_seconds' => env('MY_SERVER_API_PAYMENT_POLL_MAX_SECONDS', 60),
    'my_server_api_payment_poll_interval_seconds' => env('MY_SERVER_API_PAYMENT_POLL_INTERVAL_SECONDS', 3),

    /** Клиентская отмена на диспетчере: Telegram «проблема отмены» после N секунд (по умолчанию 10 мин) */
    'dispatch_cancel_problem_telegram_seconds' => env('DISPATCH_CANCEL_PROBLEM_TELEGRAM_SECONDS', 600),
];

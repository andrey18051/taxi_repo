<?php

return [
    // Настройки повторов запросов
    'max_retries' => 3,

    // Настройки кэширования
    'geocoding_cache' => [
        'enabled' => true,
        'ttl' => 86400, // 24 часа в секундах
        'prefix' => 'geo_coords_',
    ],

    // Настройки логирования
    'logging' => [
        'enabled' => true,
        'level' => env('TAXI_LOG_LEVEL', 'debug'),
    ],
];

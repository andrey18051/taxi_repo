<?php

use Illuminate\Support\Str;

return [
    /*
    |--------------------------------------------------------------------------
    | Horizon Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Horizon will be accessible from. If this
    | setting is null, Horizon will reside under the same domain as the
    | application. Otherwise, this value will serve as the subdomain.
    |
    */
    'domain' => null,

    /*
    |--------------------------------------------------------------------------
    | Horizon Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Horizon will be accessible from. Feel free
    | to change this path to anything you like. Note that the URI will not
    | affect the paths of its internal API that aren't exposed to users.
    |
    */
    'path' => 'horizon',

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Connection
    |--------------------------------------------------------------------------
    |
    | This is the name of the Redis connection where Horizon will store the
    | meta information required for it to function. It includes the list
    | of supervisors, failed jobs, job metrics, and other information.
    |
    */
    'use' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Prefix
    |--------------------------------------------------------------------------
    |
    | This prefix will be used when storing all Horizon data in Redis. You
    | may modify the prefix when you are running multiple installations
    | of Horizon on the same server so that they don't have problems.
    |
    */
    'prefix' => env('HORIZON_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_horizon:'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will get attached onto each Horizon route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply stick with this list.
    |
    */
    'middleware' => ['web', 'auth'], // Добавлен auth для безопасности

    /*
    |--------------------------------------------------------------------------
    | Queue Wait Time Thresholds
    |--------------------------------------------------------------------------
    |
    | This option allows you to configure when the LongWaitDetected event
    | will be fired. Every connection / queue combination may have its
    | own, unique threshold (in seconds) before this event is fired.
    |
    */
    'waits' => [
        'redis:high' => 60,   // Очередь high
        'redis:medium' => 60, // Очередь medium
        'redis:low' => 60,    // Очередь low
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Trimming Times
    |--------------------------------------------------------------------------
    |
    | Here you can configure for how long (in minutes) you desire Horizon to
    | persist the recent and failed jobs. Typically, recent jobs are kept
    | for one hour while all failed jobs are stored for an entire week.
    |
    */
    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics
    |--------------------------------------------------------------------------
    |
    | Here you can configure how many snapshots should be kept to display in
    | the metrics graph. This will get used in combination with Horizon's
    | `horizon:snapshot` schedule to define how long to retain metrics.
    |
    */
    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    /*
    |----------------------------------------------------------------
    | Fast Termination
    |----------------------------------------------------------------
    |
    | When this option is enabled, Horizon's "terminate" command will not
    | wait on all of the workers to terminate unless the --wait option
    | is provided. Fast termination can shorten deployment delay by
    | allowing a new instance of Horizon to start while the last
    | instance will continue to terminate each of its workers.
    |
    */
    'fast_termination' => true, // Включено для быстрого развертывания

    /*
    |----------------------------------------------------------------
    | Memory Limit (MB)
    |----------------------------------------------------------------
    |
    | This value describes the maximum amount of memory the Horizon worker
    | may consume before it is terminated and restarted. You should set
    | this value according to the resources available to your server.
    |
    */
    'memory_limit' => 256, // Увеличено до 256 МБ для тяжелых задач

    /*
    |----------------------------------------------------------------
    | Queue Worker Configuration
    |----------------------------------------------------------------
    |
    | Here you may define the queue worker settings used by your application
    | in all environments. These supervisors and settings handle all your
    | queued jobs and will be provisioned by Horizon during deployment.
    |
    */
    'defaults' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['laravel_database_queues:high', 'laravel_database_queues:medium', 'laravel_database_queues:low'],
            'balance' => 'auto',
            'maxProcesses' => 20,
            'minProcesses' => 1,
            'tries' => 1,       // 1 повтор
            'nice' => 0,
            'timeout' => 259200, // 3 суток
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-high' => [
                'connection' => 'redis',
                'queue' => ['laravel_database_queues:high'],
                'balance' => 'simple',
                'maxProcesses' => 12, // Увеличено для приоритетной очереди
                'minProcesses' => 2,
                'tries' => 1,       // без повторов
                'timeout' => 259200 // 3 дня в секундах
            ],
            'supervisor-medium' => [
                'connection' => 'redis',
                'queue' => ['laravel_database_queues:medium'],
                'balance' => 'simple',
                'maxProcesses' => 6, // Увеличено для средней очереди
                'minProcesses' => 1,
                'tries' => 1,       // без повторов
                'timeout' => 1800,  // 30 минут
            ],
            'supervisor-low' => [
                'connection' => 'redis',
                'queue' => ['laravel_database_queues:low'],
                'balance' => 'simple',
                'maxProcesses' => 2, // Оставлено без изменений для низкоприоритетной очереди
                'minProcesses' => 1,
                'tries' => 1,       // без повторов
                'timeout' => 30,  // 1 минут
            ],
        ],
        'local' => [
            'supervisor-high' => [
                'connection' => 'redis',
                'queue' => ['laravel_database_queues:high'],
                'balance' => 'simple',
                'maxProcesses' => 12, // Увеличено для приоритетной очереди
                'minProcesses' => 2,
                'tries' => 1,       // без повторов
                'timeout' => 259200 // 3 дня в секундах
            ],
            'supervisor-medium' => [
                'connection' => 'redis',
                'queue' => ['laravel_database_queues:medium'],
                'balance' => 'simple',
                'maxProcesses' => 6, // Увеличено для средней очереди
                'minProcesses' => 1,
                'tries' => 1,       // без повторов
                'timeout' => 1800,  // 30 минут
            ],
            'supervisor-low' => [
                'connection' => 'redis',
                'queue' => ['laravel_database_queues:low'],
                'balance' => 'simple',
                'maxProcesses' => 2, // Оставлено без изменений для низкоприоритетной очереди
                'minProcesses' => 1,
                'tries' => 1,       // без повторов
                'timeout' => 30,  // 1 минут
            ],
        ],
    ],

    /*
    |----------------------------------------------------------------
    | Notifications
    |----------------------------------------------------------------
    |
    | Horizon can send notifications to a Slack channel when something
    | goes wrong with your queues, such as long wait times or failed
    | jobs. Make sure to configure the webhook URL in your .env file.
    |
    */
    'notifications' => [
        'enabled' => true,
        'slack' => [
            'webhook_url' => env('HORIZON_SLACK_WEBHOOK_URL'),
            'channel' => '#horizon-alerts',
        ],
    ],
];

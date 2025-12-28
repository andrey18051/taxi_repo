<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    'timeSleepForStatusUpdate' => env('APP_Time_Sleep_For_Status_Update', 5),


    'X-WO-API-APP-ID-SITE' => 'taxi_easy_ua_site',
    'X-WO-API-APP-ID-PAS1' => 'taxi_easy_ua_pas1',
    'X-WO-API-APP-ID-PAS2' => 'taxi_easy_ua_pas2',
    'X-WO-API-APP-ID-PAS3' => 'taxi_easy_ua_pas3',
    'X-WO-API-APP-ID-PAS4' => 'taxi_easy_ua_pas4',
    'X-WO-API-APP-ID-PAS5' => 'taxi_easy_ua_pas5',

    'X-WO-API-APP-ID-TEST' => 'taxi_easy_ua_TEST',

    'version-PAS1' => '1.734',
    'version-PAS2' => '2.1042',
    'version-PAS3' => '3.001',
    'version-PAS4' => '4.065',
    'version-PAS5' => '5.146',

    'name-PAS1' => 'Таксі Доставка: легкий заказ',
    'name-PAS2' => 'Попутчик таксі Україна дешево',
    'name-PAS4' => 'Таксі Дюк Попутник',
    'name-PAS5' => 'Чат Такси AI',

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL', null),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => 'uk',

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Faker Locale
    |--------------------------------------------------------------------------
    |
    | This locale will be used by the Faker PHP library when generating fake
    | data for your database seeds. For example, this will be used to get
    | localized telephone numbers, street address information and more.
    |
    */

    'faker_locale' => 'en_US',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'keyVisicom' => env('APP_KEY_VISICOM'),
    'keyVisicomMy' => env('APP_KEY_VISICOM_MY'),
    'keyMapbox' => env('APP_KEY_MAPBOX'),
    'keyIP2Location' => env('APP_KEY_IP2Location'),

    'merchantAccount' => env('APP_KEY_MERCHANT_ACCOUNT_VOD'),
    'merchantSecretKey' => env('APP_KEY_MERCHANT_SECRET_KEY_VOD'),

    'merchantAccountMy' => env('APP_KEY_MERCHANT_ACCOUNT'),
    'merchantSecretKeyMy' => env('APP_KEY_MERCHANT_SECRET_KEY'),
    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => [

        /*
         * Laravel Framework Service Providers...
         */
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,
        Stevebauman\Location\LocationServiceProvider::class,

        /*
         * Package Service Providers...
         */

        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        // App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
        App\Providers\FirebaseServiceProvider::class,

    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => [

        'App' => Illuminate\Support\Facades\App::class,
        'Arr' => Illuminate\Support\Arr::class,
        'Artisan' => Illuminate\Support\Facades\Artisan::class,
        'Auth' => Illuminate\Support\Facades\Auth::class,
        'Blade' => Illuminate\Support\Facades\Blade::class,
        'Broadcast' => Illuminate\Support\Facades\Broadcast::class,
        'Bus' => Illuminate\Support\Facades\Bus::class,
        'Cache' => Illuminate\Support\Facades\Cache::class,
        'Config' => Illuminate\Support\Facades\Config::class,
        'Cookie' => Illuminate\Support\Facades\Cookie::class,
        'Crypt' => Illuminate\Support\Facades\Crypt::class,
        'Date' => Illuminate\Support\Facades\Date::class,
        'DB' => Illuminate\Support\Facades\DB::class,
        'Eloquent' => Illuminate\Database\Eloquent\Model::class,
        'Event' => Illuminate\Support\Facades\Event::class,
        'File' => Illuminate\Support\Facades\File::class,
        'Gate' => Illuminate\Support\Facades\Gate::class,
        'Hash' => Illuminate\Support\Facades\Hash::class,
        'Http' => Illuminate\Support\Facades\Http::class,
        'Js' => Illuminate\Support\Js::class,
        'Lang' => Illuminate\Support\Facades\Lang::class,
        'Log' => Illuminate\Support\Facades\Log::class,
        'Mail' => Illuminate\Support\Facades\Mail::class,
        'Notification' => Illuminate\Support\Facades\Notification::class,
        'Password' => Illuminate\Support\Facades\Password::class,
        'Queue' => Illuminate\Support\Facades\Queue::class,
        'RateLimiter' => Illuminate\Support\Facades\RateLimiter::class,
        'Redirect' => Illuminate\Support\Facades\Redirect::class,
        // 'Redis' => Illuminate\Support\Facades\Redis::class,
        'Request' => Illuminate\Support\Facades\Request::class,
        'Response' => Illuminate\Support\Facades\Response::class,
        'Route' => Illuminate\Support\Facades\Route::class,
        'Schema' => Illuminate\Support\Facades\Schema::class,
        'Session' => Illuminate\Support\Facades\Session::class,
        'Storage' => Illuminate\Support\Facades\Storage::class,
        'Str' => Illuminate\Support\Str::class,
        'URL' => Illuminate\Support\Facades\URL::class,
        'Validator' => Illuminate\Support\Facades\Validator::class,
        'View' => Illuminate\Support\Facades\View::class,
        'Location' => 'Stevebauman\Location\Facades\Location',

    ],



    //Киев
    'taxi2012Url_0' => 'http://167.235.113.231:7307',
    'taxi2012Url_1' => 'http://167.235.113.231:7306',
    'taxi2012Url_2' => 'http://134.249.181.173:7208',
    'taxi2012Url_3' => 'http://91.205.17.153:7208' ,
    'server' => 'Киев',
    'username' => 'ONLINE56',
    'password' => 'gggdsh5+',

//Одесса
//        'taxi2012Url_1' => 'http://31.43.107.151:7303',
//        'taxi2012Url_2' => 'http://31.43.107.151:7303',
//        'taxi2012Url_3' => 'http://31.43.107.151:7303' ,
//        'server' => 'Одесса',
//        'username' => '0936734488',
//        'password' => '22223344',

    /**
     * Номер колоны, в которую будут приходить заказы. 0, 1 или 2
     */
    'taxiColumnId' => '0',
    'taxiColumnIdKyiv' => '1',

    /**
     * reCaptha key
     * 6LeE07AhAAAAAFVS8gtRKce0L76F8U1JzADOiho9
     * 6LeE07AhAAAAALvE4Yb8eftADKOLrvW4qBtNDfJY
     */
    'RECAPTCHA_SITE_KEY' => '6LeE07AhAAAAAFVS8gtRKce0L76F8U1JzADOiho9',
    'RECAPTCHA_SECRET_KEY' => '6LeE07AhAAAAALvE4Yb8eftADKOLrvW4qBtNDfJY',

    /**
     *  Google Maps Platform
     */
    'Google_Maps_API_KEY' => 'AIzaSyCoyJk5j4GRS41GYwZTRJduPnV5k8SDCoc',

    /**
     * Комендантська година
     * 00:00 05:00
     */

     'start_time' => '00:00',
     'end_time' => '05:00',

    /**
     * Коэфициенты диапазона цен
     */

    'order_cost_min' => 1.25,
    'order_cost_max' => 1.78,

    /**
     * Id telegramm для тревоги
     */

    'chat_id_alarm' => 1379298637,
    /**
     * Fondy
     */
    'merchantId' => '1534178',
    'merchantPassword' => 'wKxF95EndeUu3xnJ5jJ3ySsLrVeq0vXT',
    /**
     * FIREBASE_API_KEY
     */
    'FIREBASE_API_KEY_PAS_1' => 'AIzaSyDPNH0xPdn1v7Wxf6k7PZ8uQTtiZ_QmXyQ',
    'FIREBASE_API_KEY_PAS_2' => '0d6517d36ea943ae80711332cf46ed6b5e5dcabf',
    'FIREBASE_API_KEY_PAS_4' => 'AIzaSyDHh_Tcy2nVUjqFgxKn4kl4ADUHRSCamyw',

    /**
     * Разница коррпектировки стоимоости
     *
     */
    'cost_correction' => '1',

    /**
     * Время работы вилки
     */
    'exec_time' => env('EXEC_TIME', 60),

    /**
     *
     */
    'driver_block_25' => env('DRIVER_BLOCK_25', 0),
    'driver_block_50' => env('DRIVER_BLOCK_50', 0),

    'invoice_prefix' => "INV"
];

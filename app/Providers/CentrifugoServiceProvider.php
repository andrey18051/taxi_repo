<?php

namespace App\Providers;

use App\Services\CentrifugoService;
use Illuminate\Support\ServiceProvider;

class CentrifugoServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('centrifugo', function ($app) {
            return new CentrifugoService();
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../config/centrifugo.php' => config_path('centrifugo.php'),
        ], 'centrifugo-config');
    }
}

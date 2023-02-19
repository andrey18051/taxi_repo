<?php

namespace App\Providers;

use App\Helpers\Telegram;
use App\Helpers\Viber;
use App\Helpers\ViberCustoms;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(Telegram::class, function ($app) {
            return new Telegram(new Http(), config('bots.bot'));
        });

        $this->app->bind(Viber::class, function ($app) {
            return new Viber(new Http(), config('bots.botViber'));
        });

        $this->app->bind(ViberCustoms::class, function ($app) {
            return new ViberCustoms(new Http(), config('bots.botViberCustoms'));
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}

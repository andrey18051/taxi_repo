<?php

namespace App\Providers;

use App\Helpers\Telegram;
use App\Helpers\Viber;
use App\Helpers\ViberCustoms;
use App\Mail\SmtpAuthFailurePlugin;
use App\Services\MailPasswordStore;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
        if ($this->app->environment('testing')) {
            return;
        }

        MailPasswordStore::applyOverride();
        $this->app->booted(function () {
            try {
                $this->app->make('mailer')->getSwiftMailer()->registerPlugin(new SmtpAuthFailurePlugin());
            } catch (\Throwable $e) {
                Log::warning('SMTP auth watcher was not registered');
            }
        });
    }
}

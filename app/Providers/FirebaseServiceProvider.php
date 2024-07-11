<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;

class FirebaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
         $this->app->singleton('firebase.messaging', function ($app) {
             $serviceAccountPath = env('FIREBASE_CREDENTIALS_PAS_2');

             return (new Factory)
                 ->withServiceAccount($serviceAccountPath)
                 ->createMessaging(); // Создание экземпляра для облачного обмена сообщениями
         });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}

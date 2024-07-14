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
            $serviceAccountPath1 = env('FIREBASE_CREDENTIALS_PAS_1');
            $serviceAccountPath2 = env('FIREBASE_CREDENTIALS_PAS_2');
            $serviceAccountPath4 = env('FIREBASE_CREDENTIALS_PAS_4');

            $firebase1 = (new Factory)
                ->withServiceAccount($serviceAccountPath1)
                ->createMessaging();

            $firebase2 = (new Factory)
                ->withServiceAccount($serviceAccountPath2)
                ->createMessaging();

            $firebase4 = (new Factory)
                ->withServiceAccount($serviceAccountPath4)
                ->createMessaging();

            return [
                'app1' => $firebase1,
                'app2' => $firebase2,
                'app4' => $firebase4,
            ];
        });

        $this->app->singleton('firebase.auth', function ($app) {
            $serviceAccountPath1 = env('FIREBASE_CREDENTIALS_PAS_1');
            $serviceAccountPath2 = env('FIREBASE_CREDENTIALS_PAS_2');
            $serviceAccountPath4 = env('FIREBASE_CREDENTIALS_PAS_4');

            $firebase1 = (new Factory)
                ->withServiceAccount($serviceAccountPath1)
                ->createAuth();

            $firebase2 = (new Factory)
                ->withServiceAccount($serviceAccountPath2)
                ->createAuth();

            $firebase4 = (new Factory)
                ->withServiceAccount($serviceAccountPath4)
                ->createAuth();

            return [
                'app1' => $firebase1,
                'app2' => $firebase2,
                'app4' => $firebase4,
            ];
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

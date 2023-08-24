<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home-Combo';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::prefix('apiTest')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/apiTest.php'));

            Route::prefix('apiPas2')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/apiPas2.php'));

            Route::prefix('apiPas2_Dnipro')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/apiPas2_Dnipro.php'));

            Route::prefix('apiPas2_Odessa')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/apiPas2_Odessa.php'));

            Route::prefix('apiPas2_Zaporizhzhia')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/apiPas2_Zaporizhzhia.php'));

            Route::prefix('apiPas2_Cherkasy')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/apiPas2_Cherkasy.php'));

            Route::prefix('apiPas3')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/apiPas3.php'));

            Route::prefix('apiPas4')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/apiPas4.php'));

            Route::prefix('apiPas4001')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/apiPas4001.php'));

            Route::prefix('apiPas4001_Dnipro')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/apiPas4001_Dnipro.php'));

            Route::prefix('apiPas4001_Odessa')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/apiPas4001_Odessa.php'));
            Route::prefix('apiPas4001_Zaporizhzhia')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/apiPas4001_Zaporizhzhia.php'));

            Route::prefix('apiPas4001_Cherkasy')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/apiPas4001_Cherkasy.php'));

            Route::prefix('api149')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api149.php'));

            Route::prefix('api151')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api151.php'));

            Route::prefix('api154')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api154.php'));

            Route::prefix('api157')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api157.php'));

            Route::prefix('api160')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api160.php'));

            Route::prefix('apiPas1700')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/apiPas1700.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });
    }
}

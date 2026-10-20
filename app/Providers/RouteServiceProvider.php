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
    public const HOME = '/dashboard';

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

            Route::prefix('api/parent')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/parent.php'));

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
            // Parent API calls with a token are limited per token, not per IP:
            // the Parent Hub sends every parent's requests from one server.
            $key = optional($request->user())->id
                ?: ($request->bearerToken() ? 'token:' . sha1($request->bearerToken()) : $request->ip());

            return Limit::perMinute(60)->by($key);
        });

        RateLimiter::for('parent-login', function (Request $request) {
            return Limit::perMinute(config('parent_api.login_attempts_per_minute', 10))
                ->by('parent-login:' . mb_strtolower((string) $request->input('username')))
                ->response(fn () => response()->json([
                    'status' => false,
                    'message' => 'Too many login attempts. Please wait a minute and try again.',
                ], 429));
        });
    }
}

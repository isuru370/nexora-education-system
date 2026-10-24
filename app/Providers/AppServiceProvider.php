<?php

namespace App\Providers;

use App\Models\Payment;
use App\Support\CardAccess;
use App\Support\CardMode;
use App\Support\Features;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;
use App\Models\Student;
use App\Observers\PaymentObserver;
use App\Observers\StudentObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register any application services.

    }


    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Paginator::useBootstrap();
        Student::observe(StudentObserver::class);
        Payment::observe(PaymentObserver::class);

        // @feature('courses') ... @endfeature
        Blade::if('feature', fn (string $key) => Features::enabled($key));

        // @cardmode('temporary') ... @endcardmode   (php artisan nexora:card-mode)
        Blade::if('cardmode', fn (string $mode) => CardMode::is($mode));

        // @cardaccess('generate') ... @endcardaccess   (App\Support\CardAccess)
        Blade::if('cardaccess', fn (string $action) => CardAccess::can($action));
    }
}

<?php

namespace Tests\Feature;

use App\Services\System\DeployCheckService;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Route;
use Laravel\SerializableClosure\SerializableClosure;
use Tests\TestCase;

/**
 * With `php artisan route:cache` a closure route (e.g. "/") is stored as a
 * serialized string; the deploy check must not call it broken.
 */
class DeployRouteCheckTest extends TestCase
{
    public function test_cached_closure_routes_are_not_broken(): void
    {
        $route = new LaravelRoute(['GET'], '/_cached_closure', [
            'uses' => serialize(new SerializableClosure(fn () => 'ok')),
        ]);
        Route::getRoutes()->add($route);

        $check = app(DeployCheckService::class)->routeCheck();

        $this->assertSame('ok', $check['status'], $check['message']);
    }

    public function test_a_missing_controller_is_still_reported(): void
    {
        Route::get('/_broken', 'App\\Http\\Controllers\\NoSuchController@index');

        $check = app(DeployCheckService::class)->routeCheck();

        $this->assertSame('fail', $check['status']);
        $this->assertStringContainsString('NoSuchController not found', $check['message']);
    }
}

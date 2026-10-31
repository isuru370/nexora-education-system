<?php

namespace Tests\Feature;

use App\Services\System\DeployCheckService;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/** nexora:deploy route check must pass when the routes are cached (closures are serialized) */
class DeployRouteCheckTest extends TestCase
{
    protected function tearDown(): void
    {
        Artisan::call('route:clear');
        parent::tearDown();
    }

    public function test_closure_routes_are_skipped_with_route_cache(): void
    {
        $this->assertTrue(DeployCheckService::isCachedClosure('O:47:"Laravel\SerializableClosure\SerializableClosure":1:{}'));
        $this->assertFalse(DeployCheckService::isCachedClosure('App\Http\Controllers\Foo@bar'));

        Artisan::call('route:cache');
        $this->refreshApplication();

        $result = app(DeployCheckService::class)->routeCheck();
        $this->assertStringContainsString('routes OK', json_encode($result));
    }
}

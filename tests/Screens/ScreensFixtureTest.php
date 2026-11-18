<?php

namespace Tests\Screens;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Not part of the normal test run (tests/Screens is not a test suite).
 *
 * The screenshots CI job runs it against a sqlite FILE database: it fills
 * the database with the test institute and writes the list of pages to
 * shoot (storage/screens/urls.json). Then `php artisan serve` + Playwright
 * (tests/Screens/shoot.mjs) log in as admin@test.lk and take screenshots.
 */
class ScreensFixtureTest extends TestCase
{
    use BuildsInstitute;

    public function test_build_the_screens_database(): void
    {
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->buildInstitute();

        $payment = DB::table('payments')->where('status', 'completed')->value('id');

        $pages = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn (Route $route) => in_array('GET', $route->methods())
                && !str_contains($route->uri(), '{')
                && in_array('web', $route->gatherMiddleware())
                && !preg_match('/export|download|backup|pdf|excel|logout|print|api\/|sanctum|_ignition|up$/i', $route->uri()))
            ->map(fn (Route $route) => '/' . ltrim($route->uri(), '/'))
            ->merge([
                "/students/{$this->inst['s1']}",
                "/students/{$this->inst['s1']}/edit",
                "/payments/$payment",
            ])
            ->unique()
            ->sort()
            ->values()
            ->all();

        @mkdir(storage_path('screens'), 0775, true);
        file_put_contents(storage_path('screens/urls.json'), json_encode($pages, JSON_PRETTY_PRINT));

        $this->assertNotEmpty($pages);
    }
}

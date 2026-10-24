<?php

namespace Tests\Feature\Web;

use App\Services\System\DeployCheckService;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The NexoraIT EDU product page (welcome.blade.php) is only on
 * edu.nexorait.lk: `php artisan nexora:feature landing_page on`.
 * Institute sites open the login page on "/".
 */
class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_institute_site_opens_the_login_page(): void
    {
        $this->get('/')->assertRedirect(route('login'));

        $check = app(DeployCheckService::class)->httpSmokeTests()['Page: home (/)'];
        $this->assertSame('ok', $check['status'], $check['message']);
    }

    public function test_product_site_shows_the_landing_page(): void
    {
        Features::set('landing_page', true);

        $this->get('/')->assertOk()->assertSee('NexoraIT EDU');

        $check = app(DeployCheckService::class)->httpSmokeTests()['Page: home (/)'];
        $this->assertSame('ok', $check['status'], $check['message']);
    }
}

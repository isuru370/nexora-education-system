<?php

namespace Tests\Feature;

use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Features switched on per institute: config/features.php + "features" table,
 * changed only with `php artisan nexora:feature`.
 */
class FeatureFlagsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildInstitute();

        Route::middleware(['web', 'feature:courses'])->get('/_test/courses', fn () => 'courses page');
        Route::middleware(['api', 'feature:courses'])->get('/api/_test/courses', fn () => ['ok' => true]);
    }

    public function test_features_are_off_until_switched_on(): void
    {
        $this->assertFalse(feature('courses'));
        $this->assertFalse(feature('not-a-feature'));
        $this->assertSame(['courses' => false], Features::all());

        $this->get('/_test/courses')->assertNotFound();
        $this->getJson('/api/_test/courses')->assertNotFound()->assertJson(['success' => false]);
    }

    public function test_command_switches_a_feature_on_and_off(): void
    {
        $this->artisan('nexora:feature courses on')
            ->expectsOutput('Courses is now ON for this institute.')
            ->assertExitCode(0);

        $this->assertTrue(feature('courses'));
        $this->assertDatabaseHas('features', ['key' => 'courses', 'is_enabled' => true]);
        $this->get('/_test/courses')->assertOk()->assertSee('courses page');
        $this->getJson('/api/_test/courses')->assertOk();

        $this->artisan('nexora:feature courses off')->assertExitCode(0);

        $this->assertFalse(feature('courses'));
        $this->get('/_test/courses')->assertNotFound();
    }

    public function test_command_lists_features_and_refuses_unknown_ones(): void
    {
        $this->artisan('nexora:feature')
            ->expectsTable(['Key', 'Feature', 'This institute'], [['courses', 'Courses', 'off']])
            ->assertExitCode(0);

        $this->artisan('nexora:feature cours on')->assertExitCode(1);
        $this->artisan('nexora:feature courses maybe')->assertExitCode(1);
        $this->assertDatabaseMissing('features', ['key' => 'cours']);

        $this->expectException(InvalidArgumentException::class);
        Features::set('cours', true);
    }

    public function test_blade_directive(): void
    {
        $template = "@feature('courses') COURSES-MENU @endfeature";

        $this->blade($template)->assertDontSee('COURSES-MENU');

        Features::set('courses', true);

        $this->blade($template)->assertSee('COURSES-MENU');
    }

    public function test_mobile_app_reads_the_features(): void
    {
        $this->getJson('/api/features')->assertUnauthorized();

        Sanctum::actingAs($this->inst['cashier']);

        $this->getJson('/api/features')
            ->assertOk()
            ->assertExactJson([
                'status' => 'success',
                'message' => 'Features loaded successfully.',
                'data' => ['courses' => false],
                'card_mode' => 'permanent',
            ]);

        Features::set('courses', true);

        $this->getJson('/api/features')->assertOk()->assertJsonPath('data.courses', true);
        $this->getJson('/api/me')->assertOk()->assertJsonPath('features.courses', true);
    }

    public function test_login_response_has_the_features(): void
    {
        Features::set('courses', true);

        $this->postJson('/api/login', ['email' => 'cashier@test.lk', 'password' => 'password123'])
            ->assertOk()
            ->assertJsonPath('features.courses', true);
    }
}

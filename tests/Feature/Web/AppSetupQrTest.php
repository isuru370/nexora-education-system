<?php

namespace Tests\Feature\Web;

use App\Support\AppSetup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Staff app setup QR: admin page, phone camera help page, key check API.
 */
class AppSetupQrTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildInstitute();
        config(['app.url' => 'https://savidyaedu.nexorait.lk']);
    }

    public function test_admin_sees_the_qr_and_others_cannot(): void
    {
        $this->actingAs($this->inst['admin'])
            ->get('/app-setup-qr')
            ->assertOk()
            ->assertSee('Staff App QR')
            ->assertSee('<svg', false)
            ->assertSee('https://savidyaedu.nexorait.lk');

        $this->assertSame(40, strlen(AppSetup::key()));
        $this->assertSame('https://savidyaedu.nexorait.lk/app-setup?key=' . AppSetup::key(), AppSetup::qrContent());

        $this->actingAs($this->inst['cashier'])->get('/app-setup-qr')->assertForbidden();
    }

    public function test_the_app_can_verify_only_the_current_key(): void
    {
        $key = AppSetup::key();

        $this->postJson('/api/app-setup/verify', ['key' => $key])
            ->assertOk()
            ->assertJsonPath('data.api_base_url', 'https://savidyaedu.nexorait.lk')
            ->assertJsonStructure(['data' => ['name', 'logo_url', 'api_base_url']]);

        $this->postJson('/api/app-setup/verify', ['key' => str_repeat('x', 40)])->assertStatus(422);
        $this->postJson('/api/app-setup/verify', [])->assertStatus(422);
    }

    public function test_new_qr_stops_the_old_one(): void
    {
        $old = AppSetup::key();

        $this->actingAs($this->inst['admin'])
            ->post('/app-setup-qr/regenerate')
            ->assertRedirect('/app-setup-qr');

        $this->assertNotSame($old, AppSetup::key());
        $this->postJson('/api/app-setup/verify', ['key' => $old])->assertStatus(422);
        $this->postJson('/api/app-setup/verify', ['key' => AppSetup::key()])->assertOk();
    }

    public function test_verify_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/app-setup/verify', ['key' => 'wrong'])->assertStatus(422);
        }

        $this->postJson('/api/app-setup/verify', ['key' => AppSetup::key()])->assertStatus(429);
    }

    public function test_camera_scan_opens_a_help_page_without_the_key(): void
    {
        $key = AppSetup::key();

        $this->get('/app-setup?key=' . $key)
            ->assertOk()
            ->assertSee('Scan institute QR')
            ->assertDontSee($key);
    }
}

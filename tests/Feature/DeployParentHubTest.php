<?php

namespace Tests\Feature;

use App\Services\ParentHub\ParentHubService;
use App\Services\System\DeployCheckService;
use App\Support\CardMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * nexora:deploy shows the card mode and saves this institute in the
 * Parent Hub (fails when the hub is not set up / not reachable).
 */
class DeployParentHubTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // TestCase fakes every call with 200; start clean so each test can
        // fake the hub's answer (Laravel 8: the first fake that matches wins)
        Http::swap(new \Illuminate\Http\Client\Factory());

        config([
            'app.name' => 'Savidya Higher Education Institute',
            'app.url' => 'https://savidya.nexorait.lk',
            'app.version' => '3.0.0',
            'services.parent_hub.url' => 'https://hub.test/api/v1',
            'services.parent_hub.secret' => 'shared-secret',
            'services.institute.code' => 'SHEI',
            'services.institute.name' => null,
            'services.institute.contact_email' => 'info@savidya.lk',
            'services.institute.contact_mobile' => '0771234567',
            'services.institute.logo' => 'logo/logo.png',
        ]);
    }

    private function checker(): DeployCheckService
    {
        return app(DeployCheckService::class);
    }

    public function test_card_mode_check_warns_until_it_is_set(): void
    {
        $this->assertSame('warn', $this->checker()->cardMode()['status']);
        $this->assertStringContainsString('nexora:card-mode', $this->checker()->cardMode()['message']);

        CardMode::set('temporary');

        $check = $this->checker()->cardMode();
        $this->assertSame('ok', $check['status']);
        $this->assertStringStartsWith('TEMPORARY', $check['message']);
    }

    public function test_institute_details_are_sent_to_the_hub(): void
    {
        Http::fake([
            'hub.test/*' => Http::response(['success' => true, 'data' => ['id' => 1, 'name' => 'Savidya Higher Education Institute', 'code' => 'SHEI']]),
        ]);

        $check = $this->checker()->parentHubSync();

        $this->assertSame('ok', $check['status'], $check['message']);
        $this->assertStringContainsString('Saved: SHEI - Savidya Higher Education Institute', $check['message']);

        Http::assertSent(fn (Request $r) => $r->url() === 'https://hub.test/api/v1/register-institute'
            && $r['secret_key'] === 'shared-secret'
            && $r['institute_code'] === 'SHEI'
            && $r['institute_name'] === 'Savidya Higher Education Institute'
            && $r['api_url'] === 'https://savidya.nexorait.lk'
            && $r['contact_mobile'] === '0771234567'
            && $r['logo'] === 'https://savidya.nexorait.lk/logo/logo.png'
            && $r['app_version'] === '3.0.0');
    }

    public function test_institute_name_comes_from_institute_name_first(): void
    {
        config(['services.institute.name' => 'Savidya Institute']);

        $this->assertSame('Savidya Institute', app(ParentHubService::class)->institutePayload()['institute_name']);
    }

    public function test_missing_settings_fail(): void
    {
        config(['services.parent_hub.secret' => null, 'services.institute.code' => '']);
        Http::fake();

        $check = $this->checker()->parentHubSettings();
        $this->assertSame('fail', $check['status']);
        $this->assertSame('Missing in .env: PARENT_HUB_SECRET, INSTITUTE_CODE', $check['message']);

        $this->assertSame('fail', $this->checker()->parentHubSync()['status']);
        Http::assertNothingSent();
    }

    public function test_wrong_secret_unreachable_and_bad_data_fail(): void
    {
        Http::fakeSequence('hub.test/*')
            ->push(['success' => false, 'message' => 'Unauthorized'], 401)
            ->push(['message' => 'invalid', 'errors' => ['api_url' => ['The api url must be a valid URL.']]], 422);

        $check = $this->checker()->parentHubSync();
        $this->assertSame('fail', $check['status']);
        $this->assertStringContainsString('PARENT_HUB_SECRET (401)', $check['message']);

        $this->assertStringContainsString('The api url must be a valid URL.', $this->checker()->parentHubSync()['message']);
    }

    public function test_hub_not_reachable_fails(): void
    {
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('Could not resolve host'));

        $check = $this->checker()->parentHubSync();
        $this->assertSame('fail', $check['status']);
        $this->assertStringContainsString('Cannot reach the Parent Hub', $check['message']);
    }

    public function test_parenthub_register_command_reports_the_result(): void
    {
        Http::fakeSequence('hub.test/*')
            ->push(['success' => true, 'data' => ['code' => 'SHEI']])
            ->push([], 401);

        $this->artisan('parenthub:register')
            ->expectsOutput('Institute registered successfully: SHEI - Savidya Higher Education Institute')
            ->assertExitCode(0);

        $this->artisan('parenthub:register')->assertExitCode(1);
    }
}

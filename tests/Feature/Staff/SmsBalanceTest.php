<?php

namespace Tests\Feature\Staff;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Mobile dashboard SMS balance: ChatBiz /v1/balance answers "main_balance".
 */
class SmsBalanceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildInstitute();
        Cache::flush();

        // TestCase fakes every HTTP call with an empty answer: start clean so
        // the provider answers below are used
        Http::swap(new \Illuminate\Http\Client\Factory());
        config(['services.sms.base_url' => 'https://smssender.chatbiz.net/v1']);
    }

    public function test_main_balance_is_read(): void
    {
        Http::fake(['*/balance*' => Http::response([
            'status_code' => 204,
            'main_balance' => '12450.00',
            'sub_accounts_total' => '3100.00',
            'sub_accounts' => [],
        ])]);

        Sanctum::actingAs($this->inst['admin']);
        $response = $this->getJson('/api/mobile-dashboard')->assertOk();

        $this->assertTrue($response->json('data.sms_balance.success'));
        $this->assertSame('12450.00', $response->json('data.sms_balance.current_balance'));
        $this->assertSame('12450.00', $response->json('data.sms_balance.data.current_balance'));
        $this->assertNotContains('sms_balance', collect($response->json('data.alerts'))->pluck('type')->all());
    }

    public function test_provider_error_still_shows_the_warning(): void
    {
        Http::fake(['*/balance*' => Http::response(['status_code' => 202])]);

        Sanctum::actingAs($this->inst['admin']);
        $response = $this->getJson('/api/mobile-dashboard')->assertOk();

        $this->assertFalse($response->json('data.sms_balance.success'));
        $this->assertContains('sms_balance', collect($response->json('data.alerts'))->pluck('type')->all());
    }
}

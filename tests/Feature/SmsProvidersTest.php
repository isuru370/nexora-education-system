<?php

namespace Tests\Feature;

use App\Jobs\SendLoginDetailsSms;
use App\Jobs\SendPaymentSms;
use App\Jobs\SendSystemSms;
use App\Services\SmsService;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * SMS: the institute account (SMS_PROVIDER chatbiz | sendlk) for payment,
 * attendance ... and the NexoraIT ChatBiz account (SMS_SYSTEM_*) for
 * login / registration SMS.
 */
class SmsProvidersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::swap(new Factory());

        config([
            'services.sms' => [
                'provider' => 'sendlk', 'base_url' => null,
                'user_id' => null, 'api_key' => '7|institute-token', 'sender_id' => 'SUCCESSACA',
            ],
            'services.sms_system' => [
                'provider' => 'chatbiz', 'base_url' => null,
                'user_id' => '99', 'api_key' => 'nexora-key', 'sender_id' => 'NexoraIT',
            ],
        ]);
    }

    public function test_sendlk_send_uses_the_bearer_token_and_reads_the_answer(): void
    {
        Http::fake(['sms.send.lk/api/v3/sms/send' => Http::response([
            'status' => 'success', 'data' => ['uid' => 'abc123', 'status' => 'Delivered'],
        ])]);

        $result = app(SmsService::class)->sendSms('077 123 4567', 'Hello');

        $this->assertTrue($result['success']);
        $this->assertSame('abc123', $result['message_id']);
        Http::assertSent(fn (Request $r) => $r->url() === 'https://sms.send.lk/api/v3/sms/send'
            && $r->hasHeader('Authorization', 'Bearer 7|institute-token')
            && $r['recipient'] === '94771234567'
            && $r['sender_id'] === 'SUCCESSACA'
            && $r['message'] === 'Hello');
    }

    public function test_sendlk_error_message_is_kept(): void
    {
        Http::fake(['sms.send.lk/*' => Http::response(['status' => 'error', 'message' => 'Insufficient balance'], 403)]);

        $result = app(SmsService::class)->sendBulkSms(['0771234567', '0712345678'], 'Hi');

        $this->assertFalse($result['success']);
        $this->assertSame('Insufficient balance', $result['error']);
        Http::assertSent(fn (Request $r) => $r['recipient'] === '94771234567,94712345678');
    }

    public function test_sendlk_balance(): void
    {
        Http::fake(['sms.send.lk/api/v3/balance' => Http::response(['status' => 'success', 'data' => ['remaining_unit' => '1500']])]);

        $result = app(SmsService::class)->getBalance();

        $this->assertTrue($result['success']);
        $this->assertSame('1500', $result['current_balance']);
    }

    public function test_login_and_registration_sms_go_through_the_nexora_chatbiz_account(): void
    {
        Http::fake([
            'smssender.chatbiz.net/*' => Http::response(['status_code' => 204, 'msg_id' => 'm1']),
            'sms.send.lk/*' => Http::response(['status' => 'success', 'data' => []]),
        ]);

        (new SendSystemSms('0771234567', 'Registered'))->handle();
        (new SendLoginDetailsSms('0771234567', 'Login', 1))->handle();
        app()->call([new SendPaymentSms('0771234567', 'Paid'), 'handle']);

        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'chatbiz') && $r['message'] === 'Registered'
            && $r['api_key'] === 'nexora-key' && $r['sender_id'] === 'NexoraIT');
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'chatbiz') && $r['message'] === 'Login');
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'send.lk') && $r['message'] === 'Paid');
        Http::assertSentCount(3);
    }

    public function test_without_a_nexora_account_the_institute_account_is_used(): void
    {
        config(['services.sms_system.api_key' => '']);
        Http::fake(['sms.send.lk/*' => Http::response(['status' => 'success', 'data' => []])]);

        (new SendSystemSms('0771234567', 'Registered'))->handle();

        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'send.lk') && $r['message'] === 'Registered');
    }

    public function test_chatbiz_institute_account_still_works(): void
    {
        config(['services.sms' => ['provider' => 'chatbiz', 'base_url' => null, 'user_id' => '5', 'api_key' => 'k', 'sender_id' => 'Savidya']]);
        Http::fake(['smssender.chatbiz.net/v1/send' => Http::response(['status_code' => 204, 'msg_id' => 'x'])]);

        $this->assertTrue(app(SmsService::class)->sendSms('0771234567', 'Hi')['success']);
        $this->artisan('nexora:sms-test')->assertExitCode(0);
    }
}

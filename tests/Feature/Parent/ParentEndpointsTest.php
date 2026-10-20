<?php

namespace Tests\Feature\Parent;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Every parent API route answers with the logged-in student's data.
 */
class ParentEndpointsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-13 10:00:00');
        $this->buildInstitute();

        $this->token = $this->postJson('/api/parent/v1/auth/login', ['username' => 'S1', 'password' => 'pass1234'])->json('token');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function api(string $path, array $body = [])
    {
        return $this->withToken($this->token)->postJson('/api/parent/v1' . $path, $body);
    }

    public function test_dashboard(): void
    {
        $this->api('/dashboard')->assertOk()->assertJson(['status' => true])->assertSee('Maths 10 CMB');
    }

    public function test_attendance(): void
    {
        $this->api('/attendance')->assertOk()->assertSee('Maths 10 CMB');
    }

    public function test_payments_show_completed_payments_only(): void
    {
        $response = $this->api('/payments')->assertOk()->assertSee('Maths 10 CMB');

        // the fee comes from the enrollment's fee option
        $this->assertEquals(2000, $response->json('data.classes.0.monthly_fee'));
        // the cancelled Rs. 999 November payment is not counted as paid
        $this->assertEquals(2000, $response->json('data.summary.total_paid_amount'));
        $this->assertSame(['2026-10'], $response->json('data.classes.0.paid_months'));
        $this->assertTrue($response->json('data.classes.0.is_current_month_paid'));
        $this->assertStringNotContainsString('999', $response->getContent());
    }

    public function test_exams_and_results(): void
    {
        $this->api('/exams')->assertOk()->assertSee('Term test');
        $this->api('/results', ['exam_id' => $this->inst['exam']])->assertOk()->assertJsonFragment(['marks' => 78]);
    }

    public function test_teachers_and_schedule(): void
    {
        $this->api('/teachers')->assertOk()->assertSee('Nimal Perera');
        $this->api('/schedule')->assertOk()->assertSee('Maths 10 CMB');
    }

    public function test_notification_routes_are_not_mistaken_for_an_id(): void
    {
        $this->api('/notifications')->assertOk()->assertSee('Payment received');
        $this->api('/notifications/unread')->assertOk()->assertSee('Payment received');
        $this->api('/notifications/unread/count')->assertOk()->assertSee('1');
        $this->api('/notifications/stats')->assertOk();

        $this->api('/notifications/read-all')->assertOk();
        $this->assertDatabaseMissing('notifications', ['id' => $this->inst['n1'], 'read_at' => null]);

        $this->api('/notifications/read/delete')->assertOk();
    }

    public function test_fcm_token_can_be_saved_for_web_browsers(): void
    {
        $this->api('/fcm/token', ['token' => 'web-token-1', 'device_id' => 'web-abc', 'device_type' => 'web'])->assertOk();
        $this->api('/fcm/token', ['token' => 'x', 'device_id' => 'y', 'device_type' => 'windows'])->assertStatus(422);

        $this->assertDatabaseHas('fcm_tokens', ['student_id' => $this->inst['s1'], 'device_id' => 'web-abc']);
    }
}

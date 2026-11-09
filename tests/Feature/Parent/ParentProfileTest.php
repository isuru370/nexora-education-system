<?php

namespace Tests\Feature\Parent;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * The parent app's profile: institute name, the registered details (read
 * only), the card QR and the classes.
 */
class ParentProfileTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-13 10:00:00');
        config(['services.institute.name' => 'Test Education Institute']);
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

    public function test_profile_has_the_institute_details_card_and_classes(): void
    {
        $response = $this->api('/profile')->assertOk()->assertJson(['status' => true]);

        $this->assertSame('Test Education Institute', $response->json('data.institute.name'));
        $this->assertSame('S1', $response->json('data.student.custom_id'));
        $this->assertNotEmpty($response->json('data.branch.name'));
        $this->assertSame('S1', $response->json('data.login.username'));
        $this->assertSame('Maths 10 CMB', $response->json('data.classes.0.class_name'));
        $this->assertEquals(2000, $response->json('data.classes.0.monthly_fee'));
        $this->assertSame('Nimal Perera', $response->json('data.classes.0.teacher'));
    }

    public function test_profile_is_always_the_token_students(): void
    {
        $other = DB::table('students')->where('custom_id', '!=', 'S1')->value('id');

        $this->api('/profile', ['student_id' => $other])
            ->assertOk()
            ->assertJsonPath('data.student.custom_id', 'S1');
    }

    public function test_card_qr_follows_the_card_the_staff_app_scans(): void
    {
        $id = DB::table('students')->where('custom_id', 'S1')->value('id');

        DB::table('students')->where('id', $id)->update(['permanent_qr_active' => true]);
        $this->api('/profile')->assertJsonPath('data.card.qr_code', 'S1')->assertJsonPath('data.card.qr_type', 'permanent');

        DB::table('students')->where('id', $id)->update([
            'permanent_qr_active' => false,
            'temporary_qr_code' => 'TMP001',
            'temporary_qr_code_expire_date' => '2026-10-20 00:00:00',
        ]);
        $this->api('/profile')->assertJsonPath('data.card.qr_code', 'TMP001')->assertJsonPath('data.card.qr_type', 'temporary');

        // expired temporary card: no QR
        DB::table('students')->where('id', $id)->update(['temporary_qr_code_expire_date' => '2026-10-01 00:00:00']);
        $this->api('/profile')->assertJsonPath('data.card.qr_code', null);
    }

    public function test_login_and_dashboard_carry_the_institute_name(): void
    {
        $this->postJson('/api/parent/v1/auth/login', ['username' => 'S1', 'password' => 'pass1234'])
            ->assertJsonPath('data.institute.name', 'Test Education Institute')
            ->assertJsonStructure(['data' => ['card' => ['qr_code', 'qr_type'], 'branch_name']]);

        $this->api('/dashboard')
            ->assertJsonPath('data.institute.name', 'Test Education Institute')
            ->assertJsonStructure(['data' => ['student' => ['card']]]);
    }

    public function test_dashboard_shows_completed_payments_only_with_a_full_photo_url(): void
    {
        DB::table('students')->where('custom_id', 'S1')->update(['img_url' => 'students/s1.jpg']);

        $response = $this->api('/dashboard')->assertOk();

        $this->assertStringStartsWith('http', $response->json('data.student.img_url'));
        $this->assertSame(['completed'], collect($response->json('data.recent_payments'))->pluck('status')->unique()->values()->all());
        $this->assertStringNotContainsString('"amount":999', $response->getContent());
    }

    public function test_profile_needs_a_token(): void
    {
        $this->postJson('/api/parent/v1/profile')->assertUnauthorized();
    }
}

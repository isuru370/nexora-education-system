<?php

namespace Tests\Feature\Parent;

use App\Models\StudentPortalLogin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

class ParentAuthTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildInstitute();
    }

    private function login(string $username = 'S1', string $password = 'pass1234')
    {
        return $this->postJson('/api/parent/v1/auth/login', ['username' => $username, 'password' => $password]);
    }

    public function test_login_returns_the_student_and_a_token(): void
    {
        $response = $this->login()->assertOk()
            ->assertJson(['status' => true, 'token_type' => 'Bearer', 'data' => ['student_id' => $this->inst['s1'], 'custom_id' => 'S1']]);

        $token = $response->json('token');
        $this->assertNotEmpty($token);
        $this->assertSame($token, $response->json('data.token'));
        $this->assertSame(1, PersonalAccessToken::count());
        $this->assertNotNull(StudentPortalLogin::where('username', 'S1')->value('last_login_at'));
    }

    public function test_wrong_password_unknown_user_and_disabled_login_are_refused(): void
    {
        $this->login('S1', 'wrong')->assertStatus(401)->assertJson(['status' => false]);
        $this->login('NOPE')->assertStatus(401);

        StudentPortalLogin::where('username', 'S1')->update(['is_active' => false]);
        $this->login()->assertStatus(401);

        $this->assertSame(0, PersonalAccessToken::count());
    }

    public function test_login_is_rate_limited_per_username(): void
    {
        config(['parent_api.login_attempts_per_minute' => 3]);

        foreach (range(1, 3) as $i) {
            $this->login('S1', 'wrong')->assertStatus(401);
        }

        $this->login('S1', 'wrong')->assertStatus(429)->assertJson(['status' => false]);

        // another username (e.g. another parent through the same Parent Hub server) still works
        $this->login('S2')->assertOk();
    }

    public function test_logout_revokes_the_token_and_turns_off_the_device(): void
    {
        $token = $this->login()->json('token');

        $this->withToken($token)->postJson('/api/parent/v1/fcm/token', [
            'token' => 'fcm-abc', 'device_id' => 'phone-1', 'device_type' => 'android',
        ])->assertOk();

        $this->withToken($token)->postJson('/api/parent/v1/auth/logout', ['device_id' => 'phone-1'])
            ->assertOk()->assertJson(['status' => true]);

        $this->assertSame(0, PersonalAccessToken::count());
        $this->assertDatabaseHas('fcm_tokens', ['device_id' => 'phone-1', 'is_active' => false]);

        $this->withToken($token)->postJson('/api/parent/v1/dashboard')->assertStatus(401);
    }

    public function test_changing_the_password_signs_out_other_devices_only(): void
    {
        $phone = $this->login()->json('token');
        $tablet = $this->login()->json('token');

        $this->withToken($phone)->postJson('/api/parent/v1/auth/change-password', [
            'current_password' => 'pass1234',
            'new_password' => 'newpass99',
            'new_password_confirmation' => 'newpass99',
        ])->assertOk()->assertJson(['status' => true]);

        $this->withToken($phone)->postJson('/api/parent/v1/dashboard')->assertOk();
        $this->withToken($tablet)->postJson('/api/parent/v1/dashboard')->assertStatus(401);

        $this->login('S1', 'pass1234')->assertStatus(401);
        $this->login('S1', 'newpass99')->assertOk();
    }

    public function test_wrong_current_password_does_not_change_anything(): void
    {
        $token = $this->login()->json('token');

        $this->withToken($token)->postJson('/api/parent/v1/auth/change-password', [
            'current_password' => 'nope',
            'new_password' => 'newpass99',
            'new_password_confirmation' => 'newpass99',
        ])->assertStatus(400)->assertJson(['status' => false]);

        $this->login('S1', 'pass1234')->assertOk();
    }

    public function test_admin_resetting_or_disabling_the_portal_login_revokes_tokens(): void
    {
        $token = $this->login()->json('token');

        StudentPortalLogin::where('username', 'S1')->first()->update(['password' => 'reset1234']);
        $this->withToken($token)->postJson('/api/parent/v1/dashboard')->assertStatus(401);

        $token = $this->login('S1', 'reset1234')->json('token');
        StudentPortalLogin::where('username', 'S1')->first()->update(['is_active' => false]);
        $this->withToken($token)->postJson('/api/parent/v1/dashboard')->assertStatus(401);
    }
}

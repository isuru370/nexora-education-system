<?php

namespace Tests\Feature\Parent;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

class ParentApiSecurityTest extends TestCase
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

    private function api(string $path, array $body = [], ?string $token = null)
    {
        $request = $token === '' ? $this : $this->withToken($token ?? $this->token);

        return $request->postJson('/api/parent/v1' . $path, $body);
    }

    public function test_a_parent_token_cannot_open_the_staff_api(): void
    {
        $this->assertNotEmpty($this->token);

        // Staff API routes use auth:sanctum - a parent's token must not pass.
        $this->withToken($this->token)->getJson('/api/me')->assertStatus(401);
    }

    public function test_staff_tokens_work_on_the_staff_api_but_not_the_parent_api(): void
    {
        $staffToken = $this->inst['admin']->createToken('test')->plainTextToken;

        $this->withToken($staffToken)->getJson('/api/me')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->withToken($staffToken)->postJson('/api/parent/v1/dashboard', ['student_id' => $this->inst['s1']])->assertStatus(401);
    }

    public function test_the_token_decides_the_student_not_the_request(): void
    {
        // S1's token asking for S2's data gets S1's data
        $result = $this->api('/results', ['student_id' => $this->inst['s2'], 'exam_id' => $this->inst['exam']])->assertOk();
        $this->assertSame(78, $result->json('data.student_result.marks'));

        $this->api('/notifications', ['student_id' => $this->inst['s2']])
            ->assertOk()
            ->assertSee('Payment received')
            ->assertDontSee('Private to S2');

        // query string cannot override it either
        $this->withToken($this->token)
            ->postJson('/api/parent/v1/notifications?student_id=' . $this->inst['s2'])
            ->assertOk()
            ->assertDontSee('Private to S2');
    }

    public function test_one_student_cannot_open_or_change_another_students_notification(): void
    {
        $other = $this->inst['n2'];

        $this->api("/notifications/$other")->assertStatus(404);
        $this->api("/notifications/$other/read")->assertStatus(404);
        $this->api("/notifications/$other/delete")->assertStatus(404);

        $this->assertDatabaseHas('notifications', ['id' => $other, 'read_at' => null]);
        $this->api("/notifications/{$this->inst['n1']}")->assertOk()->assertSee('Payment received');
    }

    public function test_bad_or_expired_tokens_are_refused(): void
    {
        $this->api('/dashboard', [], 'not-a-real-token')->assertStatus(401)->assertJson(['status' => false]);
        $this->api('/dashboard', [], '1|' . str_repeat('x', 40))->assertStatus(401);

        config(['parent_api.token_days' => 30]);
        Carbon::setTestNow(now()->addDays(31));
        $this->api('/dashboard')->assertStatus(401);
    }

    public function test_requests_without_a_token_follow_the_require_token_setting(): void
    {
        // Default: blocked.
        $this->assertTrue(config('parent_api.require_token'));
        $this->api('/payments', ['student_id' => $this->inst['s2']], '')->assertStatus(401);
        $this->api('/auth/change-password', ['student_id' => $this->inst['s2'], 'current_password' => 'x', 'new_password' => 'hacked1', 'new_password_confirmation' => 'hacked1'], '')->assertStatus(401);

        config(['parent_api.require_token' => true]);
        $this->api('/dashboard', ['student_id' => $this->inst['s1']], '')->assertStatus(401);

        // temporary fallback for old apps: PARENT_API_REQUIRE_TOKEN=false
        config(['parent_api.require_token' => false]);
        $this->api('/dashboard', ['student_id' => $this->inst['s1']], '')
            ->assertOk()
            ->assertHeader('X-Parent-Api-Warning', 'token-missing');
    }

    public function test_rate_limit_is_per_token_not_per_server(): void
    {
        $other = $this->postJson('/api/parent/v1/auth/login', ['username' => 'S2', 'password' => 'pass1234'])->json('token');

        foreach (range(1, 60) as $i) {
            $this->api('/notifications/unread/count');
        }

        $this->api('/notifications/unread/count')->assertStatus(429);
        // a different parent coming through the same Parent Hub IP is not blocked
        $this->api('/notifications/unread/count', [], $other)->assertOk();
    }
}

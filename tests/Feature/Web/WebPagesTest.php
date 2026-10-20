<?php

namespace Tests\Feature\Web;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Every web page without a URL parameter opens without a server error,
 * for an admin and for a branch cashier.
 */
class WebPagesTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-13 15:20:00');
        $this->buildInstitute();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** GET pages with no {parameter}; downloads and exports are skipped. */
    private function pages(): array
    {
        return collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn (Route $route) => in_array('GET', $route->methods())
                && !str_contains($route->uri(), '{')
                && in_array('web', $route->gatherMiddleware())
                && !preg_match('/export|download|backup|pdf|excel|logout|print/i', $route->uri()))
            ->map(fn (Route $route) => '/' . ltrim($route->uri(), '/'))
            ->unique()
            ->values()
            ->all();
    }

    private function assertNoServerErrors(array $uris): void
    {
        $errors = [];

        foreach ($uris as $uri) {
            $response = $this->get($uri);

            if ($response->status() >= 500) {
                $errors[] = $uri . ' -> ' . $response->status() . ($response->exception ? ': ' . mb_substr($response->exception->getMessage(), 0, 200) : '');
            }
        }

        $this->assertSame([], $errors, "Pages with server errors:\n" . implode("\n", $errors));
    }

    public function test_login_page_and_admin_login(): void
    {
        $this->get('/login')->assertOk();

        $this->post('/login', ['email' => 'admin@test.lk', 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => 'admin@test.lk', 'password' => 'password123'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->inst['admin']);
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/students')->assertRedirect('/login');
    }

    public function test_every_page_opens_for_an_admin(): void
    {
        $this->actingAs($this->inst['admin']);

        $this->assertNoServerErrors($this->pages());
    }

    public function test_every_page_opens_for_a_branch_cashier(): void
    {
        $this->actingAs($this->inst['cashier']);

        $this->assertNoServerErrors($this->pages());
    }

    public function test_detail_pages_open(): void
    {
        $this->actingAs($this->inst['admin']);
        $payment = DB::table('payments')->where('status', 'completed')->value('id');

        $this->assertNoServerErrors([
            "/students/{$this->inst['s1']}",
            "/students/{$this->inst['s1']}/edit",
            "/payments/$payment",
            "/payments/$payment/receipt",
            '/admin/fcm-tokens/stats',
        ]);
    }
}

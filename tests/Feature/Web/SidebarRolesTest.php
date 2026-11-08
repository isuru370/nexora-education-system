<?php

namespace Tests\Feature\Web;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Roles: Super Admin (Nexora) = everything + "Nexora Admin"; Admin = the
 * institute without the Nexora pages; Branch Admin = branch work only.
 * Sidebar is grouped, one layout on every page.
 */
class SidebarRolesTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildInstitute();
    }

    private const NEXORA_PAGES = ['/payment-rules', '/card-designer', '/app-feedback', '/logs/laravel'];

    public function test_nexora_pages_are_super_admin_only(): void
    {
        $super = $this->makeRoleUser('SUPER_ADMIN');
        $branchAdmin = $this->makeRoleUser('BRANCH_ADMIN', $this->inst['b1']);

        foreach (self::NEXORA_PAGES as $url) {
            $this->actingAs($this->inst['admin'])->get($url)->assertForbidden();
            $this->actingAs($branchAdmin)->get($url)->assertForbidden();
            $this->actingAs($this->inst['cashier'])->get($url)->assertForbidden();
            $this->assertNotSame(403, $this->actingAs($super)->get($url)->status(), $url);
        }
    }

    public function test_database_backup_is_super_admin_only(): void
    {
        $this->actingAs($this->inst['admin'])->get('/admin/setting')
            ->assertOk()
            ->assertSee('Parent Notifications')
            ->assertDontSee('Database Backup Management')
            ->assertSee('Staff App QR');

        $this->actingAs($this->inst['admin'])->get('/admin/setting/backup/export')->assertForbidden();
        $this->actingAs($this->inst['admin'])->post('/admin/setting/backup/import')->assertForbidden();

        $this->actingAs($this->makeRoleUser('SUPER_ADMIN'))->get('/admin/setting')
            ->assertOk()
            ->assertSee('Database Backup Management');
    }

    public function test_branch_admin_cannot_open_institute_wide_finance(): void
    {
        $branchAdmin = $this->makeRoleUser('BRANCH_ADMIN', $this->inst['b1']);

        $this->actingAs($branchAdmin)->get('/reports/institute-income')->assertForbidden();
        $this->actingAs($branchAdmin)->get('/reports/monthly-collection')->assertForbidden();
    }

    /** Only the sidebar's HTML of a page. */
    private function sidebarOf($user, string $url = '/dashboard'): string
    {
        $html = $this->actingAs($user)->get($url)->assertOk()->getContent();
        $start = strpos($html, '<aside class="sidebar"');
        $this->assertNotFalse($start);

        return substr($html, $start, strpos($html, '</aside>', $start) - $start);
    }

    public function test_sidebar_groups_follow_the_role(): void
    {
        $super = $this->sidebarOf($this->makeRoleUser('SUPER_ADMIN'));
        foreach (['Nexora Admin', 'Payment &amp; Attendance Rules', 'Database Backup', 'Laravel Logs', 'Search menu', 'Administration'] as $text) {
            $this->assertStringContainsString($text, $super);
        }

        $admin = $this->sidebarOf($this->inst['admin']);
        foreach (['Administration', 'Institute Income', 'Monthly Collection', 'Settings', 'Staff App QR'] as $text) {
            $this->assertStringContainsString($text, $admin);
        }
        foreach (['Nexora Admin', 'Laravel Logs', 'ID Card Designer', 'Database Backup'] as $text) {
            $this->assertStringNotContainsString($text, $admin);
        }

        $branchAdmin = $this->sidebarOf($this->makeRoleUser('BRANCH_ADMIN', $this->inst['b1']));
        foreach (['Teacher Salaries', 'Institute Expenses', 'Extra Incomes', 'Branch-wise Collection'] as $text) {
            $this->assertStringContainsString($text, $branchAdmin);
        }
        foreach (['Institute Income', 'Monthly Collection', 'Administration', 'Nexora Admin', 'Staff App QR'] as $text) {
            $this->assertStringNotContainsString($text, $branchAdmin);
        }
    }

    public function test_the_current_page_group_is_open(): void
    {
        $html = $this->actingAs($this->inst['admin'])->get('/teacher-salaries')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/sidebar-group is-open\s*"\s*data-group="staff"/', $html);
        $this->assertDoesNotMatchRegularExpression('/sidebar-group is-open\s*"\s*data-group="reports"/', $html);
    }
}

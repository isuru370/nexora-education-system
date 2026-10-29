<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Web system checks before going live: every page needs a login, the pages
 * that lost their scripts have them again, and pages that used to fail open.
 */
class WebProductionReadyTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    /** Pages anyone may open. */
    private const PUBLIC_URIS = [
        '/',
        'login',
        'logout',
        'contact_administrator',
        'forgot-password',
        'forgot-password/*',
        'sanctum/csrf-cookie',
        'app-setup',   // help page a phone camera opens from the staff app QR
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildInstitute();
    }

    public function test_every_web_route_needs_a_login(): void
    {
        $open = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn (Route $route) => in_array('web', $route->gatherMiddleware(), true)
                && !str_starts_with($route->uri(), 'api/')
                && !$this->isPublic($route->uri()))
            ->reject(fn (Route $route) => collect($route->gatherMiddleware())
                ->contains(fn ($m) => is_string($m) && ($m === 'auth' || str_starts_with($m, 'auth:') || str_starts_with($m, 'role:'))))
            ->map(fn (Route $route) => implode('|', $route->methods()) . ' ' . $route->uri())
            ->values()
            ->all();

        $this->assertSame([], $open, "Web routes without a login check:\n" . implode("\n", $open));
    }

    public function test_guests_are_sent_to_login(): void
    {
        $schedule = $this->inst['schedule'];

        $this->get('/class-schedules')->assertRedirect('/login');
        $this->get("/class-schedules/{$schedule}")->assertRedirect('/login');
        $this->get('/student-class-management')->assertRedirect('/login');
        $this->get("/class-category-fees/{$this->inst['f1']}/fee-options")->assertRedirect('/login');
        $this->get('/class-time-table/weekly')->assertRedirect('/login');
    }

    public function test_teachers_can_view_schedules_but_not_change_them(): void
    {
        $teacher = User::forceCreate([
            'name' => 'Teacher User',
            'email' => 'teacher.user@test.lk',
            'password' => 'password123',
            'user_type_id' => DB::table('user_types')->insertGetId(['name' => 'TEACHER', 'code' => 'TEACHER', 'is_active' => 1]),
            'is_active' => 1,
        ]);
        DB::table('teachers')->where('id', $this->inst['teacher'])->update(['user_id' => $teacher->id]);
        DB::table('teacher_branches')->insert(['teacher_id' => $this->inst['teacher'], 'branch_id' => $this->inst['b1']]);
        $schedule = $this->inst['schedule'];

        $this->actingAs($teacher);

        $this->get('/class-schedules')->assertOk();
        $this->get("/class-schedules/{$schedule}")->assertOk()->assertDontSee("class-schedules/{$schedule}/edit");
        $this->get('/class-schedules/create')->assertForbidden();
        $this->get("/class-schedules/{$schedule}/edit")->assertForbidden();
        $this->patch("/class-schedules/{$schedule}/cancel")->assertForbidden();
        $this->get('/student-class-management')->assertForbidden();

        // Staff still can.
        $this->actingAs($this->inst['admin']);
        $this->get("/class-schedules/{$schedule}")->assertOk()->assertSee("class-schedules/{$schedule}/edit");
        $this->assertNotSame(403, $this->get('/class-schedules/create')->status());
    }

    public function test_pages_that_used_to_fail_now_open(): void
    {
        $this->actingAs($this->inst['admin']);

        // "export" / "cleanup" were taken as a notification id.
        $this->get('/admin/notifications/export')->assertSuccessful();
        $this->delete('/admin/notifications/cleanup')->assertRedirect();

        // The student page had no view: it now shows the token list for the student.
        $this->get("/admin/fcm-tokens/student/{$this->inst['s1']}")
            ->assertRedirect(route('admin.fcm-tokens.index', ['search' => 'S1']));

        $expense = DB::table('institute_payments')->insertGetId([
            'branch_id' => $this->inst['b1'],
            'amount' => 1500,
            'payment_date' => '2026-10-10',
            'reason' => 'Electricity',
            'payment_type' => 'expense',
            'status' => 'paid',
            'user_id' => $this->inst['admin']->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get("/admin/institute-expenses/{$expense}")->assertOk()->assertSee('Admin');
        $this->get('/admin/institute-expenses')->assertOk();
    }

    /** The attendance page reads the API with the web login (Sanctum session). */
    public function test_attendance_page_can_read_a_student_with_the_web_login(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->post('/login', ['email' => 'admin@test.lk', 'password' => 'password123'])->assertRedirect();
        $this->app['auth']->forgetGuards();

        $this->withHeaders(['Referer' => 'http://localhost/admin/attendance'])
            ->postJson('/api/attendance/read', [
                'qr_code' => 'S1',
                'student_class_id' => $this->inst['c1'],
                'class_category_fee_id' => $this->inst['f1'],
            ])
            ->assertOk()
            // Fields the page shows
            ->assertJsonPath('data.enrollment.is_enrolled', true)
            ->assertJsonPath('data.enrollment.class_name', 'Maths 10 CMB')
            ->assertJsonStructure(['data' => [
                'student' => ['id', 'initial_name', 'guardian_mobile', 'qr_type'],
                'enrollment' => ['enrollment_id', 'student_class_id', 'class_category_fee_id', 'category_name', 'final_fee'],
                'last_payment',
                'attendance' => ['attended_classes', 'total_classes', 'attendance_percentage'],
            ]]);
    }

    public function test_pages_have_their_scripts(): void
    {
        $this->actingAs($this->inst['admin']);

        $this->get('/admin/attendance')->assertOk()
            ->assertSee('html5-qrcode@2.3.8', false)
            ->assertSee(str_replace('/', '\\/', route('api.attendance.read')), false)
            ->assertSee(str_replace('/', '\\/', route('api.attendance.store')), false)
            ->assertSee('markAttendanceBtn', false);

        $this->get('/admin/setting')->assertOk()->assertSee('function confirmImport()', false);

        $this->get('/admin/profile')->assertOk()->assertSee("querySelectorAll('.toggle-password')", false);
    }

    private function isPublic(string $uri): bool
    {
        foreach (self::PUBLIC_URIS as $pattern) {
            if ($uri === $pattern || fnmatch($pattern, $uri)) {
                return true;
            }
        }

        return false;
    }
}

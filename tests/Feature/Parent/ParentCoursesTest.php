<?php

namespace Tests\Feature\Parent;

use App\Models\CourseSession;
use App\Models\Notification;
use App\Models\NotificationSetting;
use App\Models\Student;
use App\Services\Course\CourseAttendanceService;
use App\Services\Course\CourseEnrollmentService;
use App\Services\Course\CoursePaymentService;
use App\Services\Course\CourseSessionService;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\Support\BuildsCourse;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Parent app: the student's courses (fee, installments, payments, class
 * days, attendance) and the installment reminder.
 */
class ParentCoursesTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;
    use BuildsCourse;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-01 09:00:00');
        $this->buildInstitute();
        Features::set('courses', true);
        $this->buildCourse();
        Cache::flush();

        $this->token = $this->postJson('/api/parent/v1/auth/login', ['username' => 'S1', 'password' => 'pass1234'])->json('token');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function api(string $path)
    {
        return $this->withToken($this->token)->postJson('/api/parent/v1' . $path);
    }

    private function enroll(string $plan = 'installment')
    {
        return app(CourseEnrollmentService::class)->enroll($this->inst['course'], Student::findOrFail($this->inst['s1']), $plan);
    }

    public function test_courses_show_fee_installments_payments_and_attendance(): void
    {
        $enrollment = $this->enroll();
        app(CoursePaymentService::class)->pay($enrollment, 2500, 'cash', 'manual_web', $this->inst['admin']->id);

        // Saturday 7 Nov: present
        Carbon::setTestNow('2026-11-07 09:00:00');
        $session = CourseSession::query()->where('course_id', $this->inst['course']->id)->whereDate('session_date', '2026-11-07')->firstOrFail();
        app(CourseAttendanceService::class)->mark($session, Student::findOrFail($this->inst['s1']), 'manual_web', $this->inst['admin']->id);
        // Sunday 8 Nov: cancelled
        $sunday = CourseSession::query()->where('course_id', $this->inst['course']->id)->whereDate('session_date', '2026-11-08')->firstOrFail();
        app(CourseSessionService::class)->cancel($sunday, 'Teacher sick', $this->inst['admin']->id);

        $course = $this->api('/courses')->assertOk()
            ->assertJsonPath('data.enabled', true)
            ->json('data.courses.0');

        $this->assertSame('Diploma in IT', $course['name']);
        $this->assertEquals(12000, $course['fees']['total_fee']);
        $this->assertEquals(2500, $course['fees']['paid']);
        $this->assertEquals(9500, $course['fees']['balance']);
        $this->assertSame('installment', $course['fees']['payment_plan']);
        $this->assertSame('paid', $course['fees']['installments'][0]['status']);
        $this->assertCount(1, $course['payments']);
        $this->assertSame(1, $course['attendance']['held']);
        $this->assertSame(1, $course['attendance']['attended']);
        $this->assertSame('Teacher sick', $course['cancelled_sessions'][0]['cancel_reason']);
        $this->assertNotEmpty($course['upcoming_sessions']);
    }

    public function test_dashboard_lists_the_courses_only_with_the_feature(): void
    {
        $this->enroll();

        $this->api('/dashboard')->assertJsonPath('data.courses.0.name', 'Diploma in IT');

        Features::set('courses', false);
        $this->api('/dashboard')->assertJsonPath('data.courses', []);
        $this->api('/courses')->assertOk()->assertJsonPath('data.enabled', false)->assertJsonPath('data.courses', []);
    }

    public function test_cancelled_enrollment_and_payment_are_not_shown(): void
    {
        $enrollment = $this->enroll();
        $payment = app(CoursePaymentService::class)->pay($enrollment, 1000, 'cash', 'manual_web', $this->inst['admin']->id);
        $payment->update(['status' => 'cancelled']);

        $this->api('/courses')->assertJsonPath('data.courses.0.fees.paid', 0)->assertJsonPath('data.courses.0.payments', []);

        $enrollment->update(['status' => 'cancelled']);
        $this->api('/courses')->assertJsonPath('data.courses', []);
    }

    private function reminders(): int
    {
        return Notification::query()->where('student_id', $this->inst['s1'])->where('title', 'like', 'Course installment due%')->count();
    }

    public function test_installment_reminder_three_days_before_and_on_the_due_date_once(): void
    {
        NotificationSetting::query()->updateOrCreate(['key' => NotificationSetting::COURSE_INSTALLMENT_REMINDER], [
            'label' => 'x', 'is_enabled' => true, 'channel' => NotificationSetting::PUSH,
        ]);
        NotificationSetting::clearCache();

        $enrollment = $this->enroll();
        $due = $enrollment->installments()->orderBy('installment_no')->skip(1)->first()->due_date->copy();

        Carbon::setTestNow($due->copy()->subDays(3)->setTime(9, 0));
        $this->artisan('course-installments:remind')->assertExitCode(0);
        $this->artisan('course-installments:remind')->assertExitCode(0); // run twice: still one
        $this->assertSame(1, $this->reminders()); // installment 2 (installment 1 is already overdue)

        Carbon::setTestNow($due->copy()->setTime(9, 0));
        $this->artisan('course-installments:remind');
        $this->assertSame(2, $this->reminders());
    }

    public function test_paid_installment_and_switched_off_setting_send_nothing(): void
    {
        $enrollment = $this->enroll();
        $due = $enrollment->installments()->orderBy('installment_no')->skip(1)->first()->due_date->copy();
        app(CoursePaymentService::class)->pay($enrollment, 12000, 'cash', 'manual_web', $this->inst['admin']->id);

        Carbon::setTestNow($due->copy()->subDays(3)->setTime(9, 0));
        $this->artisan('course-installments:remind');
        $this->assertSame(0, $this->reminders());

        NotificationSetting::query()->updateOrCreate(['key' => NotificationSetting::COURSE_INSTALLMENT_REMINDER], [
            'label' => 'x', 'is_enabled' => false, 'channel' => NotificationSetting::PUSH,
        ]);
        NotificationSetting::clearCache();
        $this->artisan('course-installments:remind');
        $this->assertSame(0, $this->reminders());
    }
}

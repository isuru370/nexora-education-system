<?php

namespace Tests\Feature\Course;

use App\Jobs\SendAttendanceSuccessSmsJob;
use App\Jobs\SendPaymentSms;
use App\Models\CourseSession;
use App\Models\NotificationSetting;
use App\Models\Student;
use App\Services\Course\CourseAttendanceService;
use App\Services\Course\CourseEnrollmentService;
use App\Services\Course\CoursePaymentService;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\Support\BuildsCourse;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/** Course income in the Institute Income report; course notices in Settings > Notifications. */
class CourseIncomeAndNoticesTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;
    use BuildsCourse;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-01 09:00:00');
        $this->buildInstitute();
        Features::set('courses', true);
        $this->buildCourse();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function setting(string $key, ?string $channel): void
    {
        NotificationSetting::query()->updateOrCreate(['key' => $key], [
            'label' => $key,
            'is_enabled' => $channel !== null,
            'channel' => $channel ?? NotificationSetting::PUSH,
        ]);
        NotificationSetting::clearCache();
    }

    public function test_course_income_is_institute_income(): void
    {
        $e = app(CourseEnrollmentService::class)->enroll($this->inst['course'], Student::findOrFail($this->inst['s1']), 'full');
        app(CoursePaymentService::class)->pay($e, 10000, 'cash', 'manual_web', $this->inst['admin']->id); // institute 30% = 3000

        $this->actingAs($this->inst['admin']);
        $query = '?from=2026-11-01&to=2026-11-30';

        $this->get('/reports/institute-income' . $query)->assertOk()
            ->assertSee('Institute share of course fees')
            ->assertSee('Course fees collected')
            ->assertSee('3,000.00')
            ->assertSee('By Course');

        $this->get('/reports/institute-income/courses' . $query)->assertOk()->assertSee('Diploma in IT')->assertSee('7,000.00');
        $this->get('/reports/institute-income/courses/export/excel' . $query)->assertOk();

        // Without the feature: no course lines, no tab
        Features::set('courses', false);
        $this->get('/reports/institute-income' . $query)->assertOk()->assertDontSee('course fees')->assertDontSee('By Course');
        $this->get('/reports/institute-income/courses' . $query)->assertNotFound();
    }

    public function test_settings_page_shows_course_notices_only_with_courses(): void
    {
        $this->actingAs($this->inst['admin']);

        $this->get('/admin/setting/notifications')->assertOk()
            ->assertSee('Course class changes')->assertSee('Course fee payment')->assertSee('Course attendance marked');

        Features::set('courses', false);
        $this->get('/admin/setting/notifications')->assertOk()->assertDontSee('Course fee payment');
    }

    public function test_payment_and_attendance_tell_the_parent_by_the_setting(): void
    {
        Queue::fake();
        $student = Student::findOrFail($this->inst['s1']);
        $e = app(CourseEnrollmentService::class)->enroll($this->inst['course'], $student, 'installment');
        $day = CourseSession::where('course_id', $this->inst['course']->id)->whereDate('session_date', '2026-11-01')->firstOrFail();

        // Push (default for both)
        app(CoursePaymentService::class)->pay($e, 2000);
        app(CourseAttendanceService::class)->mark($day, $student, 'qr_mobile');
        $this->assertDatabaseHas('notifications', ['student_id' => $student->id, 'title' => 'Course payment received']);
        $this->assertDatabaseHas('notifications', ['student_id' => $student->id, 'title' => 'Course attendance marked']);

        // SMS
        $this->setting(NotificationSetting::COURSE_PAYMENT, NotificationSetting::SMS);
        app(CoursePaymentService::class)->pay($e, 1000);
        Queue::assertPushed(SendPaymentSms::class, 1);

        // Off: nothing more is sent, the payment still works
        $this->setting(NotificationSetting::COURSE_PAYMENT, null);
        $this->setting(NotificationSetting::COURSE_ATTENDANCE, null);
        app(CoursePaymentService::class)->pay($e, 500);
        Queue::assertPushed(SendPaymentSms::class, 1);
        Queue::assertNotPushed(SendAttendanceSuccessSmsJob::class);
        $this->assertDatabaseCount('course_payments', 3);
    }
}

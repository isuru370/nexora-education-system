<?php

namespace Tests\Feature\Course;

use App\Jobs\SendClassScheduleSms;
use App\Models\CourseSession;
use App\Models\NotificationSetting;
use App\Models\Student;
use App\Services\Course\CourseAttendanceService;
use App\Services\Course\CourseEnrollmentService;
use App\Services\Course\CourseSessionService;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\Support\BuildsCourse;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Class day status: scheduled -> ongoing -> completed, or cancelled.
 */
class CourseSessionStatusTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;
    use BuildsCourse;

    private CourseSession $day; // Sunday 1 Nov 2026, 08:00 - 12:00

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-30 10:00:00');
        $this->buildInstitute();
        Features::set('courses', true);
        $this->buildCourse();

        foreach (['s1', 's2'] as $s) {
            app(CourseEnrollmentService::class)->enroll($this->inst['course'], Student::findOrFail($this->inst[$s]), 'installment');
        }

        $this->day = CourseSession::where('course_id', $this->inst['course']->id)->whereDate('session_date', '2026-11-01')->firstOrFail();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function channel(?string $channel): void
    {
        NotificationSetting::query()->updateOrCreate(['key' => NotificationSetting::COURSE_SCHEDULE], [
            'label' => 'Course class changes',
            'is_enabled' => $channel !== null,
            'channel' => $channel ?? NotificationSetting::SMS,
        ]);
        NotificationSetting::clearCache();
    }

    public function test_first_attendance_makes_the_day_ongoing_and_the_command_completes_it(): void
    {
        $this->assertSame('scheduled', $this->day->status);

        Carbon::setTestNow('2026-11-01 08:05:00');
        app(CourseAttendanceService::class)->mark($this->day, Student::findOrFail($this->inst['s1']), 'qr_mobile');
        $this->assertSame('ongoing', $this->day->fresh()->status);

        // Before the end time nothing changes
        Carbon::setTestNow('2026-11-01 11:59:00');
        $this->artisan('course-sessions:complete-finished')->assertExitCode(0);
        $this->assertSame('ongoing', $this->day->fresh()->status);

        // Late comers can still be marked while ongoing
        app(CourseAttendanceService::class)->mark($this->day->fresh(), Student::findOrFail($this->inst['s2']), 'manual_web');

        Carbon::setTestNow('2026-11-01 12:00:00');
        $this->artisan('course-sessions:complete-finished')->assertExitCode(0);
        $this->assertSame('completed', $this->day->fresh()->status);

        // Completed: no more marking
        $this->expectException(ValidationException::class);
        app(CourseAttendanceService::class)->mark($this->day->fresh(), Student::findOrFail($this->inst['s2']), 'manual_web');
    }

    public function test_a_day_nobody_attended_is_cancelled_after_its_end_time(): void
    {
        Carbon::setTestNow('2026-11-01 12:01:00');
        $this->artisan('course-sessions:complete-finished')->assertExitCode(0);

        $day = $this->day->fresh();
        $this->assertSame('cancelled', $day->status);
        $this->assertSame('No attendance was marked.', $day->cancel_reason);

        // Later days are untouched
        $this->assertSame('scheduled', CourseSession::whereDate('session_date', '2026-11-07')->value('status'));
    }

    public function test_the_command_does_nothing_when_courses_are_off(): void
    {
        Features::set('courses', false);
        Carbon::setTestNow('2026-11-01 12:01:00');

        $this->artisan('course-sessions:complete-finished')->assertExitCode(0);
        $this->assertSame('scheduled', $this->day->fresh()->status);
    }

    public function test_cancelling_a_day_sends_sms_to_parents(): void
    {
        Queue::fake();
        $this->channel(NotificationSetting::SMS);

        $told = app(CourseSessionService::class)->cancel($this->day, 'Poya holiday', $this->inst['admin']->id);

        $this->assertSame(2, $told);
        $this->assertSame('cancelled', $this->day->fresh()->status);
        Queue::assertPushed(SendClassScheduleSms::class, 2);

        // Restored before it ends
        app(CourseSessionService::class)->restore($this->day->fresh());
        $this->assertSame('scheduled', $this->day->fresh()->status);
    }

    public function test_cancelling_a_day_sends_a_parent_app_notification(): void
    {
        Queue::fake();
        $this->channel(NotificationSetting::PUSH);

        app(CourseSessionService::class)->cancel($this->day, 'Poya holiday', $this->inst['admin']->id);

        $this->assertDatabaseHas('notifications', ['student_id' => $this->inst['s1'], 'title' => 'Course class cancelled']);
        $this->assertDatabaseHas('notifications', ['student_id' => $this->inst['s2'], 'title' => 'Course class cancelled']);
        Queue::assertNotPushed(SendClassScheduleSms::class);
    }

    public function test_nothing_is_sent_when_course_class_notices_are_off(): void
    {
        Queue::fake();
        $this->channel(null);

        $this->assertSame(0, app(CourseSessionService::class)->cancel($this->day, 'Rain', null));
        Queue::assertNothingPushed();
    }

    public function test_a_started_day_cannot_be_cancelled(): void
    {
        Carbon::setTestNow('2026-11-01 08:01:00');

        $this->expectException(ValidationException::class);
        app(CourseSessionService::class)->cancel($this->day, 'Late', null);
    }

    public function test_admin_cancels_from_the_web(): void
    {
        Queue::fake();
        $this->channel(NotificationSetting::SMS);
        $this->actingAs($this->inst['admin']);

        $this->patch("/courses/{$this->inst['course']->id}/sessions/{$this->day->id}/status", ['status' => 'cancelled', 'cancel_reason' => 'Poya'])
            ->assertSessionHas('success', 'Class day cancelled. 2 parent(s) will be told.');

        $this->get("/courses/{$this->inst['course']->id}")->assertOk()->assertSee('Poya');

        $this->actingAs($this->inst['cashier']);
        $this->patch("/courses/{$this->inst['course']->id}/sessions/{$this->day->id}/status", ['status' => 'scheduled'])->assertForbidden();
    }
}

<?php

namespace Tests\Feature\Course;

use App\Jobs\SendClassScheduleSms;
use App\Models\CourseSession;
use App\Models\NotificationSetting;
use App\Models\Student;
use App\Services\Course\CourseEnrollmentService;
use App\Services\Course\CourseService;
use App\Services\Course\CourseSessionService;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\Support\BuildsCourse;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Each week day with its own time and hall; one class day can be changed,
 * and extra days added.
 */
class CourseScheduleDaysTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;
    use BuildsCourse;

    private int $hall2;
    private int $hall3;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-24 10:00:00');
        $this->buildInstitute();
        Features::set('courses', true);

        $hall = fn (string $name) => DB::table('class_halls')->insertGetId([
            'branch_id' => $this->inst['b1'], 'code' => $name, 'hall_name' => $name, 'is_active' => 1,
        ]);
        $this->hall2 = $hall('Hall 2');
        $this->hall3 = $hall('Hall 3');

        // Sunday 08:00-12:00 Hall 2, Friday 12:00-17:00 Hall 3
        $this->buildCourse(['days' => [
            ['day' => 'sunday', 'start_time' => '08:00', 'end_time' => '12:00', 'class_hall_id' => $this->hall2],
            ['day' => 'friday', 'start_time' => '12:00', 'end_time' => '17:00', 'class_hall_id' => $this->hall3],
        ]]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function sessionOn(string $date): ?CourseSession
    {
        return CourseSession::where('course_id', $this->inst['course']->id)->whereDate('session_date', $date)->first();
    }

    private function update(array $days): void
    {
        $course = $this->inst['course']->fresh();
        app(CourseService::class)->update($course, [
            'branch_id' => $course->branch_id, 'name' => $course->name, 'full_fee' => 12000, 'one_time_fee' => 10000,
            'start_date' => '2026-11-01', 'end_date' => '2027-04-30', 'days' => $days,
        ], [['teacher_id' => $this->inst['teacher'], 'percentage' => 35]]);
    }

    public function test_each_day_has_its_own_time_and_hall(): void
    {
        $sunday = $this->sessionOn('2026-11-01');
        $friday = $this->sessionOn('2026-11-06');

        $this->assertSame(['08:00', '12:00', $this->hall2], [substr($sunday->start_time, 0, 5), substr($sunday->end_time, 0, 5), $sunday->class_hall_id]);
        $this->assertSame(['12:00', '17:00', $this->hall3], [substr($friday->start_time, 0, 5), substr($friday->end_time, 0, 5), $friday->class_hall_id]);
        $this->assertNull($this->sessionOn('2026-11-07')); // Saturday: no class

        $this->assertSame(
            'Fri 12:00 PM - 05:00 PM (Hall 3) · Sun 08:00 AM - 12:00 PM (Hall 2)',
            $this->inst['course']->fresh()->scheduleLabel()
        );
    }

    public function test_the_same_day_twice_is_refused(): void
    {
        $this->expectException(ValidationException::class);
        $this->update([
            ['day' => 'sunday', 'start_time' => '08:00', 'end_time' => '12:00'],
            ['day' => 'sunday', 'start_time' => '14:00', 'end_time' => '16:00'],
        ]);
    }

    public function test_change_one_day_and_add_an_extra_day(): void
    {
        Queue::fake();
        NotificationSetting::query()->updateOrCreate(['key' => NotificationSetting::CLASS_SCHEDULE], ['label' => 'Class schedule', 'is_enabled' => true, 'channel' => 'sms']);
        NotificationSetting::clearCache();
        app(CourseEnrollmentService::class)->enroll($this->inst['course'], Student::findOrFail($this->inst['s1']), 'installment');

        $sessions = app(CourseSessionService::class);

        // Sunday 8 Nov moves to Monday 9 Nov, 14:00-16:00, Hall 3
        $told = $sessions->edit($this->sessionOn('2026-11-08'), ['session_date' => '2026-11-09', 'start_time' => '14:00', 'end_time' => '16:00', 'class_hall_id' => $this->hall3]);
        $this->assertSame(1, $told);
        $moved = $this->sessionOn('2026-11-09');
        $this->assertTrue($moved->is_custom);
        $this->assertSame('2026-11-08', $moved->original_date->toDateString());
        $this->assertNull($this->sessionOn('2026-11-08'));

        // Extra Wednesday class
        $added = $sessions->add($this->inst['course'], ['session_date' => '2026-11-11', 'start_time' => '15:00', 'end_time' => '17:00', 'class_hall_id' => $this->hall2]);
        $this->assertTrue($added['session']->is_custom);
        $this->assertNull($added['session']->original_date);
        Queue::assertPushed(SendClassScheduleSms::class, 2);

        // One class per day; not in the past
        foreach ([['2026-11-01', '09:00', '10:00'], ['2026-10-20', '09:00', '10:00'], ['2026-11-12', '10:00', '09:00']] as [$d, $s, $e]) {
            try {
                $sessions->add($this->inst['course'], ['session_date' => $d, 'start_time' => $s, 'end_time' => $e]);
                $this->fail("Added {$d} {$s}-{$e}");
            } catch (ValidationException $ex) {
                $this->assertNotEmpty($ex->errors());
            }
        }

        // Editing the weekly schedule keeps both hand-made days and does not bring 8 Nov back
        $this->update([
            ['day' => 'sunday', 'start_time' => '09:00', 'end_time' => '11:00', 'class_hall_id' => $this->hall2],
            ['day' => 'friday', 'start_time' => '12:00', 'end_time' => '17:00', 'class_hall_id' => $this->hall3],
        ]);

        $this->assertNull($this->sessionOn('2026-11-08'));
        $this->assertSame('14:00', substr($this->sessionOn('2026-11-09')->start_time, 0, 5));
        $this->assertNotNull($this->sessionOn('2026-11-11'));
        $this->assertSame('09:00', substr($this->sessionOn('2026-11-15')->start_time, 0, 5)); // other Sundays follow the new time
    }

    public function test_started_days_cannot_be_changed(): void
    {
        Carbon::setTestNow('2026-11-01 08:30:00');

        $this->expectException(ValidationException::class);
        app(CourseSessionService::class)->edit($this->sessionOn('2026-11-01'), ['session_date' => '2026-11-02', 'start_time' => '08:00', 'end_time' => '10:00']);
    }

    public function test_web_add_and_edit_days(): void
    {
        $course = $this->inst['course'];
        $this->actingAs($this->inst['admin']);

        $this->get("/courses/{$course->id}")->assertOk()->assertSee('Add a class day')->assertSee('Hall 3');

        $this->post("/courses/{$course->id}/sessions", ['session_date' => '2026-11-11', 'start_time' => '15:00', 'end_time' => '17:00', 'class_hall_id' => $this->hall2])
            ->assertRedirect()->assertSessionHas('success');
        $this->get("/courses/{$course->id}")->assertOk()->assertSee('Extra');

        $session = $this->sessionOn('2026-11-08');
        $this->get("/courses/{$course->id}/sessions/{$session->id}")->assertOk()->assertSee('Change date / time / hall');
        $this->put("/courses/{$course->id}/sessions/{$session->id}", ['session_date' => '2026-11-01', 'start_time' => '08:00', 'end_time' => '12:00'])
            ->assertSessionHasErrors('session_date'); // 1 Nov already has a class
        $this->put("/courses/{$course->id}/sessions/{$session->id}", ['session_date' => '2026-11-10', 'start_time' => '08:00', 'end_time' => '12:00', 'class_hall_id' => $this->hall3])
            ->assertSessionHas('success');

        $this->actingAs($this->inst['cashier']);
        $this->post("/courses/{$course->id}/sessions", ['session_date' => '2026-11-12', 'start_time' => '15:00', 'end_time' => '17:00'])->assertForbidden();
        $this->get("/courses/{$course->id}/sessions/{$session->id}")->assertOk()->assertDontSee('Change date / time / hall');
    }
}

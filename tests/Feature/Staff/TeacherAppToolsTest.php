<?php

namespace Tests\Feature\Staff;

use App\Models\ClassSchedule;
use App\Models\CourseSession;
use App\Models\TeacherScheduleRequest;
use App\Models\User;
use App\Support\Features;
use App\Support\TeacherAppRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsCourse;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Teacher side of the staff app: attendance, class day changes and own
 * payments, each behind a Settings > Teacher App switch.
 */
class TeacherAppToolsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;
    use BuildsCourse;

    private User $teacherUser;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-13 14:30:00'); // Tuesday, class 15:00 - 17:00
        Queue::fake(); // parent SMS jobs
        $this->buildInstitute();

        $type = DB::table('user_types')->insertGetId(['name' => 'TEACHER', 'code' => 'TEACHER', 'is_active' => 1]);
        $this->teacherUser = User::forceCreate(['name' => 'Nimal', 'email' => 'nimal@test.lk', 'password' => 'password123', 'user_type_id' => $type, 'is_active' => 1]);
        DB::table('teachers')->where('id', $this->inst['teacher'])->update(['user_id' => $this->teacherUser->id]);
        DB::table('student_classes')->update(['is_ongoing' => 1]);
        DB::table('teacher_branches')->insert([
            ['teacher_id' => $this->inst['teacher'], 'branch_id' => $this->inst['b1']],
            ['teacher_id' => $this->inst['teacher'], 'branch_id' => $this->inst['b2']],
        ]);

        Sanctum::actingAs($this->teacherUser);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function rules(array $on): void
    {
        TeacherAppRules::save(array_fill_keys($on, true));
    }

    public function test_everything_is_off_until_the_institute_turns_it_on(): void
    {
        $this->getJson('/api/v1/teacher/features')->assertOk()
            ->assertJsonPath('data.attendance', false)
            ->assertJsonPath('data.schedule', false)
            ->assertJsonPath('data.payments', false);

        $this->getJson('/api/v1/teacher/attendance/today')->assertStatus(403);
        $this->getJson('/api/v1/teacher/schedule')->assertStatus(403);
        $this->getJson('/api/v1/teacher/payments/month')->assertStatus(403);

        $this->rules(['attendance', 'payments']);

        $this->getJson('/api/v1/teacher/features')->assertJsonPath('data.attendance', true)->assertJsonPath('data.schedule', false);
        $this->getJson('/api/v1/teacher/attendance/today')->assertOk();
        $this->getJson('/api/v1/teacher/schedule')->assertStatus(403);
    }

    public function test_teacher_marks_attendance_in_own_class_today(): void
    {
        $this->rules(['attendance']);
        $id = $this->inst['schedule'];

        $this->getJson('/api/v1/teacher/attendance/today')->assertOk()
            ->assertJsonCount(1, 'data.classes')
            ->assertJsonPath('data.classes.0.title', 'Maths 10 CMB')
            ->assertJsonPath('data.classes.0.category', 'Theory')
            ->assertJsonPath('data.classes.0.enrolled', 2)
            ->assertJsonPath('data.classes.0.present', 0);

        $this->postJson("/api/v1/teacher/attendance/class/{$id}/mark", ['student_ids' => [$this->inst['s1'], $this->inst['s3']]])
            ->assertOk()
            ->assertJsonPath('data.marked', 1)
            ->assertJsonPath('data.failed.' . $this->inst['s3'], 'E. Kandy is not in this class.');

        // Again: already marked
        $this->postJson("/api/v1/teacher/attendance/class/{$id}/mark", ['student_ids' => [$this->inst['s1']]])
            ->assertJsonPath('data.marked', 0)->assertJsonPath('data.already', 1);

        $this->postJson("/api/v1/teacher/attendance/class/{$id}/scan", ['qr_code' => 'S2'])->assertOk()
            ->assertJsonPath('data.student.name', 'B. Perera');
        $this->postJson("/api/v1/teacher/attendance/class/{$id}/scan", ['qr_code' => 'S3'])->assertStatus(422);

        $students = $this->getJson("/api/v1/teacher/attendance/class/{$id}/students")->assertOk()->json('data.students');
        $this->assertCount(2, $students);
        $this->assertTrue(collect($students)->every(fn ($s) => $s['present']));
        $this->assertArrayNotHasKey('guardian_mobile', $students[0]);

        $this->assertSame('manual_mobile', DB::table('student_attendances')->where('class_schedule_id', $id)->where('student_id', $this->inst['s1'])->value('mark_method'));
        $this->assertSame('ongoing', ClassSchedule::find($id)->status);
    }

    public function test_attendance_only_for_own_classes_and_today(): void
    {
        $this->rules(['attendance']);

        $otherTeacher = DB::table('teachers')->insertGetId([
            'custom_id' => 'T9', 'full_name' => 'Other', 'initials' => 'O.', 'email' => 'o@test.lk',
            'mobile' => '0770000009', 'nic' => '900000009V', 'bday' => '1990-01-01', 'gender' => 'male', 'address1' => 'X', 'is_active' => 1,
        ]);
        DB::table('student_classes')->where('id', $this->inst['c1'])->update(['teacher_id' => $otherTeacher]);
        $this->getJson("/api/v1/teacher/attendance/class/{$this->inst['schedule']}/students")->assertNotFound();

        DB::table('student_classes')->where('id', $this->inst['c1'])->update(['teacher_id' => $this->inst['teacher']]);
        DB::table('class_schedules')->where('id', $this->inst['schedule'])->update(['class_date' => '2026-10-20']);
        $this->postJson("/api/v1/teacher/attendance/class/{$this->inst['schedule']}/mark", ['student_ids' => [$this->inst['s1']]])
            ->assertStatus(422);
    }

    public function test_class_day_change_waits_for_admin_approval(): void
    {
        $this->rules(['schedule']);
        $id = $this->inst['schedule'];

        // Today's class can only be cancelled, not changed
        $this->getJson('/api/v1/teacher/schedule')->assertJsonPath('data.schedules.0.can_change', false)->assertJsonPath('data.schedules.0.can_cancel', true);
        $this->postJson('/api/v1/teacher/schedule/requests', [
            'kind' => 'class', 'action' => 'update', 'id' => $id, 'date' => '2026-10-14', 'start_time' => '16:00', 'end_time' => '18:00',
        ])->assertStatus(422);

        Carbon::setTestNow('2026-10-12 10:00:00'); // Monday

        $this->getJson('/api/v1/teacher/schedule')->assertOk()
            ->assertJsonPath('data.auto_apply', false)
            ->assertJsonPath('data.schedules.0.id', $id)
            ->assertJsonPath('data.schedules.0.can_change', true);

        $this->getJson('/api/v1/teacher/schedule/options')->assertOk()
            ->assertJsonPath('data.classes.0.name', 'Maths 10 CMB')
            ->assertJsonPath('data.classes.0.categories.0.name', 'Theory');

        $this->postJson('/api/v1/teacher/schedule/requests', [
            'kind' => 'class', 'action' => 'update', 'id' => $id,
            'date' => '2026-10-14', 'start_time' => '16:00', 'end_time' => '18:00',
        ])->assertCreated()
            ->assertJsonPath('message', 'Sent to the admin for approval.')
            ->assertJsonPath('data.request.status', 'pending');

        // Not changed yet; a second request for the same day is refused
        $this->assertSame('2026-10-13', ClassSchedule::find($id)->class_date->toDateString());
        $this->assertSame(1, TeacherScheduleRequest::count());
        $this->postJson('/api/v1/teacher/schedule/requests', ['kind' => 'class', 'action' => 'cancel', 'id' => $id, 'reason' => 'Sick'])
            ->assertStatus(422);

        $this->getJson('/api/v1/teacher/schedule')->assertJsonPath('data.schedules.0.pending_request', true);

        // Admin approves on the web
        $request = TeacherScheduleRequest::firstOrFail();
        $this->actingAs($this->inst['admin'])
            ->get(route('admin.setting.teacher-requests'))->assertOk()->assertSee('Nimal Perera')->assertSee('Change');
        $this->actingAs($this->inst['admin'])
            ->post(route('admin.setting.teacher-requests.approve', $request->id))->assertSessionHasNoErrors();

        $schedule = ClassSchedule::find($id);
        $this->assertSame('2026-10-14', $schedule->class_date->toDateString());
        $this->assertSame('16:00', substr($schedule->start_time, 0, 5));
        $this->assertSame('approved', $request->fresh()->status);
        $this->assertSame($this->inst['admin']->id, $request->fresh()->reviewed_by);
    }

    public function test_admin_can_reject_and_teacher_can_withdraw(): void
    {
        $this->rules(['schedule']);
        $id = $this->inst['schedule'];

        $first = $this->postJson('/api/v1/teacher/schedule/requests', ['kind' => 'class', 'action' => 'cancel', 'id' => $id, 'reason' => 'Sick'])
            ->assertCreated()->json('data.request.id');

        $this->actingAs($this->inst['admin'])
            ->post(route('admin.setting.teacher-requests.reject', $first), ['review_note' => 'Find a relief teacher']);
        $this->assertSame('rejected', TeacherScheduleRequest::find($first)->status);
        $this->assertSame('scheduled', ClassSchedule::find($id)->status);

        Sanctum::actingAs($this->teacherUser);
        $second = $this->postJson('/api/v1/teacher/schedule/requests', ['kind' => 'class', 'action' => 'cancel', 'id' => $id, 'reason' => 'Sick'])
            ->assertCreated()->json('data.request.id');
        $this->deleteJson("/api/v1/teacher/schedule/requests/{$second}")->assertOk();
        $this->assertNull(TeacherScheduleRequest::find($second));

        $this->getJson('/api/v1/teacher/schedule/requests')->assertOk()
            ->assertJsonPath('data.requests.0.status', 'rejected')
            ->assertJsonPath('data.requests.0.review_note', 'Find a relief teacher');
    }

    public function test_auto_mode_applies_at_once(): void
    {
        $this->rules(['schedule', 'schedule_auto']);
        $id = $this->inst['schedule'];

        // Extra day
        $this->postJson('/api/v1/teacher/schedule/requests', [
            'kind' => 'class', 'action' => 'create', 'id' => $this->inst['c1'], 'class_category_fee_id' => $this->inst['f1'],
            'date' => '2026-10-16', 'start_time' => '15:00', 'end_time' => '17:00', 'note' => 'Revision',
        ])->assertCreated()
            ->assertJsonPath('message', 'Done. Parents are told.')
            ->assertJsonPath('data.request.status', 'approved');

        $this->assertTrue(ClassSchedule::where('student_class_id', $this->inst['c1'])->whereDate('class_date', '2026-10-16')->exists());

        // Cancel
        $this->postJson('/api/v1/teacher/schedule/requests', ['kind' => 'class', 'action' => 'cancel', 'id' => $id, 'reason' => 'Sick'])
            ->assertCreated();
        $schedule = ClassSchedule::find($id);
        $this->assertSame('cancelled', $schedule->status);
        $this->assertSame($this->teacherUser->id, (int) $schedule->cancelled_by);
        Queue::assertPushed(\App\Jobs\SendClassScheduleSms::class); // parents told

        // A failure is returned and nothing is kept
        $this->postJson('/api/v1/teacher/schedule/requests', [
            'kind' => 'class', 'action' => 'create', 'id' => $this->inst['c1'], 'class_category_fee_id' => $this->inst['f1'],
            'date' => '2026-10-16', 'start_time' => '15:00', 'end_time' => '17:00',
        ])->assertStatus(422);
        $this->assertSame(2, TeacherScheduleRequest::count());
    }

    public function test_bad_requests_are_refused(): void
    {
        $this->rules(['schedule']);

        // Past time
        $this->postJson('/api/v1/teacher/schedule/requests', [
            'kind' => 'class', 'action' => 'create', 'id' => $this->inst['c1'], 'class_category_fee_id' => $this->inst['f1'],
            'date' => '2026-10-12', 'start_time' => '15:00', 'end_time' => '17:00',
        ])->assertStatus(422);

        // End before start
        $this->postJson('/api/v1/teacher/schedule/requests', [
            'kind' => 'class', 'action' => 'update', 'id' => $this->inst['schedule'],
            'date' => '2026-10-20', 'start_time' => '17:00', 'end_time' => '15:00',
        ])->assertStatus(422);

        // Cancel without a reason
        $this->postJson('/api/v1/teacher/schedule/requests', ['kind' => 'class', 'action' => 'cancel', 'id' => $this->inst['schedule']])
            ->assertStatus(422);

        // Hall of another branch
        $hall = DB::table('class_halls')->insertGetId(['branch_id' => $this->inst['b2'], 'hall_name' => 'KDY Hall', 'code' => 'KH', 'is_active' => 1]);
        $this->postJson('/api/v1/teacher/schedule/requests', [
            'kind' => 'class', 'action' => 'update', 'id' => $this->inst['schedule'],
            'date' => '2026-10-20', 'start_time' => '15:00', 'end_time' => '17:00', 'class_hall_id' => $hall,
        ])->assertStatus(422);

        $this->assertSame(0, TeacherScheduleRequest::count());
    }

    public function test_course_days_and_course_attendance(): void
    {
        Carbon::setTestNow('2026-10-31 07:00:00'); // Saturday before the course starts
        Features::set('courses', true);
        $this->buildCourse();
        $this->rules(['schedule', 'schedule_auto', 'attendance']);

        $session = CourseSession::where('course_id', $this->inst['course']->id)->whereDate('session_date', '2026-11-01')->firstOrFail();

        $this->getJson('/api/v1/teacher/schedule')->assertOk()
            ->assertJsonFragment(['type' => 'course', 'id' => $session->id, 'title' => 'Diploma in IT']);

        $this->postJson('/api/v1/teacher/schedule/requests', [
            'kind' => 'course', 'action' => 'update', 'id' => $session->id,
            'date' => '2026-11-01', 'start_time' => '09:00', 'end_time' => '13:00',
        ])->assertCreated()->assertJsonPath('data.request.status', 'approved');

        $this->assertSame('09:00', substr($session->fresh()->start_time, 0, 5));

        // Class day: enrolled student marked from the list
        Carbon::setTestNow('2026-11-01 09:10:00');
        DB::table('course_enrollments')->insert([
            'course_id' => $this->inst['course']->id, 'student_id' => $this->inst['s1'], 'branch_id' => $this->inst['b1'],
            'payment_plan' => 'full', 'total_fee' => 10000, 'status' => 'active', 'enrolled_at' => '2026-10-30',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->getJson('/api/v1/teacher/attendance/today')->assertOk()
            ->assertJsonFragment(['type' => 'course', 'id' => $session->id, 'enrolled' => 1]);

        $this->postJson("/api/v1/teacher/attendance/course/{$session->id}/mark", ['student_ids' => [$this->inst['s1']]])
            ->assertOk()->assertJsonPath('data.marked', 1);
        $this->assertSame(1, DB::table('course_attendances')->where('course_session_id', $session->id)->count());
    }

    public function test_teacher_sees_own_payments_without_phone_numbers(): void
    {
        $this->rules(['payments']);

        $this->getJson('/api/v1/teacher/payments/branches')->assertOk()->assertJsonCount(2, 'data.branches');

        $this->getJson('/api/v1/teacher/payments/month?branch_id=' . $this->inst['b1'] . '&year=2026&month=10')->assertOk()
            ->assertJsonPath('data.branch', 'Colombo')
            ->assertJsonPath('data.salary_status', 'pending')
            ->assertJsonStructure(['data' => ['teacher_earnings', 'advance', 'welfare', 'net_payable', 'earnings', 'deductions']]);

        $this->getJson('/api/v1/teacher/payments/deductions?year=2026&month=10')->assertOk()
            ->assertJsonStructure(['data' => ['totals' => ['advance', 'deduction', 'other', 'welfare'], 'payments', 'welfare']]);

        $this->getJson('/api/v1/teacher/payments/history')->assertOk()->assertJsonCount(12, 'data.months');

        $students = $this->getJson('/api/v1/teacher/payments/students?branch_id=' . $this->inst['b1'] . '&year=2026&month=10')->assertOk();
        $students->assertJsonPath('data.summary.paid_students', 1)
            ->assertJsonPath('data.summary.unpaid_students', 1)
            ->assertJsonPath('data.classes.0.categories.0.students.unpaid.0.initial_name', 'B. Perera');
        $this->assertStringNotContainsString('guardian_mobile', $students->getContent());
        $this->assertStringNotContainsString('0771234567', $students->getContent());

        // Not the teacher's branch
        $b3 = DB::table('branches')->insertGetId(['name' => 'Galle', 'code' => 'GLL', 'is_active' => 1]);
        $this->getJson("/api/v1/teacher/payments/month?branch_id={$b3}")->assertStatus(403);

        $this->getJson('/api/v1/teacher/payments/courses')->assertOk()->assertJsonPath('data.enabled', false);
    }

    public function test_settings_page_for_admins_only(): void
    {
        $this->actingAs($this->inst['admin'])->get(route('admin.setting.teacher-app'))->assertOk()->assertSee('Teacher App');

        $this->actingAs($this->inst['admin'])->put(route('admin.setting.teacher-app.update'), [
            'attendance' => '1', 'schedule' => '1', 'schedule_auto' => '0', 'payments' => '1',
        ])->assertRedirect(route('admin.setting.teacher-app'));

        $this->assertSame(['attendance' => true, 'schedule' => true, 'schedule_auto' => false, 'payments' => true], TeacherAppRules::all());

        $this->actingAs($this->inst['cashier'])->get(route('admin.setting.teacher-app'))->assertForbidden();
        $this->actingAs($this->teacherUser)->put(route('admin.setting.teacher-app.update'), ['schedule_auto' => '1'])->assertForbidden();
        $this->assertFalse(TeacherAppRules::allows('schedule_auto'));
    }
}

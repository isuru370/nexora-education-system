<?php

namespace Tests\Feature\Course;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CoursePayment;
use App\Models\CourseSession;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsCourse;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

class CourseWebTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;
    use BuildsCourse;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-01 09:00:00'); // a Sunday class day
        $this->buildInstitute();
        DB::table('students')->whereKey($this->inst['s1'])->update(['custom_id' => 'S1']);
        DB::table('students')->whereKey($this->inst['s3'])->update(['custom_id' => 'S3']);
        Features::set('courses', true);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_courses_are_hidden_when_the_feature_is_off(): void
    {
        Features::set('courses', false);
        $this->actingAs($this->inst['admin']);

        $this->get('/courses')->assertNotFound();
        $this->get('/course-payments')->assertNotFound();
        $this->get('/dashboard')->assertOk()->assertDontSee('Course Payments');

        Features::set('courses', true);
        $this->get('/dashboard')->assertOk()->assertSee('Course Payments');
    }

    public function test_admin_creates_a_course_from_the_form(): void
    {
        $teacherB = DB::table('teachers')->insertGetId([
            'custom_id' => 'T2', 'full_name' => 'Sunil Silva', 'initials' => 'S.', 'email' => 't2@test.lk',
            'mobile' => '0770000001', 'nic' => '900000001V', 'bday' => '1990-01-01', 'gender' => 'male',
            'address1' => 'Colombo', 'is_active' => 1,
        ]);

        $this->actingAs($this->inst['admin']);
        $this->get('/courses/create')->assertOk()->assertSee('Teachers and income share');

        $form = [
            'branch_id' => $this->inst['b1'],
            'name' => 'Diploma in English',
            'full_fee' => 12000,
            'one_time_fee' => 10000,
            'start_date' => '2026-11-01',
            'end_date' => '2027-04-30',
            'days' => [
                ['day' => 'saturday', 'start_time' => '08:00', 'end_time' => '12:00', 'class_hall_id' => ''],
                ['day' => '', 'start_time' => '', 'end_time' => '', 'class_hall_id' => ''], // empty row from the form
            ],
            'teachers' => [
                ['teacher_id' => $this->inst['teacher'], 'percentage' => 35],
                ['teacher_id' => $teacherB, 'percentage' => 35],
                ['teacher_id' => '', 'percentage' => ''], // empty row from the form
            ],
        ];

        // Shares over 100% are refused
        $bad = $form;
        $bad['teachers'][1]['percentage'] = 70;
        $this->from('/courses/create')->post('/courses', $bad)->assertRedirect('/courses/create')->assertSessionHasErrors('teachers');

        $this->post('/courses', $form)->assertRedirect();
        $course = Course::where('name', 'Diploma in English')->firstOrFail();

        $this->assertSame(6, $course->installment_count);
        $this->assertSame(25, $course->sessions()->count());
        $this->assertSame(30.0, $course->institutePercentage());

        $this->get("/courses/{$course->id}")->assertOk()->assertSee('Diploma in English')->assertSee('Sunil Silva');
        $this->get("/courses/{$course->id}/edit")->assertOk()->assertSee('value="08:00"', false);

        // The same day twice is refused
        $twice = $form;
        $twice['days'][1] = ['day' => 'saturday', 'start_time' => '13:00', 'end_time' => '15:00'];
        $this->from('/courses/create')->post('/courses', $twice)->assertSessionHasErrors('days.1.day');
        $this->get('/courses')->assertOk()->assertSee('Diploma in English');
    }

    public function test_cashier_enrolls_takes_a_payment_and_marks_attendance(): void
    {
        $course = $this->buildCourse();
        $this->actingAs($this->inst['cashier']);

        // Cashiers cannot create or change courses
        $this->get('/courses/create')->assertForbidden();
        $this->get("/courses/{$course->id}/edit")->assertForbidden();

        $this->post("/courses/{$course->id}/enrollments", ['student_code' => 'S1', 'payment_plan' => 'installment'])->assertRedirect();
        $enrollment = CourseEnrollment::where('course_id', $course->id)->firstOrFail();

        // Student of another branch / unknown code
        $this->post("/courses/{$course->id}/enrollments", ['student_code' => 'S3', 'payment_plan' => 'full'])->assertSessionHasErrors('student_code');
        $this->post("/courses/{$course->id}/enrollments", ['student_code' => 'NOPE', 'payment_plan' => 'full'])->assertSessionHasErrors('student_code');

        $this->get("/courses/{$course->id}/enrollments/{$enrollment->id}")->assertOk()->assertSee('Installment 1: 2,000.00');

        $this->post("/courses/{$course->id}/enrollments/{$enrollment->id}/payments", ['amount' => 2000, 'payment_method' => 'cash'])
            ->assertRedirect();
        $payment = CoursePayment::firstOrFail();

        $this->get("/course-payments/{$payment->id}/receipt")->assertOk()
            ->assertSee($payment->receipt_number)->assertSee('10,000.00'); // balance

        $this->post("/courses/{$course->id}/enrollments/{$enrollment->id}/payments", ['amount' => 20000, 'payment_method' => 'cash'])
            ->assertSessionHasErrors('amount');

        // Cashiers cannot cancel payments
        $this->patch("/course-payments/{$payment->id}/cancel", ['cancel_reason' => 'x'])->assertForbidden();

        // Attendance
        $session = CourseSession::where('course_id', $course->id)->whereDate('session_date', '2026-11-01')->firstOrFail();
        $this->get("/courses/{$course->id}/sessions/{$session->id}")->assertOk()->assertSee('Mark present');
        $this->post("/courses/{$course->id}/sessions/{$session->id}/attendance", ['student_code' => 'S1'])->assertSessionHas('success');
        $this->post("/courses/{$course->id}/sessions/{$session->id}/attendance", ['student_code' => 'S1'])->assertSessionHas('warning');
        $this->assertDatabaseHas('course_attendances', ['course_session_id' => $session->id, 'student_id' => $this->inst['s1'], 'mark_method' => 'qr_web']);

        $this->get("/courses/{$course->id}")->assertOk()->assertSee('A. Silva');
        $this->get('/course-payments')->assertOk()->assertSee($payment->receipt_number);
    }

    public function test_admin_cancels_payment_and_pays_teacher(): void
    {
        $course = $this->buildCourse();
        $this->actingAs($this->inst['admin']);

        $this->post("/courses/{$course->id}/enrollments", ['student_code' => 'S1', 'payment_plan' => 'full']);
        $enrollment = CourseEnrollment::firstOrFail();
        $this->post("/courses/{$course->id}/enrollments/{$enrollment->id}/payments", ['amount' => 10000, 'payment_method' => 'cash']);
        $payment = CoursePayment::firstOrFail();

        $this->get('/course-teacher-payments')->assertOk()->assertSee('3,500.00');

        $this->post("/courses/{$course->id}/payouts", ['teacher_id' => $this->inst['teacher'], 'amount' => 3500, 'payment_method' => 'cash'])->assertRedirect();
        $this->post("/courses/{$course->id}/payouts", ['teacher_id' => $this->inst['teacher'], 'amount' => 1, 'payment_method' => 'cash'])->assertSessionHasErrors('amount');
        $this->assertDatabaseHas('course_teacher_payouts', ['teacher_id' => $this->inst['teacher'], 'amount' => 3500, 'status' => 'paid']);

        $this->patch("/course-payments/{$payment->id}/cancel", ['cancel_reason' => 'Wrong student'])->assertRedirect();
        $this->assertSame('cancelled', $payment->fresh()->status);
        $this->get("/courses/{$course->id}")->assertOk();
    }

    public function test_other_branch_users_cannot_open_the_course(): void
    {
        $course = $this->buildCourse(['branch_id' => $this->inst['b2']]);
        $this->actingAs($this->inst['cashier']); // Colombo only

        $this->get("/courses/{$course->id}")->assertNotFound();
        $this->get('/courses')->assertOk()->assertDontSee('Diploma in IT');
        $this->post("/courses/{$course->id}/enrollments", ['student_code' => 'S1', 'payment_plan' => 'full'])->assertNotFound();
    }

    public function test_guests_go_to_login(): void
    {
        $this->get('/courses')->assertRedirect('/login');
    }
}

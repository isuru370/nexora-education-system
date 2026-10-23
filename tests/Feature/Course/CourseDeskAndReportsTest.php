<?php

namespace Tests\Feature\Course;

use App\Models\CourseEnrollment;
use App\Models\CoursePayment;
use App\Models\CourseTeacherPayout;
use App\Models\Student;
use App\Services\Course\CourseAttendanceService;
use App\Services\Course\CourseEnrollmentService;
use App\Services\Course\CoursePaymentService;
use App\Services\Course\CourseReportService;
use App\Services\Course\CourseTeacherEarningService;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsCourse;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

class CourseDeskAndReportsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;
    use BuildsCourse;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-01 09:00:00');
        $this->buildInstitute();
        DB::table('students')->whereKey($this->inst['s1'])->update(['custom_id' => 'S1']);
        DB::table('students')->whereKey($this->inst['s2'])->update(['custom_id' => 'S2']);
        Features::set('courses', true);
        $this->buildCourse();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** S1 installments (paid 2000), S2 at once (paid 10000), S1 present today, teacher A paid 1000. */
    private function activity(): void
    {
        $course = $this->inst['course'];
        $e1 = app(CourseEnrollmentService::class)->enroll($course, Student::findOrFail($this->inst['s1']), 'installment', $this->inst['admin']->id);
        $e2 = app(CourseEnrollmentService::class)->enroll($course, Student::findOrFail($this->inst['s2']), 'full', $this->inst['admin']->id);
        app(CoursePaymentService::class)->pay($e1, 2000, 'cash', 'manual_web', $this->inst['admin']->id);
        app(CoursePaymentService::class)->pay($e2, 10000, 'bank_transfer', 'manual_web', $this->inst['admin']->id);
        app(CourseAttendanceService::class)->mark($course->sessions()->whereDate('session_date', '2026-11-01')->first(), Student::findOrFail($this->inst['s1']), 'qr_web');
        app(CourseTeacherEarningService::class)->payout($course, $this->inst['teacher'], 1000, 'cash', null, $this->inst['admin']->id);
    }

    public function test_enrollment_desk(): void
    {
        $this->actingAs($this->inst['cashier']);

        $this->get('/course-desk/enroll')->assertOk()->assertSee('Scan QR or type student ID');
        $this->get('/course-desk/enroll?code=NOPE')->assertOk()->assertSee('No student found');

        $this->get('/course-desk/enroll?code=S1')->assertOk()
            ->assertSee('A. Silva')->assertSee('Courses to join')->assertSee('Diploma in IT')
            ->assertSee('Pay at once · 10,000.00');

        $this->post("/courses/{$this->inst['course']->id}/enrollments", ['student_code' => 'S1', 'payment_plan' => 'full'])->assertRedirect();

        $this->get('/course-desk/enroll?code=S1')->assertOk()->assertSee('Already in')->assertSee('No other open courses');
    }

    public function test_payment_desk(): void
    {
        $this->actingAs($this->inst['cashier']);
        $this->get('/course-desk/payment?code=S1')->assertOk()->assertSee('Not in any course');

        app(CourseEnrollmentService::class)->enroll($this->inst['course'], Student::findOrFail($this->inst['s1']), 'installment');

        $this->get('/course-desk/payment?code=S1')->assertOk()
            ->assertSee('Diploma in IT')->assertSee('12,000.00')->assertSee('value="2000"', false);

        $enrollment = CourseEnrollment::firstOrFail();
        $this->post("/courses/{$this->inst['course']->id}/enrollments/{$enrollment->id}/payments", ['amount' => 2000, 'payment_method' => 'cash'])
            ->assertRedirect(route('course-payments.receipt', CoursePayment::firstOrFail()));

        $this->get('/course-desk/payment?code=S1')->assertOk()->assertSee('Recent payments')->assertSee('10,000.00');
    }

    public function test_attendance_desk_lists_the_days_classes(): void
    {
        $this->activity();
        $this->actingAs($this->inst['cashier']);

        $this->get('/course-desk/attendance')->assertOk()
            ->assertSee('Diploma in IT')->assertSee('Ongoing')->assertSee('Mark attendance');

        $this->get('/course-desk/attendance?date=2026-11-02')->assertOk()->assertSee('No course classes on this day');
    }

    public function test_teacher_payment_pages(): void
    {
        $this->activity();
        $this->actingAs($this->inst['admin']);

        // A: 35% of 12000 = 4200 earned, 1000 paid
        $this->get('/course-teacher-payments')->assertOk()->assertSee('Nimal Perera')->assertSee('3,200.00');
        $this->get("/course-teacher-payments/{$this->inst['teacher']}")->assertOk()
            ->assertSee('4,200.00')->assertSee('Pay teacher')->assertSee('Payments to Nimal Perera');

        $payout = CourseTeacherPayout::firstOrFail();
        $this->get("/course-teacher-payouts/{$payout->id}/slip")->assertOk()->assertSee('CTP-000001')->assertSee('3,200.00');

        $this->actingAs($this->inst['cashier']);
        $this->get('/course-teacher-payments')->assertForbidden();
        $this->get("/course-teacher-payouts/{$payout->id}/slip")->assertForbidden();
    }

    public function test_report_numbers(): void
    {
        $this->activity();
        $reports = app(CourseReportService::class);
        $branches = [$this->inst['b1']];

        $daily = $reports->daily(Carbon::parse('2026-11-01'), $branches);
        $this->assertSame('Rs. 12,000.00', $daily['summary']['Collected']);
        $this->assertSame('Rs. 8,400.00', $daily['summary']['Teachers\' share']);
        $this->assertSame('Rs. 3,600.00', $daily['summary']['Institute share']);
        $this->assertSame('Rs. 1,000.00', $daily['summary']['Paid to teachers']);
        $this->assertSame(2, $daily['summary']['New enrollments']);
        $this->assertCount(2, $daily['tables']['by_method']['rows']);
        $this->assertSame(1, $daily['tables']['sessions']['rows'][0]['present']);

        $monthly = $reports->monthly(2026, 11, $branches);
        $this->assertCount(1, $monthly['tables']['by_day']['rows']);
        $this->assertEquals(12000, $monthly['tables']['by_course']['rows'][0]['collected']);

        $yearly = $reports->yearly(2026, $branches);
        $this->assertEquals(12000, $yearly['tables']['by_month']['rows'][10]['collected']); // November
        $this->assertEquals(1000, $yearly['tables']['by_month']['rows'][10]['paid_teachers']);

        $all = $reports->courses($branches);
        $row = $all['tables']['courses']['rows'][0];
        $this->assertSame(2, $row['students']);
        $this->assertEquals(22000, $row['expected']);  // 12000 + 10000
        $this->assertEquals(12000, $row['collected']);
        $this->assertEquals(10000, $row['balance']);
        $this->assertEquals(7400, $row['teachers_balance']); // 8400 - 1000
        $this->assertSame('50%', $row['attendance']);       // 1 of 2 students on the 1 day held

        // Other branch sees nothing
        $this->assertSame('Rs. 0.00', $reports->daily(Carbon::parse('2026-11-01'), [$this->inst['b2']])['summary']['Collected']);
    }

    public function test_report_pages_and_downloads(): void
    {
        $this->activity();
        $this->actingAs($this->inst['admin']);

        $this->get('/course-reports/daily?date=2026-11-01')->assertOk()->assertSee('Course Daily Report')->assertSee('12,000.00');
        $this->get('/course-reports/monthly?month=2026-11')->assertOk()->assertSee('November 2026');
        $this->get('/course-reports/yearly?year=2026')->assertOk()->assertSee('By month');
        $this->get('/course-reports/courses')->assertOk()->assertSee('Diploma in IT');

        $excel = $this->get('/course-reports/monthly?month=2026-11&export=excel');
        $excel->assertOk();
        $this->assertStringContainsString('course-monthly-report-november-2026.xlsx', $excel->headers->get('content-disposition'));

        $pdf = $this->get('/course-reports/courses?export=pdf');
        $pdf->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('content-type'));

        $this->get('/course-reports/weekly')->assertNotFound();

        $this->actingAs($this->inst['cashier']);
        $this->get('/course-reports/daily')->assertForbidden();
    }
}

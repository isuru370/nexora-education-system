<?php

namespace Tests\Feature\Course;

use App\Models\CourseSession;
use App\Models\Student;
use App\Services\Course\CourseAttendanceService;
use App\Services\Course\CourseEnrollmentService;
use App\Services\Course\CourseFeeService;
use App\Services\Course\CoursePaymentService;
use App\Services\Course\CourseScheduleService;
use App\Services\Course\CourseService;
use App\Services\Course\CourseTeacherEarningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\Support\BuildsCourse;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

class CourseServicesTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;
    use BuildsCourse;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-24 10:00:00');
        $this->buildInstitute();
        $this->buildCourse();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_months_and_sessions(): void
    {
        $schedule = app(CourseScheduleService::class);

        $this->assertSame(6, $schedule->monthCount('2026-11-01', '2027-04-30'));
        $this->assertSame(6, $schedule->monthCount('2026-11-15', '2027-04-30'));
        $this->assertSame(1, $schedule->monthCount('2026-11-01', '2026-11-20'));
        $this->assertSame(12, $schedule->monthCount('2026-01-01', '2026-12-31'));

        $course = $this->inst['course'];
        $this->assertSame(6, $course->installment_count);
        $this->assertSame(30.0, $course->institutePercentage());

        // Nov 2026 - Apr 2027: every Saturday and Sunday
        $sessions = $course->sessions()->get();
        $this->assertCount(51, $sessions);
        $this->assertSame('2026-11-01', $sessions->first()->session_date->toDateString()); // Sunday
        $this->assertTrue($sessions->every(fn ($s) => in_array($s->session_date->englishDayOfWeek, ['Saturday', 'Sunday'])));
    }

    public function test_teacher_shares_cannot_pass_100(): void
    {
        $this->expectException(ValidationException::class);

        app(CourseService::class)->update($this->inst['course'], $this->inst['course']->toArray() + ['class_days' => ['saturday']], [
            ['teacher_id' => $this->inst['teacher'], 'percentage' => 70],
            ['teacher_id' => $this->inst['teacherB'], 'percentage' => 35],
        ]);
    }

    public function test_installment_plan_payments_and_splits(): void
    {
        $student = Student::findOrFail($this->inst['s1']);
        $enrollment = app(CourseEnrollmentService::class)->enroll($this->inst['course'], $student, 'installment', $this->inst['admin']->id);

        $this->assertSame('12000.00', (string) $enrollment->total_fee);
        $this->assertCount(6, $enrollment->installments);
        $this->assertSame(['2026-11-01', '2026-12-01'], $enrollment->installments->take(2)->map(fn ($i) => $i->due_date->toDateString())->all());
        $this->assertEquals(2000, $enrollment->installments->first()->amount);

        $payment = app(CoursePaymentService::class)->pay($enrollment, 3000, 'cash', 'manual_web', $this->inst['admin']->id);

        $this->assertStringStartsWith('REC-', $payment->receipt_number);
        $this->assertEquals([1050.0, 1050.0, 900.0], $payment->splits->pluck('amount')->map(fn ($a) => (float) $a)->all());
        $this->assertNull($payment->splits->last()->teacher_id);

        $fees = app(CourseFeeService::class)->summary($enrollment);
        $this->assertEquals(3000, $fees['paid']);
        $this->assertEquals(9000, $fees['balance']);
        $this->assertSame('paid', $fees['installments'][0]['status']);
        $this->assertSame('partly_paid', $fees['installments'][1]['status']);
        $this->assertEquals(1000, $fees['installments'][1]['remaining']);
        $this->assertSame(2, $fees['next_installment']['installment_no']);

        // More than the balance is refused
        try {
            app(CoursePaymentService::class)->pay($enrollment, 9000.01);
            $this->fail('Over-payment accepted');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
        }

        // Cancelling gives the money back to the balance
        app(CoursePaymentService::class)->cancel($payment, 'Wrong amount', $this->inst['admin']->id);
        $this->assertEquals(12000, app(CourseFeeService::class)->summary($enrollment)['balance']);
    }

    public function test_full_payment_plan(): void
    {
        $student = Student::findOrFail($this->inst['s2']);
        $enrollment = app(CourseEnrollmentService::class)->enroll($this->inst['course'], $student, 'full');

        $this->assertEquals(10000, $enrollment->total_fee);
        $this->assertCount(1, $enrollment->installments);

        app(CoursePaymentService::class)->pay($enrollment, 10000);
        $this->assertEquals(0, app(CourseFeeService::class)->summary($enrollment)['balance']);

        $this->expectException(ValidationException::class);
        app(CoursePaymentService::class)->pay($enrollment, 1);
    }

    public function test_enroll_twice_and_cancel(): void
    {
        $student = Student::findOrFail($this->inst['s1']);
        $service = app(CourseEnrollmentService::class);
        $enrollment = $service->enroll($this->inst['course'], $student, 'installment');

        try {
            $service->enroll($this->inst['course'], $student, 'full');
            $this->fail('Enrolled twice');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('student', $e->errors());
        }

        $service->cancel($enrollment);
        $again = $service->enroll($this->inst['course'], $student, 'full');
        $this->assertSame($enrollment->id, $again->id);
        $this->assertSame('full', $again->payment_plan);
        $this->assertCount(1, $again->installments);

        app(CoursePaymentService::class)->pay($again, 500);
        $this->expectException(ValidationException::class);
        $service->cancel($again);
    }

    public function test_attendance(): void
    {
        $student = Student::findOrFail($this->inst['s1']);
        $other = Student::findOrFail($this->inst['s2']);
        app(CourseEnrollmentService::class)->enroll($this->inst['course'], $student, 'installment');
        $service = app(CourseAttendanceService::class);

        $session = CourseSession::query()->where('course_id', $this->inst['course']->id)->orderBy('session_date')->first();

        // Future session
        try {
            $service->mark($session, $student, 'qr_web');
            $this->fail('Marked a future session');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('session', $e->errors());
        }

        Carbon::setTestNow('2026-11-01 09:00:00');
        $first = $service->mark($session, $student, 'qr_web', $this->inst['admin']->id);
        $this->assertFalse($first['already_marked']);
        $this->assertTrue($service->mark($session, $student, 'qr_web')['already_marked']);

        try {
            $service->mark($session, $other, 'qr_web');
            $this->fail('Marked a student who is not enrolled');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('not enrolled', $e->errors()['student'][0]);
        }

        $this->assertCount(1, $service->sessionsOn('2026-11-01', [$this->inst['b1']]));
        $this->assertCount(0, $service->sessionsOn('2026-11-01', [$this->inst['b2']]));
        $this->assertSame(['attended' => 1, 'held' => 1, 'percentage' => 100], $service->summary($first['enrollment']));

        $session->update(['status' => 'cancelled']);
        $this->expectException(ValidationException::class);
        $service->mark($session, $student, 'qr_web');
    }

    public function test_teacher_earnings_and_payouts(): void
    {
        $student = Student::findOrFail($this->inst['s1']);
        $enrollment = app(CourseEnrollmentService::class)->enroll($this->inst['course'], $student, 'full');
        app(CoursePaymentService::class)->pay($enrollment, 10000);

        $earnings = app(CourseTeacherEarningService::class);
        $rows = $earnings->forCourse($this->inst['course'])->keyBy(fn ($r) => $r['teacher']->id);

        $this->assertEquals(3500, $rows[$this->inst['teacher']]['earned']);
        $this->assertEquals(3500, $rows[$this->inst['teacherB']]['balance']);
        $this->assertEquals(3000, $earnings->instituteEarned($this->inst['course']));

        $payout = $earnings->payout($this->inst['course'], $this->inst['teacher'], 2000, 'cash', null, $this->inst['admin']->id);
        $this->assertEquals(1500, $earnings->forCourse($this->inst['course'])->first(fn ($r) => $r['teacher']->id === $this->inst['teacher'])['balance']);

        try {
            $earnings->payout($this->inst['course'], $this->inst['teacher'], 1500.01, 'cash', null, null);
            $this->fail('Paid more than the balance');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
        }

        $earnings->cancelPayout($payout);
        $this->assertEquals(3500, $earnings->forCourse($this->inst['course'])->first(fn ($r) => $r['teacher']->id === $this->inst['teacher'])['balance']);
    }

    public function test_changing_the_schedule_keeps_attended_sessions(): void
    {
        $course = $this->inst['course'];
        $student = Student::findOrFail($this->inst['s1']);
        app(CourseEnrollmentService::class)->enroll($course, $student, 'installment');

        Carbon::setTestNow('2026-11-01 09:00:00');
        $sunday = CourseSession::where('course_id', $course->id)->whereDate('session_date', '2026-11-01')->first();
        app(CourseAttendanceService::class)->mark($sunday, $student, 'qr_web');

        // Saturdays only, new time
        app(CourseService::class)->update($course, array_merge($course->only([
            'branch_id', 'name', 'code', 'full_fee', 'one_time_fee', 'class_hall_id',
        ]), [
            'start_date' => '2026-11-01', 'end_date' => '2027-04-30',
            'start_time' => '13:00', 'end_time' => '16:00', 'class_days' => ['saturday'],
        ]), [['teacher_id' => $this->inst['teacher'], 'percentage' => 70]]);

        $dates = CourseSession::where('course_id', $course->id)->orderBy('session_date')->get();
        $this->assertTrue($dates->contains(fn ($s) => $s->session_date->toDateString() === '2026-11-01')); // attended Sunday kept
        $this->assertSame(26, $dates->count()); // 25 Saturdays + the attended Sunday
        $this->assertSame('13:00', substr($dates->last()->start_time, 0, 5));
        $this->assertSame(1, $course->fresh()->courseTeachers()->count());
        $this->assertSame(30.0, $course->fresh()->institutePercentage());
    }
}

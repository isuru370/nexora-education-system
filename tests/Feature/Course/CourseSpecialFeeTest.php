<?php

namespace Tests\Feature\Course;

use App\Models\CourseEnrollment;
use App\Models\Student;
use App\Services\Course\CourseEnrollmentService;
use App\Services\Course\CourseFeeService;
use App\Services\Course\CoursePaymentService;
use App\Services\Course\CourseReportService;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Support\BuildsCourse;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/** Course fee 12000 / at once 10000 over 6 months; a student with a special fee. */
class CourseSpecialFeeTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;
    use BuildsCourse;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-24 10:00:00');
        $this->buildInstitute();
        DB::table('students')->whereKey($this->inst['s1'])->update(['custom_id' => 'S1']);
        Features::set('courses', true);
        $this->buildCourse();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function enroll(string $plan, ?float $fee, ?string $reason = 'Staff child'): CourseEnrollment
    {
        return app(CourseEnrollmentService::class)->enroll($this->inst['course'], Student::findOrFail($this->inst['s1']), $plan, null, null, $fee, $reason);
    }

    public function test_special_fee_in_installments(): void
    {
        $e = $this->enroll('installment', 8000);
        $fees = app(CourseFeeService::class)->summary($e);

        $this->assertEquals(8000, $fees['total_fee']);
        $this->assertEquals(12000, $fees['standard_fee']);
        $this->assertEquals(4000, $fees['discount']);
        $this->assertTrue($fees['special_fee']);
        $this->assertSame('Staff child', $fees['fee_reason']);
        $this->assertSame([1333.33, 1333.33, 1333.33, 1333.33, 1333.33, 1333.35], array_column($fees['installments'], 'amount'));
    }

    public function test_special_fee_paid_at_once(): void
    {
        $e = $this->enroll('full', 8000);

        $this->assertCount(1, $e->installments);
        $this->assertEquals(8000, $e->installments->first()->amount);
        $this->assertEquals(2000, $e->discount()); // 10000 - 8000
    }

    public function test_free_student_has_nothing_to_pay(): void
    {
        $e = $this->enroll('installment', 0, 'Free card');

        $this->assertCount(0, $e->installments);
        $this->assertEquals(0, app(CourseFeeService::class)->summary($e)['balance']);

        $this->expectException(ValidationException::class);
        app(CoursePaymentService::class)->pay($e, 100);
    }

    public function test_a_reason_is_needed(): void
    {
        $this->expectException(ValidationException::class);
        $this->enroll('installment', 8000, '  ');
    }

    public function test_change_fee_keeps_what_is_paid(): void
    {
        $e = $this->enroll('installment', null);       // 2000 x 6
        app(CoursePaymentService::class)->pay($e, 3000); // month 1 paid, month 2 half

        app(CourseEnrollmentService::class)->changeFee($e, 8000, 'Scholarship from December');
        $fees = app(CourseFeeService::class)->summary($e->fresh());

        $this->assertEquals(8000, $fees['total_fee']);
        $this->assertEquals(3000, $fees['paid']);
        $this->assertEquals(5000, $fees['balance']);
        $this->assertEquals(2000, $fees['installments'][0]['amount']); // paid one kept
        $this->assertSame('paid', $fees['installments'][0]['status']);
        $this->assertCount(6, $fees['installments']);                  // 5 open ones re-spread: 6000 / 5
        $this->assertEquals(1200, $fees['installments'][1]['amount']);
        $this->assertSame('2026-12-01', $fees['installments'][1]['due_date']);
        $this->assertSame('Scholarship from December', $fees['fee_reason']);

        // Not below what is paid
        $this->expectException(ValidationException::class);
        app(CourseEnrollmentService::class)->changeFee($e->fresh(), 2999, 'x');
    }

    public function test_raising_the_fee_of_a_fully_paid_student_adds_an_installment(): void
    {
        $e = $this->enroll('full', 8000);
        app(CoursePaymentService::class)->pay($e, 8000);

        app(CourseEnrollmentService::class)->changeFee($e, 10000, 'Discount removed');
        $fees = app(CourseFeeService::class)->summary($e->fresh());

        $this->assertEquals(2000, $fees['balance']);
        $this->assertSame([8000.0, 2000.0], array_column($fees['installments'], 'amount'));
    }

    public function test_web_enroll_with_special_fee_and_admin_changes_it(): void
    {
        $course = $this->inst['course'];

        // Cashiers can give a special fee
        $this->actingAs($this->inst['cashier']);
        $this->post("/courses/{$course->id}/enrollments", ['student_code' => 'S1', 'payment_plan' => 'installment', 'special_fee' => 6000])
            ->assertSessionHasErrors('special_fee_reason');
        $this->post("/courses/{$course->id}/enrollments", ['student_code' => 'S1', 'payment_plan' => 'installment', 'special_fee' => 6000, 'special_fee_reason' => 'Sibling discount'])
            ->assertRedirect();

        $e = CourseEnrollment::firstOrFail();
        $this->assertEquals(6000, $e->total_fee);
        $this->get("/courses/{$course->id}/enrollments/{$e->id}")->assertOk()->assertSee('Sibling discount')->assertDontSee('Change fee');
        $this->get("/courses/{$course->id}")->assertOk()->assertSee('Special');

        // ...but only admins change it later
        $this->patch("/courses/{$course->id}/enrollments/{$e->id}/fee", ['fee' => 5000, 'fee_reason' => 'x'])->assertForbidden();

        $this->actingAs($this->inst['admin']);
        $this->get("/courses/{$course->id}/enrollments/{$e->id}")->assertOk()->assertSee('Change fee');
        $this->patch("/courses/{$course->id}/enrollments/{$e->id}/fee", ['fee' => 5000, 'fee_reason' => 'Scholarship'])->assertSessionHas('success');
        $this->assertEquals(5000, $e->fresh()->total_fee);

        $report = app(CourseReportService::class)->courses([$this->inst['b1']]);
        $this->assertEquals(7000, $report['tables']['courses']['rows'][0]['discounts']); // 12000 - 5000
    }
}

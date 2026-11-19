<?php

namespace Tests\Feature\Audit;

use App\Models\Payment;
use App\Services\Web\MonthlyReport\MonthlyReportService;
use App\Services\Web\Payment\PaymentSplitSnapshotService;
use App\Services\Web\TeacherSalary\TeacherSalaryCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Class fee and hall fee kept apart on the payment pages (the Success
 * Academy report showed Fee 1,000 / Paid 1,100, and Paid with a hall fee
 * short), and the salary breakdown made of the salary's own payments.
 *
 * Maths 10 CMB: fee 2,000 + hall 100, teacher 60%.
 */
class PaymentReportsHallFeeTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-20 10:00:00');
        $this->buildInstitute();

        $hall = DB::table('class_halls')->insertGetId([
            'branch_id' => $this->inst['b1'], 'hall_name' => 'Hall A', 'code' => 'HA', 'hall_price' => 100, 'is_active' => 1,
        ]);
        DB::table('class_schedule_patterns')->where('student_class_id', $this->inst['c1'])->update(['class_hall_id' => $hall]);
        DB::table('teacher_branches')->insert(['teacher_id' => $this->inst['teacher'], 'branch_id' => $this->inst['b1']]);

        $this->actingAs($this->inst['admin']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function pay(string $code, int $enrollment, float $amount, string $month, string $at): void
    {
        Carbon::setTestNow($at);
        $this->postJson('/new-payment/pay', [
            'code' => $code, 'enrollment_id' => $enrollment, 'amount' => $amount, 'payment_month' => $month, 'mark_method' => 'manual_web',
        ])->assertStatus(201);
        Carbon::setTestNow('2026-11-20 10:00:00');
    }

    /** an old payment of less than class fee + hall fee (before the minimum rule) */
    private function oldPayment(int $student, int $enrollment, float $amount, string $month, string $at): void
    {
        $id = DB::table('payments')->insertGetId([
            'branch_id' => $this->inst['b1'], 'student_id' => $student, 'student_class_enrollment_id' => $enrollment,
            'user_id' => $this->inst['cashier']->id, 'amount' => $amount, 'paid_at' => $at, 'payment_month' => $month,
            'payment_method' => 'cash', 'status' => 'completed', 'receipt_number' => 'OLD' . $enrollment,
        ]);
        $snapshot = app(PaymentSplitSnapshotService::class)->refreshForPayment(Payment::findOrFail($id));
        $snapshot->update(['payment_date' => $at]);
    }

    private function categoryPage(int $year, int $month)
    {
        return $this->get("/student-class-enrollments/classes/{$this->inst['c1']}/categories/{$this->inst['f1']}/payments/$year/$month");
    }

    public function test_category_page_shows_class_fee_and_hall_fee_apart(): void
    {
        $this->pay('S2', $this->inst['e2'], 2100, '2026-11-01', '2026-11-03 10:00:00');
        $this->oldPayment($this->inst['s1'], $this->inst['e1'], 2000, '2026-11-01', '2026-11-04 10:00:00');

        $page = $this->categoryPage(2026, 11)->assertOk();

        $rows = collect($page->viewData('students')->items())->keyBy('student_code');

        // paid in full: 2,000 class fee + 100 hall fee
        $this->assertSame('paid', $rows['S2']['status']);
        $this->assertEquals([2000, 100, 2100, 2100, 0], [$rows['S2']['class_fee'], $rows['S2']['hall_fee'], $rows['S2']['payable'], $rows['S2']['paid'], $rows['S2']['balance']]);

        // 2,000 paid: the hall fee is taken first, so 100 class fee is still owed (was "Paid")
        $this->assertSame('partial', $rows['S1']['status']);
        $this->assertEquals([100, 1900, 100], [$rows['S1']['hall_paid'], $rows['S1']['class_paid'], $rows['S1']['balance']]);

        $summary = $page->viewData('summary');
        $this->assertEquals([4200, 4100, 200, 100], [$summary['payable'], $summary['collected'], $summary['hall_paid'], $summary['balance']]);
        $page->assertSee('Partly paid')->assertSee('0771234567');
    }

    public function test_category_page_leaves_out_cancelled_payments(): void
    {
        // the fixture has a cancelled Rs. 999 for S1 November: it was counted as paid money
        $rows = collect($this->categoryPage(2026, 11)->viewData('students')->items())->keyBy('student_code');

        $this->assertSame('unpaid', $rows['S1']['status']);
        $this->assertEquals(0, $rows['S1']['paid']);
    }

    public function test_teacher_sees_only_own_class_and_no_phone_numbers(): void
    {
        $user = $this->makeRoleUser('TEACHER', $this->inst['b1']);
        DB::table('teachers')->where('id', $this->inst['teacher'])->update(['user_id' => $user->id]);

        $this->actingAs($user->fresh())->categoryPage(2026, 11)->assertOk()->assertDontSee('0771234567');

        // not this teacher's class any more
        DB::table('teachers')->where('id', $this->inst['teacher'])->update(['user_id' => null]);
        $this->assertContains($this->actingAs($user->fresh())->categoryPage(2026, 11)->status(), [403, 404]);
    }

    public function test_salary_breakdown_is_made_of_the_salary_payments(): void
    {
        // October: S1's fee received in October; October salary paid on 28 Oct
        $this->pay('S2', $this->inst['e2'], 2100, '2026-10-01', '2026-10-20 10:00:00');
        Carbon::setTestNow('2026-10-28 10:00:00');
        $this->post("/teacher-salaries/{$this->inst['teacher']}/pay", ['year' => 2026, 'month' => 10, 'branch_id' => $this->inst['b1']])
            ->assertSessionHasNoErrors();
        Carbon::setTestNow('2026-11-20 10:00:00');
        $this->assertSame(1, DB::table('teacher_salaries')->where('status', 'paid')->count());

        // after the October salary: S1's October fee (-> November salary)
        $this->pay('S1', $this->inst['e1'], 2100, '2026-11-01', '2026-10-30 10:00:00');
        // November: S2 pays November; and a September fee received in November
        $this->pay('S2', $this->inst['e2'], 2100, '2026-11-01', '2026-11-05 10:00:00');
        $this->pay('S2', $this->inst['e2'], 2100, '2026-09-01', '2026-11-06 10:00:00');

        $page = $this->get("/teacher-salaries/{$this->inst['teacher']}/2026/11/summary?branch_id={$this->inst['b1']}")->assertOk();
        $data = $page->viewData('data');

        // 3 payments: 6,300 received, 300 hall fee, 6,000 class fee, teacher 60% = 3,600
        $this->assertEquals([3, 6300, 300, 6000, 3600], [
            $data['totals']['payments'], $data['totals']['collected'], $data['totals']['hall_fee'], $data['totals']['class_fee'], $data['totals']['teacher'],
        ]);
        $this->assertEquals(1200, $data['carried']['teacher']);

        // the same as the salary: teacher earnings + balance from earlier months
        $salary = app(TeacherSalaryCalculator::class)->forTeacher($this->inst['teacher'], $this->inst['b1'], 2026, 11);
        $this->assertEquals($salary['gross'] + $salary['carried'], $data['totals']['teacher']);

        $rows = collect($data['classes'][0]['categories'][0]['payments']);
        $this->assertEqualsCanonicalizing(['Nov 2026', 'Nov 2026', 'Sep 2026'], $rows->pluck('paid_for')->all());
        $this->assertTrue($rows->firstWhere('paid_on', '30 Oct 2026')['carried']);
        $page->assertSee('After last salary');

        // the November fee status: S1 and S2 paid
        $this->assertSame(2, $data['classes'][0]['categories'][0]['fee_status']['paid']);

        // October (paid): only up to the salary; S1's fee after it is listed apart
        $october = $this->get("/teacher-salaries/{$this->inst['teacher']}/2026/10/summary?branch_id={$this->inst['b1']}")->viewData('data');
        $this->assertEquals(1200, $october['totals']['teacher']);
        $this->assertSame(1, $october['after_salary']['totals']['payments']);
    }

    public function test_collection_report_is_the_salary_and_fee_month_report_has_the_hall_fee(): void
    {
        $this->pay('S2', $this->inst['e2'], 2100, '2026-11-01', '2026-11-05 10:00:00');
        $this->pay('S2', $this->inst['e2'], 2100, '2026-10-01', '2026-11-06 10:00:00');
        $this->oldPayment($this->inst['s1'], $this->inst['e1'], 2000, '2026-11-01', '2026-11-07 10:00:00');

        $service = app(MonthlyReportService::class);

        $bySalary = $service->teacherStudentPaymentReport($this->inst['teacher'], 2026, 11, 'paid-date', $this->inst['b1']);
        $salary = app(TeacherSalaryCalculator::class)->forTeacher($this->inst['teacher'], $this->inst['b1'], 2026, 11);
        $this->assertEquals($salary['gross'] + $salary['carried'], $bySalary['summary']['teacher_share']);
        $this->assertEquals([6200, 300, 5900], [$bySalary['summary']['total_collected'], $bySalary['summary']['hall_collected'], $bySalary['summary']['class_collected']]);

        $s2 = $bySalary['classes'][0]['categories'][0]['students']['paid'][0];
        $this->assertSame('Oct 2026, Nov 2026', $s2['paid_for']);
        $this->assertEquals(4200, $s2['paid_amount']);

        $byFeeMonth = $service->teacherStudentPaymentReport($this->inst['teacher'], 2026, 11, 'fee-month', $this->inst['b1']);
        $s1 = $byFeeMonth['classes'][0]['categories'][0]['students']['unpaid'][0];
        $this->assertSame('partial', $s1['status']);
        $this->assertEquals([2100, 2000, 100], [$s1['payable'], $s1['paid_amount'], $s1['balance']]);
        $this->assertSame(1, $byFeeMonth['summary']['partial_students']);

        $html = view('pdf.teacher.teacher_with_student_payment_report', ['report' => $byFeeMonth, 'year' => 2026, 'month' => 11])->render();
        $this->assertStringContainsString('Hall Fee', $html);
        $this->assertStringContainsString('Partly paid', $html);
    }
}

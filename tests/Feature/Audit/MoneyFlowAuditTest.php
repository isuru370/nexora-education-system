<?php

namespace Tests\Feature\Audit;

use App\Models\Student;
use App\Services\Course\CourseEnrollmentService;
use App\Services\Course\CoursePaymentService;
use App\Services\Web\Payment\PaymentDeleteService;
use App\Services\Web\Report\AnnualReportService;
use App\Services\Web\Report\DailyCollectionReportService;
use App\Services\Web\Report\InstituteIncomeReportService;
use App\Services\Web\Report\MonthlyCollectionReportService;
use App\Services\Web\Report\PaymentReportQuery;
use App\Services\Web\TeacherSalary\TeacherSalaryCalculator;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsCourse;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * One month of an institute, end to end, with numbers worked out by hand:
 * class fees (full, part, deleted), teacher advance / deduction / other,
 * salary paid, extra income and expenses (incl. cancelled ones), course
 * fees. Every report must give the same money.
 *
 *   Class fees (Maths 10 CMB, teacher 60% / institute 40%, no hall fee)
 *     S1 October 2,000 · S2 October 2,000 · S1 November 2,000 then DELETED
 *     -> collected 4,000 · teacher 2,400 · institute 1,600
 *   Teacher: advance 300, deduction 100, other 50 -> net salary 1,950 (paid)
 *   Extra income 1,000 received (+ 700 cancelled) · Expenses 400 paid (+ 250 cancelled)
 *   Course: S1 pays 3,000 -> teacher A 35% 1,050 · teacher B 35% 1,050 · institute 30% 900;
 *           paid out: teacher 1,000 (+ 999 cancelled), owner 500
 */
class MoneyFlowAuditTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;
    use BuildsCourse;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-28 10:00:00');
        $this->buildInstitute();
        Features::set('courses', true);
        $this->buildCourse();
        DB::table('teacher_branches')->insert(['teacher_id' => $this->inst['teacher'], 'branch_id' => $this->inst['b1']]);

        // start from no money at all (the fixture adds sample payments)
        DB::table('payment_split_snapshots')->delete();
        DB::table('payments')->delete();
        DB::table('extra_incomes')->delete();
        DB::table('institute_payments')->delete();

        $this->actingAs($this->inst['admin']);
        $this->runMonth();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function classFee(string $code, int $enrollment, float $amount, string $month): int
    {
        $res = $this->postJson('/api/new-payment/bulk-pay', ['payments' => [[
            'code' => $code, 'enrollment_id' => $enrollment, 'amount' => $amount, 'discount_amount' => 0,
            'payment_month' => $month, 'paid_at' => null, 'payment_method' => 'cash',
            'mark_method' => 'qr_mobile', 'note' => null,
        ]]]);
        $this->assertTrue($res->isSuccessful(), $res->getContent());


        return (int) DB::table('payments')->latest('id')->value('id');
    }

    private function runMonth(): void
    {
        $this->classFee('S1', $this->inst['e1'], 2000, '2026-10');
        $this->classFee('S2', $this->inst['e2'], 2000, '2026-10');
        $wrong = $this->classFee('S1', $this->inst['e1'], 2000, '2026-11');
        app(PaymentDeleteService::class)->delete($wrong, 'Entered twice');

        foreach ([['advance', 300], ['deduction', 100], ['other', 50]] as [$type, $amount]) {
            $this->post('/teacher-salaries/' . $this->inst['teacher'] . '/payment', [
                'branch_id' => $this->inst['b1'], 'payment_type' => $type, 'amount' => $amount,
                'payment_date' => '2026-10-28', 'reason' => ucfirst($type),
            ])->assertSessionHas('success');
        }

        $this->post('/teacher-salaries/' . $this->inst['teacher'] . '/pay', [
            'branch_id' => $this->inst['b1'], 'year' => 2026, 'month' => 10,
        ])->assertSessionHas('success');

        $row = ['branch_id' => $this->inst['b1'], 'user_id' => $this->inst['admin']->id, 'created_at' => now(), 'updated_at' => now()];
        DB::table('extra_incomes')->insert([
            $row + ['amount' => 1000, 'income_date' => '2026-10-20', 'reason' => 'Hall rent', 'income_type' => 'hall_rent', 'status' => 'received'],
            $row + ['amount' => 700, 'income_date' => '2026-10-21', 'reason' => 'Wrong', 'income_type' => 'other', 'status' => 'cancelled'],
        ]);
        DB::table('institute_payments')->insert([
            $row + ['amount' => 400, 'payment_date' => '2026-10-22', 'reason' => 'Electricity', 'payment_type' => 'expense', 'status' => 'paid'],
            $row + ['amount' => 250, 'payment_date' => '2026-10-23', 'reason' => 'Wrong', 'payment_type' => 'expense', 'status' => 'cancelled'],
        ]);

        $enrollment = app(CourseEnrollmentService::class)->enroll($this->inst['course'], Student::findOrFail($this->inst['s1']), 'installment');
        app(CoursePaymentService::class)->pay($enrollment, 3000, 'cash', 'manual_web', $this->inst['admin']->id);

        $payout = ['branch_id' => $this->inst['b1'], 'course_id' => $this->inst['course']->id, 'paid_on' => '2026-10-28', 'user_id' => $this->inst['admin']->id, 'created_at' => now(), 'updated_at' => now()];
        DB::table('course_teacher_payouts')->insert([
            $payout + ['teacher_id' => $this->inst['teacher'], 'amount' => 1000, 'status' => 'paid'],
            $payout + ['teacher_id' => $this->inst['teacher'], 'amount' => 999, 'status' => 'cancelled'],
        ]);
        DB::table('course_owner_payouts')->insert($payout + ['amount' => 500, 'status' => 'paid']);
    }

    private function october()
    {
        return app(PaymentReportQuery::class)->filter('2026-10-01', '2026-10-31');
    }

    public function test_every_payment_is_split_exactly(): void
    {
        $rows = DB::table('payments')->whereNull('payments.deleted_at')->where('payments.status', 'completed')
            ->join('payment_split_snapshots', 'payment_split_snapshots.payment_id', '=', 'payments.id')
            ->whereNull('payment_split_snapshots.deleted_at')
            ->get(['payments.amount', 'teacher_amount', 'organizer_amount', 'institution_amount']);

        $this->assertCount(2, $rows);
        foreach ($rows as $r) {
            $this->assertEquals((float) $r->amount, round($r->teacher_amount + $r->organizer_amount + $r->institution_amount, 2));
        }

        // the deleted payment's split is gone too
        $this->assertSame(0, DB::table('payment_split_snapshots')->whereNull('deleted_at')
            ->whereIn('payment_id', DB::table('payments')->whereNotNull('deleted_at')->pluck('id'))->count());

        $course = DB::table('course_payment_splits')->sum('amount');
        $this->assertEquals(3000, $course);
        $this->assertEquals(1050, DB::table('course_payment_splits')->where('teacher_id', $this->inst['teacher'])->sum('amount'));
        $this->assertEquals(900, DB::table('course_payment_splits')->whereNull('teacher_id')->sum('amount'));
    }

    public function test_teacher_salary(): void
    {
        $this->assertDatabaseHas('teacher_salaries', [
            'teacher_id' => $this->inst['teacher'], 'branch_id' => $this->inst['b1'], 'salary_year' => 2026, 'salary_month' => 10,
            'gross_amount' => 2400, 'advance_deduction' => 300, 'other_deduction' => 150, 'net_amount' => 1950, 'status' => 'paid',
        ]);

        $totals = app(TeacherSalaryCalculator::class)->forTeacher($this->inst['teacher'], $this->inst['b1'], 2026, 10);
        $this->assertSame(2400.0, (float) $totals['gross']);
    }

    public function test_institute_income_report(): void
    {
        $s = app(InstituteIncomeReportService::class)->summary($this->october());

        $this->assertEquals(4000, $s['class_fees']);
        $this->assertSame(2, $s['class_fee_count']);
        $this->assertEquals(['Teacher share' => 2400, 'Organizer share' => 0, 'Institute share' => 1600], $s['split']);
        $this->assertEquals(1600, $s['income']['Institute share of class fees']);
        $this->assertEquals(900, $s['income']['Institute share of course fees']);
        $this->assertEquals(1000, $s['income']['Extra income']);
        $this->assertEquals(3500, $s['total_income']);
        $this->assertEquals(400, $s['expenses']);
        $this->assertEquals(3100, $s['net']);
        $this->assertEquals(['payments' => 1, 'collected' => 3000, 'teachers' => 2100, 'institute' => 900], $s['courses']);
        $this->assertSame(0, $s['unsplit_count']);
        $this->assertEquals(300, $s['teacher_advances']);
    }

    public function test_daily_collection_report_cash_in_and_out(): void
    {
        $s = app(DailyCollectionReportService::class)->summary($this->october());

        $this->assertEquals(4000, $s['income']['Class fee payments']);
        $this->assertEquals(3000, $s['income']['Course fee payments'] ?? null, 'course fees are cash collected too');
        $this->assertEquals(1000, $s['income']['Extra income']);
        $this->assertEquals(8000, $s['total_income']);

        $this->assertEquals(1950, $s['expenses']['Teacher salaries']);
        $this->assertEquals(350, $s['expenses']['Teacher advances / other']);
        $this->assertEquals(400, $s['expenses']['Institute expenses']);
        $this->assertEquals(1000, $s['expenses']['Course teacher payments']);
        $this->assertEquals(500, $s['expenses']['Course owner payments']);
        $this->assertEquals(4200, $s['total_expenses']);
        $this->assertEquals(3800, $s['net']);
    }

    public function test_daily_report_without_courses_is_unchanged(): void
    {
        Features::set('courses', false);
        $s = app(DailyCollectionReportService::class)->summary($this->october());

        $this->assertSame(['Class fee payments', 'Admission fees', 'Extra income'], array_keys($s['income']));
        $this->assertSame(['Teacher salaries', 'Teacher advances / other', 'Organizer payments', 'Institute expenses'], array_keys($s['expenses']));
        $this->assertEquals(5000, $s['total_income']);
        $this->assertEquals(2700, $s['total_expenses']);
    }

    public function test_annual_report_matches_the_daily_report(): void
    {
        // the year's report runs up to today; look at October once it is over
        Carbon::setTestNow('2026-11-01 08:00:00');
        $annual = app(AnnualReportService::class);
        $october = collect($annual->generate($annual->yearFilter(2026))['rows'])->firstWhere('month', 10);
        $daily = app(DailyCollectionReportService::class)->summary($this->october());

        $this->assertEquals(3000, $october['course_fees']);
        $this->assertEquals(1500, $october['course_payouts']);
        $this->assertEquals($daily['total_income'], $october['total_income']);
        $this->assertEquals($daily['total_expenses'], $october['total_expenses']);
        $this->assertEquals($daily['net'], $october['net']);

        $this->actingAs($this->inst['admin'])->get('/reports/annual?year=2026')->assertOk()->assertSee('Course Fees')->assertSee('Course Payouts');
    }

    public function test_monthly_collection_report(): void
    {
        $r = app(MonthlyCollectionReportService::class)->generate($this->october(), Carbon::parse('2026-10-01'));
        $row = collect($r['rows'])->firstWhere('class_name', 'Maths 10 CMB');

        $this->assertEquals(4000, $row['collected']);
        $this->assertEquals(2400, $row['teacher_amount']);
        $this->assertEquals(1600, $row['institution_amount']);
        $this->assertSame(2, $row['paid_students']);
    }
}

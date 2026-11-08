<?php

namespace Tests\Feature\Staff;

use App\Services\Web\Payment\PaymentSplitSnapshotService;
use App\Services\Web\TeacherSalary\TeacherSalaryCalculator;
use App\Services\Web\TeacherSalary\TeacherSalaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * End to end: class fees -> teacher share -> advance / deduction / other ->
 * salary paid -> slip. Also: a payment in a paid salary month cannot be
 * deleted, and a paid slip lists only the payments that were in it.
 */
class TeacherSalaryAccuracyTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    protected function setUp(): void
    {
        parent::setUp();
        // last 5 days of October: the October salary can be paid
        Carbon::setTestNow('2026-10-28 10:00:00');
        $this->buildInstitute();
        DB::table('teacher_branches')->insert(['teacher_id' => $this->inst['teacher'], 'branch_id' => $this->inst['b1']]);
        $this->actingAs($this->inst['admin']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function classFee(string $code, int $enrollment, string $month): int
    {
        $this->postJson('/api/new-payment/bulk-pay', ['payments' => [[
            'code' => $code, 'enrollment_id' => $enrollment, 'amount' => 2000, 'discount_amount' => 0,
            'payment_month' => $month, 'paid_at' => null, 'payment_method' => 'cash',
            'mark_method' => 'qr_mobile', 'note' => null,
        ]]])->assertSuccessful();

        return (int) DB::table('payments')->latest('id')->value('id');
    }

    private function teacherPayment(string $type, float $amount)
    {
        return $this->post('/teacher-salaries/' . $this->inst['teacher'] . '/payment', [
            'branch_id' => $this->inst['b1'], 'payment_type' => $type, 'amount' => $amount,
            'payment_date' => '2026-10-28', 'reason' => ucfirst($type),
        ]);
    }

    private function totals(): array
    {
        return app(TeacherSalaryCalculator::class)->forTeacher($this->inst['teacher'], $this->inst['b1'], 2026, 10);
    }

    public function test_split_keeps_the_hall_fee_with_the_institute(): void
    {
        $this->assertSame([2925.0, 0.0, 1075.0], PaymentSplitSnapshotService::split(4000, 100, 75, 0));
        $this->assertSame([1170.0, 195.0, 635.0], PaymentSplitSnapshotService::split(2000, 50, 60, 10));
        // less than the hall fee paid: all hall fee
        $this->assertSame([0.0, 0.0, 80.0], PaymentSplitSnapshotService::split(80, 100, 60, 0));
    }

    public function test_salary_from_fees_minus_advance_deduction_and_other(): void
    {
        $this->classFee('S2', $this->inst['e2'], '2026-10');
        $this->classFee('S1', $this->inst['e1'], '2026-11');

        // 2 x 2,000 at 60%
        $this->assertSame(2400.0, $this->totals()['gross']);

        $this->teacherPayment('advance', 300)->assertSessionHas('success');
        $this->teacherPayment('deduction', 100)->assertSessionHas('success');
        $this->teacherPayment('other', 50)->assertSessionHas('success');

        // more than what is left: refused
        $this->teacherPayment('advance', 2000)->assertSessionHas('error');

        $totals = $this->totals();
        $this->assertSame(450.0, $totals['total_deductions']);
        $this->assertSame(1950.0, $totals['net']);

        $this->post('/teacher-salaries/' . $this->inst['teacher'] . '/pay', [
            'branch_id' => $this->inst['b1'], 'year' => 2026, 'month' => 10,
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('teacher_salaries', [
            'teacher_id' => $this->inst['teacher'], 'branch_id' => $this->inst['b1'], 'salary_year' => 2026, 'salary_month' => 10,
            'gross_amount' => 2400, 'advance_deduction' => 300, 'other_deduction' => 150, 'net_amount' => 1950, 'status' => 'paid',
        ]);

        // paid month: no more advances, no second payment
        $this->teacherPayment('advance', 10)->assertSessionHas('error');
        $this->post('/teacher-salaries/' . $this->inst['teacher'] . '/pay', [
            'branch_id' => $this->inst['b1'], 'year' => 2026, 'month' => 10,
        ])->assertSessionHas('error');
    }

    public function test_paid_slip_shows_only_the_payments_in_that_salary(): void
    {
        $this->classFee('S2', $this->inst['e2'], '2026-10');
        $this->post('/teacher-salaries/' . $this->inst['teacher'] . '/pay', [
            'branch_id' => $this->inst['b1'], 'year' => 2026, 'month' => 10,
        ])->assertSessionHas('success');

        // a class fee paid after the salary, still in October
        Carbon::setTestNow('2026-10-29 09:00:00');
        $late = $this->classFee('S1', $this->inst['e1'], '2026-11');

        $slip = app(TeacherSalaryService::class)->salarySlip($this->inst['teacher'], $this->inst['b1'], 2026, 10);

        $this->assertSame(1200.0, $slip['total_addition']);
        $this->assertSame(1200.0, round(collect($slip['earnings'])->sum('amount'), 2));
        $this->assertSame(1200.0, $slip['net_salary']);

        // and that payment cannot be deleted (the October salary is paid)
        $this->deleteJson("/api/payments/{$late}", ['reason' => 'Wrong class paid'])->assertStatus(422);
        $this->assertDatabaseHas('payments', ['id' => $late, 'deleted_at' => null]);
    }

    public function test_payment_can_be_deleted_while_the_salary_is_not_paid(): void
    {
        $payment = $this->classFee('S2', $this->inst['e2'], '2026-10');
        $this->assertSame(1200.0, $this->totals()['gross']);

        $this->deleteJson("/api/payments/{$payment}", ['reason' => 'Wrong class paid'])->assertSuccessful();

        $this->assertSame(0.0, $this->totals()['gross']);
    }

    /* ---------- Extra income / expenses totals ---------- */

    public function test_extra_income_total_leaves_out_cancelled(): void
    {
        foreach ([['received', 1000], ['pending', 500], ['cancelled', 300]] as $i => [$status, $amount]) {
            DB::table('extra_incomes')->insert([
                'branch_id' => $this->inst['b1'], 'amount' => $amount, 'income_date' => '2026-10-10',
                'income_type' => 'extra', 'status' => $status, 'receipt_number' => 'EX' . $i,
                'user_id' => $this->inst['admin']->id, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $view = $this->get('/admin/extra-incomes?year=2026&month=10')->assertOk();
        $this->assertSame(1500.0, (float) $view->viewData('totalIncome'));
        $this->assertSame(1000.0, (float) $view->viewData('receivedIncome'));
    }

    public function test_expenses_page_shows_the_paid_total_of_every_filtered_row(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            DB::table('institute_payments')->insert([
                'branch_id' => $this->inst['b1'], 'amount' => 100, 'payment_date' => '2026-10-' . sprintf('%02d', $i),
                'payment_type' => 'expense', 'status' => $i === 20 ? 'pending' : 'paid',
                'user_id' => $this->inst['admin']->id, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $view = $this->get('/admin/institute-expenses')->assertOk();
        // 19 paid x 100, across both pages (15 per page)
        $this->assertSame(1900.0, (float) $view->viewData('paidTotal'));
        $view->assertSee('Total Paid (all filtered)');
    }
}

<?php

namespace Tests\Feature\Import;

use App\Models\Branch;
use App\Models\ClassCategory;
use App\Models\Grade;
use App\Models\Payment;
use App\Models\PaymentSplitSnapshot;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherSalary;
use App\Services\Web\TeacherSalary\TeacherSalaryCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** Moving an institute from the old system: class payments and settled teacher salaries. */
class ImportPaymentsTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-27 10:00:00');

        $branch = Branch::create(['name' => 'Main Branch', 'code' => 'MAIN', 'is_active' => true]);
        $grade = Grade::create(['grade_name' => '10', 'is_active' => true]);
        ClassCategory::create(['category_name' => 'Theory', 'code' => 'theory', 'is_active' => true]);
        Teacher::create(['custom_id' => 'CT0001', 'full_name' => 'T', 'initials' => 'T', 'email' => 't@t.lk', 'mobile' => '0771234567',
            'nic' => '1', 'bday' => '1980-01-01', 'gender' => 'male', 'address1' => 'x']);
        foreach (['CS0001', 'CS0002'] as $id) {
            Student::create(['custom_id' => $id, 'branch_id' => $branch->id, 'initial_name' => $id, 'guardian_mobile' => '0771234567',
                'grade_id' => $grade->id, 'img_url' => 'uploads/male.png']);
        }

        $this->dir = sys_get_temp_dir() . '/nexora-pay-' . uniqid();
        mkdir($this->dir);

        // one class, 75% teacher; CS0001 in it twice in the old system (#1, #2 - merged into one)
        $this->csv('classes.csv', ['old_class_id', 'old_fee_id', 'class_name', 'teacher_custom_id', 'teacher_percentage', 'subject', 'grade', 'is_active', 'is_ongoing', 'category', 'fee', 'created_at'], [
            [11, 25, 'ENGLISH G10', 'CT0001', 75, 'English', '10', 1, 1, 'Theory', 1000, '2024-09-15 00:00:00'],
        ]);
        $this->csv('enrollments.csv', ['old_id', 'custom_id', 'old_fee_id', 'is_active', 'is_free_card', 'enrolled_at', 'updated_at'], [
            [1, 'CS0001', 25, 1, 0, '2024-10-01 09:00:00', ''],
            [2, 'CS0001', 25, 0, 0, '2024-10-02 09:00:00', ''],
            [3, 'CS0002', 25, 1, 0, '2024-10-01 09:00:00', ''],
        ]);
        $this->csv('payments.csv', ['old_id', 'custom_id', 'old_enrollment_id', 'old_fee_id', 'payment_month', 'amount', 'paid_at', 'status'], [
            [10, 'CS0001', 1, 25, '2026 Aug', 1000, '2026-08-05 10:00:00', 1],
            [11, 'CS0002', 3, 25, '2026 Aug', 1000, '2026-08-06 10:00:00', 1],
            [12, 'CS0001', 2, 25, '2026 Aug', 1000, '2026-08-07 10:00:00', 1],  // the merged enrollment - Aug already paid
            [13, 'CS0001', 2, 25, '2026-09', 1000, '2026-09-04 10:00:00', 1],   // the merged enrollment - goes to #1
            [14, 'CS0002', 3, 25, '2026 Sep', 800, '2026-09-05 10:00:00', 1],
            [15, 'CS0002', 3, 25, '2026 Oct', 1000, '2026-10-02 10:00:00', 0],  // cancelled in the old system
        ]);
        $this->csv('teacher_payments.csv', ['old_id', 'teacher_custom_id', 'reason_code', 'reason', 'amount', 'paid_at', 'salary_month', 'status'], [
            [1, 'CT0001', 'salary', 'Teacher Payment Monthly', 1000, '2026-09-02 10:00:00', '08 2026', 1],
            [2, 'CT0001', '1', 'Teacher Advance Payment', 500, '2026-08-20 10:00:00', '08 2026', 1],
        ]);

        $this->artisan('nexora:import-classes', ['file' => $this->dir . '/classes.csv', '--enrollments' => $this->dir . '/enrollments.csv'])->assertExitCode(0);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*') ?: []);
        @rmdir($this->dir);
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function csv(string $name, array $header, array $rows): void
    {
        $h = fopen($this->dir . '/' . $name, 'w');
        fputcsv($h, $header);
        foreach ($rows as $row) {
            fputcsv($h, $row);
        }
        fclose($h);
    }

    public function test_salaries_need_the_payments_first(): void
    {
        $this->artisan('nexora:import-teacher-salaries', ['file' => $this->dir . '/teacher_payments.csv', '--settled-until' => '2026-08', '--dry-run' => true])
            ->expectsOutput('No class payments in the database yet. Import them first: php artisan nexora:import-payments ...')
            ->assertExitCode(1);

        $this->assertSame(0, TeacherSalary::count());
    }

    public function test_dry_run_changes_nothing(): void
    {
        $this->artisan('nexora:import-payments', ['file' => $this->dir . '/payments.csv', '--dry-run' => true])
            ->expectsOutput('DRY RUN - nothing was changed')
            ->assertExitCode(0);

        $this->assertSame(0, Payment::count());
    }

    public function test_payments_and_salaries_are_imported(): void
    {
        $this->artisan('nexora:import-payments', ['file' => $this->dir . '/payments.csv'])
            ->expectsOutput('All months match.')
            ->assertExitCode(0);

        $this->assertSame(4, Payment::count());              // #10, #11, #13, #14
        $this->assertEquals(3800, Payment::sum('amount'));

        // receipts in date order, month and date kept, the split made (75 / 25)
        $this->assertSame(['REC-000001', 'REC-000002', 'REC-000003', 'REC-000004'], Payment::orderBy('paid_at')->pluck('receipt_number')->all());
        $merged = Payment::where('note', 'Imported from old system #13')->first();
        $this->assertSame('2026-09-01', Carbon::parse($merged->payment_month)->toDateString());
        $this->assertSame('2026-09-04 10:00:00', $merged->paid_at->format('Y-m-d H:i:s'));
        $this->assertSame('CS0001', $merged->student->custom_id);
        $this->assertSame(4, PaymentSplitSnapshot::count());
        $this->assertEquals(2850, PaymentSplitSnapshot::sum('teacher_amount'));
        $this->assertEquals(950, PaymentSplitSnapshot::sum('institution_amount'));

        $problems = file_get_contents(storage_path('logs/import-payments-problems.txt'));
        $this->assertStringContainsString('#12 CS0001: NOT imported - 2026-08 already paid for this class (double entry, Rs. 1000)', $problems);
        $this->assertStringContainsString('#15 CS0002: not imported - cancelled in the old system', $problems);

        // again: nothing new
        $this->artisan('nexora:import-payments', ['file' => $this->dir . '/payments.csv'])->assertExitCode(0);
        $this->assertSame(4, Payment::count());

        // Salaries: August settled in the old system, September open
        $this->artisan('nexora:import-teacher-salaries', ['file' => $this->dir . '/teacher_payments.csv', '--settled-until' => '2026-08'])
            ->assertExitCode(0);

        $aug = TeacherSalary::where('salary_year', 2026)->where('salary_month', 8)->first();
        $this->assertSame('paid', $aug->status);
        $this->assertEquals(1500, $aug->gross_amount);     // 75% of 2000
        $this->assertEquals(1500, $aug->net_amount);       // 1000 salary + 500 advance
        $this->assertEquals(500, $aug->advance_deduction);
        $this->assertStringContainsString('Paid in the old system', $aug->note);
        $this->assertSame(1, TeacherSalary::count());

        $calc = app(TeacherSalaryCalculator::class);
        $teacherId = Teacher::value('id');
        $branchId = Branch::value('id');
        $this->assertSame('Salary for this month is already paid.', $calc->payBlockReason(2026, 8, $aug, $calc->forTeacher($teacherId, $branchId, 2026, 8)));
        $sep = $calc->forTeacher($teacherId, $branchId, 2026, 9);
        $this->assertEquals(1350, $sep['gross']);           // 75% of 1800
        $this->assertNull($calc->payBlockReason(2026, 9, null, $sep));

        // again: nothing new
        $this->artisan('nexora:import-teacher-salaries', ['file' => $this->dir . '/teacher_payments.csv', '--settled-until' => '2026-08'])->assertExitCode(0);
        $this->assertSame(1, TeacherSalary::count());
    }

    public function test_months_without_an_old_salary_are_closed_until_the_settled_month(): void
    {
        $this->artisan('nexora:import-payments', ['file' => $this->dir . '/payments.csv'])->assertExitCode(0);
        $this->csv('none.csv', ['old_id', 'teacher_custom_id', 'reason_code', 'reason', 'amount', 'paid_at', 'salary_month', 'status'], []);

        $this->artisan('nexora:import-teacher-salaries', ['file' => $this->dir . '/none.csv', '--settled-until' => '2026-09'])->assertExitCode(0);

        $this->assertSame([8, 9], TeacherSalary::orderBy('salary_month')->pluck('salary_month')->all());
        $this->assertEquals(0, TeacherSalary::where('salary_month', 9)->value('net_amount'));
        $this->assertStringContainsString('no salary payment recorded', TeacherSalary::where('salary_month', 9)->value('note'));

        $this->artisan('nexora:import-teacher-salaries', ['file' => $this->dir . '/none.csv', '--settled-until' => '2026-13'])->assertExitCode(1);
    }
}

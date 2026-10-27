<?php

namespace Tests\Feature\Report;

use App\Models\Branch;
use App\Models\ClassCategory;
use App\Models\ClassCategoryFee;
use App\Models\ClassCategoryFeeOption;
use App\Models\Grade;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\StudentClassEnrollment;
use App\Models\Subject;
use App\Models\Teacher;
use App\Services\Web\Report\MonthlyCollectionReportService;
use App\Services\Web\Report\ReportFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Monthly collection: inactive classes are not expected; money paid to them still shows. */
class MonthlyCollectionInactiveClassTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;
    private int $n = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create(['name' => 'Main', 'code' => 'MAIN', 'is_active' => true]);
        Grade::create(['grade_name' => '10', 'is_active' => true]);
        Subject::create(['subject_name' => 'English', 'is_active' => true]);
        ClassCategory::create(['category_name' => 'Theory', 'code' => 'theory', 'is_active' => true]);
        Teacher::create(['custom_id' => 'SAT001', 'full_name' => 'T', 'initials' => 'T', 'email' => 't@t.lk', 'mobile' => '0771234567',
            'nic' => '1', 'bday' => '1980-01-01', 'gender' => 'male', 'address1' => 'x']);
    }

    /** A class with one enrolled student; returns the enrollment. */
    private function classWithStudent(string $name, bool $active, float $fee): StudentClassEnrollment
    {
        $this->n++;
        $class = StudentClass::create(['branch_id' => $this->branch->id, 'class_name' => $name, 'class_type' => 'offline', 'medium' => 'Sinhala',
            'teacher_id' => Teacher::value('id'), 'subject_id' => Subject::value('id'), 'grade_id' => Grade::value('id'),
            'is_active' => $active, 'is_ongoing' => $active]);
        $categoryFee = ClassCategoryFee::create(['student_class_id' => $class->id, 'class_category_id' => ClassCategory::value('id'), 'is_active' => $active]);
        $option = ClassCategoryFeeOption::create(['class_category_fee_id' => $categoryFee->id, 'label' => 'Theory Only', 'fee' => $fee, 'is_default' => true, 'is_active' => true]);
        $student = Student::create(['custom_id' => 'CS000' . $this->n, 'branch_id' => $this->branch->id, 'initial_name' => 'S' . $this->n,
            'guardian_mobile' => '0771234567', 'grade_id' => Grade::value('id'), 'img_url' => 'uploads/male.png']);

        return StudentClassEnrollment::create(['branch_id' => $this->branch->id, 'student_id' => $student->id, 'student_class_id' => $class->id,
            'class_category_fee_id' => $categoryFee->id, 'class_category_fee_option_id' => $option->id, 'is_active' => true, 'enrolled_at' => '2026-01-01']);
    }

    private function pay(StudentClassEnrollment $enrollment, float $amount): void
    {
        DB::table('payments')->insert([
            'branch_id' => $this->branch->id, 'student_id' => $enrollment->student_id, 'student_class_enrollment_id' => $enrollment->id,
            'mark_method' => 'manual_web', 'amount' => $amount, 'discount_amount' => 0, 'paid_at' => '2026-10-05 10:00:00',
            'payment_month' => '2026-10-01', 'payment_method' => 'cash', 'status' => 'completed',
            'receipt_number' => 'REC-T' . $enrollment->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_inactive_classes_are_left_out_unless_paid(): void
    {
        $this->classWithStudent('Active class', true, 1000);
        $this->classWithStudent('Closed class', false, 2000);
        $paidToClosed = $this->classWithStudent('Closed but paid', false, 1500);
        $this->pay($paidToClosed, 1500);

        $month = Carbon::parse('2026-10-01');
        $filter = new ReportFilter($month->copy()->startOfMonth(), $month->copy()->endOfMonth(), collect([$this->branch->id]));
        $report = app(MonthlyCollectionReportService::class)->generate($filter, $month);

        $this->assertSame(['Active class', 'Closed but paid'], $report['rows']->pluck('class_name')->all());
        $this->assertEquals(2500, $report['summary']['expected']);   // not the closed class's 2000
        $this->assertEquals(1500, $report['summary']['collected']);  // money paid to a closed class still shows
        $this->assertSame(2, $report['summary']['student_count']);
    }
}

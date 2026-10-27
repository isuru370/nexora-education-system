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
use App\Services\Web\Attendance\MonthlyClassAttendanceReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Monthly class attendance report: inactive classes are left out. */
class MonthlyClassAttendanceInactiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_classes_are_not_in_the_report(): void
    {
        $branch = Branch::create(['name' => 'Main', 'code' => 'MAIN', 'is_active' => true]);
        $grade = Grade::create(['grade_name' => '10', 'is_active' => true]);
        $subject = Subject::create(['subject_name' => 'English', 'is_active' => true]);
        $category = ClassCategory::create(['category_name' => 'Theory', 'code' => 'theory', 'is_active' => true]);
        $teacher = Teacher::create(['custom_id' => 'SAT001', 'full_name' => 'T', 'initials' => 'T', 'email' => 't@t.lk', 'mobile' => '0771234567',
            'nic' => '1', 'bday' => '1980-01-01', 'gender' => 'male', 'address1' => 'x']);
        $student = Student::create(['custom_id' => 'CS0001', 'branch_id' => $branch->id, 'initial_name' => 'S', 'guardian_mobile' => '0771234567',
            'grade_id' => $grade->id, 'img_url' => 'uploads/male.png']);

        foreach (['Active class' => true, 'Closed class' => false] as $name => $active) {
            $class = StudentClass::create(['branch_id' => $branch->id, 'class_name' => $name, 'class_type' => 'offline', 'medium' => 'Sinhala',
                'teacher_id' => $teacher->id, 'subject_id' => $subject->id, 'grade_id' => $grade->id, 'is_active' => $active, 'is_ongoing' => $active]);
            $fee = ClassCategoryFee::create(['student_class_id' => $class->id, 'class_category_id' => $category->id, 'is_active' => true]);
            $option = ClassCategoryFeeOption::create(['class_category_fee_id' => $fee->id, 'label' => 'Theory Only', 'fee' => 1000, 'is_default' => true, 'is_active' => true]);
            StudentClassEnrollment::create(['branch_id' => $branch->id, 'student_id' => $student->id, 'student_class_id' => $class->id,
                'class_category_fee_id' => $fee->id, 'class_category_fee_option_id' => $option->id, 'is_active' => true, 'enrolled_at' => '2026-01-01']);
        }

        $report = app(MonthlyClassAttendanceReportService::class)->generate('2026-09');

        $this->assertSame(['Active class'], collect($report['classes'])->pluck('class_name')->all());
    }
}

<?php

namespace Tests\Feature\Import;

use App\Models\Branch;
use App\Models\ClassCategory;
use App\Models\ClassCategoryFee;
use App\Models\ClassCategoryFeeOption;
use App\Models\ClassPaymentConfig;
use App\Models\Grade;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\StudentClassEnrollment;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Moving an institute from the old system: classes, fees, teacher % and enrollments. */
class ImportClassesTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Main Branch', 'code' => 'MAIN', 'is_active' => true]);
        $grade = Grade::create(['grade_name' => '2027 A/L', 'is_active' => true]);
        Grade::create(['grade_name' => '10', 'is_active' => true]);
        ClassCategory::create(['category_name' => 'Theory', 'code' => 'theory', 'is_active' => true]);
        Subject::create(['subject_name' => 'English', 'is_active' => true]);

        foreach (['CT0001', 'SAT0002'] as $i => $id) {
            Teacher::create(['custom_id' => $id, 'full_name' => $id, 'initials' => $id, 'email' => "t{$i}@t.lk", 'mobile' => '0771234567',
                'nic' => "nic{$i}", 'bday' => '1980-01-01', 'gender' => 'male', 'address1' => 'x']);
        }
        foreach (['CS0001', 'CS0002'] as $id) {
            Student::create(['custom_id' => $id, 'branch_id' => $branch->id, 'initial_name' => $id, 'guardian_mobile' => '0771234567',
                'grade_id' => $grade->id, 'img_url' => 'uploads/male.png']);
        }

        $this->dir = sys_get_temp_dir() . '/nexora-cls-' . uniqid();
        mkdir($this->dir);

        $this->csv('classes.csv', ['old_class_id', 'old_fee_id', 'class_name', 'teacher_custom_id', 'teacher_percentage', 'subject', 'grade', 'is_active', 'is_ongoing', 'category', 'fee', 'created_at', 'option_label'], [
            [11, 25, 'ENGLISH - GRADE10', 'CT0001', 75, 'ENGLISH', '10', 1, 1, 'Theory', 1100, '2024-09-15 00:23:30', ''],
            [11, 26, 'ENGLISH - GRADE10', 'CT0001', 75, 'ENGLISH', '10', 1, 1, 'Revision', 500, '2024-09-15 00:23:30', ''],
            // the old category "Theory+Revision" as a Theory option (listed first: still not the default),
            // and Theory twice with another fee (= "Special") or the same fee (= the same option)
            [49, 61, 'MATHS 2027', 'SAT0002', 80, 'MATHS', '2027 A/L Grade', 0, 0, 'Theory', 2500, '2024-12-01 10:00:00', 'Theory + Revision'],
            [49, 60, 'MATHS 2027', 'SAT0002', 80, 'MATHS', '2027 A/L Grade', 0, 0, 'Theory', 2000, '2024-12-01 10:00:00', ''],
            [49, 62, 'MATHS 2027', 'SAT0002', 80, 'MATHS', '2027 A/L Grade', 0, 0, 'Theory', 0, '2024-12-01 10:00:00', ''],
            [49, 63, 'MATHS 2027', 'SAT0002', 80, 'MATHS', '2027 A/L Grade', 0, 0, 'Theory', 2000, '2024-12-01 10:00:00', ''],
            // a class without fees
            [70, '', 'Spare class', 'CT0001', 75, 'English', '10', 1, 0, '', '', '2025-01-01 08:00:00', ''],
            // unknown teacher
            [80, 90, 'No teacher', 'CT0099', 75, 'English', '10', 1, 1, 'Theory', 1000, '2025-01-01 08:00:00', ''],
        ]);

        $this->csv('enrollments.csv', ['old_id', 'custom_id', 'old_fee_id', 'is_active', 'is_free_card', 'enrolled_at', 'updated_at'], [
            [1, 'CS0001', 25, 1, 0, '2024-10-01 09:00:00'],
            [2, 'CS0001', 60, 0, 0, '2024-12-02 09:00:00'],
            [3, 'CS0001', 63, 1, 1, '2024-12-03 09:00:00'], // same option as fee #60 - the active row wins
            [4, 'CS0002', 62, 1, 1, '2024-12-03 09:00:00'], // the free option
            [5, 'CS9999', 25, 1, 0, '2024-12-03 09:00:00'], // no student
            [6, 'CS0002', 90, 1, 0, '2024-12-03 09:00:00'], // class not imported
            [7, 'CS0002', 25, 0, 0, '2024-10-01 09:00:00', '2025-02-10 11:00:00'], // left in February
        ]);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*') ?: []);
        @rmdir($this->dir);
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

    private function run_import(bool $dry = false)
    {
        return $this->artisan('nexora:import-classes', array_filter([
            'file' => $this->dir . '/classes.csv',
            '--enrollments' => $this->dir . '/enrollments.csv',
            '--dry-run' => $dry,
        ]));
    }

    public function test_missing_grade_stops_the_import(): void
    {
        Grade::where('grade_name', '10')->delete();

        $this->run_import()
            ->expectsOutput('These grades are not in the database: "10"')
            ->assertExitCode(1);

        $this->assertSame(0, StudentClass::count());
    }

    public function test_dry_run_changes_nothing(): void
    {
        $this->run_import(true)->expectsOutput('DRY RUN - nothing was changed')->assertExitCode(0);

        $this->assertSame(0, StudentClass::count());
        $this->assertSame(1, Subject::count());
        $this->assertSame(1, ClassCategory::count());
    }

    public function test_classes_fees_and_enrollments_are_imported(): void
    {
        $this->run_import()->assertExitCode(0);

        $this->assertSame(3, StudentClass::count());
        $this->assertSame(['English', 'MATHS'], Subject::orderBy('subject_name')->pluck('subject_name')->all()); // "ENGLISH" = English
        $this->assertSame(['Theory', 'Revision'], ClassCategory::orderBy('id')->pluck('category_name')->all()); // no "Theory+Revision" 

        $english = StudentClass::where('class_name', 'ENGLISH - GRADE10')->first();
        $this->assertTrue($english->is_active);
        $this->assertTrue($english->is_ongoing);
        $this->assertSame('CT0001', $english->teacher->custom_id);
        $this->assertSame('10', $english->grade->grade_name);
        $this->assertSame('2024-09-15 00:23:30', $english->created_at->format('Y-m-d H:i:s'));

        $config = ClassPaymentConfig::where('student_class_id', $english->id)->first();
        $this->assertEquals(75, $config->teacher_percentage);
        $this->assertEquals(25, $config->institution_percentage);
        $this->assertEquals(0, $config->organizer_percentage);

        $maths = StudentClass::where('class_name', 'MATHS 2027')->first();
        $this->assertFalse($maths->is_active);
        $this->assertSame('2027 A/L', $maths->grade->grade_name);
        $this->assertEquals(20, ClassPaymentConfig::where('student_class_id', $maths->id)->value('institution_percentage'));

        // English: "Theory Only" and "Revision Only"
        $this->assertSame(['Theory Only', 'Revision Only'], ClassCategoryFeeOption::whereIn('class_category_fee_id',
            ClassCategoryFee::where('student_class_id', $english->id)->pluck('id'))->orderBy('id')->pluck('label')->all());

        // Maths: one Theory fee - Theory Only 2000 (default), Special 0, Theory + Revision 2500
        $fees = ClassCategoryFee::where('student_class_id', $maths->id)->with('category')->get();
        $this->assertCount(1, $fees);
        $theory = $fees->first();
        $this->assertSame('Theory', $theory->category->category_name);
        $this->assertSame(
            [['Theory Only', '2000.00', true], ['Special', '0.00', false], ['Theory + Revision', '2500.00', false]],
            ClassCategoryFeeOption::where('class_category_fee_id', $theory->id)->orderBy('id')->get()
                ->map(fn ($o) => [$o->label, (string) $o->fee, $o->is_default])->all()
        );
        $this->assertStringContainsString('fee #60,63', ClassCategoryFeeOption::where('class_category_fee_id', $theory->id)->where('is_default', true)->value('note'));

        // the class without fees has its % but no fee
        $spare = StudentClass::where('class_name', 'Spare class')->first();
        $this->assertSame(0, ClassCategoryFee::where('student_class_id', $spare->id)->count());
        $this->assertSame(1, ClassPaymentConfig::where('student_class_id', $spare->id)->count());

        // enrollments
        $this->assertSame(4, StudentClassEnrollment::count());
        $cs1 = StudentClassEnrollment::whereHas('student', fn ($q) => $q->where('custom_id', 'CS0001'))
            ->where('student_class_id', $maths->id)->get();
        $this->assertCount(1, $cs1);
        $this->assertTrue($cs1->first()->is_active);          // the active row (#3) won
        $this->assertTrue((bool) $cs1->first()->is_free_card);
        $this->assertSame('2024-12-03', $cs1->first()->enrolled_at->format('Y-m-d'));

        $free = StudentClassEnrollment::whereHas('student', fn ($q) => $q->where('custom_id', 'CS0002'))->where('student_class_id', $maths->id)->first();
        $this->assertSame('Special', $free->classCategoryFeeOption->label);
        $this->assertNull($free->left_at);

        // inactive: left on the old row's last update (as deactivating from the web)
        $left = StudentClassEnrollment::whereHas('student', fn ($q) => $q->where('custom_id', 'CS0002'))->where('student_class_id', $english->id)->first();
        $this->assertFalse($left->is_active);
        $this->assertSame('2025-02-10', $left->left_at->format('Y-m-d'));

        $problems = file_get_contents(storage_path('logs/import-classes-problems.txt'));
        $this->assertStringContainsString('class #80 No teacher: NOT imported - teacher CT0099 not found', $problems);
        $this->assertStringContainsString('enrollment #2 CS0001: NOT imported - already in this class + category', $problems);
        $this->assertStringContainsString('enrollment #5 CS9999: NOT imported - student not found', $problems);
        $this->assertStringContainsString('enrollment #6 CS0002: NOT imported - class fee #90 not imported', $problems);

        // again: nothing new
        $this->run_import()->assertExitCode(0);
        $this->assertSame(3, StudentClass::count());
        $this->assertSame(3, ClassCategoryFee::count());
        $this->assertSame(5, ClassCategoryFeeOption::count());
        $this->assertSame(3, ClassPaymentConfig::count());
        $this->assertSame(4, StudentClassEnrollment::count());
    }
}

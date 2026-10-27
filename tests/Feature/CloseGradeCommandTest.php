<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * php artisan nexora:close-grade: closes the classes of a grade, takes the
 * students out and deactivates them; a student still in a class of another
 * grade stays active; --undo puts everything back.
 */
class CloseGradeCommandTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    private int $allClass;
    private int $allEnrollment;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Carbon::setTestNow('2026-10-01 10:00:00');

        $this->buildInstitute();
        $i = $this->inst;

        // an "ALL" grade class: B. Perera (S2) is in it too
        $all = DB::table('grades')->insertGetId(['grade_name' => 'ALL']);
        $this->allClass = DB::table('student_classes')->insertGetId([
            'branch_id' => $i['b1'], 'class_name' => 'Spoken English ALL', 'teacher_id' => $i['teacher'],
            'subject_id' => DB::table('subjects')->value('id'), 'grade_id' => $all, 'is_active' => 1,
        ]);
        $this->allEnrollment = DB::table('student_class_enrollments')->insertGetId([
            'branch_id' => $i['b1'], 'student_id' => $i['s2'], 'student_class_id' => $this->allClass,
            'class_category_fee_id' => $i['f1'], 'class_category_fee_option_id' => $i['o1'],
            'is_active' => 1, 'is_free_card' => 0, 'enrolled_at' => '2026-01-01',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_dry_run_changes_nothing_and_lists_kept_students(): void
    {
        $this->artisan('nexora:close-grade', ['grades' => ['10'], '--dry-run' => true])
            ->expectsOutput('[DRY RUN] Closing grades: 10')
            ->expectsOutput('Kept active, still in an active class of another grade:')
            ->expectsOutput('Nothing changed. Run again without --dry-run to apply.')
            ->assertExitCode(0);

        $this->assertSame(0, DB::table('student_classes')->where('is_active', false)->count());
        $this->assertSame(0, DB::table('students')->where('is_active', false)->count());
        $this->assertSame(0, DB::table('grade_closures')->count());
    }

    public function test_closes_grade_and_undo_puts_it_back(): void
    {
        $i = $this->inst;

        $this->artisan('nexora:close-grade', ['grades' => ['10']])->assertExitCode(0);

        // classes of grade 10 closed, the ALL class not
        $this->assertFalse((bool) DB::table('student_classes')->where('id', $i['c1'])->value('is_active'));
        $this->assertFalse((bool) DB::table('student_classes')->where('id', $i['c2'])->value('is_active'));
        $this->assertTrue((bool) DB::table('student_classes')->where('id', $this->allClass)->value('is_active'));

        // enrollments ended today; the ALL enrollment untouched
        $e1 = DB::table('student_class_enrollments')->find($i['e1']);
        $this->assertFalse((bool) $e1->is_active);
        $this->assertSame('2026-10-01', substr($e1->left_at, 0, 10));
        $this->assertTrue((bool) DB::table('student_class_enrollments')->where('id', $this->allEnrollment)->value('is_active'));

        // S1, S3 deactivated + disabled; S2 still in the ALL class -> active
        foreach (['s1', 's3'] as $s) {
            $row = DB::table('students')->find($i[$s]);
            $this->assertFalse((bool) $row->is_active);
            $this->assertTrue((bool) $row->student_disable);
        }
        $this->assertTrue((bool) DB::table('students')->where('id', $i['s2'])->value('is_active'));

        // the future session cancelled, the pattern off
        $this->assertSame('cancelled', DB::table('class_schedules')->where('id', $i['schedule'])->value('status'));
        $this->assertSame(0, DB::table('class_schedule_patterns')->where('is_active', true)->count());

        // payments / attendance kept
        $this->assertGreaterThan(0, DB::table('payments')->count());
        $this->assertGreaterThan(0, DB::table('student_attendances')->count());

        // saved in the history
        $closure = DB::table('grade_closures')->first();
        $this->assertSame('command', $closure->source);
        $this->assertSame(['10'], json_decode($closure->grade_names, true));

        // ---- undo
        $this->artisan('nexora:close-grade', ['--undo' => 'last'])->assertExitCode(0);
        $this->assertNotNull(DB::table('grade_closures')->value('undone_at'));

        $this->assertSame(0, DB::table('student_classes')->where('is_active', false)->count());
        $e1 = DB::table('student_class_enrollments')->find($i['e1']);
        $this->assertTrue((bool) $e1->is_active);
        $this->assertNull($e1->left_at);
        $this->assertSame(0, DB::table('students')->where('is_active', false)->count());
        $this->assertSame(0, DB::table('students')->where('student_disable', true)->count());
        $this->assertSame('scheduled', DB::table('class_schedules')->where('id', $i['schedule'])->value('status'));
        $this->assertSame(0, DB::table('class_schedule_patterns')->where('is_active', false)->count());

        // nothing left to undo
        $this->artisan('nexora:close-grade', ['--undo' => 'last'])->assertExitCode(1);
    }

    public function test_unknown_grade_is_refused(): void
    {
        $this->artisan('nexora:close-grade', ['grades' => ['2099 A/L']])
            ->expectsOutput('Grade not found: 2099 A/L')
            ->assertExitCode(1);
    }

    public function test_run_saved_as_file_by_the_first_version_is_added_to_history_and_can_be_undone(): void
    {
        $i = $this->inst;

        // what the first version of the command did and saved
        DB::table('student_classes')->where('id', $i['c1'])->update(['is_active' => false]);
        DB::table('students')->where('id', $i['s1'])->update(['is_active' => false, 'student_disable' => true]);
        Storage::put('close-grade/20261027_213000.json', json_encode([
            'grades' => ['10'],
            'at' => '2026-10-27 21:30:00',
            'classes' => [['id' => $i['c1'], 'class_name' => 'Maths 10 CMB', 'is_active' => 1]],
            'enrollments' => [],
            'patterns' => [],
            'schedules' => [],
            'students' => [['id' => $i['s1'], 'is_active' => 1, 'student_disable' => 0]],
        ]));

        $this->artisan('nexora:close-grade', ['--undo' => 'last'])->assertExitCode(0);

        $this->assertSame('file', DB::table('grade_closures')->value('source'));
        $this->assertTrue(Storage::exists('close-grade/20261027_213000.json.imported'));
        $this->assertTrue((bool) DB::table('student_classes')->where('id', $i['c1'])->value('is_active'));
        $this->assertTrue((bool) DB::table('students')->where('id', $i['s1'])->value('is_active'));
        $this->assertFalse((bool) DB::table('students')->where('id', $i['s1'])->value('student_disable'));
    }
}

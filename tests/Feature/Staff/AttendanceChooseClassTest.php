<?php

namespace Tests\Feature\Staff;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * A student in two back-to-back classes:
 *   A  4:30 PM - 6:00 PM
 *   B  6:00 PM - 8:00 PM   (scanning opens one hour early, at 5:00 PM)
 * At 5:30 PM both are open, so the scan must ask which class.
 */
class AttendanceChooseClassTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    private int $scheduleA;
    private int $scheduleB;

    protected function setUp(): void
    {
        parent::setUp();
        $i = $this->buildInstitute();

        // Only these two classes today
        DB::table('class_schedules')->update(['is_active' => 0]);

        $classB = DB::table('student_classes')->insertGetId([
            'branch_id' => $i['b1'], 'class_name' => 'Physics 10 CMB', 'teacher_id' => $i['teacher'],
            'subject_id' => DB::table('subjects')->value('id'), 'grade_id' => DB::table('grades')->value('id'), 'is_active' => 1,
        ]);
        $feeB = DB::table('class_category_fees')->insertGetId([
            'student_class_id' => $classB, 'class_category_id' => DB::table('class_categories')->value('id'),
        ]);
        $optionB = DB::table('class_category_fee_options')->insertGetId([
            'class_category_fee_id' => $feeB, 'label' => 'Normal', 'fee' => 1500, 'is_default' => 1,
        ]);
        DB::table('class_payment_configs')->insert([
            'branch_id' => $i['b1'], 'student_class_id' => $classB, 'teacher_id' => $i['teacher'],
            'teacher_percentage' => 60, 'organizer_percentage' => 0, 'institution_percentage' => 40,
            'effective_from' => '2026-01-01', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('student_class_enrollments')->insert([
            'branch_id' => $i['b1'], 'student_id' => $i['s1'], 'student_class_id' => $classB,
            'class_category_fee_id' => $feeB, 'class_category_fee_option_id' => $optionB,
            'is_active' => 1, 'is_free_card' => 0, 'enrolled_at' => '2026-01-01',
        ]);

        $schedule = fn (int $class, int $fee, string $from, string $to) => DB::table('class_schedules')->insertGetId([
            'branch_id' => $i['b1'], 'student_class_id' => $class, 'class_category_fee_id' => $fee,
            'class_date' => '2026-10-13', 'day_of_week' => 'tuesday', 'start_time' => $from, 'end_time' => $to,
            'status' => 'scheduled', 'is_active' => 1,
        ]);

        $this->scheduleA = $schedule($i['c1'], $i['f1'], '16:30', '18:00');
        $this->scheduleB = $schedule($classB, $feeB, '18:00', '20:00');

        Sanctum::actingAs($i['cashier']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function scan(?int $scheduleId = null)
    {
        return $this->postJson('/api/new-attendance/scan', array_filter([
            'code' => 'S1', 'mark_method' => 'qr_mobile', 'schedule_id' => $scheduleId,
        ]));
    }

    private function marked(): array
    {
        return DB::table('student_attendances')->where('student_id', $this->inst['s1'])
            ->whereIn('class_schedule_id', [$this->scheduleA, $this->scheduleB])
            ->orderBy('class_schedule_id')
            ->pluck('class_schedule_id')->map(fn ($id) => (int) $id)->all();
    }

    public function test_two_open_classes_ask_which_class_and_mark_nothing(): void
    {
        Carbon::setTestNow('2026-10-13 17:30:00');

        $this->scan()
            ->assertStatus(409)
            ->assertJsonPath('data.requires_class_selection', true)
            ->assertJsonPath('data.student.custom_id', 'S1')
            ->assertJsonCount(2, 'data.classes')
            ->assertJsonPath('data.classes.0.schedule_id', $this->scheduleA)
            ->assertJsonPath('data.classes.0.class.class_name', 'Maths 10 CMB')
            ->assertJsonPath('data.classes.0.is_started', true)
            ->assertJsonPath('data.classes.1.schedule_id', $this->scheduleB)
            ->assertJsonPath('data.classes.1.class.class_name', 'Physics 10 CMB')
            ->assertJsonPath('data.classes.1.is_started', false)
            ->assertJsonPath('data.classes.1.already_marked', false);

        $this->assertSame([], $this->marked());
    }

    public function test_the_picked_class_is_marked_and_only_that_one(): void
    {
        Carbon::setTestNow('2026-10-13 17:30:00');

        $this->scan($this->scheduleB)->assertStatus(201)
            ->assertJsonPath('data.schedule.id', $this->scheduleB);

        $this->assertSame([$this->scheduleB], $this->marked());

        // Scanned again later: still asks, and shows B as already marked
        $this->scan()->assertStatus(409)
            ->assertJsonPath('data.classes.0.already_marked', false)
            ->assertJsonPath('data.classes.1.already_marked', true);
    }

    public function test_one_open_class_is_marked_straight_away_as_before(): void
    {
        Carbon::setTestNow('2026-10-13 16:00:00'); // only A is open (B opens at 5 PM)

        $this->scan()->assertStatus(201)->assertJsonPath('data.schedule.id', $this->scheduleA);
        $this->assertSame([$this->scheduleA], $this->marked());
    }

    public function test_a_cancelled_class_is_skipped(): void
    {
        Carbon::setTestNow('2026-10-13 17:30:00');
        DB::table('class_schedules')->where('id', $this->scheduleA)->update(['status' => 'cancelled']);

        $this->scan()->assertStatus(201)->assertJsonPath('data.schedule.id', $this->scheduleB);
        $this->assertSame([$this->scheduleB], $this->marked());
    }

    public function test_a_class_that_is_not_open_or_not_the_students_cannot_be_picked(): void
    {
        Carbon::setTestNow('2026-10-13 16:00:00'); // B not open yet

        $this->scan($this->scheduleB)->assertStatus(422)
            ->assertJsonPath('message', 'The selected class is not open for attendance now.');

        // another class's schedule, not the student's
        Carbon::setTestNow('2026-10-13 17:30:00');
        $other = DB::table('class_schedules')->insertGetId([
            'branch_id' => $this->inst['b1'], 'student_class_id' => $this->inst['c1'], 'class_category_fee_id' => $this->inst['f1'],
            'class_date' => '2026-10-14', 'day_of_week' => 'wednesday', 'start_time' => '16:30', 'end_time' => '18:00',
            'status' => 'scheduled', 'is_active' => 1,
        ]);
        $this->scan($other)->assertStatus(422);

        $this->assertSame([], $this->marked());
    }
}

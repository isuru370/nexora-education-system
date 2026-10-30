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
        \App\Support\AttendanceRules::forget();
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

    /* ---------- Attendance Rules (per institute) ---------- */

    public function test_default_window_is_one_hour_before_until_the_end(): void
    {
        \App\Support\AttendanceRules::forget();

        Carbon::setTestNow('2026-10-13 15:00:00'); // A opens 15:30
        $this->scan()->assertStatus(422);

        Carbon::setTestNow('2026-10-13 20:30:00'); // B ended 20:00
        $this->scan()->assertStatus(422);
        $this->assertSame([], $this->marked());
    }

    public function test_two_hours_before_and_after(): void
    {
        \App\Support\AttendanceRules::save(['before_minutes' => 120, 'after_minutes' => 120]);

        Carbon::setTestNow('2026-10-13 14:30:00'); // A: 14:30 - 20:00, B opens 16:00
        $this->scan()->assertStatus(201)->assertJsonPath('data.schedule.id', $this->scheduleA);

        // 21:30: only B (until 22:00), even after the 8:45 PM auto-complete
        DB::table('class_schedules')->where('id', $this->scheduleB)->update(['status' => 'completed']);
        Carbon::setTestNow('2026-10-13 21:30:00');
        $this->scan()->assertStatus(201)->assertJsonPath('data.schedule.id', $this->scheduleB);

        Carbon::setTestNow('2026-10-13 22:01:00');
        $this->assertSame([$this->scheduleA, $this->scheduleB], $this->marked());
    }

    public function test_no_time_limit_lists_todays_classes_only(): void
    {
        \App\Support\AttendanceRules::save(['no_time_limit' => true]);

        // A class yesterday is never offered
        DB::table('class_schedules')->insert([
            'branch_id' => $this->inst['b1'], 'student_class_id' => $this->inst['c1'], 'class_category_fee_id' => $this->inst['f1'],
            'class_date' => '2026-10-12', 'day_of_week' => 'monday', 'start_time' => '16:30', 'end_time' => '18:00',
            'status' => 'completed', 'is_active' => 1,
        ]);

        Carbon::setTestNow('2026-10-13 09:00:00'); // long before both classes
        $this->scan()
            ->assertStatus(409)
            ->assertJsonCount(2, 'data.classes')
            ->assertJsonPath('data.classes.0.schedule_id', $this->scheduleA)
            ->assertJsonPath('data.classes.1.schedule_id', $this->scheduleB);

        $this->scan($this->scheduleB)->assertStatus(201);

        // Only one class today: marked straight away
        DB::table('class_schedules')->where('id', $this->scheduleB)->update(['status' => 'cancelled']);
        $this->scan()->assertStatus(201)->assertJsonPath('data.schedule.id', $this->scheduleA);
    }

    public function test_attendance_rules_are_saved_on_the_rules_page(): void
    {
        $type = DB::table('user_types')->insertGetId(['name' => 'SUPER_ADMIN', 'code' => 'SUPER_ADMIN', 'is_active' => 1]);
        $super = \App\Models\User::forceCreate([
            'name' => 'Super', 'email' => 'super2@test.lk', 'password' => 'password123',
            'user_type_id' => $type, 'is_active' => 1,
        ]);

        $this->actingAs($super)->get('/admin/setting')->assertOk()->assertSee('Payment &amp; Attendance Rules', false);
        $this->actingAs($this->inst['admin'])->get('/admin/setting')->assertOk()->assertDontSee('Payment &amp; Attendance Rules', false);

        $this->actingAs($super)->put('/payment-rules', [
            'before_minutes' => 120, 'after_minutes' => 120, 'no_time_limit' => '0',
        ])->assertRedirect('/payment-rules');

        $this->assertSame(
            ['before_minutes' => 120, 'after_minutes' => 120, 'no_time_limit' => false],
            \App\Support\AttendanceRules::all()
        );
    }
}

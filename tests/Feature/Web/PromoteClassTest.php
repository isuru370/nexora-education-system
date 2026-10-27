<?php

namespace Tests\Feature\Web;

use App\Models\ClassPromotion;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * "Promote Class": "Maths 10 CMB" (grade 10) goes on as "Maths 11 CMB"
 * (grade 11) from a start date; the old class stays as it was.
 */
class PromoteClassTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    private int $grade11;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-01 10:00:00');
        $this->buildInstitute();
        $this->grade11 = DB::table('grades')->insertGetId(['grade_name' => '11']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function promote(string $start, array $students, array $schedules = null)
    {
        $i = $this->inst;

        return $this->actingAs($i['admin'])->post('/class-promotions', [
            'from_class_id' => $i['c1'],
            'class_name' => 'Maths 11 CMB',
            'grade_id' => $this->grade11,
            'teacher_id' => $i['teacher'],
            'start_date' => $start,
            'schedule_until' => Carbon::parse($start)->addYear()->subDay()->toDateString(),
            'student_ids' => $students,
            'schedules' => $schedules ?? [
                ['include' => 1, 'class_day' => 'sunday', 'start_time' => '08:00', 'end_time' => '10:00', 'class_category_fee_id' => $i['f1']],
                ['include' => 1, 'class_day' => 'wednesday', 'start_time' => '15:00', 'end_time' => '17:00', 'class_category_fee_id' => $i['f1']],
                ['class_day' => 'friday', 'start_time' => '15:00', 'end_time' => '17:00', 'class_category_fee_id' => $i['f1']], // not ticked
            ],
        ]);
    }

    private function newClassId(): int
    {
        return (int) ClassPromotion::value('to_class_id');
    }

    public function test_only_admin(): void
    {
        $this->actingAs($this->inst['cashier'])->get('/class-promotions')->assertForbidden();
        $this->actingAs($this->inst['admin'])->get('/class-promotions')->assertOk()->assertSee('Maths 10 CMB');
    }

    public function test_form_suggests_next_grade_copies_schedule_and_warns_about_paid_months(): void
    {
        $this->actingAs($this->inst['admin'])
            ->get('/class-promotions/create?class_id=' . $this->inst['c1'] . '&start_date=2026-10-01')
            ->assertOk()
            ->assertSee('value="Maths 11 CMB"', false)
            ->assertSee('A. Silva')
            ->assertSee('B. Perera')
            ->assertSee('Oct 2026')                                  // S1 paid October to the old class
            ->assertSee('already paid the old class')
            ->assertSee('<option value="tuesday" selected>', false); // old schedule: Tuesday
    }

    public function test_promote_from_next_year_then_the_start_date_closes_the_old_class(): void
    {
        $i = $this->inst;

        $this->promote('2027-01-01', [$i['s1']])->assertRedirect();

        $p = ClassPromotion::first();
        $this->assertSame(ClassPromotion::SCHEDULED, $p->status);
        $new = $this->newClassId();

        // new class: grade 11, same subject / branch; fee, option and teacher % copied
        $class = DB::table('student_classes')->find($new);
        $this->assertSame('Maths 11 CMB', $class->class_name);
        $this->assertSame($this->grade11, (int) $class->grade_id);
        $this->assertSame($i['b1'], (int) $class->branch_id);
        $option = DB::table('class_category_fee_options as o')
            ->join('class_category_fees as f', 'f.id', '=', 'o.class_category_fee_id')
            ->where('f.student_class_id', $new)->first(['o.id', 'o.fee', 'f.id as fee_id']);
        $this->assertEquals(2000, $option->fee);
        $this->assertSame(1, DB::table('class_payment_configs')->where('student_class_id', $new)->where('teacher_percentage', 60)->count());

        // only S1 moved, from the start date, same fee option
        $enrollment = DB::table('student_class_enrollments')->where('student_class_id', $new)->get();
        $this->assertCount(1, $enrollment);
        $this->assertSame($i['s1'], (int) $enrollment[0]->student_id);
        $this->assertSame('2027-01-01', substr($enrollment[0]->enrolled_at, 0, 10));
        $this->assertSame($option->id, (int) $enrollment[0]->class_category_fee_option_id);

        // schedule: Sunday + Wednesday in 2027 (Friday not ticked)
        $this->assertSame(2, DB::table('class_schedule_patterns')->where('student_class_id', $new)->count());
        $this->assertSame(52 + 52, DB::table('class_schedules')->where('student_class_id', $new)->count());
        $this->assertSame(0, DB::table('class_schedules')->where('student_class_id', $new)->where('class_date', '<', '2027-01-01')->count());

        // old class: goes on until 2026-12-31
        $this->assertTrue((bool) DB::table('student_classes')->where('id', $i['c1'])->value('is_active'));
        $e1 = DB::table('student_class_enrollments')->find($i['e1']);
        $this->assertTrue((bool) $e1->is_active);
        $this->assertSame('2026-12-31', substr($e1->left_at, 0, 10));
        $this->assertSame('scheduled', DB::table('class_schedules')->where('id', $i['schedule'])->value('status'));
        $grade10 = (int) DB::table('grades')->where('grade_name', '10')->value('id');
        $this->assertSame($grade10, (int) DB::table('students')->where('id', $i['s1'])->value('grade_id'));

        // ---- the start date comes
        Carbon::setTestNow('2027-01-01 00:30:00');
        $this->artisan('nexora:apply-class-promotions')->expectsOutput('Finished 1 class promotion(s).')->assertExitCode(0);

        $this->assertSame(ClassPromotion::DONE, $p->fresh()->status);
        $this->assertFalse((bool) DB::table('student_classes')->where('id', $i['c1'])->value('is_active'));
        $this->assertFalse((bool) DB::table('student_class_enrollments')->where('id', $i['e1'])->value('is_active'));
        $this->assertFalse((bool) DB::table('student_class_enrollments')->where('id', $i['e2'])->value('is_active'));
        $this->assertSame($this->grade11, (int) DB::table('students')->where('id', $i['s1'])->value('grade_id'));
        $this->assertSame($grade10, (int) DB::table('students')->where('id', $i['s2'])->value('grade_id')); // left, not moved

        // the old class name / grade did not change (old reports stay right)
        $this->assertSame('Maths 10 CMB', DB::table('student_classes')->where('id', $i['c1'])->value('class_name'));
        $this->assertSame($grade10, (int) DB::table('student_classes')->where('id', $i['c1'])->value('grade_id'));

        $this->actingAs($i['admin'])->get('/class-promotions/' . $p->id)
            ->assertOk()->assertSee('Maths 10 CMB → Maths 11 CMB', false)->assertSee('B. Perera')->assertSee('Done');
    }

    public function test_promote_from_today_closes_old_class_now_and_cancels_its_sessions(): void
    {
        $i = $this->inst;

        // S2's grade was not up to date (still 9 in a grade 10 class): gets 11 too
        $grade9 = DB::table('grades')->insertGetId(['grade_name' => '9']);
        DB::table('students')->where('id', $i['s2'])->update(['grade_id' => $grade9]);

        $this->promote('2026-10-01', [$i['s1'], $i['s2']]);

        $this->assertSame(ClassPromotion::DONE, ClassPromotion::value('status'));
        $this->assertFalse((bool) DB::table('student_classes')->where('id', $i['c1'])->value('is_active'));
        $this->assertSame('cancelled', DB::table('class_schedules')->where('id', $i['schedule'])->value('status'));
        $this->assertSame('2026-09-30', substr(DB::table('class_schedule_patterns')->where('student_class_id', $i['c1'])->value('end_date'), 0, 10));
        $this->assertSame(2, DB::table('students')->where('grade_id', $this->grade11)->count());
    }

    public function test_undo_puts_everything_back_unless_the_new_class_has_payments(): void
    {
        $i = $this->inst;

        $this->promote('2026-10-01', [$i['s1'], $i['s2']]);
        $p = ClassPromotion::first();
        $new = $this->newClassId();

        // a payment on the new class blocks undo
        $enrollment = DB::table('student_class_enrollments')->where('student_class_id', $new)->value('id');
        $payment = DB::table('payments')->insertGetId([
            'branch_id' => $i['b1'], 'student_id' => $i['s1'], 'student_class_enrollment_id' => $enrollment,
            'user_id' => $i['cashier']->id, 'amount' => 2000, 'paid_at' => now(), 'payment_month' => '2026-10-01',
            'payment_method' => 'cash', 'status' => 'completed', 'receipt_number' => 'R-NEW',
        ]);
        $this->actingAs($i['admin'])->post("/class-promotions/{$p->id}/undo")
            ->assertSessionHas('error', 'The new class already has payments: it cannot be undone.');

        DB::table('payments')->where('id', $payment)->delete();

        $this->actingAs($i['admin'])->post("/class-promotions/{$p->id}/undo")->assertRedirect("/class-promotions/{$p->id}");

        $this->assertSame(ClassPromotion::UNDONE, $p->fresh()->status);
        $this->assertNotNull(DB::table('student_classes')->where('id', $new)->value('deleted_at'));
        $this->assertTrue((bool) DB::table('student_classes')->where('id', $i['c1'])->value('is_active'));
        $e1 = DB::table('student_class_enrollments')->find($i['e1']);
        $this->assertTrue((bool) $e1->is_active);
        $this->assertNull($e1->left_at);
        $this->assertSame('scheduled', DB::table('class_schedules')->where('id', $i['schedule'])->value('status'));
        $this->assertSame('2026-12-31', substr(DB::table('class_schedule_patterns')->where('student_class_id', $i['c1'])->value('end_date'), 0, 10));
        $this->assertSame(0, DB::table('students')->where('grade_id', $this->grade11)->count());

        // the old class can be promoted again
        $this->promote('2026-10-01', [$i['s1']])->assertSessionHasNoErrors();
        $this->assertSame(2, ClassPromotion::count());
    }

    public function test_no_student_ticked_is_refused(): void
    {
        $this->promote('2027-01-01', [])->assertSessionHasErrors('student_ids');
        $this->assertSame(0, ClassPromotion::count());
    }
}

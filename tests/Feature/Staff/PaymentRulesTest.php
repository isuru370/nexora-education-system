<?php

namespace Tests\Feature\Staff;

use App\Models\User;
use App\Services\Web\MonthlyReport\MonthlyReportService;
use App\Services\Web\PaymentReminder\PaymentReminderService;
use App\Support\PaymentRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Payment Rules (per institute, Super Admin): custom amount, fee by
 * attendance, tute with the payment. The fee is 2,000 (no hall fee).
 * S2 is unpaid in class 1 (Colombo); November is used for new payments.
 */
class PaymentRulesTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-28 15:00:00');
        $this->buildInstitute();
        PaymentRules::forget();
        Sanctum::actingAs($this->inst['cashier']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        PaymentRules::forget();
        parent::tearDown();
    }

    private function attend(int $enrollment, int $student, int $times, string $month = '2026-11'): void
    {
        $base = (array) DB::table('class_schedules')->where('id', $this->inst['schedule'])->first();
        unset($base['id']);

        for ($i = 1; $i <= $times; $i++) {
            $date = sprintf('%s-%02d', $month, $i * 3);
            $schedule = DB::table('class_schedules')->insertGetId(['class_date' => $date, 'status' => 'completed'] + $base);

            DB::table('student_attendances')->insert([
                'branch_id' => $this->inst['b1'], 'student_id' => $student,
                'student_class_enrollment_id' => $enrollment, 'class_schedule_id' => $schedule,
                'attended_at' => sprintf('%s-%02d 15:05:00', $month, $i * 3), 'mark_method' => 'qr_mobile',
            ]);
        }
    }

    private function pay(float $amount, array $extra = [])
    {
        return $this->postJson('/api/new-payment/pay', [
            'code' => 'S2', 'enrollment_id' => $this->inst['e2'], 'amount' => $amount,
            'payment_month' => '2026-11-01', 'mark_method' => 'qr_mobile', 'payment_method' => 'cash',
        ] + $extra);
    }

    private function monthInfo()
    {
        return $this->postJson('/api/new-payment/month-info', [
            'code' => 'S2', 'enrollment_id' => $this->inst['e2'], 'payment_month' => '2026-11-01',
        ]);
    }

    private function halfOption(): int
    {
        return DB::table('class_category_fee_options')->insertGetId([
            'class_category_fee_id' => $this->inst['f1'], 'label' => 'Half', 'fee' => 1000,
            'is_default' => 0, 'is_active' => 1,
        ]);
    }

    /* ---------- Off by default: nothing changes ---------- */

    public function test_rules_are_off_by_default_and_less_than_the_fee_is_refused(): void
    {
        $this->attend($this->inst['e2'], $this->inst['s2'], 1);
        $half = $this->halfOption();

        $this->monthInfo()
            ->assertOk()
            ->assertJsonPath('data.attendance_count', 1)
            ->assertJsonPath('data.fee_option_enabled', false)
            ->assertJsonPath('data.fee_options', [])
            ->assertJsonPath('data.no_attendance', false)
            ->assertJsonPath('data.custom_amount', false)
            ->assertJsonPath('data.tute.enabled', false);

        $this->pay(1000, ['fee_option_id' => $half])->assertStatus(422);
        $this->assertSame(0, DB::table('payments')->where('student_class_enrollment_id', $this->inst['e2'])->count());
    }

    /* ---------- Fee option picked at payment ---------- */

    public function test_cashier_picks_the_half_option_for_the_month(): void
    {
        PaymentRules::save(['fee_option' => true]);
        $this->attend($this->inst['e2'], $this->inst['s2'], 1);
        $half = $this->halfOption();

        $this->monthInfo()
            ->assertJsonPath('data.attendance_count', 1)
            ->assertJsonPath('data.fee_option_enabled', true)
            ->assertJsonPath('data.fee_options.0.label', 'Normal')
            ->assertJsonPath('data.fee_options.0.is_student_option', true)
            ->assertJsonPath('data.fee_options.1.label', 'Half')
            ->assertJsonPath('data.fee_options.1.amount', 1000);

        $this->pay(999, ['fee_option_id' => $half])->assertStatus(422);   // below the option
        $this->pay(1000)->assertStatus(422);                              // no option picked
        $this->pay(1000, ['fee_option_id' => $half])->assertCreated();

        $payment = DB::table('payments')->where('student_class_enrollment_id', $this->inst['e2'])->first();
        $this->assertEquals(1000, $payment->amount);
        $this->assertEquals(1000, $payment->discount_amount);
        $this->assertStringContainsString('Fee option: Half', $payment->note);

        // The student's own option is unchanged
        $this->assertSame(
            $this->inst['o1'],
            (int) DB::table('student_class_enrollments')->where('id', $this->inst['e2'])->value('class_category_fee_option_id')
        );
    }

    public function test_an_option_of_another_class_is_refused(): void
    {
        PaymentRules::save(['fee_option' => true]);
        $other = DB::table('class_category_fee_options')->insertGetId([
            'class_category_fee_id' => $this->inst['f2'] ?? $this->inst['f1'] + 1, 'label' => 'Cheap', 'fee' => 100,
            'is_default' => 0, 'is_active' => 1,
        ]);

        $this->pay(100, ['fee_option_id' => $other])->assertStatus(422);
    }

    /* ---------- No attendance ---------- */

    public function test_no_attendance_is_not_unpaid_in_reports_and_reminders(): void
    {
        PaymentRules::save(['no_attendance' => true]);
        DB::table('teacher_branches')->insert(['teacher_id' => $this->inst['teacher'], 'branch_id' => $this->inst['b1']]);
        $this->actingAs($this->inst['admin']);

        $this->monthInfo()->assertJsonPath('data.no_attendance', true);

        $report = app(MonthlyReportService::class)->teacherStudentPaymentReport($this->inst['teacher'], 2026, 11, 'fee-month');
        $category = $report['classes'][0]['categories'][0];
        $this->assertSame(2, $category['noattend_count']);   // S1 and S2: no attendance in November
        $this->assertSame(0, $category['unpaid_count']);

        $reminders = app(PaymentReminderService::class)
            ->report($this->inst['c1'], $this->inst['f1'], Carbon::parse('2026-11-01'));
        $this->assertSame(0, $reminders['summary']['unpaid']);
        $this->assertSame(2, $reminders['summary']['no_attendance']);

        // One attendance: unpaid again
        $this->attend($this->inst['e2'], $this->inst['s2'], 1);
        $reminders = app(PaymentReminderService::class)
            ->report($this->inst['c1'], $this->inst['f1'], Carbon::parse('2026-11-01'));
        $this->assertSame(1, $reminders['summary']['unpaid']);
    }

    /* ---------- Custom amount ---------- */

    public function test_custom_amount_saves_the_rest_as_a_discount_and_the_month_is_paid(): void
    {
        PaymentRules::save(['custom_amount' => true]);

        $this->monthInfo()->assertJsonPath('data.custom_amount', true);
        $this->pay(1500)->assertCreated();

        $payment = DB::table('payments')->where('student_class_enrollment_id', $this->inst['e2'])->first();
        $this->assertEquals(1500, $payment->amount);
        $this->assertEquals(500, $payment->discount_amount);
        $this->assertStringContainsString('Custom amount', $payment->note);

        $this->monthInfo()->assertJsonPath('data.already_paid', true);
        $this->pay(500)->assertStatus(409);   // month already paid
    }

    /* ---------- Tute ---------- */

    public function test_tute_can_be_given_with_the_payment_once(): void
    {
        PaymentRules::save(['tute' => true]);

        $this->monthInfo()->assertJsonPath('data.tute.enabled', true)->assertJsonPath('data.tute.issued', false);

        $this->pay(2000, ['give_tute' => true])->assertCreated();

        $this->assertSame(1, DB::table('student_tutes')->where('student_class_enrollment_id', $this->inst['e2'])->count());
        $this->monthInfo()->assertJsonPath('data.tute.issued', true);
    }

    public function test_give_tute_is_ignored_when_tutes_are_off(): void
    {
        $this->pay(2000, ['give_tute' => true])->assertCreated();
        $this->assertSame(0, DB::table('student_tutes')->count());
    }

    /* ---------- Settings page ---------- */

    public function test_only_super_admin_can_change_the_rules(): void
    {
        $this->actingAs($this->inst['admin'])->get('/payment-rules')->assertForbidden();

        $type = DB::table('user_types')->insertGetId(['name' => 'SUPER_ADMIN', 'code' => 'SUPER_ADMIN', 'is_active' => 1]);
        $super = User::forceCreate([
            'name' => 'Super', 'email' => 'super@test.lk', 'password' => 'password123',
            'user_type_id' => $type, 'is_active' => 1,
        ]);

        $this->actingAs($super)->get('/payment-rules')->assertOk()->assertSee('Attendance Rules');

        $this->actingAs($super)->put('/payment-rules', [
            'fee_option' => '1',
            'custom_amount' => '0',
            'no_attendance' => '1',
            'tute' => '0',
        ])->assertRedirect('/payment-rules');

        $this->assertSame(
            ['fee_option' => true, 'custom_amount' => false, 'no_attendance' => true, 'tute' => false],
            PaymentRules::all()
        );

        // The page shows the saved switches as on
        $this->actingAs($super)->get('/payment-rules')
            ->assertSee('id="fee_option" checked', false)
            ->assertSee('id="no_attendance" checked', false)
            ->assertDontSee('id="tute" checked', false)
            ->assertDontSee('@checked', false);
    }
}

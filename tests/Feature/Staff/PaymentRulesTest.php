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

    /* ---------- Off by default: nothing changes ---------- */

    public function test_rules_are_off_by_default_and_less_than_the_fee_is_refused(): void
    {
        $this->attend($this->inst['e2'], $this->inst['s2'], 1);

        $this->monthInfo()
            ->assertOk()
            ->assertJsonPath('data.attendance_count', 1)
            ->assertJsonPath('data.rule.enabled', false)
            ->assertJsonPath('data.suggested_amount', 2000)
            ->assertJsonPath('data.custom_amount', false)
            ->assertJsonPath('data.tute.enabled', false);

        $this->pay(1000)->assertStatus(422);
        $this->assertSame(0, DB::table('payments')->where('student_class_enrollment_id', $this->inst['e2'])->count());
    }

    /* ---------- Fee by attendance ---------- */

    public function test_one_attendance_gives_half_fee_saved_as_a_discount(): void
    {
        PaymentRules::save(['attendance_rule' => true, 'tiers' => [0 => 0, 1 => 50]]);
        $this->attend($this->inst['e2'], $this->inst['s2'], 1);

        $this->monthInfo()
            ->assertJsonPath('data.attendance_count', 1)
            ->assertJsonPath('data.rule.percent', 50)
            ->assertJsonPath('data.rule.reason', 'Attendance 1: 50% fee')
            ->assertJsonPath('data.suggested_amount', 1000)
            ->assertJsonPath('data.suggested_discount', 1000);

        $this->pay(999)->assertStatus(422);   // below the rule, custom amount is off
        $this->pay(1000)->assertCreated();

        $payment = DB::table('payments')->where('student_class_enrollment_id', $this->inst['e2'])->first();
        $this->assertEquals(1000, $payment->amount);
        $this->assertEquals(1000, $payment->discount_amount);
        $this->assertStringContainsString('Attendance 1: 50% fee', $payment->note);
    }

    public function test_two_or_more_attendances_pay_the_full_fee(): void
    {
        PaymentRules::save(['attendance_rule' => true, 'tiers' => [0 => 0, 1 => 50]]);
        $this->attend($this->inst['e2'], $this->inst['s2'], 3);

        $this->monthInfo()->assertJsonPath('data.rule.percent', 100)->assertJsonPath('data.suggested_amount', 2000);
        $this->pay(1000)->assertStatus(422);
    }

    public function test_no_attendance_means_no_fee_in_reports_and_reminders(): void
    {
        PaymentRules::save(['attendance_rule' => true, 'tiers' => [0 => 0, 1 => 50]]);
        DB::table('teacher_branches')->insert(['teacher_id' => $this->inst['teacher'], 'branch_id' => $this->inst['b1']]);
        $this->actingAs($this->inst['admin']);

        $this->monthInfo()
            ->assertJsonPath('data.rule.no_fee', true)
            ->assertJsonPath('data.suggested_amount', 0);

        $report = app(MonthlyReportService::class)->teacherStudentPaymentReport($this->inst['teacher'], 2026, 11, 'fee-month');
        $category = $report['classes'][0]['categories'][0];
        $this->assertSame(2, $category['nofee_count']);   // S1 and S2: no attendance in November
        $this->assertSame(0, $category['unpaid_count']);

        $reminders = app(PaymentReminderService::class)
            ->report($this->inst['c1'], $this->inst['f1'], Carbon::parse('2026-11-01'));
        $this->assertSame(0, $reminders['summary']['unpaid']);
        $this->assertSame(2, $reminders['summary']['no_fee']);
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

        $this->actingAs($super)->get('/payment-rules')->assertOk()->assertSee('Payment Rules');

        $this->actingAs($super)->put('/payment-rules', [
            'custom_amount' => '1',
            'attendance_rule' => '1',
            'tute' => '0',
            'tiers' => [['attendance' => 1, 'percent' => 50], ['attendance' => 0, 'percent' => 0], ['attendance' => 2, 'percent' => 75]],
        ])->assertRedirect('/payment-rules');

        $this->assertTrue(PaymentRules::customAmount());
        $this->assertTrue(PaymentRules::attendanceRule());
        $this->assertFalse(PaymentRules::tute());
        $this->assertSame([0 => 0, 1 => 50, 2 => 75], PaymentRules::tiers());
        $this->assertSame(75, PaymentRules::percentFor(2));
        $this->assertSame(100, PaymentRules::percentFor(5));
    }
}

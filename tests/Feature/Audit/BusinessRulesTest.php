<?php

namespace Tests\Feature\Audit;

use App\Models\StudentClassEnrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Money rules found in the business logic review (Nov 2026).
 *
 * Fixture: Maths 10 CMB, fee 2,000, teacher 60% / institute 40%, no hall fee.
 * S1 paid October 2,000 (paid_at 5 Oct).
 */
class BusinessRulesTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-28 10:00:00');
        $this->buildInstitute();
        Sanctum::actingAs($this->inst['cashier']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function pay(string $code, int $enrollment, float $amount, string $month = '2026-10-01', array $extra = [])
    {
        return $this->postJson('/api/new-payment/pay', $extra + [
            'code' => $code, 'enrollment_id' => $enrollment, 'amount' => $amount,
            'payment_month' => $month, 'mark_method' => 'qr_mobile', 'payment_method' => 'cash',
        ]);
    }

    public function test_more_than_the_fee_is_saved_as_it_is(): void
    {
        // no upper limit (asked by the institute): a wrong amount is found in
        // the day's audit and the payment is deleted
        $this->pay('S2', $this->inst['e2'], 2500)->assertStatus(201);

        $payment = DB::table('payments')->where('student_id', $this->inst['s2'])->first();
        $this->assertEquals(2500, $payment->amount);
        $this->assertEquals(2500, DB::table('payment_split_snapshots')->where('payment_id', $payment->id)->value('payment_amount'));

        // less than the fee is still refused (no Payment Rules)
        $this->pay('S2', $this->inst['e2'], 1500, '2026-09-01')->assertStatus(422);

        // fee - discount is fine
        $this->pay('S2', $this->inst['e2'], 1800, '2026-09-01', ['discount_amount' => 200])->assertStatus(201);
    }

    public function test_a_fee_option_change_does_not_make_a_paid_month_payable_again(): void
    {
        // S1 paid October on "Normal"; then moved to a "Half" option (a new enrollment row)
        $half = DB::table('class_category_fee_options')->insertGetId([
            'class_category_fee_id' => $this->inst['f1'], 'label' => 'Half', 'fee' => 1000, 'is_default' => 0, 'is_active' => 1,
        ]);
        DB::table('student_class_enrollments')->where('id', $this->inst['e1'])->update(['is_active' => 0, 'left_at' => '2026-10-20']);
        $new = DB::table('student_class_enrollments')->insertGetId([
            'branch_id' => $this->inst['b1'], 'student_id' => $this->inst['s1'], 'student_class_id' => $this->inst['c1'],
            'class_category_fee_id' => $this->inst['f1'], 'class_category_fee_option_id' => $half,
            'is_active' => 1, 'is_free_card' => 0, 'enrolled_at' => '2026-10-20',
        ]);

        $this->pay('S1', $new, 1000)
            ->assertStatus(409)
            ->assertJsonPath('message', 'Payment already completed for this class and month.');

        // November is a new month on the new option
        $this->pay('S1', $new, 1000, '2026-11-01')->assertStatus(201);
    }

    public function test_payment_status_is_this_months(): void
    {
        $e1 = StudentClassEnrollment::findOrFail($this->inst['e1']);

        // October is paid
        $this->assertSame('paid', $e1->payment_status);
        $this->assertEquals(0, $e1->balance);

        // In December nothing is paid yet (was "paid": S1 had paid once, in October)
        Carbon::setTestNow('2026-12-10 10:00:00');
        $this->assertSame('unpaid', $e1->fresh()->payment_status);
        $this->assertEquals(2000, $e1->fresh()->balance);

        // a free card has nothing to pay
        DB::table('student_class_enrollments')->where('id', $this->inst['e1'])->update(['is_free_card' => 1]);
        $this->assertSame('paid', $e1->fresh()->payment_status);
    }
}

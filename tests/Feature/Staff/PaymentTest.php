<?php

namespace Tests\Feature\Staff;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Staff app / web: read a student's fees and collect a payment (POST /api/new-payment/*).
 */
class PaymentTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-13 15:20:00');
        $this->buildInstitute();
        Sanctum::actingAs($this->inst['cashier']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function pay(string $code, int $enrollment, float $amount = 2000)
    {
        return $this->postJson('/api/new-payment/pay', [
            'code' => $code, 'enrollment_id' => $enrollment, 'amount' => $amount,
            'payment_month' => '2026-10-01', 'mark_method' => 'qr_mobile', 'payment_method' => 'cash',
        ]);
    }

    public function test_read_shows_the_fee_and_payment_status(): void
    {
        $this->postJson('/api/new-payment/read', ['code' => 'S2'])
            ->assertOk()
            ->assertJsonPath('data.student.custom_id', 'S2')
            ->assertJsonPath('data.classes.0.class_name', 'Maths 10 CMB')
            ->assertJsonPath('data.classes.0.final_fee', 2000)
            ->assertJsonPath('data.classes.0.payment_status', 'unpaid');

        $this->postJson('/api/new-payment/read', ['code' => 'NOPE'])->assertStatus(404);
    }

    public function test_payment_is_saved_with_receipt_and_split(): void
    {
        $this->pay('S2', $this->inst['e2'])
            ->assertStatus(201)
            ->assertJsonPath('data.payment.fee.payment_status', 'paid')
            ->assertJsonPath('data.payment.fee.balance', 0)
            ->assertJsonPath('data.receipt.total', 2000);

        $payment = DB::table('payments')->where('student_id', $this->inst['s2'])->first();
        $this->assertSame('completed', $payment->status);
        $this->assertSame($this->inst['cashier']->id, (int) $payment->user_id);
        $this->assertNotEmpty($payment->receipt_number);

        $split = DB::table('payment_split_snapshots')->where('payment_id', $payment->id)->first();
        $this->assertEquals(1200, $split->teacher_amount);
        $this->assertEquals(800, $split->institution_amount);

        $this->postJson('/api/new-payment/read', ['code' => 'S2'])->assertJsonPath('data.classes.0.payment_status', 'paid');
    }

    public function test_less_than_the_full_fee_is_refused(): void
    {
        $this->pay('S2', $this->inst['e2'], 500)
            ->assertStatus(422)
            ->assertJsonPath('message', 'Payment amount must be at least Rs. 2000.00.');

        $this->assertSame(0, DB::table('payments')->where('student_id', $this->inst['s2'])->count());
    }

    public function test_cashier_cannot_take_payments_for_another_branch(): void
    {
        $this->pay('S3', $this->inst['e3'])->assertStatus(403)
            ->assertJsonPath('message', 'You do not have access to this branch.');
        $this->assertSame(0, DB::table('payments')->where('student_id', $this->inst['s3'])->count());

        // the other branch's student cannot even be looked up
        $this->postJson('/api/new-payment/read', ['code' => 'S3'])->assertStatus(403);

        // an admin can take it
        Sanctum::actingAs($this->inst['admin']);
        $this->pay('S3', $this->inst['e3'])->assertStatus(201);
    }

    public function test_enrollment_must_belong_to_the_student(): void
    {
        $this->pay('S1', $this->inst['e2'])->assertStatus(409);
        $this->pay('S2', $this->inst['e2'], 0)->assertStatus(422);
    }
}

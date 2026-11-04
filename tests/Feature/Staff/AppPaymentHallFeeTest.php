<?php

namespace Tests\Feature\Staff;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * The app pays the "final_fee" from /api/payments/read. With a hall fee it
 * sent only the class fee and the server refused it:
 * "Payment amount must be at least Rs. 2100.00."
 */
class AppPaymentHallFeeTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    public function test_app_amount_includes_the_hall_fee_and_is_accepted(): void
    {
        $this->buildInstitute();
        $hall = DB::table('class_halls')->insertGetId([
            'branch_id' => $this->inst['b1'], 'code' => 'HA', 'hall_name' => 'Hall A', 'hall_price' => 100,
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('class_schedule_patterns')->where('student_class_id', $this->inst['c1'])->update(['class_hall_id' => $hall]);

        Sanctum::actingAs($this->inst['admin']);

        $classes = collect($this->postJson('/api/payments/read', ['qr_code' => 'S1'])->assertOk()->json('data.classes')
            ?? $this->postJson('/api/payments/read', ['qr_code' => 'S1'])->json('classes'));
        $row = $classes->firstWhere('enrollment_id', $this->inst['e1']);

        $this->assertEquals(2000, $row['class_fee']);
        $this->assertEquals(100, $row['hall_fee']);
        $this->assertEquals(2100, $row['final_fee']);

        // what the app sends
        $this->postJson('/api/new-payment/bulk-pay', ['payments' => [[
            'code' => 'S1', 'enrollment_id' => $this->inst['e1'], 'amount' => $row['final_fee'], 'discount_amount' => 0,
            'payment_month' => '2026-12', 'paid_at' => null, 'payment_method' => 'cash', 'mark_method' => 'qr_mobile', 'note' => null,
        ]]])->assertSuccessful();

        $this->assertEquals(2100, DB::table('payments')->where('student_class_enrollment_id', $this->inst['e1'])
            ->where('payment_month', 'like', '2026-12%')->value('amount'));
    }
}

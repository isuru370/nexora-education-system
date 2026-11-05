<?php

namespace Tests\Feature\Staff;

use App\Support\ScannedCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/** Old Success cards hold {"std_ID":"SA11045"} in the QR. */
class OldCardQrTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    public function test_clean_reads_the_student_id_from_old_card_json(): void
    {
        $this->assertSame('SA11045', ScannedCode::clean('{"std_ID":"SA11045"}'));
        $this->assertSame('SA11045', ScannedCode::clean(' { "std_id" : "SA11045" } '));
        $this->assertSame('SA11045', ScannedCode::clean('{"std_ID":"SA11045"'));   // cut
        $this->assertSame('SA11045', ScannedCode::clean('SA11045'));               // new card
        $this->assertSame('{"name":"x"}', ScannedCode::clean('{"name":"x"}'));     // nothing to take
        $this->assertNull(ScannedCode::clean(null));
    }

    public function test_old_card_qr_works_on_the_app_scan_screens(): void
    {
        $this->buildInstitute();
        Sanctum::actingAs($this->inst['admin']);
        $old = '{"std_ID":"S1"}';

        // Payment
        $this->postJson('/api/new-payment/read', ['code' => $old])->assertOk()->assertSee('S1');
        $this->postJson('/api/payments/read', ['qr_code' => $old])->assertOk();

        // Attendance: the student is found (no "student not found")
        $scan = $this->postJson('/api/new-attendance/scan', ['code' => $old, 'mark_method' => 'qr_mobile']);
        $this->assertNotSame(404, $scan->status());
        $this->assertStringNotContainsStringIgnoringCase('not found', (string) $scan->json('message'));

        // Same answer as the plain code
        $this->assertSame(
            $this->postJson('/api/new-payment/read', ['code' => 'S1'])->json('data.student.custom_id'),
            $this->postJson('/api/new-payment/read', ['code' => $old])->json('data.student.custom_id')
        );

        // Bulk payment: payments[*].code
        $this->postJson('/api/new-payment/bulk-pay', ['payments' => [[
            'code' => $old, 'enrollment_id' => $this->inst['e1'], 'amount' => 2000, 'discount_amount' => 0,
            'payment_month' => '2026-12', 'paid_at' => null, 'payment_method' => 'cash', 'mark_method' => 'qr_mobile', 'note' => null,
        ]]])->assertSuccessful();
    }
}

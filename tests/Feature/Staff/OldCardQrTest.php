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

    public function test_student_class_management_finds_old_cards_and_students_with_no_class(): void
    {
        $this->buildInstitute();
        $this->actingAs($this->inst['admin']);
        $name = \Illuminate\Support\Facades\DB::table('students')->where('id', $this->inst['s1'])->value('initial_name');

        $this->post('/student-class-management/search', ['search' => '{"std_ID":"S1"}'])->assertOk()->assertSee($name);
        $this->get('/student-class-management/scan/' . rawurlencode('{"std_ID":"S1"}'))->assertOk()->assertSee($name);

        // registered, no class yet: found, so a class can be assigned
        \Illuminate\Support\Facades\DB::table('students')->insert([
            'custom_id' => 'S9', 'branch_id' => $this->inst['b1'], 'initial_name' => 'New Kid', 'full_name' => 'New Kid',
            'guardian_mobile' => '0771234567', 'grade_id' => \Illuminate\Support\Facades\DB::table('grades')->value('id'),
            'img_url' => '', 'permanent_qr_active' => 1, 'is_active' => 1,
        ]);
        $this->post('/student-class-management/search', ['search' => 'S9'])->assertOk()->assertSee('New Kid');

        // another branch's student without a class is not shown to a cashier of B1
        \Illuminate\Support\Facades\DB::table('students')->insert([
            'custom_id' => 'K9', 'branch_id' => $this->inst['b2'], 'initial_name' => 'Kandy Kid', 'full_name' => 'Kandy Kid',
            'guardian_mobile' => '0771234567', 'grade_id' => \Illuminate\Support\Facades\DB::table('grades')->value('id'),
            'img_url' => '', 'permanent_qr_active' => 1, 'is_active' => 1,
        ]);
        $this->actingAs($this->inst['cashier'])
            ->post('/student-class-management/search', ['search' => 'K9'])
            ->assertRedirect(route('student-class-management.index'));
    }
}

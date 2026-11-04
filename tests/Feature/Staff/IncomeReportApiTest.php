<?php

namespace Tests\Feature\Staff;

use App\Models\Admission;
use App\Models\AdmissionPayment;
use App\Models\ExtraIncome;
use App\Models\InstitutePayment;
use App\Models\TeacherPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/** Staff app Income Report: money in / out for a date range. */
class IncomeReportApiTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    public function test_income_expenses_advances_hall_fee_and_days(): void
    {
        $this->buildInstitute();
        $b1 = $this->inst['b1'];

        $admission = Admission::create(['name' => 'Admission', 'amount' => 500, 'is_active' => true]);
        AdmissionPayment::create(['branch_id' => $b1, 'student_id' => $this->inst['s1'], 'admission_id' => $admission->id,
            'amount' => 500, 'payment_method' => 'cash', 'status' => 'paid', 'paid_at' => '2026-10-05 10:00:00']);
        ExtraIncome::create(['branch_id' => $b1, 'amount' => 300, 'income_date' => '2026-10-06', 'reason' => 'Hall rent',
            'income_type' => 'hall_rent', 'status' => 'received', 'receipt_number' => 'EXT-1']);
        InstitutePayment::create(['branch_id' => $b1, 'amount' => 400, 'payment_date' => '2026-10-05', 'reason' => 'Electricity bill',
            'payment_type' => 'expense', 'status' => 'paid']);
        TeacherPayment::create(['branch_id' => $b1, 'teacher_id' => $this->inst['teacher'], 'payment_type' => 'advance',
            'amount' => 250, 'payment_date' => '2026-10-05', 'status' => 'paid']);
        // a deduction is not money given out
        TeacherPayment::create(['branch_id' => $b1, 'teacher_id' => $this->inst['teacher'], 'payment_type' => 'deduction',
            'amount' => 9999, 'payment_date' => '2026-10-05', 'status' => 'paid']);

        $config = (int) DB::table('class_payment_configs')->where('student_class_id', $this->inst['c1'])->value('id');
        $payment = (int) DB::table('payments')->where('student_id', $this->inst['s1'])->where('status', 'completed')->value('id');
        DB::table('payment_split_snapshots')->insert([
            'branch_id' => $b1, 'payment_id' => $payment, 'class_fee' => 1900, 'hall_fee' => 100, 'total_fee' => 2000,
            'student_class_id' => $this->inst['c1'], 'student_class_enrollment_id' => $this->inst['e1'],
            'class_payment_config_id' => $config, 'teacher_id' => $this->inst['teacher'], 'payment_amount' => 2000,
            'teacher_percentage' => 60, 'organizer_percentage' => 0, 'institution_percentage' => 40,
            'teacher_amount' => 1140, 'organizer_amount' => 0, 'institution_amount' => 860,
            'payment_date' => '2026-10-05 09:00:00', 'created_at' => now(), 'updated_at' => now(),
        ]);

        // the cashier can open it too
        Sanctum::actingAs($this->inst['cashier']);

        $data = $this->getJson('/api/reports/income?from=2026-10-01&to=2026-10-31')
            ->assertOk()
            ->json('data');

        $this->assertEquals(2000, $data['income']['class_fees']['amount']);   // cancelled 999 left out
        $this->assertSame(1, $data['income']['class_fees']['count']);
        $this->assertEquals(100, $data['income']['class_fees']['hall_fee']);
        $this->assertEquals(['cash' => 2000], $data['income']['class_fees']['by_method']);
        $this->assertEquals(500, $data['income']['admission']['amount']);
        $this->assertEquals(300, $data['income']['extra_income']['amount']);
        $this->assertEquals(2800, $data['income']['total']);
        $this->assertEquals(400, $data['expenses']['institute']['amount']);
        $this->assertEquals(250, $data['expenses']['teacher_advances']['amount']);
        $this->assertEquals(650, $data['expenses']['total']);
        $this->assertEquals(2150, $data['net']);

        $this->assertSame(['2026-10-05', '2026-10-06'], array_column($data['days'], 'date'));
        $this->assertEquals(2500 - 650, $data['days'][0]['net']);
        $this->assertSame('Electricity bill', $data['expense_items'][0]['reason']);
        $this->assertCount(1, $data['advance_items']);

        // a short range (sqlite keeps date columns with a time, so not a single day here)
        $day = $this->getJson('/api/reports/income?from=2026-10-06&to=2026-10-07')->assertOk()->json('data');
        $this->assertEquals(300, $day['net']);
    }

    public function test_teachers_cannot_open_it(): void
    {
        $this->buildInstitute();
        $type = DB::table('user_types')->where('code', 'TEACHER')->value('id')
            ?? DB::table('user_types')->insertGetId(['name' => 'TEACHER', 'code' => 'TEACHER', 'is_active' => 1]);
        $teacher = \App\Models\User::forceCreate([
            'name' => 'T', 'email' => 't@income.test', 'password' => 'password123', 'user_type_id' => $type, 'is_active' => 1,
        ]);

        Sanctum::actingAs($teacher);
        $this->getJson('/api/reports/income')->assertForbidden();
    }
}

<?php

namespace Tests\Feature\Report;

use App\Models\Admission;
use App\Models\AdmissionPayment;
use App\Models\ExtraIncome;
use App\Models\TeacherPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/** Institute Income page: admission, extra income and teacher salary / advance cards. */
class InstituteIncomeCardsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    public function test_admission_extra_income_and_teacher_cards(): void
    {
        Carbon::setTestNow('2026-09-30 12:00:00');
        $this->buildInstitute();

        $admission = Admission::create(['name' => 'Grade class admissions fees', 'amount' => 800, 'is_active' => true]);
        AdmissionPayment::create(['branch_id' => $this->inst['b1'], 'student_id' => $this->inst['s1'], 'admission_id' => $admission->id,
            'amount' => 1234, 'payment_method' => 'cash', 'status' => 'paid', 'paid_at' => '2026-09-10 10:00:00']);
        ExtraIncome::create(['branch_id' => $this->inst['b1'], 'amount' => 5678, 'income_date' => '2026-09-12', 'reason' => 'Hall rent',
            'income_type' => 'hall_rent', 'status' => 'received', 'receipt_number' => 'EXT-1']);
        TeacherPayment::create(['branch_id' => $this->inst['b1'], 'teacher_id' => $this->inst['teacher'], 'payment_type' => 'advance',
            'amount' => 432, 'payment_date' => '2026-09-15', 'status' => 'paid']);
        TeacherPayment::create(['branch_id' => $this->inst['b1'], 'teacher_id' => $this->inst['teacher'], 'payment_type' => 'advance',
            'amount' => 999, 'payment_date' => '2026-09-16', 'status' => 'cancelled']);

        $this->actingAs($this->inst['admin'])
            ->get('/reports/institute-income?from=2026-09-01&to=2026-09-30')
            ->assertOk()
            ->assertSeeInOrder(['Admission Payments', 'Rs. 1,234.00', 'Extra Income', 'Rs. 5,678.00',
                'Teachers Salary', 'Rs. 0.00', 'Teacher Advances', 'Rs. 432.00', 'Teacher Net Salary', 'Rs. -432.00', 'Institute Income', 'Institute Net Income (Net Profit)', 'Rs. 6,912.00']);

        Carbon::setTestNow();
    }
}

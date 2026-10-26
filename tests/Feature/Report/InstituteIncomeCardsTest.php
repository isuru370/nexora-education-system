<?php

namespace Tests\Feature\Report;

use App\Models\Admission;
use App\Models\AdmissionPayment;
use App\Models\ExtraIncome;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/** Institute Income page: admission payments and extra income have their own cards. */
class InstituteIncomeCardsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    public function test_admission_and_extra_income_cards(): void
    {
        Carbon::setTestNow('2026-09-30 12:00:00');
        $this->buildInstitute();

        $admission = Admission::create(['name' => 'Grade class admissions fees', 'amount' => 800, 'is_active' => true]);
        AdmissionPayment::create(['branch_id' => $this->inst['b1'], 'student_id' => $this->inst['s1'], 'admission_id' => $admission->id,
            'amount' => 1234, 'payment_method' => 'cash', 'status' => 'paid', 'paid_at' => '2026-09-10 10:00:00']);
        ExtraIncome::create(['branch_id' => $this->inst['b1'], 'amount' => 5678, 'income_date' => '2026-09-12', 'reason' => 'Hall rent',
            'income_type' => 'hall_rent', 'status' => 'received', 'receipt_number' => 'EXT-1']);

        $this->actingAs($this->inst['admin'])
            ->get('/reports/institute-income?from=2026-09-01&to=2026-09-30')
            ->assertOk()
            ->assertSeeInOrder(['Admission Payments', 'Rs. 1,234.00', 'Extra Income', 'Rs. 5,678.00', 'Institute Income']);

        Carbon::setTestNow();
    }
}

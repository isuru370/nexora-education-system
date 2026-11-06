<?php

namespace Tests\Feature\Web;

use App\Models\ExtraIncome;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

class ExtraIncomeSaveTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    public function test_admin_can_save_an_extra_income_with_a_receipt_number(): void
    {
        $this->buildInstitute();

        $response = $this->actingAs($this->inst['admin'])->post('/admin/extra-incomes', [
            'amount' => 1500,
            'income_date' => '2026-11-06',
            'reason' => 'Hall rent - Sunday',
            'income_type' => 'hall_rent',
            'status' => 'received',
            'note' => 'Paid in cash',
        ]);

        $income = ExtraIncome::query()->first();

        $this->assertNotNull($income, 'Extra income was not saved.');
        $response->assertRedirect(route('admin.extra-incomes.show', $income));
        $this->assertSame('1500.00', (string) $income->amount);
        $this->assertMatchesRegularExpression('/^REC-\d{6}$/', $income->receipt_number);
        $this->assertSame($this->inst['admin']->id, $income->user_id);

        $this->get(route('admin.extra-incomes.show', $income))->assertOk()->assertSee('Hall rent - Sunday');
    }
}

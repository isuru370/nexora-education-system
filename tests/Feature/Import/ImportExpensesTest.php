<?php

namespace Tests\Feature\Import;

use App\Models\Branch;
use App\Models\InstitutePayment;
use App\Models\PaymentReason;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Moving an institute from the old system: institute expenses. */
class ImportExpensesTest extends TestCase
{
    use RefreshDatabase;

    private string $file;

    protected function setUp(): void
    {
        parent::setUp();
        Branch::create(['name' => 'Main Branch', 'code' => 'MAIN', 'is_active' => true]);
        PaymentReason::create(['reason_code' => 'electricity', 'name' => 'Electricity Bills', 'is_active' => true]);

        $this->file = sys_get_temp_dir() . '/nexora-exp-' . uniqid() . '.csv';
        $h = fopen($this->file, 'w');
        fputcsv($h, ['old_id', 'reason', 'amount', 'payment_date', 'status']);
        fputcsv($h, [191, 'Electricity bills', 8300, '2026-09-20 16:40:28', 1]);
        fputcsv($h, [193, 'Reloading the phone', 1599, '2026-09-30 08:58:03', 0]);
        fputcsv($h, [194, 'Reloading the phone', 159, '2026-09-30 08:58:47', 1]);
        fputcsv($h, [198, 'Land Loan - Com Bank', 113000, '2026-09-30 09:00:57', 1]);
        fclose($h);
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
        parent::tearDown();
    }

    public function test_expenses_are_imported_once(): void
    {
        $this->artisan('nexora:import-expenses', ['file' => $this->file, '--dry-run' => true])
            ->expectsOutput('DRY RUN - nothing was changed')->assertExitCode(0);
        $this->assertSame(0, InstitutePayment::count());
        $before = PaymentReason::count(); // the system reasons + Electricity Bills

        $this->artisan('nexora:import-expenses', ['file' => $this->file])->assertExitCode(0);

        $this->assertSame(4, InstitutePayment::count());
        $this->assertEquals(121459, InstitutePayment::where('status', 'paid')->sum('amount'));
        $this->assertSame('cancelled', InstitutePayment::where('note', 'Imported from old system #193')->value('status'));

        // existing reason matched by name, new ones added
        $this->assertSame('electricity', InstitutePayment::where('note', 'Imported from old system #191')->value('reason_code'));
        $this->assertSame(['Reloading the phone', 'Land Loan - Com Bank'], PaymentReason::orderBy('id')->skip($before)->take(5)->pluck('name')->all());
        $this->assertSame('land_loan_com_bank', PaymentReason::where('name', 'Land Loan - Com Bank')->value('reason_code'));

        $expense = InstitutePayment::where('note', 'Imported from old system #198')->first();
        $this->assertSame('2026-09-30', $expense->payment_date->format('Y-m-d'));
        $this->assertSame('expense', $expense->payment_type);

        $this->artisan('nexora:import-expenses', ['file' => $this->file])->assertExitCode(0);
        $this->assertSame(4, InstitutePayment::count());
        $this->assertSame($before + 2, PaymentReason::count());
    }
}

<?php

namespace Tests\Feature\Import;

use App\Models\Branch;
use App\Models\ExtraIncome;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Moving an institute from the old system: extra incomes. */
class ImportExtraIncomesTest extends TestCase
{
    use RefreshDatabase;

    private string $file;

    protected function setUp(): void
    {
        parent::setUp();
        Branch::create(['name' => 'Main Branch', 'code' => 'MAIN', 'is_active' => true]);

        $this->file = sys_get_temp_dir() . '/nexora-extra-' . uniqid() . '.csv';
        $h = fopen($this->file, 'w');
        fputcsv($h, ['old_id', 'reason', 'amount', 'income_date']);
        fputcsv($h, [7, 'Town center', 25000, '2026-09-21 02:12:16']);
        fputcsv($h, [3, 'Publicity - Handbills', 5278, '2026-09-02 10:00:00']);
        fputcsv($h, [9, '', 0, '2026-09-03 10:00:00']); // no amount
        fclose($h);
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
        parent::tearDown();
    }

    public function test_extra_incomes_are_imported_once(): void
    {
        $this->artisan('nexora:import-extra-incomes', ['file' => $this->file, '--dry-run' => true])
            ->expectsOutput('DRY RUN - nothing was changed')->assertExitCode(0);
        $this->assertSame(0, ExtraIncome::count());

        $this->artisan('nexora:import-extra-incomes', ['file' => $this->file])->assertExitCode(0);

        $this->assertSame(2, ExtraIncome::count());
        $this->assertEquals(30278, ExtraIncome::sum('amount'));

        $first = ExtraIncome::orderBy('receipt_number')->first();
        $this->assertSame('REC-000001', $first->receipt_number);
        $this->assertSame('Publicity - Handbills', $first->reason);
        $this->assertSame('2026-09-02', $first->income_date->format('Y-m-d'));
        $this->assertSame('received', $first->status);
        $this->assertSame('extra', $first->income_type);

        $this->artisan('nexora:import-extra-incomes', ['file' => $this->file])->assertExitCode(0);
        $this->assertSame(2, ExtraIncome::count());
    }
}

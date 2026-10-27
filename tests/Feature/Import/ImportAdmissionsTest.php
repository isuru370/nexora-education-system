<?php

namespace Tests\Feature\Import;

use App\Models\Admission;
use App\Models\AdmissionPayment;
use App\Models\Branch;
use App\Models\Grade;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Moving an institute from the old system: admission payments. */
class ImportAdmissionsTest extends TestCase
{
    use RefreshDatabase;

    private string $file;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create(['name' => 'Main Branch', 'code' => 'MAIN', 'is_active' => true]);
        $grade = Grade::create(['grade_name' => '10', 'is_active' => true]);

        foreach (['CS0001' => false, 'CS0002' => true] as $id => $admission) {
            Student::create([
                'custom_id' => $id, 'branch_id' => $branch->id, 'initial_name' => $id, 'guardian_mobile' => '0771234567',
                'grade_id' => $grade->id, 'img_url' => 'uploads/male.png', 'admission' => $admission,
            ]);
        }

        // existing type, other case
        Admission::create(['name' => 'grade class admissions fees', 'amount' => 800, 'is_active' => true]);

        $this->file = sys_get_temp_dir() . '/nexora-adm-' . uniqid() . '.csv';
        $h = fopen($this->file, 'w');
        fputcsv($h, ['old_id', 'custom_id', 'admission_name', 'admission_amount', 'amount', 'paid_at']);
        foreach ([
            [5, 'CS0002', 'Spoken Course', 1000, 1000, '2025-03-01 09:00:00'],
            [2, 'CS0001', 'Grade class admissions fees', 800, 700, '2025-01-21 20:46:06'],
            [3, 'CS0001', 'Grade class admissions fees', 800, 700, '2025-01-21 20:47:00'], // double click
            [4, 'CS9999', 'Grade class admissions fees', 800, 800, '2025-02-01 10:00:00'], // no student
            [6, 'CS0002', 'Grade class admissions fees', 800, 800, '2025-02-01 10:00:00'],
        ] as $row) {
            fputcsv($h, $row);
        }
        fclose($h);
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
        parent::tearDown();
    }

    public function test_dry_run_changes_nothing(): void
    {
        $this->artisan('nexora:import-admissions', ['file' => $this->file, '--dry-run' => true])
            ->expectsOutput('DRY RUN - nothing was changed')
            ->expectsOutput('Admission types to add: Spoken Course (1000)')
            ->assertExitCode(0);

        $this->assertSame(0, AdmissionPayment::count());
        $this->assertSame(1, Admission::count());
    }

    public function test_payments_are_imported_once(): void
    {
        $this->artisan('nexora:import-admissions', ['file' => $this->file])->assertExitCode(0);

        $this->assertSame(2, Admission::count());
        $this->assertSame(3, AdmissionPayment::count());
        $this->assertEquals(2500, AdmissionPayment::sum('amount'));

        // receipt numbers follow the dates
        $this->assertSame(
            ['REC-000001' => 'Imported from old system #2', 'REC-000002' => 'Imported from old system #6', 'REC-000003' => 'Imported from old system #5'],
            AdmissionPayment::orderBy('receipt_number')->pluck('note', 'receipt_number')->all()
        );

        $first = AdmissionPayment::where('note', 'Imported from old system #2')->first();
        $this->assertSame('700.00', (string) $first->amount);
        $this->assertSame('2025-01-21 20:46:06', $first->paid_at->format('Y-m-d H:i:s'));
        $this->assertSame('paid', $first->status);
        $this->assertSame('grade class admissions fees', $first->admission->name); // the existing type
        $this->assertTrue((bool) Student::where('custom_id', 'CS0001')->value('admission'));

        $problems = file_get_contents(storage_path('logs/import-admissions-problems.txt'));
        $this->assertStringContainsString('#3 CS0001: NOT imported - Grade class admissions fees already paid that day', $problems);
        $this->assertStringContainsString('#4 CS9999: NOT imported - student not found', $problems);

        // again: nothing new
        $this->artisan('nexora:import-admissions', ['file' => $this->file])->assertExitCode(0);
        $this->assertSame(3, AdmissionPayment::count());
    }
}

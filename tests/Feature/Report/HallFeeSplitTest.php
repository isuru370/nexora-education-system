<?php

namespace Tests\Feature\Report;

use App\Models\Payment;
use App\Models\PaymentSplitSnapshot;
use App\Services\Web\Payment\PaymentSplitSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * The hall fee belongs to the institute: the teacher gets their % of the
 * payment without the hall fee.
 */
class HallFeeSplitTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    public function test_split_gives_the_hall_fee_to_the_institute(): void
    {
        $this->assertSame([2925.0, 0.0, 1075.0], PaymentSplitSnapshotService::split(4000, 100, 75, 0));
        $this->assertSame([3000.0, 0.0, 1000.0], PaymentSplitSnapshotService::split(4000, 0, 75, 0));
        $this->assertSame([0.0, 0.0, 50.0], PaymentSplitSnapshotService::split(50, 100, 75, 0));   // only part of the hall fee paid
        $this->assertSame([2340.0, 195.0, 1465.0], PaymentSplitSnapshotService::split(4000, 100, 60, 5));
    }

    public function test_new_payment_and_old_payments_are_split_without_the_hall_fee(): void
    {
        $this->buildInstitute();   // class C1: teacher 60%, institute 40%
        $hall = DB::table('class_halls')->insertGetId([
            'branch_id' => $this->inst['b1'], 'code' => 'HA', 'hall_name' => 'Hall A', 'hall_price' => 100,
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('class_schedule_patterns')->where('student_class_id', $this->inst['c1'])->update(['class_hall_id' => $hall]);

        $payment = Payment::query()->where('student_id', $this->inst['s1'])->where('status', 'completed')->firstOrFail();
        $payment->update(['amount' => 2100]);
        $snapshot = app(PaymentSplitSnapshotService::class)->createForPayment($payment);

        $this->assertEquals(100, $snapshot->hall_fee);
        $this->assertEquals(1200, $snapshot->teacher_amount);       // 60% of 2,000
        $this->assertEquals(900, $snapshot->institution_amount);    // 40% of 2,000 + 100

        // a payment split the old way (teacher got 60% of the hall fee too)
        $snapshot->forceFill(['teacher_amount' => 1260, 'institution_amount' => 840])->save();

        $this->artisan('nexora:recalculate-hall-fee-splits', ['--dry-run' => true])->assertExitCode(0);
        $this->assertEquals(1260, $snapshot->fresh()->teacher_amount);

        $this->artisan('nexora:recalculate-hall-fee-splits')
            ->expectsOutput('Re-split 1 payment(s): Rs. 60.00 moved from teachers to the institute.')
            ->assertExitCode(0);
        $this->assertEquals(1200, $snapshot->fresh()->teacher_amount);
        $this->assertEquals(900, $snapshot->fresh()->institution_amount);

        // again: nothing to change
        $this->artisan('nexora:recalculate-hall-fee-splits')
            ->expectsOutput('Re-split 0 payment(s): Rs. 0.00 moved from teachers to the institute.')
            ->assertExitCode(0);
    }
}

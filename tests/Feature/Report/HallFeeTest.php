<?php

namespace Tests\Feature\Report;

use App\Models\StudentClassEnrollment;
use App\Services\Web\Payment\PaymentMarker;
use App\Support\HallFee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * A class held in two halls (Hall A Rs. 50, Hall B Rs. 0) is charged the
 * highest hall price of its active patterns; the Institute Income report
 * shows the hall fees and where they came from.
 */
class HallFeeTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    protected int $hallA;
    protected int $patternA;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildInstitute();

        $hall = fn (string $code, string $name, float $price) => DB::table('class_halls')->insertGetId([
            'branch_id' => $this->inst['b1'], 'code' => $code, 'hall_name' => $name, 'hall_price' => $price,
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->hallA = $hall('HA', 'Hall A', 50);
        $hallB = $hall('HB', 'Hall B', 0);

        // fixture pattern (Tuesday) -> Hall A, older
        $this->patternA = (int) DB::table('class_schedule_patterns')->where('student_class_id', $this->inst['c1'])->value('id');
        DB::table('class_schedule_patterns')->where('id', $this->patternA)->update(['class_hall_id' => $this->hallA]);

        // newer Saturday pattern in Hall B (price 0): used to win before
        DB::table('class_schedule_patterns')->insert([
            'branch_id' => $this->inst['b1'], 'student_class_id' => $this->inst['c1'], 'class_category_fee_id' => $this->inst['f1'],
            'class_hall_id' => $hallB, 'start_date' => '2026-06-01', 'end_date' => '2026-12-31', 'start_time' => '08:00:00',
            'end_time' => '10:00:00', 'class_day' => 'saturday', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_the_highest_hall_price_of_the_active_patterns_is_charged(): void
    {
        $enrollment = StudentClassEnrollment::query()->findOrFail($this->inst['e1']);

        $this->assertSame(50.0, HallFee::forEnrollment($enrollment));
        $this->assertSame(50.0, app(PaymentMarker::class)->hallFee($enrollment));
        $this->assertSame('Hall A', HallFee::forClass($this->inst['c1'], $this->inst['f1'])['hall']->hall_name);

        // Hall A pattern made inactive -> no hall fee
        DB::table('class_schedule_patterns')->where('id', $this->patternA)->update(['is_active' => 0]);
        $this->assertSame(0.0, HallFee::forEnrollment($enrollment));
    }

    public function test_bulk_edit_page_shows_the_hall_fee_charged(): void
    {
        DB::table('class_category_fees')->where('id', $this->inst['f1'])->update(['is_active' => 1]);
        DB::table('student_classes')->where('id', $this->inst['c1'])->update(['is_active' => 1, 'is_ongoing' => 1]);
        DB::table('class_categories')->update(['is_active' => 1]);

        $this->actingAs($this->inst['admin'])
            ->get('/class-schedules/bulk-edit?student_class_id=' . $this->inst['c1'] . '&class_category_fee_id=' . $this->inst['f1'])
            ->assertOk()
            ->assertSee('Hall fee added to each payment')
            ->assertSee('Rs. 50.00')
            ->assertSee('Hall fee charged');
    }

    public function test_income_report_shows_hall_fees_and_how_they_came(): void
    {
        $config = (int) DB::table('class_payment_configs')->where('student_class_id', $this->inst['c1'])->value('id');
        $payment = (int) DB::table('payments')->where('student_id', $this->inst['s1'])->where('status', 'completed')->value('id');

        DB::table('payment_split_snapshots')->insert([
            'branch_id' => $this->inst['b1'], 'payment_id' => $payment, 'class_fee' => 1950, 'hall_fee' => 50, 'total_fee' => 2000,
            'student_class_id' => $this->inst['c1'], 'student_class_enrollment_id' => $this->inst['e1'],
            'class_payment_config_id' => $config, 'teacher_id' => $this->inst['teacher'], 'payment_amount' => 2000,
            'teacher_percentage' => 60, 'organizer_percentage' => 0, 'institution_percentage' => 40,
            'teacher_amount' => 1170, 'organizer_amount' => 0, 'institution_amount' => 830,
            'payment_date' => '2026-10-05 09:00:00', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->inst['admin']);

        $this->get('/reports/institute-income?from=2026-10-01&to=2026-10-31')
            ->assertOk()
            ->assertSeeInOrder(['Hall Fees', 'Rs. 50.00', 'From 1 class fee payment(s)']);

        $className = DB::table('student_classes')->where('id', $this->inst['c1'])->value('class_name');

        $this->get('/reports/institute-income/hall-fees?from=2026-10-01&to=2026-10-31')
            ->assertOk()
            ->assertSee('highest hall price')
            ->assertSeeInOrder([$className, 'Theory', 'Hall A (Rs. 50.00)', '50.00', '1', '50.00']);
    }
}

<?php

namespace Tests\Feature\Audit;

use App\Services\Notification\PaymentNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Organizer salary per branch + month, Attendance "Add class" with a fee
 * option, parent message for a single payment (business logic review).
 *
 * Maths 10 CMB: teacher 50% / organizer 10% / institute 40%, fee 2,000
 * -> organizer gets 200 a payment.
 */
class OrganizerSalaryTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    private int $org;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-20 10:00:00');
        $this->buildInstitute();
        DB::table('payment_split_snapshots')->delete();
        DB::table('payments')->delete();

        $this->org = DB::table('organizers')->insertGetId(['code' => 'ORG1', 'name' => 'Org One', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('class_payment_configs')->where('student_class_id', $this->inst['c1'])->update([
            'organizer_id' => $this->org, 'teacher_percentage' => 50, 'organizer_percentage' => 10, 'institution_percentage' => 40,
        ]);

        $this->actingAs($this->inst['admin']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function classFee(string $code, int $enrollment, string $month): void
    {
        $this->postJson('/new-payment/pay', [
            'code' => $code, 'enrollment_id' => $enrollment, 'amount' => 2000, 'payment_month' => $month, 'mark_method' => 'manual_web',
        ])->assertStatus(201);
    }

    private function url(string $what, int $year, int $month): string
    {
        return "/admin/organizer-payments/{$this->org}/$what?year=$year&month=$month&branch_id={$this->inst['b1']}";
    }

    public function test_last_months_salary_paid_next_month_is_that_months_salary(): void
    {
        $this->classFee('S2', $this->inst['e2'], '2026-09-01');   // organizer 200 (September)

        // paid on 2 October (the usual day)
        Carbon::setTestNow('2026-10-02 10:00:00');
        $this->post("/admin/organizer-payments/{$this->org}/store", [
            'payment_type' => 'salary', 'year' => 2026, 'month' => 9, 'branch_id' => $this->inst['b1'],
        ])->assertRedirect()->assertSessionHas('success');

        $row = DB::table('organizer_payments')->first();
        $this->assertEquals([200.0, 2026, 9, $this->inst['b1']], [(float) $row->amount, (int) $row->salary_year, (int) $row->salary_month, (int) $row->branch_id]);

        // September is paid (was "unpaid" and could be paid again), the slip opens
        $this->get($this->url('pay', 2026, 9))->assertSee('Salary Already Paid');
        $this->get($this->url('salary-slip', 2026, 9))->assertOk()->assertSee('200.00');
        $this->post("/admin/organizer-payments/{$this->org}/store", [
            'payment_type' => 'salary', 'year' => 2026, 'month' => 9, 'branch_id' => $this->inst['b1'],
        ])->assertSessionHas('error', 'Organizer salary for this month is already paid.');
        $this->assertSame(1, DB::table('organizer_payments')->count());

        // October is not touched by September's salary
        $this->get('/admin/organizer-payments?year=2026&month=10&branch_id=' . $this->inst['b1'])->assertOk();
    }

    public function test_advance_limit_month_salary_and_fees_after_the_salary(): void
    {
        Carbon::setTestNow('2026-10-10 10:00:00');
        $this->classFee('S2', $this->inst['e2'], '2026-10-01');   // organizer 200 (October)

        $advance = fn (float $amount) => $this->post("/admin/organizer-payments/{$this->org}/adjustment", [
            'payment_type' => 'advance', 'amount' => $amount, 'payment_date' => '2026-10-10', 'branch_id' => $this->inst['b1'],
        ]);
        $advance(1000)->assertSessionHas('error');               // more than earned
        $advance(50)->assertSessionHas('success');

        // October salary on 28 Oct: 200 - 50
        Carbon::setTestNow('2026-10-28 10:00:00');
        $this->post("/admin/organizer-payments/{$this->org}/store", [
            'payment_type' => 'salary', 'year' => 2026, 'month' => 10, 'branch_id' => $this->inst['b1'],
        ])->assertSessionHas('success');
        $this->assertEquals(150, DB::table('organizer_payments')->where('payment_type', 'salary')->value('amount'));

        // a fee on 29 Oct (after the salary): goes into November's salary
        Carbon::setTestNow('2026-10-29 10:00:00');
        $this->classFee('S1', $this->inst['e1'], '2026-11-01');   // organizer 200, dated October
        Carbon::setTestNow('2026-11-28 10:00:00');
        $this->get($this->url('pay', 2026, 11))->assertSee('Rs. 200.00')->assertSee('from earlier months');
        $this->get($this->url('salary-slip', 2026, 10))->assertSee('Fees received after this salary');

        // no advance after the month's salary
        $this->post("/admin/organizer-payments/{$this->org}/adjustment", [
            'payment_type' => 'other', 'amount' => 10, 'payment_date' => '2026-10-29', 'branch_id' => $this->inst['b1'],
        ])->assertSessionHas('error', 'Organizer salary for October 2026 is already paid.');
    }

    public function test_branch_admin_cannot_use_another_branch(): void
    {
        $branchAdmin = $this->makeRoleUser('BRANCH_ADMIN', $this->inst['b1']);
        $this->actingAs($branchAdmin)
            ->get("/admin/organizer-payments?branch_id={$this->inst['b2']}")
            ->assertForbidden();
    }

    public function test_attendance_add_class_sends_a_fee_option(): void
    {
        $page = $this->get("/admin/attendance?class_schedule_id={$this->inst['schedule']}&student_class_id={$this->inst['c1']}&class_category_fee_id={$this->inst['f1']}");
        $page->assertOk()->assertSee('modalFeeOption', false)->assertSee('Normal - Rs. 2,000.00')->assertDontSee('modalCustomFee', false);

        $this->postJson(route('api.student-class-enrollments.store'), [
            'student_id' => $this->inst['s3'], 'student_class_id' => $this->inst['c1'], 'class_category_fee_id' => $this->inst['f1'],
            'class_category_fee_option_id' => $this->inst['o1'], 'is_free_card' => 0, 'enrolled_at' => '2026-09-20',
        ])->assertSuccessful();
    }

    public function test_a_single_payment_tells_the_parent(): void
    {
        $sent = 0;
        $this->mock(PaymentNotificationService::class, function ($mock) use (&$sent) {
            $mock->shouldReceive('sendSuccess')->andReturnUsing(function () use (&$sent) {
                $sent++;
            });
        });

        $this->classFee('S2', $this->inst['e2'], '2026-09-01');

        $this->assertSame(1, $sent);
    }
}

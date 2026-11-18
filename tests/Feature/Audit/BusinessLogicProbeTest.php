<?php

namespace Tests\Feature\Audit;

use App\Models\StudentClassEnrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/** PROBE (review): each test prints what the code does today. Not for merge. */
class BusinessLogicProbeTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-28 10:00:00');
        $this->buildInstitute();
        $this->actingAs($this->inst['admin']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function organizer(): int
    {
        return DB::table('organizers')->insertGetId(['code' => 'ORG1', 'name' => 'Org One', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_probe_overpayment(): void
    {
        $res = $this->postJson('/new-payment/pay', [
            'code' => 'S2', 'enrollment_id' => $this->inst['e2'], 'amount' => 20000, 'payment_month' => '2026-10-01',
            'mark_method' => 'manual_web', 'payment_method' => 'cash',
        ]);
        fwrite(STDERR, "\nPROBE overpayment (fee 2000, paid 20000): HTTP " . $res->status() . ' ' . substr($res->getContent(), 0, 200)
            . ' | teacher_amount=' . DB::table('payment_split_snapshots')->latest('id')->value('teacher_amount') . "\n");
        $this->assertTrue(true);
    }

    public function test_probe_organizer_adjustment_and_salary(): void
    {
        $org = $this->organizer();
        $res = $this->post("/admin/organizer-payments/$org/adjustment", [
            'payment_type' => 'advance', 'amount' => 100, 'payment_date' => '2026-10-05',
        ]);
        fwrite(STDERR, "\nPROBE organizer advance: HTTP " . $res->status() . ' rows=' . DB::table('organizer_payments')->count()
            . ' exception=' . ($res->exception ? substr($res->exception->getMessage(), 0, 160) : '-') . "\n");

        // organizer income in September, salary paid on 2 October (the normal case)
        DB::table('class_payment_configs')->where('student_class_id', $this->inst['c1'])->update(['organizer_id' => $org, 'teacher_percentage' => 50, 'organizer_percentage' => 10, 'institution_percentage' => 40]);
        Carbon::setTestNow('2026-09-20 10:00:00');
        $this->postJson('/new-payment/pay', ['code' => 'S2', 'enrollment_id' => $this->inst['e2'], 'amount' => 2000, 'payment_month' => '2026-09-01', 'mark_method' => 'manual_web']);
        Carbon::setTestNow('2026-10-02 10:00:00');
        $res = $this->post("/admin/organizer-payments/$org/store", ['payment_type' => 'salary', 'year' => 2026, 'month' => 9]);
        fwrite(STDERR, 'PROBE organizer September salary paid on 2 Oct: HTTP ' . $res->status()
            . ' exception=' . ($res->exception ? substr($res->exception->getMessage(), 0, 160) : '-')
            . ' rows=' . json_encode(DB::table('organizer_payments')->get(['payment_type', 'amount', 'payment_date'])) . "\n");
        $page = $this->get("/admin/organizer-payments/$org/pay?year=2026&month=9");
        fwrite(STDERR, 'PROBE September pay page after paying: ' . (str_contains($page->getContent(), 'already') ? 'shows paid' : 'NOT shown as paid') . "\n");
        $this->assertTrue(true);
    }

    public function test_probe_payment_status_is_all_time(): void
    {
        // S1 paid October (fixture). In December nothing is paid.
        Carbon::setTestNow('2026-12-10 10:00:00');
        $e = StudentClassEnrollment::find($this->inst['e1']);
        fwrite(STDERR, "\nPROBE payment_status in December (paid only October): " . $e->payment_status . ' balance=' . $e->balance . "\n");
        $this->assertTrue(true);
    }

    public function test_probe_single_pay_notification(): void
    {
        $sent = 0;
        $this->mock(\App\Services\Notification\PaymentNotificationService::class, function ($m) use (&$sent) {
            $m->shouldReceive('sendSuccess')->andReturnUsing(function () use (&$sent) { $sent++; });
        });
        $this->postJson('/new-payment/pay', ['code' => 'S2', 'enrollment_id' => $this->inst['e2'], 'amount' => 2000, 'payment_month' => '2026-10-01', 'mark_method' => 'manual_web']);
        $single = $sent;
        $this->postJson('/new-payment/bulk-pay', ['payments' => [['code' => 'S1', 'enrollment_id' => $this->inst['e1'], 'amount' => 2000, 'payment_month' => '2026-11-01', 'mark_method' => 'manual_web']]]);
        fwrite(STDERR, "\nPROBE parent notifications: single pay=$single bulk pay=" . ($sent - $single) . "\n");
        $this->assertTrue(true);
    }

    public function test_probe_attendance_add_class_payload(): void
    {
        // same payload the Attendance page "Add class" modal sends
        $res = $this->postJson(route('api.student-class-enrollments.store'), [
            'student_id' => $this->inst['s3'], 'student_class_id' => $this->inst['c1'], 'class_category_fee_id' => $this->inst['f1'],
            'is_free_card' => 0, 'custom_fee' => 1500, 'custom_fee_reason' => 'x', 'discount_percentage' => null, 'discount_reason' => null,
            'enrolled_at' => '2026-10-28', 'note' => null,
        ]);
        fwrite(STDERR, "\nPROBE attendance add-class: HTTP " . $res->status() . ' ' . substr($res->getContent(), 0, 250) . "\n");
        $this->assertTrue(true);
    }
}

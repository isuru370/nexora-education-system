<?php

namespace Tests\Feature\Staff;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Staff app: scan a student's QR at the start of a class (POST /api/new-attendance/scan).
 */
class AttendanceScanTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    protected function setUp(): void
    {
        parent::setUp();
        // Tuesday, during the 3 PM - 5 PM class
        Carbon::setTestNow('2026-10-13 15:20:00');
        $this->buildInstitute();
        Sanctum::actingAs($this->inst['cashier']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function scan(string $code)
    {
        return $this->postJson('/api/new-attendance/scan', ['code' => $code, 'mark_method' => 'qr_mobile']);
    }

    public function test_scan_marks_attendance_once_and_shows_this_months_payment(): void
    {
        $this->scan('S1')
            ->assertStatus(201)
            ->assertJsonPath('data.schedule.class.class_name', 'Maths 10 CMB')
            ->assertJsonPath('data.schedule.class.teacher', 'Nimal Perera')
            ->assertJsonPath('data.attendance.already_marked', false)
            ->assertJsonPath('data.current_month_payment.month', '2026-10')
            ->assertJsonPath('data.current_month_payment.status', 'paid')
            ->assertJsonPath('data.current_month_payment.balance', 0)
            ->assertJsonPath('data.last_payment.receipt_number', 'R1');

        $this->assertSame(1, DB::table('student_attendances')->where('student_id', $this->inst['s1'])->where('class_schedule_id', $this->inst['schedule'])->count());

        // scanning again does not mark twice
        $this->scan('S1')->assertJsonPath('data.attendance.already_marked', true);
        $this->assertSame(1, DB::table('student_attendances')->where('student_id', $this->inst['s1'])->where('class_schedule_id', $this->inst['schedule'])->count());
    }

    public function test_unpaid_and_partly_paid_students(): void
    {
        $this->scan('S2')
            ->assertJsonPath('data.current_month_payment.status', 'unpaid')
            ->assertJsonPath('data.current_month_payment.is_paid', false)
            ->assertJsonPath('data.current_month_payment.balance', 2000);

        DB::table('payments')->insert([
            'branch_id' => $this->inst['b1'], 'student_id' => $this->inst['s2'], 'student_class_enrollment_id' => $this->inst['e2'],
            'user_id' => $this->inst['cashier']->id, 'amount' => 500, 'paid_at' => '2026-10-13 09:00:00',
            'payment_month' => '2026-10-01', 'payment_method' => 'cash', 'status' => 'completed', 'receipt_number' => 'R9',
        ]);

        $this->scan('S2')
            ->assertJsonPath('data.current_month_payment.status', 'partial')
            ->assertJsonPath('data.current_month_payment.paid_amount', 500)
            ->assertJsonPath('data.current_month_payment.balance', 1500);
    }

    public function test_cancelled_payment_does_not_count(): void
    {
        DB::table('payments')->insert([
            'branch_id' => $this->inst['b1'], 'student_id' => $this->inst['s2'], 'student_class_enrollment_id' => $this->inst['e2'],
            'user_id' => $this->inst['cashier']->id, 'amount' => 2000, 'paid_at' => '2026-10-13 09:00:00',
            'payment_month' => '2026-10-01', 'payment_method' => 'cash', 'status' => 'cancelled', 'receipt_number' => 'R10',
        ]);

        $this->scan('S2')
            ->assertJsonPath('data.current_month_payment.status', 'unpaid')
            ->assertJsonPath('data.last_payment', null);
    }

    public function test_unknown_student_and_no_class_now(): void
    {
        $this->scan('NOPE')->assertStatus(404);

        Carbon::setTestNow('2026-10-13 20:00:00');
        $this->scan('S1')->assertStatus(422);
    }

    public function test_staff_login_is_required(): void
    {
        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', '')->postJson('/api/new-attendance/scan', ['code' => 'S1', 'mark_method' => 'qr_mobile'])->assertStatus(401);
    }
}

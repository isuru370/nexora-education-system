<?php

namespace Tests\Feature\Staff;

use App\Services\Web\Attendance\AttendanceMarker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Staff app offline mode: the day's pack, and the sync of attendance and
 * payments recorded without internet (kept at the time they happened).
 *
 * Fixture: Maths 10 CMB, 13 Oct 2026 15:00-17:00, fee 2,000; S1 paid October.
 */
class OfflineStaffTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-13 08:00:00');
        $this->buildInstitute();
        Sanctum::actingAs($this->inst['cashier']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function attendance(int $student, int $enrollment, string $at, ?string $uuid = null): array
    {
        return [
            'local_uuid' => $uuid ?? (string) Str::uuid(), 'type' => 'attendance', 'happened_at' => $at,
            'student_id' => $student, 'enrollment_id' => $enrollment, 'schedule_id' => $this->inst['schedule'], 'mark_method' => 'qr_mobile',
        ];
    }

    private function payment(int $student, int $enrollment, string $month, float $amount, string $at, string $receipt, ?string $uuid = null): array
    {
        return [
            'local_uuid' => $uuid ?? (string) Str::uuid(), 'type' => 'payment', 'happened_at' => $at,
            'student_id' => $student, 'enrollment_id' => $enrollment, 'payment_month' => $month, 'amount' => $amount,
            'receipt_number' => $receipt, 'mark_method' => 'qr_mobile',
        ];
    }

    private function sync(array $items)
    {
        return $this->postJson('/api/offline/sync', ['device_id' => 'phone-A3F2', 'items' => $items]);
    }

    public function test_pack_has_todays_classes_students_and_months_paid_without_phone_numbers(): void
    {
        $response = $this->getJson('/api/offline/pack')->assertOk();
        $pack = $response->json('data');

        $this->assertSame('2026-10-13', $pack['date']);
        $this->assertContains($this->inst['schedule'], array_column($pack['schedules'], 'id'));
        $this->assertSame(Carbon::parse('2026-10-13 17:00')->toIso8601String(), $pack['sync_at']);

        $codes = collect($pack['students'])->pluck('codes')->flatten()->all();
        $this->assertContains('S1', $codes);
        $this->assertContains('S2', $codes);

        $e1 = collect($pack['enrollments'])->firstWhere('id', $this->inst['e1']);
        $this->assertEquals(2000, $e1['payable']);
        $this->assertContains('2026-10', $e1['paid_months']);   // fixture: S1 paid October

        $this->assertStringNotContainsString('guardian', $response->getContent());

        // a class not held today is still there for payments
        DB::table('class_schedules')->where('id', $this->inst['schedule'])->update(['class_date' => '2026-10-14']);
        $pack = $this->getJson('/api/offline/pack')->assertOk()->json('data');
        $this->assertSame([], $pack['schedules']);
        $e1 = collect($pack['enrollments'])->firstWhere('id', $this->inst['e1']);
        $this->assertNotNull($e1);
        $this->assertContains('2026-10', $e1['paid_months']);
        $this->assertNotEmpty($pack['fee_options'][(string) $e1['class_category_fee_id']] ?? []);
        // another branch's class is not for this cashier
        $this->assertNull(collect($pack['enrollments'])->firstWhere('student_class_id', $this->inst['c2']));
        $this->assertStringNotContainsString('0771234567', $response->getContent());
    }

    public function test_offline_items_keep_their_time_and_a_second_sync_saves_nothing_twice(): void
    {
        $items = [
            $this->attendance($this->inst['s2'], $this->inst['e2'], '2026-10-13T15:05:00+05:30'),
            $this->payment($this->inst['s2'], $this->inst['e2'], '2026-10', 2000, '2026-10-13T15:06:00+05:30', 'OF-A3F2-0001'),
        ];

        // back online in the evening
        Carbon::setTestNow('2026-10-13 21:00:00');
        $this->sync($items)->assertOk()->assertJsonPath('summary.synced', 2);

        $attendance = DB::table('student_attendances')->where('local_uuid', $items[0]['local_uuid'])->first();
        $this->assertSame('2026-10-13 15:05:00', Carbon::parse($attendance->attended_at)->format('Y-m-d H:i:s'));

        $payment = DB::table('payments')->where('local_uuid', $items[1]['local_uuid'])->first();
        $this->assertSame('2026-10-13 15:06:00', Carbon::parse($payment->paid_at)->format('Y-m-d H:i:s'));
        $this->assertSame('OF-A3F2-0001', $payment->receipt_number);
        // the teacher salary is made from this date, not the sync time
        $this->assertSame('2026-10-13 15:06:00', Carbon::parse(DB::table('payment_split_snapshots')->where('payment_id', $payment->id)->value('payment_date'))->format('Y-m-d H:i:s'));

        // the phone did not get the answer and sends it again
        $again = $this->sync($items)->assertOk();
        $this->assertSame(['synced', 'synced'], array_column($again->json('results'), 'status'));
        $this->assertSame(1, DB::table('payments')->where('student_id', $this->inst['s2'])->where('status', 'completed')->count());
        $this->assertSame(1, DB::table('student_attendances')->where('student_id', $this->inst['s2'])->where('class_schedule_id', $this->inst['schedule'])->count());
        $this->assertSame(2, DB::table('offline_sync_items')->count());
    }

    public function test_a_month_paid_meanwhile_is_a_conflict_for_review(): void
    {
        // S1 paid October already (another phone / the web)
        Carbon::setTestNow('2026-10-13 21:00:00');
        $response = $this->sync([$this->payment($this->inst['s1'], $this->inst['e1'], '2026-10', 2000, '2026-10-13T15:10:00+05:30', 'OF-A3F2-0002')])
            ->assertOk()
            ->assertJsonPath('summary.conflict', 1);

        $this->assertStringContainsString('already', strtolower($response->json('results.0.message')));
        $this->assertSame(1, DB::table('offline_sync_items')->where('status', 'conflict')->whereNull('reviewed_at')->count());
    }

    public function test_a_payment_saved_online_whose_answer_was_lost_is_not_a_conflict(): void
    {
        // online bulk-pay reached the server, the phone timed out and saved it offline too
        Carbon::setTestNow('2026-10-13 15:06:00');
        auth()->shouldUse('sanctum');   // the cashier, as in the API call
        app(\App\Services\Web\Payment\PaymentService::class)->pay([
            'code' => 'S2', 'enrollment_id' => $this->inst['e2'], 'amount' => 2000, 'discount_amount' => 0,
            'payment_month' => '2026-10-01', 'payment_method' => 'cash', 'mark_method' => 'qr_mobile',
        ]);

        Carbon::setTestNow('2026-10-13 21:00:00');
        $this->sync([$this->payment($this->inst['s2'], $this->inst['e2'], '2026-10', 2000, '2026-10-13T15:06:30+05:30', 'OF-A3F2-0003')])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'duplicate');

        $this->assertSame(1, DB::table('payments')->where('student_id', $this->inst['s2'])->count());
        $this->assertSame(0, DB::table('offline_sync_items')->whereIn('status', ['conflict', 'failed'])->count());
    }

    public function test_attendance_synced_after_the_class_was_closed_without_attendance_reopens_it_as_held(): void
    {
        DB::table('class_schedules')->where('id', $this->inst['schedule'])->update([
            'status' => 'cancelled', 'cancel_reason' => AttendanceMarker::AUTO_CANCEL_REASON, 'cancelled_at' => '2026-10-13 20:00:00',
        ]);

        Carbon::setTestNow('2026-10-13 21:00:00');
        $this->sync([$this->attendance($this->inst['s2'], $this->inst['e2'], '2026-10-13T15:05:00+05:30')])->assertJsonPath('summary.synced', 1);

        $this->assertSame('completed', DB::table('class_schedules')->where('id', $this->inst['schedule'])->value('status'));
    }

    public function test_wrong_items_are_refused_for_review(): void
    {
        Carbon::setTestNow('2026-10-13 21:00:00');
        $response = $this->sync([
            // phone clock ahead
            $this->attendance($this->inst['s2'], $this->inst['e2'], '2026-10-14T09:00:00+05:30'),
            // less than the fee
            $this->payment($this->inst['s2'], $this->inst['e2'], '2026-10', 500, '2026-10-13T15:06:00+05:30', 'OF-A3F2-0003'),
        ])->assertOk();

        $this->assertSame(2, $response->json('summary.failed'));
        $this->assertSame(0, DB::table('payments')->where('student_id', $this->inst['s2'])->count());
    }

    public function test_admin_checks_the_conflicts_on_the_web(): void
    {
        Carbon::setTestNow('2026-10-13 21:00:00');
        $this->sync([$this->payment($this->inst['s1'], $this->inst['e1'], '2026-10', 2000, '2026-10-13T15:10:00+05:30', 'OF-A3F2-0009')]);
        $item = DB::table('offline_sync_items')->first();

        $this->actingAs($this->inst['admin'])->get('/offline-sync')->assertOk()
            ->assertSee('OF-A3F2-0009')->assertSee('Conflict')->assertSee('phone-A3F2');

        $this->post("/offline-sync/{$item->id}/reviewed")->assertRedirect();
        $this->assertNotNull(DB::table('offline_sync_items')->value('reviewed_at'));
        $this->get('/offline-sync')->assertSee('Nothing to check.');

        // a cashier cannot open it
        $this->actingAs($this->inst['cashier'])->get('/offline-sync')->assertForbidden();
    }

    public function test_bad_requests_are_rejected(): void
    {
        $this->postJson('/api/offline/sync', ['device_id' => 'x', 'items' => [['type' => 'payment']]])->assertStatus(422);
    }
}

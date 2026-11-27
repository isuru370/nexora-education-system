<?php

namespace Tests\Feature\Staff;

use App\Support\CardMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Staff app offline mode: a student registered and a class added without
 * internet, and the attendance / payment taken for them on the same phone
 * (they point to the offline student / class with student_ref / enrollment_ref).
 *
 * Fixture: Maths 10 CMB, 13 Oct 2026 15:00-17:00, fee 2,000.
 */
class OfflineRegistrationTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    private int $grade;
    private int $admission;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['*' => Http::response(['status' => true, 'success' => true])]);
        Storage::fake('public');
        Carbon::setTestNow('2026-10-13 08:00:00');
        $this->buildInstitute();
        Sanctum::actingAs($this->inst['cashier']);

        $this->grade = (int) DB::table('grades')->value('id');
        $this->admission = DB::table('admissions')->insertGetId(['name' => 'Registration', 'amount' => 500, 'is_active' => 1]);

        foreach (['TMP045', 'TMP046'] as $number) {
            DB::table('temporary_id_cards')->insert([
                'temporary_id_number' => $number, 'card_number' => 'C-' . $number, 'status' => 'issued',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        foreach ([1, 2] as $n) {
            DB::table('student_cards')->insert([
                'branch_id' => $this->inst['b1'], 'card_sequence' => $n, 'global_qr_sequence' => $n,
                'card_number' => "CMB-000$n", 'qr_code' => "NXCMB000$n", 'status' => 'available', 'is_current' => 0,
            ]);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function student(string $card, string $at, string $name = 'K. Perera', ?string $uuid = null): array
    {
        $temporary = CardMode::get() === CardMode::TEMPORARY;

        return [
            'local_uuid' => $uuid ?? (string) Str::uuid(), 'type' => 'student', 'happened_at' => $at,
            'admission_receipt_number' => 'OF-A3F2-0009',
            'student' => [
                'branch_id' => $this->inst['b1'], 'initial_name' => $name, 'guardian_mobile' => '0779998887',
                'grade_id' => $this->grade, 'gender' => 'male', 'admission_id' => $this->admission,
                $temporary ? 'temporary_qr_code' : 'card_qr_code' => $card,
            ],
        ];
    }

    private function enrollment(array $student, string $at): array
    {
        return [
            'local_uuid' => (string) Str::uuid(), 'type' => 'enrollment', 'happened_at' => $at,
            'student_ref' => $student['local_uuid'],
            'student_class_id' => $this->inst['c1'], 'class_category_fee_id' => $this->inst['f1'],
            'class_category_fee_option_id' => $this->inst['o1'],
        ];
    }

    private function sync(array $items)
    {
        return $this->postJson('/api/offline/sync', ['device_id' => 'phone-A3F2', 'items' => $items]);
    }

    public function test_pack_has_the_classes_grades_admissions_and_cards_to_register_offline(): void
    {
        CardMode::set('permanent');
        $pack = $this->getJson('/api/offline/pack')->assertOk()->json('data');

        $class = collect($pack['classes'])->firstWhere('class_id', $this->inst['c1']);
        $this->assertSame('Maths 10 CMB', $class['class_name']);
        $this->assertSame($this->inst['o1'], $class['category_fees'][0]['fee_options'][0]['id']);
        $this->assertArrayHasKey('hall_fee', $class['category_fees'][0]);
        // another branch's class is not for this cashier
        $this->assertNull(collect($pack['classes'])->firstWhere('class_id', $this->inst['c2']));

        $this->assertContains($this->grade, array_column($pack['grades'], 'id'));
        $this->assertContains('Registration', array_column($pack['admissions'], 'name'));
        $this->assertSame('permanent', $pack['registration']['card_mode']);
        $this->assertSame(['NXCMB0001', 'NXCMB0002'], array_column($pack['registration']['cards'], 'qr_code'));

        CardMode::set('temporary');
        $pack = $this->getJson('/api/offline/pack')->assertOk()->json('data');
        $this->assertSame(['TMP045', 'TMP046'], array_column($pack['registration']['cards'], 'qr_code'));
    }

    public function test_a_student_registered_offline_gets_the_class_attendance_and_payment_of_the_phone(): void
    {
        CardMode::set('temporary');

        $student = $this->student('TMP045', '2026-10-13T15:01:00+05:30');
        $enrollment = $this->enrollment($student, '2026-10-13T15:02:00+05:30');
        $attendance = [
            'local_uuid' => (string) Str::uuid(), 'type' => 'attendance', 'happened_at' => '2026-10-13T15:03:00+05:30',
            'student_ref' => $student['local_uuid'], 'enrollment_ref' => $enrollment['local_uuid'],
            'schedule_id' => $this->inst['schedule'], 'mark_method' => 'qr_mobile',
        ];
        $payment = [
            'local_uuid' => (string) Str::uuid(), 'type' => 'payment', 'happened_at' => '2026-10-13T15:04:00+05:30',
            'student_ref' => $student['local_uuid'], 'enrollment_ref' => $enrollment['local_uuid'],
            'payment_month' => '2026-10', 'amount' => 2000, 'receipt_number' => 'OF-A3F2-0010', 'mark_method' => 'qr_mobile',
        ];

        // back online in the evening; the server puts them in the order they happened
        Carbon::setTestNow('2026-10-13 21:00:00');
        $response = $this->sync([$payment, $attendance, $enrollment, $student])->assertOk()->assertJsonPath('summary.synced', 4);

        $row = DB::table('students')->where('temporary_qr_code', 'TMP045')->first();
        $this->assertNotNull($row);
        $this->assertSame('0779998887', $row->guardian_mobile);
        $result = collect($response->json('results'))->firstWhere('local_uuid', $student['local_uuid']);
        $this->assertSame($row->custom_id, $result['custom_id']);
        $this->assertSame($row->id, $result['student_id']);

        // admission paid when the phone took it, with the phone's receipt
        $admission = DB::table('admission_payments')->where('student_id', $row->id)->first();
        $this->assertSame('2026-10-13 15:01:00', Carbon::parse($admission->paid_at)->format('Y-m-d H:i:s'));
        $this->assertSame('OF-A3F2-0009', $admission->receipt_number);

        $enrolled = DB::table('student_class_enrollments')->where('student_id', $row->id)->where('student_class_id', $this->inst['c1'])->first();
        $this->assertNotNull($enrolled);
        $this->assertSame(1, DB::table('student_attendances')->where('student_id', $row->id)->where('class_schedule_id', $this->inst['schedule'])->count());
        $paid = DB::table('payments')->where('student_class_enrollment_id', $enrolled->id)->first();
        $this->assertSame('OF-A3F2-0010', $paid->receipt_number);
        $this->assertSame('2026-10-13 15:04:00', Carbon::parse($paid->paid_at)->format('Y-m-d H:i:s'));

        // no phone number kept on the Offline Sync page
        $this->assertStringNotContainsString('0779998887', (string) DB::table('offline_sync_items')->where('type', 'student')->value('payload'));

        // the phone did not get the answer and sends everything again: nothing twice
        $this->sync([$student, $enrollment, $attendance, $payment])->assertOk();
        $this->assertSame(1, DB::table('students')->where('temporary_qr_code', 'TMP045')->count());
        $this->assertSame(1, DB::table('payments')->where('student_class_enrollment_id', $enrolled->id)->count());
        $this->assertSame(1, DB::table('admission_payments')->where('student_id', $row->id)->count());
    }

    public function test_a_card_given_meanwhile_is_a_conflict_and_the_students_class_is_refused(): void
    {
        CardMode::set('permanent');
        Carbon::setTestNow('2026-10-13 21:00:00');

        // NXCMB0001 was given on the web this afternoon
        $this->sync([$this->student('NXCMB0001', '2026-10-13T14:00:00+05:30', 'Web Student')])->assertJsonPath('results.0.status', 'synced');

        $student = $this->student('NXCMB0001', '2026-10-13T15:01:00+05:30', 'Phone Student');
        $enrollment = $this->enrollment($student, '2026-10-13T15:02:00+05:30');
        $response = $this->sync([$student, $enrollment])->assertOk();

        $this->assertSame(['conflict', 'failed'], array_column($response->json('results'), 'status'));
        $this->assertStringContainsString('new student', $response->json('results.1.message'));
        $this->assertSame(0, DB::table('students')->where('initial_name', 'Phone Student')->count());
        $this->assertSame(2, DB::table('offline_sync_items')->whereIn('status', ['conflict', 'failed'])->count());
    }

    public function test_a_registration_saved_online_whose_answer_was_lost_is_the_same_student(): void
    {
        CardMode::set('permanent');
        Carbon::setTestNow('2026-10-13 15:01:00');

        $this->postJson('/api/student-new', [
            'branch_id' => $this->inst['b1'], 'card_qr_code' => 'NXCMB0002', 'initial_name' => 'K. Perera',
            'guardian_mobile' => '0779998887', 'grade_id' => $this->grade, 'gender' => 'male',
        ])->assertCreated();

        Carbon::setTestNow('2026-10-13 21:00:00');
        $this->sync([$this->student('NXCMB0002', '2026-10-13T15:01:20+05:30')])->assertJsonPath('results.0.status', 'duplicate');
        $this->assertSame(1, DB::table('students')->where('custom_id', 'NXCMB0002')->count());
    }

    public function test_a_class_waits_for_its_student_and_a_class_added_twice_is_not_an_error(): void
    {
        Carbon::setTestNow('2026-10-13 21:00:00');

        // its student is still on the phone (not sent yet)
        $waiting = $this->enrollment(['local_uuid' => (string) Str::uuid()], '2026-10-13T15:02:00+05:30');
        $this->sync([$waiting])->assertOk()->assertJsonPath('results.0.status', 'retry');
        $this->assertSame(0, DB::table('offline_sync_items')->count());

        // S2 is already in Maths 10 CMB with this fee option
        $again = ['student_id' => $this->inst['s2'], 'student_ref' => null] + $waiting;
        $again['local_uuid'] = (string) Str::uuid();
        $this->sync([$again])->assertOk()->assertJsonPath('results.0.status', 'duplicate')
            ->assertJsonPath('results.0.enrollment_id', $this->inst['e2']);
    }
}

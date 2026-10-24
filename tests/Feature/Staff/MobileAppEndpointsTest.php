<?php

namespace Tests\Feature\Staff;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Calls every endpoint the staff / teacher mobile app uses, with the same
 * request bodies the app sends, and checks each one works. A Colombo
 * cashier is then pointed at Kandy records and must be refused (403).
 */
class MobileAppEndpointsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    public function test_every_app_endpoint_works_and_branches_are_isolated(): void
    {
        Carbon::setTestNow('2026-10-13 15:30:00');
        Http::fake(['*' => Http::response(['status' => true, 'success' => true])]);
        Storage::fake('public');
        $i = $this->buildInstitute();
        $b1 = $i['b1'];

        $hall = DB::table('class_halls')->insertGetId(['branch_id' => $b1, 'code' => 'H1', 'hall_name' => 'Hall A', 'is_active' => 1]);
        $adm = DB::table('admissions')->insertGetId(['name' => 'New', 'amount' => 1500, 'is_active' => 1]);
        DB::table('temporary_id_cards')->insert(['temporary_id_number' => 'TMP001', 'card_number' => 'T-001', 'status' => 'issued']);
        foreach ([1, 2, 3] as $n) {
            DB::table('student_cards')->insert(['branch_id' => $b1, 'card_sequence' => $n, 'global_qr_sequence' => $n, 'card_number' => "CMB-000$n", 'qr_code' => "NXCMB000$n", 'status' => 'available', 'is_current' => 0]);
        }
        foreach (['QP001', 'QP002', 'QP003'] as $qp) {
            DB::table('quick_photos')->insert(['custom_id' => $qp, 'image_path' => "quick-photos/$qp.jpg", 'is_active' => 1]);
        }
        // S1 gets card 1 as current card
        DB::table('student_cards')->where('qr_code', 'NXCMB0001')->update(['student_id' => $i['s1'], 'status' => 'assigned', 'is_current' => 1]);
        $s1 = DB::table('students')->find($i['s1']);

        $teacherType = DB::table('user_types')->insertGetId(['name' => 'TEACHER', 'code' => 'TEACHER', 'is_active' => 1]);
        $teacherUser = User::forceCreate(['name' => 'Nimal', 'email' => 'nimal@test.lk', 'password' => 'password123', 'user_type_id' => $teacherType, 'is_active' => 1]);
        DB::table('teachers')->where('id', $i['teacher'])->update(['user_id' => $teacherUser->id]);

        $out = [];
        // Answers that are correct even though they are not 2xx.
        $expected = [
            'deactivate enrollment' => 422,      // toggle above already deactivated it
            'card re-assign' => 404,             // TMP001 is a temporary ID, not a card
            'teacher on admin route' => 403,
            'admin on teacher route' => 403,
        ];

        $call = function (string $label, string $method, string $uri, array $data = [], ?User $as = null) use (&$out, $i, $expected) {
            Sanctum::actingAs($as ?? $i['admin']);
            $r = $this->json($method, $uri, $data);
            $out[] = ['label' => $label, 'method' => $method, 'uri' => $uri, 'status' => $r->status(), 'json' => $r->json()];
            $this->app['auth']->forgetGuards();

            if (str_starts_with($label, 'X ')) {
                $this->assertSame(403, $r->status(), "$label should be refused: " . $r->getContent());
                $this->assertSame('You do not have access to this branch.', $r->json('message'), $label);
            } elseif (isset($expected[$label])) {
                $this->assertSame($expected[$label], $r->status(), "$label: " . $r->getContent());
            } else {
                $this->assertTrue($r->status() < 300, "$label returned {$r->status()}: " . substr($r->getContent(), 0, 500));
            }

            return $r->json();
        };

        // ---- auth
        $this->app['auth']->forgetGuards();
        $r = $this->postJson('/api/login', ['email' => 'admin@test.lk', 'password' => 'password123']);
        $r->assertOk()->assertJsonStructure(['token', 'user', 'role', 'home']);
        $this->app['auth']->forgetGuards();
        $r = $this->postJson('/api/login', ['email' => 'nimal@test.lk', 'password' => 'password123']);
        $r->assertOk()->assertJsonPath('role', 'TEACHER');
        $call('me', 'GET', '/api/me');

        // ---- lookups
        $call('grades', 'GET', '/api/grades');
        $call('halls', 'GET', '/api/institute-halls');
        $call('admission', 'GET', '/api/admission');
        $call('dashboard', 'GET', '/api/mobile-dashboard', ['branch_id' => $b1]);
        $call('students', 'GET', '/api/students');
        $call('students search', 'GET', '/api/students/search/S1');
        $call('students filter', 'GET', '/api/students/filter?qr_code=S1');
        $call('classes by grade', 'GET', '/api/student-classes/' . DB::table('grades')->value('id'));
        $call('enrollments of student', 'GET', '/api/student-class-enrollments/' . $i['s1']);

        // ---- registration
        $grade = DB::table('grades')->value('id');
        // each institute registers one way (nexora:card-mode); try both here
        \App\Support\CardMode::set('temporary');
        $reg = $call('student (temporary)', 'POST', '/api/student', ['branch_id' => $b1, 'temporary_qr_code' => 'TMP001', 'quick_image_id' => 'QP001', 'initial_name' => 'K. Perera', 'guardian_mobile' => '0771234567', 'grade_id' => $grade, 'gender' => 'male', 'admission_id' => $adm]);
        \App\Support\CardMode::set('permanent');
        $call('student-new (card)', 'POST', '/api/student-new', ['branch_id' => $b1, 'card_qr_code' => 'NXCMB0002', 'quick_image_id' => 'QP002', 'initial_name' => 'L. Perera', 'guardian_mobile' => '0771234567', 'grade_id' => $grade, 'gender' => 'female', 'admission_id' => $adm]);
        $newId = $reg['student']['id'] ?? null;
        $call('admission payments', 'GET', '/api/admission-payments');
        $call('admission pay', 'POST', '/api/admission-payments/store', ['admission_id' => $adm, 'student_id' => $i['s2']]);

        // ---- enrollments
        $call('enroll class', 'POST', '/api/student-class-enrollments', ['student_id' => $newId, 'student_class_id' => $i['c1'], 'class_category_fee_id' => $i['f1'], 'class_category_fee_option_id' => $i['o1'], 'is_free_card' => false]);
        $call('read student class', 'POST', '/api/student-class-enrollments/read-student-class', ['qr_code' => 'S1']);
        $call('toggle enrollment', 'PATCH', '/api/student-class-enrollments/toggle-status/' . $i['e2']);
        $call('deactivate enrollment', 'PATCH', '/api/student-class-enrollments/' . $i['e2'] . '/deactivate');

        // ---- schedules
        $call('ongoing class', 'GET', '/api/class-schedule/ongoing-class');
        $call('today class', 'GET', '/api/class-schedule/today-class');
        $call('category', 'GET', '/api/class-schedule/category?class_id=' . $i['c1']);
        $call('fetch schedules', 'GET', '/api/class-schedule/fetch?class_category_fee_id=' . $i['f1']);
        $new = $call('new day', 'POST', '/api/class-schedule/store', ['student_class_id' => $i['c1'], 'class_category_fee_id' => $i['f1'], 'class_date' => '2026-10-15', 'day_of_week' => 'thursday', 'start_time' => '15:00', 'end_time' => '17:00', 'class_hall_id' => $hall, 'note' => 'extra']);
        $newSchedule = $new['data']['id'] ?? $new['data']['schedule']['id'] ?? null;
        $call('update schedule', 'PUT', '/api/class-schedule/update', ['schedule_id' => $newSchedule, 'class_category_fee_id' => $i['f1'], 'class_date' => '2026-10-16', 'day_of_week' => 'friday', 'start_time' => '16:00', 'end_time' => '18:00', 'class_hall_id' => $hall, 'note' => 'moved']);
        $call('cancel schedule', 'PATCH', '/api/class-schedule/cancel', ['schedule_id' => $newSchedule, 'cancel_reason' => 'rain']);
        $call('attendance schedules', 'POST', '/api/attendance/schedules', ['student_class_id' => $i['c1'], 'class_category_fee_id' => $i['f1']]);

        // ---- attendance
        $call('attendance read', 'POST', '/api/attendance/read', ['qr_code' => 'S1', 'student_class_id' => $i['c1'], 'class_category_fee_id' => $i['f1']]);
        $scan = $call('attendance scan', 'POST', '/api/new-attendance/scan', ['code' => 'S1', 'mark_method' => 'qr_mobile']);
        $att = $call('attendance store', 'POST', '/api/attendance/store', ['student_id' => $i['s2'], 'student_class_id' => $i['c1'], 'class_category_fee_id' => $i['f1'], 'class_schedule_id' => $i['schedule'], 'mark_method' => 'qr_mobile', 'mark_tute' => false, 'note' => null]);
        $call('attendance report', 'POST', '/api/attendance/report', ['schedule_id' => $i['schedule']]);
        $call('attendance history', 'GET', "/api/attendance/students/{$i['s1']}/enrollments/{$i['e1']}");
        $attId = DB::table('student_attendances')->where('student_id', $i['s2'])->value('id');
        $call('attendance delete', 'DELETE', '/api/student-attendances/' . $attId);

        // ---- payments
        $call('payment read (old)', 'POST', '/api/payments/read', ['qr_code' => 'S1']);
        $call('payment read (new)', 'POST', '/api/new-payment/read', ['code' => 'S1']);
        $call('bulk pay', 'POST', '/api/new-payment/bulk-pay', ['payments' => [['code' => 'S1', 'enrollment_id' => $i['e1'], 'amount' => 2000, 'discount_amount' => 0, 'payment_month' => '2026-11', 'paid_at' => null, 'payment_method' => 'cash', 'mark_method' => 'qr_mobile', 'note' => null]]]);
        $call('payments today', 'POST', '/api/payments/today', ['date' => '2026-10-13']);
        $call('payment history', 'GET', "/api/payments/students/{$i['s1']}/enrollments/{$i['e1']}");

        // ---- tutes
        $call('tute read', 'POST', '/api/student-tutes/read', ['qr_code' => 'S1']);
        $call('tute store', 'POST', '/api/student-tutes/store', ['student_id' => $i['s1'], 'student_class_enrollment_id' => $i['e1'], 'issued_month' => '2026-10', 'is_issued' => true, 'issued_at' => '2026-10-13', 'note' => null]);
        $call('tute history', 'GET', "/api/student-tutes/students/{$i['s1']}/enrollments/{$i['e1']}");

        // ---- cards
        $call('qr-code', 'POST', '/api/qr-code', ['card_qr_code' => 'NXCMB0001']);
        $call('card search student', 'POST', '/api/student-card-assignment/search-student', ['code' => 'S2']);
        $call('card assign', 'POST', '/api/student-card-assignment/assign', ['student_id' => $i['s2'], 'card_qr_code' => 'NXCMB0003']);
        $call('card re-assign', 'POST', '/api/re-assign', ['old_qr_code' => 'NXCMB0003', 'new_qr_code' => 'TMP001', 'reason' => 'lost', 'remarks' => '']);

        // ---- images
        $call('fetch images', 'GET', '/api/students-image/fetch-image');
        $call('update image', 'POST', '/api/students-image/update-image', ['student_id' => $i['s1'], 'quick_image_id' => 'QP003']);
        Sanctum::actingAs($i['admin']);
        $r = $this->post('/api/quick-photo/upload', ['image' => UploadedFile::fake()->image('a.jpg')], ['Accept' => 'application/json']);
        $r->assertStatus(201);
        $this->app['auth']->forgetGuards();

        // ---- teacher
        $call('teacher dashboard', 'GET', '/api/v1/teacher/dashboard', [], $teacherUser);
        $call('teacher classes', 'GET', '/api/v1/teacher/classes', [], $teacherUser);
        $call('teacher students', 'GET', '/api/v1/teacher/students', ['class_id' => $i['c1']], $teacherUser);
        $call('teacher timetable', 'GET', '/api/v1/teacher/timetable', ['date' => '2026-10-13'], $teacherUser);
        $call('teacher profile', 'GET', '/api/v1/teacher/profile', [], $teacherUser);
        $call('teacher profile update', 'PUT', '/api/v1/teacher/profile', ['mobile' => '0771112223'], $teacherUser);
        $call('teacher change password', 'POST', '/api/v1/teacher/profile/change-password', ['current_password' => 'password123', 'new_password' => 'newpass123', 'new_password_confirmation' => 'newpass123'], $teacherUser);
        $call('teacher on admin route', 'GET', '/api/students', [], $teacherUser);
        $call('admin on teacher route', 'GET', '/api/v1/teacher/dashboard', []);

        // ---- cross-branch: Colombo cashier on Kandy data (s3 / e3 / c2 / f2)
        $c = $i['cashier'];
        $kSchedule = DB::table('class_schedules')->insertGetId(['branch_id' => $i['b2'], 'student_class_id' => $i['c2'], 'class_category_fee_id' => DB::table('class_category_fees')->where('student_class_id', $i['c2'])->value('id'), 'class_date' => '2026-10-13', 'day_of_week' => 'tuesday', 'start_time' => '15:00', 'end_time' => '17:00', 'status' => 'ongoing', 'is_active' => 1]);
        $f2 = DB::table('class_category_fees')->where('student_class_id', $i['c2'])->value('id');
        $o2 = DB::table('class_category_fee_options')->where('class_category_fee_id', $f2)->value('id');
        $call('students search other branch', 'GET', '/api/students/search/S3', [], $c);
        $call('X students filter S3', 'GET', '/api/students/filter?qr_code=S3', [], $c);
        $call('X enrollments of S3', 'GET', '/api/student-class-enrollments/' . $i['s3'], [], $c);
        $call('X read student class S3', 'POST', '/api/student-class-enrollments/read-student-class', ['qr_code' => 'S3'], $c);
        $call('X attendance scan S3', 'POST', '/api/new-attendance/scan', ['code' => 'S3', 'mark_method' => 'qr_mobile'], $c);
        $call('X attendance read S3', 'POST', '/api/attendance/read', ['qr_code' => 'S3', 'student_class_id' => $i['c2'], 'class_category_fee_id' => $f2], $c);
        $call('X attendance store S3', 'POST', '/api/attendance/store', ['student_id' => $i['s3'], 'student_class_id' => $i['c2'], 'class_category_fee_id' => $f2, 'class_schedule_id' => $kSchedule, 'mark_method' => 'qr_mobile', 'mark_tute' => false, 'note' => null], $c);
        $call('X attendance report KDY', 'POST', '/api/attendance/report', ['schedule_id' => $kSchedule], $c);
        $call('X attendance history S3', 'GET', "/api/attendance/students/{$i['s3']}/enrollments/{$i['e3']}", [], $c);
        $call('X payment read S3', 'POST', '/api/payments/read', ['qr_code' => 'S3'], $c);
        $call('X payment history S3', 'GET', "/api/payments/students/{$i['s3']}/enrollments/{$i['e3']}", [], $c);
        $call('X tute read S3', 'POST', '/api/student-tutes/read', ['qr_code' => 'S3'], $c);
        $call('X tute store S3', 'POST', '/api/student-tutes/store', ['student_id' => $i['s3'], 'student_class_enrollment_id' => $i['e3'], 'issued_month' => '2026-10', 'is_issued' => true, 'issued_at' => '2026-10-13', 'note' => null], $c);
        $call('X tute history S3', 'GET', "/api/student-tutes/students/{$i['s3']}/enrollments/{$i['e3']}", [], $c);
        $call('X enroll S1 in KDY class', 'POST', '/api/student-class-enrollments', ['student_id' => $i['s1'], 'student_class_id' => $i['c2'], 'class_category_fee_id' => $f2, 'class_category_fee_option_id' => $o2, 'is_free_card' => false], $c);
        $call('X toggle KDY enrollment', 'PATCH', '/api/student-class-enrollments/toggle-status/' . $i['e3'], [], $c);
        $call('X schedules of KDY class', 'GET', '/api/class-schedule/category?class_id=' . $i['c2'], [], $c);
        $call('X new day KDY', 'POST', '/api/class-schedule/store', ['student_class_id' => $i['c2'], 'class_category_fee_id' => $f2, 'class_date' => '2026-10-20', 'day_of_week' => 'tuesday', 'start_time' => '15:00', 'end_time' => '17:00', 'class_hall_id' => null, 'note' => null], $c);
        $call('X cancel KDY schedule', 'PATCH', '/api/class-schedule/cancel', ['schedule_id' => $kSchedule, 'cancel_reason' => 'x'], $c);
        $call('X update image S3', 'POST', '/api/students-image/update-image', ['student_id' => $i['s3'], 'quick_image_id' => 'QP003'], $c);
        $call('X admission pay S3', 'POST', '/api/admission-payments/store', ['admission_id' => $adm, 'student_id' => $i['s3']], $c);
        $call('X card assign S3', 'POST', '/api/student-card-assignment/assign', ['student_id' => $i['s3'], 'card_qr_code' => 'NXCMB0002'], $c);
        $kAtt = DB::table('student_attendances')->where('student_id', $i['s3'])->value('id') ?? DB::table('student_attendances')->insertGetId(['branch_id' => $i['b2'], 'student_id' => $i['s3'], 'student_class_enrollment_id' => $i['e3'], 'class_schedule_id' => $kSchedule, 'attended_at' => now(), 'mark_method' => 'qr_mobile']);
        $call('X delete KDY attendance', 'DELETE', '/api/student-attendances/' . $kAtt, [], $c);

        // the same Kandy records are fine for an admin
        $this->assertTrue($call('admin: KDY attendance history', 'GET', "/api/attendance/students/{$i['s3']}/enrollments/{$i['e3']}")['success'] ?? false);

        $call('logout', 'POST', '/api/logout');

        Carbon::setTestNow();
    }
}

<?php

namespace Tests\Feature\Course;

use App\Models\CourseEnrollment;
use App\Models\CoursePayment;
use App\Models\CourseSession;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsCourse;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/** Staff app course endpoints. */
class CourseApiTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;
    use BuildsCourse;

    private CourseSession $today;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-01 08:30:00'); // Sunday class day 08:00-12:00
        $this->buildInstitute();
        DB::table('students')->whereKey($this->inst['s1'])->update(['custom_id' => 'S1']);
        DB::table('students')->whereKey($this->inst['s2'])->update(['custom_id' => 'S2']);
        DB::table('students')->whereKey($this->inst['s3'])->update(['custom_id' => 'S3']);
        Features::set('courses', true);
        $this->buildCourse();
        $this->today = CourseSession::where('course_id', $this->inst['course']->id)->whereDate('session_date', '2026-11-01')->firstOrFail();

        Sanctum::actingAs($this->inst['cashier']); // Colombo cashier
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_enroll_and_pay_from_the_app(): void
    {
        $course = $this->inst['course'];

        $this->postJson('/api/courses/student', ['code' => 'S1'])->assertOk()
            ->assertJsonPath('data.student.custom_id', 'S1')
            ->assertJsonCount(0, 'data.enrollments')
            ->assertJsonPath('data.available_courses.0.code', 'CMB-C26001')
            ->assertJsonPath('data.available_courses.0.installment_amount', 2000);

        $this->postJson("/api/courses/{$course->id}/enroll", ['code' => 'S1', 'payment_plan' => 'installment', 'special_fee' => 9000])
            ->assertStatus(422)->assertJsonValidationErrors('special_fee_reason');

        $enroll = $this->postJson("/api/courses/{$course->id}/enroll", ['code' => 'S1', 'payment_plan' => 'installment', 'special_fee' => 9000, 'special_fee_reason' => 'Sibling'])
            ->assertCreated()
            ->assertJsonPath('data.enrollment.fees.total_fee', 9000)
            ->assertJsonPath('data.enrollment.fees.special_fee', true)
            ->assertJsonPath('data.enrollment.fees.next_installment.remaining', 1500);
        $enrollmentId = $enroll->json('data.enrollment.id');

        $this->postJson("/api/courses/{$course->id}/enroll", ['code' => 'S1', 'payment_plan' => 'full'])->assertStatus(422);

        $pay = $this->postJson("/api/courses/enrollments/{$enrollmentId}/pay", ['amount' => 1500, 'payment_method' => 'cash'])
            ->assertCreated()
            ->assertJsonPath('data.receipt.title', 'COURSE PAYMENT')
            ->assertJsonPath('data.receipt.amount', 1500)
            ->assertJsonPath('data.receipt.balance', 7500)
            ->assertJsonPath('data.receipt.special_fee_reason', 'Sibling')
            ->assertJsonPath('data.receipt.course_code', 'CMB-C26001')
            ->assertJsonPath('data.enrollment.fees.paid', 1500);

        $this->assertStringStartsWith('REC-', $pay->json('data.receipt.receipt_number'));
        $this->assertSame('qr_mobile', CoursePayment::firstOrFail()->mark_method);

        $this->postJson("/api/courses/enrollments/{$enrollmentId}/pay", ['amount' => 99999])->assertStatus(422)->assertJsonValidationErrors('amount');

        $this->getJson('/api/courses/payments/' . CoursePayment::first()->id . '/receipt')->assertOk()->assertJsonPath('data.receipt.amount', 1500);

        $this->postJson('/api/courses/student', ['code' => 'S1'])->assertOk()
            ->assertJsonCount(1, 'data.enrollments')
            ->assertJsonCount(0, 'data.available_courses');
    }

    public function test_attendance_and_the_present_absent_report(): void
    {
        $course = $this->inst['course'];
        foreach (['S1', 'S2'] as $code) {
            $this->postJson("/api/courses/{$course->id}/enroll", ['code' => $code, 'payment_plan' => 'installment'])->assertCreated();
        }

        $this->getJson('/api/courses/sessions')->assertOk()
            ->assertJsonPath('data.sessions.0.id', $this->today->id)
            ->assertJsonPath('data.sessions.0.can_mark', true)
            ->assertJsonPath('data.sessions.0.students_count', 2);

        $this->postJson("/api/courses/sessions/{$this->today->id}/attendance", ['code' => 'S1'])
            ->assertCreated()
            ->assertJsonPath('data.already_marked', false)
            ->assertJsonPath('data.session.status', 'ongoing')
            ->assertJsonPath('data.fees.balance', 12000);

        $this->postJson("/api/courses/sessions/{$this->today->id}/attendance", ['code' => 'S1'])
            ->assertOk()->assertJsonPath('data.already_marked', true);

        $this->postJson("/api/courses/sessions/{$this->today->id}/attendance", ['code' => 'NOPE'])
            ->assertStatus(422)->assertJsonValidationErrors('code');

        $report = $this->getJson("/api/courses/sessions/{$this->today->id}/report")->assertOk()
            ->assertJsonPath('data.summary.present', 1)
            ->assertJsonPath('data.summary.absent', 1)
            ->assertJsonPath('data.present.0.custom_id', 'S1')
            ->assertJsonPath('data.absent.0.custom_id', 'S2');

        $text = $report->json('data.share_text');
        $this->assertStringContainsString('*Diploma in IT* (CMB-C26001)', $text);
        $this->assertStringContainsString("Present: 1 / 2", $text);
        $this->assertStringContainsString("*Absent*\n1. S2 B. Perera", $text);

        // 077 0000000 -> wa.me/94770000000 with the message filled in
        $teacher = collect($report->json('data.teachers'))->firstWhere('name', 'Nimal Perera');
        $this->assertStringStartsWith('https://wa.me/94770000000?text=', $teacher['whatsapp_url']);
        $this->assertSame($text, rawurldecode(substr($teacher['whatsapp_url'], strlen('https://wa.me/94770000000?text='))));
    }

    public function test_other_branches_and_feature_off(): void
    {
        $kandy = $this->buildCourse(['branch_id' => $this->inst['b2']]);
        $kandyDay = $kandy->sessions()->whereDate('session_date', '2026-11-01')->firstOrFail();

        // Colombo cashier
        $this->getJson('/api/courses')->assertOk()->assertJsonCount(1, 'data.courses');
        $this->postJson("/api/courses/{$kandy->id}/enroll", ['code' => 'S1', 'payment_plan' => 'full'])->assertNotFound();
        $this->getJson("/api/courses/sessions/{$kandyDay->id}/report")->assertNotFound();
        $this->postJson('/api/courses/student', ['code' => 'S3'])->assertStatus(422); // Kandy student

        Features::set('courses', false);
        $this->getJson('/api/courses')->assertNotFound();
        $this->getJson('/api/features')->assertOk()->assertJsonPath('data.courses', false);
    }

    public function test_teachers_cannot_use_the_staff_course_endpoints(): void
    {
        $teacherUser = \App\Models\User::forceCreate([
            'name' => 'T', 'email' => 'tu@test.lk', 'password' => 'password123', 'is_active' => 1,
            'user_type_id' => DB::table('user_types')->insertGetId(['name' => 'TEACHER', 'code' => 'TEACHER', 'is_active' => 1]),
        ]);
        Sanctum::actingAs($teacherUser);

        $this->getJson('/api/courses')->assertForbidden();
    }
}

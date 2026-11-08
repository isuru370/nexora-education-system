<?php

namespace Tests\Feature\Staff;

use App\Jobs\SendTeacherSalarySlip;
use App\Mail\TeacherSalarySlipMail;
use App\Support\TeacherSalaryEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Settings > Notifications > "Email salary slip to teacher" (off by default):
 * paying a salary emails the slip + Student Payments (by collection date,
 * that branch) PDFs to the teacher. "Email Slip" sends it again.
 */
class TeacherSalaryEmailTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-29 10:00:00');
        $this->buildInstitute();
        TeacherSalaryEmail::forget();
        $i = $this->inst;

        $payment = DB::table('payments')->where('status', 'completed')->value('id');
        DB::table('payment_split_snapshots')->insert([
            'branch_id' => $i['b1'], 'payment_id' => $payment, 'class_fee' => 2000, 'total_fee' => 2000,
            'student_class_id' => $i['c1'], 'student_class_enrollment_id' => $i['e1'],
            'class_payment_config_id' => DB::table('class_payment_configs')->where('student_class_id', $i['c1'])->value('id'),
            'teacher_id' => $i['teacher'], 'payment_amount' => 2000, 'teacher_percentage' => 60,
            'institution_percentage' => 40, 'teacher_amount' => 1200, 'institution_amount' => 800,
            'payment_date' => '2026-10-05 09:00:00', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('teacher_branches')->insert(['teacher_id' => $i['teacher'], 'branch_id' => $i['b1']]);
        config(['services.institute.name' => 'Success Education', 'mail.from.address' => 'no-reply@test.lk']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        TeacherSalaryEmail::forget();
        parent::tearDown();
    }

    private function pay()
    {
        return $this->actingAs($this->inst['admin'])->post('/teacher-salaries/' . $this->inst['teacher'] . '/pay', [
            'branch_id' => $this->inst['b1'], 'year' => 2026, 'month' => 10,
        ]);
    }

    private function emailSlip()
    {
        return $this->actingAs($this->inst['admin'])->post(
            '/teacher-salaries/' . $this->inst['teacher'] . '/2026/10/email-slip',
            ['branch_id' => $this->inst['b1']]
        );
    }

    public function test_off_by_default_no_email_is_sent(): void
    {
        Bus::fake([SendTeacherSalarySlip::class]);

        $this->assertFalse(TeacherSalaryEmail::enabled());
        $this->pay()->assertSessionHas('success', 'Salary paid successfully.');

        Bus::assertNotDispatched(SendTeacherSalarySlip::class);
    }

    public function test_paying_emails_the_slip_and_the_branch_report_from_the_institute(): void
    {
        TeacherSalaryEmail::save(true);

        $this->pay()->assertSessionHas('success', 'Salary paid successfully. Salary slip will be emailed to t@test.lk.');

        $messages = app('mailer')->getSwiftMailer()->getTransport()->messages();
        $this->assertCount(1, $messages);
        $message = $messages->first();

        $this->assertSame(['t@test.lk'], array_keys($message->getTo()));
        $this->assertSame(['no-reply@test.lk' => 'Success Education'], $message->getFrom());
        $this->assertSame('Salary slip - 2026 Oct - Success Education', $message->getSubject());
        $this->assertStringContainsString('LKR 1,200.00', $message->getBody());
        $this->assertStringContainsString('Powered by NexoraIT', $message->getBody());

        $files = collect($message->getChildren())
            ->filter(fn ($part) => $part instanceof \Swift_Attachment)
            ->map(fn ($part) => $part->getFilename())
            ->values()
            ->all();
        $this->assertSame(['salary-slip-T1-2026-10.pdf', 'student-payments-T1-2026-10.pdf'], $files);
    }

    public function test_teacher_without_email_is_skipped_and_told(): void
    {
        TeacherSalaryEmail::save(true);
        DB::table('teachers')->where('id', $this->inst['teacher'])->update(['email' => '']);
        Mail::fake();

        $this->pay()->assertSessionHas('success', 'Salary paid successfully. Teacher has no email, slip not sent.');
        $this->assertDatabaseHas('teacher_salaries', ['teacher_id' => $this->inst['teacher'], 'status' => 'paid']);

        Mail::assertNothingSent();
        $this->emailSlip()->assertSessionHas('error');
    }

    public function test_email_slip_button_sends_again_only_for_a_paid_salary(): void
    {
        Mail::fake();

        $this->emailSlip()->assertSessionHas('error', 'Pay the salary first, then email the slip.');

        $this->pay();
        Mail::assertNothingSent();   // setting is off

        $this->emailSlip()->assertSessionHas('success', 'Salary slip will be emailed to t@test.lk.');
        Mail::assertSent(TeacherSalarySlipMail::class, fn ($mail) => $mail->hasTo('t@test.lk'));

        $this->actingAs($this->inst['admin'])
            ->get('/teacher-salaries/' . $this->inst['teacher'] . '/2026/10?branch_id=' . $this->inst['b1'])
            ->assertOk()
            ->assertSee('Email Slip');
    }

    public function test_the_report_is_for_the_paid_branch_only(): void
    {
        $this->actingAs($this->inst['admin']);
        $service = app(\App\Services\Web\MonthlyReport\MonthlyReportService::class);

        $all = $service->teacherStudentPaymentReport($this->inst['teacher'], 2026, 10, 'paid-date');
        $b2 = $service->teacherStudentPaymentReport($this->inst['teacher'], 2026, 10, 'paid-date', $this->inst['b2']);

        $this->assertGreaterThan(count($b2['classes']), count($all['classes']));
        $this->assertSame([$this->inst['c2']], collect($b2['classes'])->pluck('class_id')->all());
    }

    public function test_setting_is_saved_on_the_notifications_page(): void
    {
        $settings = DB::table('notification_settings')->pluck('key')
            ->mapWithKeys(fn ($key) => [$key => ['is_enabled' => '0']])->all();

        $this->actingAs($this->inst['admin'])->get('/admin/setting/notifications')
            ->assertOk()->assertSee('Email salary slip to teacher');

        $this->actingAs($this->inst['admin'])->put('/admin/setting/notifications', [
            'settings' => $settings, 'teacher_salary_email' => '1',
        ])->assertSessionHas('success');

        $this->assertTrue(TeacherSalaryEmail::enabled());
    }
}

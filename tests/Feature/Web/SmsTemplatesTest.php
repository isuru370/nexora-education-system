<?php

namespace Tests\Feature\Web;

use App\Jobs\SendAttendanceSuccessSmsJob;
use App\Jobs\SendPaymentSms;
use App\Models\NotificationSetting;
use App\Models\Payment;
use App\Services\Notification\PaymentNotificationService;
use App\Services\SmsService;
use App\Support\SmsTemplates;
use App\Support\SmsText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Settings > SMS Messages: editable SMS texts, cost per SMS, English only,
 * and the hall fee that must be in the payment SMS.
 */
class SmsTemplatesTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    public function test_sms_length_and_parts(): void
    {
        $this->assertSame(1, SmsText::parts(str_repeat('a', 160)));
        $this->assertSame(2, SmsText::parts(str_repeat('a', 161)));
        $this->assertSame(2, SmsText::parts(str_repeat('a', 306)));
        $this->assertSame(3, SmsText::parts(str_repeat('a', 307)));
        $this->assertSame(0, SmsText::parts(''));

        // [ and ] count as 2 each
        $this->assertSame(5, SmsText::length('[a]'));

        // English only: Sinhala removed, smart quotes made plain
        $this->assertSame("Paid 'OK' - thanks", SmsText::english("Paid \u{2018}OK\u{2019} \u{2013} ගෙවා thanks"));
    }

    public function test_payment_sms_has_the_hall_fee_only_when_the_hall_price_is_not_zero(): void
    {
        $values = ['[Student Name]' => 'A. Silva', '[Class]' => 'Maths', '[Month]' => 'October 2026',
            '[Amount]' => '2,100.00', '[Receipt]' => 'R1'];

        $with = SmsTemplates::render(SmsTemplates::PAYMENT, $values + ['[Hall Fee]' => '100.00']);
        $this->assertSame("A. Silva - Rs. 2,100.00 paid.\nMaths - October 2026\nIncl. hall fee: Rs. 100.00\nReceipt: R1\nThank you.", $with);

        $without = SmsTemplates::render(SmsTemplates::PAYMENT, $values + ['[Hall Fee]' => '']);
        $this->assertStringNotContainsString('Hall fee', $without);

        // a text without [Hall Fee] still gets it (after the amount)
        $custom = SmsTemplates::render(
            SmsTemplates::PAYMENT,
            $values + ['[Hall Fee]' => '100.00'],
            "Paid Rs. [Amount] for [Class].\nThank you."
        );
        $this->assertSame("Paid Rs. 2,100.00 for Maths.\nIncl. hall fee: Rs. 100.00\nThank you.", $custom);
    }

    public function test_payment_sms_uses_the_saved_text_and_the_class_hall_price(): void
    {
        $this->buildInstitute();
        Queue::fake();
        $this->sendBy(NotificationSetting::PAYMENT);

        $hall = DB::table('class_halls')->insertGetId([
            'branch_id' => $this->inst['b1'], 'code' => 'H1', 'hall_name' => 'Hall A', 'hall_price' => 150,
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('class_schedule_patterns')->where('student_class_id', $this->inst['c1'])->update(['class_hall_id' => $hall]);

        SmsTemplates::save(SmsTemplates::PAYMENT, "[Student Name] paid Rs. [Amount] ([Month]).\nHall fee: Rs. [Hall Fee]\nReceipt [Receipt]");

        $payment = Payment::query()->where('student_id', $this->inst['s1'])->where('status', 'completed')->firstOrFail();
        app(PaymentNotificationService::class)->sendSuccess($payment);

        $name = DB::table('students')->where('id', $this->inst['s1'])->value('initial_name');
        Queue::assertPushed(SendPaymentSms::class, function ($job) use ($name) {
            $message = (fn () => $this->message)->call($job);

            return $message === "{$name} paid Rs. 2,000.00 (October 2026).\nHall fee: Rs. 150.00\nReceipt R1";
        });
    }

    public function test_attendance_sms_default_text(): void
    {
        $text = SmsTemplates::render(SmsTemplates::ATTENDANCE, [
            '[Student Name]' => 'A. Silva', '[Class]' => 'Maths', '[Category]' => 'Theory',
            '[Grade]' => '10', '[Date]' => '2026-11-04', '[Time]' => '08:15',
        ]);

        $this->assertSame("Attendance marked.\nStudent: A. Silva\nClass: Maths\nCategory: Theory\nGrade: 10\nDate: 2026-11-04\nTime: 08:15\nThank you.", $text);
        $this->assertTrue(class_exists(SendAttendanceSuccessSmsJob::class));
    }

    public function test_admin_edits_texts_and_cost(): void
    {
        $this->buildInstitute();
        $this->actingAs($this->inst['admin']);

        $this->get('/admin/setting/sms-templates')
            ->assertOk()
            ->assertSee('SMS Messages')
            ->assertSee('Payment')
            ->assertSee('[Hall Fee]', false);

        $this->put('/admin/setting/sms-templates', [
            'cost' => '0.70',
            'templates' => [
                'attendance' => '[Student Name] came to [Class] at [Time].',
                'payment' => '', // empty = default
            ],
        ])->assertRedirect('/admin/setting/sms-templates');

        $this->assertSame(0.70, SmsTemplates::costPerSms());
        $this->assertSame('[Student Name] came to [Class] at [Time].', SmsTemplates::body('attendance'));
        $this->assertFalse(SmsTemplates::isCustom('payment'));

        // back to the default text = row removed
        $this->put('/admin/setting/sms-templates', [
            'cost' => '0.65',
            'templates' => ['attendance' => SmsTemplates::definition('attendance')['default']],
        ])->assertRedirect();
        $this->assertFalse(SmsTemplates::isCustom('attendance'));
    }

    public function test_bad_texts_are_not_saved(): void
    {
        $this->buildInstitute();
        $this->actingAs($this->inst['admin']);

        $this->put('/admin/setting/sms-templates', [
            'cost' => '0.65',
            'templates' => [
                'payment' => 'Paid Rs. [Amount].',              // no [Hall Fee]
                'attendance' => 'ළමයා [Student Name] came.',    // Sinhala
                'class_schedule' => 'Class [Clas] changed.',    // unknown placeholder
            ],
        ])->assertSessionHasErrors(['templates.payment', 'templates.attendance', 'templates.class_schedule']);

        $this->assertFalse(SmsTemplates::isCustom('payment'));
        $this->assertSame(SmsTemplates::DEFAULT_COST, SmsTemplates::costPerSms());
    }

    public function test_test_sms_sends_the_preview(): void
    {
        $this->buildInstitute();
        $this->actingAs($this->inst['admin']);

        $sms = Mockery::mock(SmsService::class);
        $sms->shouldReceive('sendSms')->once()
            ->withArgs(fn ($mobile, $message) => $mobile === '0771234567'
                && str_contains($message, 'came to 2027 A/L Combined Maths'))
            ->andReturn(['success' => true]);
        $this->app->instance(SmsService::class, $sms);

        $this->post('/admin/setting/sms-templates/test', [
            'key' => 'attendance',
            'body' => '[Student Name] came to [Class].',
            'mobile' => '0771234567',
        ])->assertSessionHas('success');
    }

    public function test_only_admins_open_the_page(): void
    {
        $this->buildInstitute();
        $this->actingAs($this->inst['cashier']);

        $this->get('/admin/setting/sms-templates')->assertForbidden();
    }

    public function test_payment_reminder_default_comes_from_the_settings(): void
    {
        SmsTemplates::save(SmsTemplates::PAYMENT_REMINDER, 'Pay [Amount] for [Class], [Month].');

        $this->assertSame(
            'Pay [Amount] for [Class], [Month].',
            app(\App\Services\Web\PaymentReminder\PaymentReminderService::class)->defaultMessage()
        );
    }

    protected function sendBy(string $key): void
    {
        NotificationSetting::query()->updateOrCreate(
            ['key' => $key],
            ['label' => $key, 'is_enabled' => true, 'channel' => NotificationSetting::SMS]
        );
        NotificationSetting::clearCache();
    }
}

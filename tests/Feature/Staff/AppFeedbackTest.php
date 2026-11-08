<?php

namespace Tests\Feature\Staff;

use App\Jobs\SendAppFeedbackEmail;
use App\Mail\AppFeedbackMail;
use App\Models\AppFeedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/** Staff app "Send Feedback": saved, emailed to Nexora, Super Admin page */
class AppFeedbackTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildInstitute();
        Storage::fake('public');
    }

    public function test_staff_send_feedback_with_a_screenshot_and_it_is_emailed(): void
    {
        Queue::fake();
        Sanctum::actingAs($this->inst['cashier']);

        $this->postJson('/api/feedback', [
            'type' => 'bug', 'message' => 'Printer does not connect after update',
            'screenshot' => UploadedFile::fake()->image('s.png', 400, 800),
            'app_version' => '1.1.3 (26110801)', 'device' => 'Redmi Note 12', 'platform' => 'Android 14',
        ])->assertCreated()->assertJsonPath('success', true);

        $feedback = AppFeedback::firstOrFail();
        $this->assertSame('new', $feedback->status);
        $this->assertSame($this->inst['cashier']->id, $feedback->user_id);
        Storage::disk('public')->assertExists($feedback->screenshot_path);
        Queue::assertPushed(SendAppFeedbackEmail::class, fn ($job) => $job->feedbackId === $feedback->id);

        $this->postJson('/api/feedback', ['type' => 'nonsense', 'message' => 'x'])->assertStatus(422);
    }

    public function test_email_goes_to_nexora(): void
    {
        $feedback = AppFeedback::create(['user_id' => $this->inst['admin']->id, 'type' => 'idea', 'message' => 'Dark mode please', 'status' => 'new']);

        // rendered email
        $html = (new AppFeedbackMail($feedback))->render();
        $this->assertStringContainsString('Dark mode please', $html);
        $this->assertStringContainsString('Idea', $html);

        Mail::fake();
        config(['services.nexora.feedback_email' => 'info@nexorait.lk']);

        (new SendAppFeedbackEmail($feedback->id))->handle();

        Mail::assertSent(AppFeedbackMail::class, fn ($mail) => $mail->hasTo('info@nexorait.lk'));
        $this->assertNotNull($feedback->fresh()->emailed_at);
    }

    public function test_super_admin_page_lists_and_updates_feedback(): void
    {
        $super = User::forceCreate([
            'name' => 'Owner', 'email' => 'super@test.lk', 'password' => 'password123', 'is_active' => 1,
            'user_type_id' => DB::table('user_types')->insertGetId(['name' => 'SUPER_ADMIN', 'code' => 'SUPER_ADMIN', 'is_active' => 1]),
        ]);
        $feedback = AppFeedback::create(['user_id' => $this->inst['cashier']->id, 'type' => 'bug', 'message' => 'Receipt month wrong', 'status' => 'new']);

        $this->actingAs($this->inst['admin'])->get('/app-feedback')->assertForbidden();

        $this->actingAs($super)->get('/app-feedback')->assertOk()->assertSee('Receipt month wrong')->assertSee('App Feedback');
        $this->patch('/app-feedback/' . $feedback->id, ['status' => 'done', 'admin_note' => 'Fixed in 1.1.3'])->assertRedirect();
        $this->assertSame('done', $feedback->fresh()->status);
        $this->get('/app-feedback?status=new')->assertOk()->assertDontSee('Receipt month wrong');
    }
}

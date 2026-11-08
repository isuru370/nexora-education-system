<?php

namespace Tests\Feature\Web;

use App\Mail\PasswordResetMail;
use App\Models\StudentPortalLogin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Every way a password changes must really change it: own profile, admin
 * (change / new user), teacher reset and the teacher app, forgot password.
 */
class PasswordFlowsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildInstitute();
        RateLimiter::clear('password-reset-send:admin@test.lk');
        RateLimiter::clear('password-reset-verify:admin@test.lk');
    }

    private function passwordOf(User $user): string
    {
        return (string) DB::table('users')->where('id', $user->id)->value('password');
    }

    /* ---------- the model ---------- */

    public function test_a_hashed_value_is_kept_and_a_plain_one_is_hashed(): void
    {
        $user = $this->inst['cashier'];

        $user->update(['password' => Hash::make('NewPass123')]);
        $this->assertTrue(Hash::check('NewPass123', $this->passwordOf($user)));

        $user->update(['password' => 'Another456']);
        $this->assertTrue(Hash::check('Another456', $this->passwordOf($user)));

        // empty: unchanged
        $user->update(['password' => '']);
        $this->assertTrue(Hash::check('Another456', $this->passwordOf($user)));

        $login = new StudentPortalLogin();
        $login->password = Hash::make('Parent123');
        $this->assertTrue(Hash::check('Parent123', $login->getAttributes()['password']));
        $login->password = 'Parent456';
        $this->assertTrue(Hash::check('Parent456', $login->getAttributes()['password']));
    }

    /* ---------- own profile ---------- */

    public function test_profile_change_password(): void
    {
        $user = $this->inst['cashier'];

        $this->actingAs($user)->post('/admin/profile/password', [
            'current_password' => 'wrong-pass',
            'password' => 'NewPass123', 'password_confirmation' => 'NewPass123',
        ])->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('password123', $this->passwordOf($user)));

        $this->actingAs($user)->post('/admin/profile/password', [
            'current_password' => 'password123',
            'password' => 'NewPass123', 'password_confirmation' => 'NewPass123',
        ])->assertSessionHas('success');

        $this->assertTrue(Hash::check('NewPass123', $this->passwordOf($user)));
    }

    /* ---------- admin ---------- */

    public function test_admin_changes_a_users_password(): void
    {
        $user = $this->inst['cashier'];

        $this->actingAs($this->inst['admin'])->patch("/admin/users/{$user->id}/change-password", [
            'password' => 'Reset1234', 'password_confirmation' => 'Reset1234', 'send_sms' => '0',
        ])->assertSessionHas('success');

        $this->assertTrue(Hash::check('Reset1234', $this->passwordOf($user)));
    }

    public function test_admin_creates_a_user_who_can_log_in(): void
    {
        $type = DB::table('user_types')->where('code', 'USER')->value('id');

        $this->actingAs($this->inst['admin'])->post('/admin/users', [
            'name' => 'New Cashier', 'email' => 'new.cashier@test.lk',
            'password' => 'Cashier123', 'password_confirmation' => 'Cashier123',
            'user_type_id' => $type, 'is_active' => '1', 'send_sms' => '0',
        ]);

        $user = User::where('email', 'new.cashier@test.lk')->first();
        $this->assertNotNull($user, 'user was not created');
        $this->assertTrue(Hash::check('Cashier123', $this->passwordOf($user)));
    }

    /* ---------- teacher ---------- */

    public function test_teacher_reset_by_admin_and_change_in_the_app(): void
    {
        $teacherUser = $this->makeRoleUser('TEACHER');
        DB::table('teachers')->where('id', $this->inst['teacher'])->update(['user_id' => $teacherUser->id]);
        DB::table('teacher_branches')->insert(['teacher_id' => $this->inst['teacher'], 'branch_id' => $this->inst['b1']]);

        $this->actingAs($this->inst['admin'])->post('/teachers/' . $this->inst['teacher'] . '/reset-password', [
            'password' => 'Teacher123', 'password_confirmation' => 'Teacher123', 'send_sms' => '0',
        ])->assertSessionHas('success');
        $this->assertTrue(Hash::check('Teacher123', $this->passwordOf($teacherUser)));

        $this->actingAs($teacherUser, 'sanctum')->postJson('/api/v1/teacher/profile/change-password', [
            'current_password' => 'Teacher123',
            'new_password' => 'Teacher456', 'new_password_confirmation' => 'Teacher456',
        ])->assertOk();
        $this->assertTrue(Hash::check('Teacher456', $this->passwordOf($teacherUser)));
    }

    /* ---------- forgot password ---------- */

    private function sendOtp(): string
    {
        Mail::fake();
        $this->post('/forgot-password/send-otp', ['email' => 'admin@test.lk'])
            ->assertRedirect('/forgot-password');

        $otp = null;
        Mail::assertSent(PasswordResetMail::class, function ($mail) use (&$otp) {
            $otp = $mail->otp;

            return true;
        });

        return $otp;
    }

    public function test_forgot_password_changes_the_password_and_ends_app_logins(): void
    {
        $admin = $this->inst['admin'];
        $admin->createToken('staff-app');

        $otp = $this->sendOtp();
        $this->get('/forgot-password')->assertSee('name="otp"', false);

        $this->post('/forgot-password/verify-otp', ['email' => 'admin@test.lk', 'otp' => $otp])
            ->assertSessionHas('success', 'OTP verified successfully.');

        // mismatch: stays on the new password step
        $this->post('/forgot-password/reset-password', [
            'email' => 'admin@test.lk', 'password' => 'Forgot1234', 'password_confirmation' => 'nope',
        ])->assertSessionHasErrors('password');
        $this->get('/forgot-password')->assertSee('name="password_confirmation"', false);

        $this->post('/forgot-password/reset-password', [
            'email' => 'admin@test.lk', 'password' => 'Forgot1234', 'password_confirmation' => 'Forgot1234',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('Forgot1234', $this->passwordOf($admin)));
        $this->assertSame(0, $admin->tokens()->count());
        $this->assertSame(0, DB::table('password_reset_otps')->count());

        // the flow is finished: back to the email step
        $this->get('/forgot-password')->assertDontSee('name="otp"', false);
    }

    public function test_wrong_otp_stays_on_the_otp_step_and_five_wrong_cancel_it(): void
    {
        $otp = $this->sendOtp();
        $wrong = $otp === '111111' ? '222222' : '111111';

        $this->post('/forgot-password/verify-otp', ['email' => 'admin@test.lk', 'otp' => $wrong])
            ->assertSessionHasErrors(['otp' => 'Invalid OTP. 4 tries left.']);
        $this->get('/forgot-password')->assertSee('name="otp"', false);

        for ($i = 0; $i < 4; $i++) {
            $this->post('/forgot-password/verify-otp', ['email' => 'admin@test.lk', 'otp' => $wrong]);
        }

        // cancelled: even the right code no longer works
        $this->post('/forgot-password/verify-otp', ['email' => 'admin@test.lk', 'otp' => $otp])
            ->assertSessionHasErrors('otp');
        $this->assertTrue(Hash::check('password123', $this->passwordOf($this->inst['admin'])));
    }

    public function test_new_password_needs_the_browser_that_verified_the_otp(): void
    {
        $otp = $this->sendOtp();
        $this->post('/forgot-password/verify-otp', ['email' => 'admin@test.lk', 'otp' => $otp]);

        // another browser (no session) cannot set it
        $this->flushSession();
        $this->post('/forgot-password/reset-password', [
            'email' => 'admin@test.lk', 'password' => 'Hacker1234', 'password_confirmation' => 'Hacker1234',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password123', $this->passwordOf($this->inst['admin'])));
    }

    public function test_start_again_clears_the_steps(): void
    {
        $this->sendOtp();
        $this->get('/forgot-password')->assertSee('name="otp"', false);

        $this->get('/forgot-password?restart=1')->assertRedirect('/forgot-password');
        $this->get('/forgot-password')->assertDontSee('name="otp"', false);
    }
}

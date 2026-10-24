<?php

namespace Tests\Feature\Web;

use App\Jobs\SendLoginDetailsSms;
use App\Services\Auth\LoginDetailsSms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Creating a login (teacher, system user, user) or setting a new password
 * sends the username, password, institute name and login link by SMS,
 * unless "Send login details by SMS" is unticked.
 */
class LoginDetailsSmsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildInstitute();

        config([
            'app.name' => 'NexoraEDU',
            'app.url' => 'https://savidya.nexorait.lk',
            'services.institute.name' => 'Savidya Higher Education Institute',
        ]);

        DB::table('user_types')->insert(['name' => 'Teacher', 'code' => 'TEACHER', 'is_active' => 1]);

        // teachers are reached through their branches
        DB::table('teacher_branches')->insert(['teacher_id' => $this->inst['teacher'], 'branch_id' => $this->inst['b1']]);

        Bus::fake([SendLoginDetailsSms::class]);
        $this->actingAs($this->inst['admin']);
    }

    private function teacherMobile(): string
    {
        return DB::table('teachers')->where('id', $this->inst['teacher'])->value('mobile');
    }

    public function test_message_has_the_login_details(): void
    {
        $this->assertSame(
            "Savidya Higher Education Institute\n"
            . "Hi Nimal Perera,\n"
            . "Your teacher login is ready.\n"
            . "Username: t@test.lk\n"
            . "Password: Abc12345\n"
            . "Login: https://savidya.nexorait.lk/login\n"
            . 'Please change your password after you log in.',
            LoginDetailsSms::message('Nimal Perera', 't@test.lk', 'Abc12345', 'teacher')
        );

        $this->assertStringContainsString('Your staff password was changed.', LoginDetailsSms::message('A', 'a', 'b', 'staff', true));

        config(['services.institute.name' => null]);
        $this->assertStringStartsWith("NexoraEDU\n", LoginDetailsSms::message('A', 'a', 'b', 'staff'));
    }

    public function test_teacher_login_and_password_reset_send_an_sms(): void
    {
        $teacher = $this->inst['teacher'];

        $this->post(route('teachers.create-login', $teacher), [
            'password' => 'Abc12345',
            'password_confirmation' => 'Abc12345',
            'send_sms' => '1',
        ])->assertRedirect()->assertSessionHas('success', fn ($m) => str_contains($m, 'Login details sent by SMS to ' . $this->teacherMobile()));

        Bus::assertDispatched(SendLoginDetailsSms::class, fn ($job) => $job->mobile === $this->teacherMobile()
            && str_contains($job->message, 'Savidya Higher Education Institute')
            && str_contains($job->message, 'Your teacher login is ready.')
            && str_contains($job->message, 'Username: t@test.lk')
            && str_contains($job->message, 'Password: Abc12345')
            && str_contains($job->message, 'Login: https://savidya.nexorait.lk/login'));

        $this->post(route('teachers.reset-password', $teacher), [
            'password' => 'NewPass99',
            'password_confirmation' => 'NewPass99',
            'send_sms' => '1',
        ])->assertRedirect();

        Bus::assertDispatched(SendLoginDetailsSms::class, fn ($job) => str_contains($job->message, 'Your teacher password was changed.')
            && str_contains($job->message, 'Password: NewPass99'));
        Bus::assertDispatchedTimes(SendLoginDetailsSms::class, 2);
    }

    public function test_unticked_box_sends_nothing(): void
    {
        $this->post(route('teachers.create-login', $this->inst['teacher']), [
            'password' => 'Abc12345',
            'password_confirmation' => 'Abc12345',
            'send_sms' => '0',
        ])->assertRedirect()->assertSessionHas('success', 'Login account created successfully.');

        Bus::assertNotDispatched(SendLoginDetailsSms::class);
    }

    public function test_new_system_user_gets_an_sms(): void
    {
        $cashierType = DB::table('user_types')->where('code', 'USER')->value('id');

        $this->post(route('admin.system-users.store'), [
            'user_type_id' => $cashierType,
            'branch_ids' => [$this->inst['b1']],
            'full_name' => 'Kamal Silva',
            'mobile' => '0712223334',
            'nic' => '991234567V',
            'gender' => 'male',
            'address1' => 'Colombo',
            'is_active' => '1',
            'email' => 'kamal@savidya.lk',
            'password' => 'Kamal123',
            'send_sms' => '1',
        ])->assertRedirect(route('admin.system-users.index'));

        Bus::assertDispatched(SendLoginDetailsSms::class, fn ($job) => $job->mobile === '0712223334'
            && str_contains($job->message, 'Hi Kamal Silva,')
            && str_contains($job->message, 'Your cashier login is ready.')
            && str_contains($job->message, 'Username: kamal@savidya.lk')
            && str_contains($job->message, 'Password: Kamal123'));
    }

    public function test_change_password_uses_the_system_user_mobile(): void
    {
        $cashier = $this->inst['cashier'];

        // no system user profile yet -> no mobile
        $this->patch(route('admin.users.changePassword', $cashier), [
            'password' => 'Changed123',
            'password_confirmation' => 'Changed123',
            'send_sms' => '1',
        ])->assertSessionHas('success', fn ($m) => str_contains($m, 'No mobile number'));
        Bus::assertNotDispatched(SendLoginDetailsSms::class);

        DB::table('system_users')->insert([
            'custom_id' => 'SU-T1', 'user_id' => $cashier->id, 'full_name' => 'Cashier',
            'mobile' => '0751112223', 'gender' => 'female', 'is_active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->patch(route('admin.users.changePassword', $cashier), [
            'password' => 'Changed456',
            'password_confirmation' => 'Changed456',
            'send_sms' => '1',
        ])->assertSessionHas('success');

        Bus::assertDispatched(SendLoginDetailsSms::class, fn ($job) => $job->mobile === '0751112223'
            && str_contains($job->message, 'Your cashier password was changed.')
            && str_contains($job->message, 'Password: Changed456'));
    }

    public function test_forms_show_the_sms_box(): void
    {
        $this->get(route('teachers.loginDetails', $this->inst['teacher']))->assertOk()->assertSee('Send login details by SMS');
        $this->get(route('admin.system-users.create'))->assertOk()->assertSee('Send login details by SMS');
        $this->get(route('admin.users.create'))->assertOk()->assertSee('Send login details by SMS');
    }
}

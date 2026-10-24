<?php

namespace App\Services\Auth;

use App\Jobs\SendLoginDetailsSms;
use App\Models\SystemUser;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * SMS with the login details when an admin creates a login (system user,
 * teacher, user) or sets a new password:
 *
 *   Savidya Higher Education Institute
 *   Hi Nimal Perera,
 *   Your teacher login is ready.
 *   Username: nimal@savidya.lk
 *   Password: Abc12345
 *   Login: https://savidya.nexorait.lk/login
 *   Please change your password after you log in.
 *
 * The forms have a "Send login details by SMS" box (send_sms, ticked).
 */
class LoginDetailsSms
{
    public const SENT = 'sent';
    public const NO_MOBILE = 'no_mobile';
    public const SKIPPED = 'skipped';
    public const FAILED = 'failed';

    /**
     * @return string  SENT / NO_MOBILE / SKIPPED / FAILED
     */
    public function send(
        User $user,
        string $password,
        bool $reset = false,
        ?string $mobile = null,
        bool $wanted = true
    ): string {
        if (!$wanted) {
            return self::SKIPPED;
        }

        $mobile = trim((string) ($mobile ?: $this->mobileFor($user)));

        if ($mobile === '') {
            return self::NO_MOBILE;
        }

        try {
            SendLoginDetailsSms::dispatch(
                $mobile,
                self::message($user->name, (string) $user->email, $password, $this->roleOf($user), $reset),
                (int) $user->id
            );

            return self::SENT;
        } catch (Throwable $e) {
            Log::error('Login details SMS could not be queued', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            return self::FAILED;
        }
    }

    public static function message(
        string $name,
        string $username,
        string $password,
        string $role,
        bool $reset = false
    ): string {
        $institute = config('services.institute.name') ?: config('app.name');
        $login = rtrim((string) config('app.url'), '/') . '/login';

        return implode("\n", [
            $institute,
            'Hi ' . trim($name) . ',',
            $reset ? "Your {$role} password was changed." : "Your {$role} login is ready.",
            'Username: ' . $username,
            'Password: ' . $password,
            'Login: ' . $login,
            'Please change your password after you log in.',
        ]);
    }

    /** "teacher", "staff", "admin" ... for the SMS text. */
    public function roleOf(User $user): string
    {
        return match (strtoupper((string) optional($user->userType)->code)) {
            'TEACHER' => 'teacher',
            'ADMIN', 'SUPER_ADMIN' => 'admin',
            'BRANCH_ADMIN' => 'branch admin',
            'USER' => 'cashier',
            default => 'staff',
        };
    }

    /** Mobile of the teacher / system user behind this login. */
    public function mobileFor(User $user): ?string
    {
        $teacher = Teacher::query()->where('user_id', $user->id)->first()
            ?? ($user->teacher_id ? Teacher::query()->find($user->teacher_id) : null);

        if ($teacher && filled($teacher->mobile)) {
            return $teacher->mobile;
        }

        return SystemUser::query()->where('user_id', $user->id)->value('mobile') ?: null;
    }

    /** Line added to the success message on the page. */
    public static function notice(string $result, ?string $mobile = null): string
    {
        return match ($result) {
            self::SENT => ' Login details sent by SMS' . ($mobile ? " to {$mobile}" : '') . '.',
            self::NO_MOBILE => ' No mobile number, so the login details were not sent by SMS.',
            self::FAILED => ' The SMS could not be sent - give the login details yourself.',
            default => '',
        };
    }
}

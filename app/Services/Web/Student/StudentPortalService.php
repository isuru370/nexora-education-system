<?php

namespace App\Services\Web\Student;

use App\Jobs\SendStudentPortalLoginSms;
use App\Models\Student;
use App\Models\StudentPortalLogin;
use App\Services\ParentHub\ParentHubService;
use Illuminate\Support\Facades\DB;

class StudentPortalService
{
    public function __construct(
        private ParentHubService $parentHubService
    ) {
    }

    /**
     * Create student portal login and return plain password.
     */
    public function createStudentPortalLogin(Student $student): string
    {
        $plainPassword = $this->generateStudentPassword(
            $student->initial_name,
            $student->guardian_mobile
        );

        StudentPortalLogin::create([
            'student_id' => $student->id,
            'username' => $student->custom_id,
            'password' => $plainPassword,
            'is_verified' => true,
            'is_active' => true,
        ]);

        DB::afterCommit(function () use (
            $student,
            $plainPassword
        ) {

            $this->parentHubService->registerStudent(
                $student->custom_id,
                $student->custom_id
            );

            // SendStudentPortalLoginSms::dispatch(
            //     $student->guardian_mobile,
            //     $student->custom_id,
            //     $plainPassword
            // );
        });

        return $plainPassword;
    }

    /**
     * Reset student portal password.
     */
    public function resetPortalPassword(
        StudentPortalLogin $login
    ): void {

        $plainPassword = $this->generateStudentPassword(
            $login->student->initial_name,
            $login->student->guardian_mobile
        );

        $login->password = $plainPassword;
        $login->save();

        SendStudentPortalLoginSms::dispatch(
            $login->student->guardian_mobile,
            $login->username,
            $plainPassword
        );
    }

    /**
     * Generate student password.
     */
    private function generateStudentPassword(
        string $initialName,
        string $guardianMobile
    ): string {

        $letters = preg_replace(
            '/[^A-Za-z]/',
            '',
            $initialName
        );

        $initials = strtoupper(
            substr($letters, 0, 2)
        );

        if (strlen($initials) < 2) {
            $initials = str_pad(
                $initials,
                2,
                'X'
            );
        }

        $mobileDigits = preg_replace(
            '/\D/',
            '',
            $guardianMobile
        );

        $lastFour = substr(
            $mobileDigits,
            -4
        );

        $randomLetters =
            chr(random_int(65, 90))
            .
            chr(random_int(97, 122));

        return $initials
            . $lastFour
            . $randomLetters;
    }
}
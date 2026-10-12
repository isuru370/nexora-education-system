<?php

namespace App\Services\Teacher;

use App\Models\Teacher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TeacherProfileService
{
    /**
     * Get teacher profile.
     */
    public function getProfile(int $teacherId): array
    {
        $teacher = Teacher::query()
            ->with([
                'user:id,name,email',
                'bankBranch:id,bank_id,branch_name,branch_code',
            ])
            ->findOrFail($teacherId);

        return [
            'teacher' => $this->formatTeacher($teacher),
        ];
    }

    /**
     * Update teacher profile.
     */
    public function updateProfile(
        int $teacherId,
        array $data
    ): array {
        return DB::transaction(function () use ($teacherId, $data) {

            $teacher = Teacher::query()
                ->with([
                    'user:id,name,email',
                    'bankBranch:id,bank_id,branch_name,branch_code',
                ])
                ->findOrFail($teacherId);

            /*
             * Update Teacher table.
             */
            $teacherData = collect($data)
                ->only([
                    'full_name',
                    'initials',
                    'email',
                    'mobile',
                    'nic',
                    'bday',
                    'gender',
                    'address1',
                    'address2',
                    'address3',
                    'graduation_details',
                    'experience',
                    'account_number',
                    'bank_branch_id',
                ])
                ->filter(function ($value) {
                    return $value !== null;
                })
                ->toArray();

            if (!empty($teacherData)) {
                $teacher->update($teacherData);
            }

            /*
             * Keep User name/email synchronized.
             */
            if ($teacher->user) {

                $userData = [];

                if (array_key_exists('full_name', $data)) {
                    $userData['name'] = $data['full_name'];
                }

                if (array_key_exists('email', $data)) {
                    $userData['email'] = $data['email'];
                }

                if (!empty($userData)) {
                    $teacher->user->update($userData);
                }
            }

            /*
             * Reload updated relationships.
             */
            $teacher->refresh();

            $teacher->load([
                'user:id,name,email',
                'bankBranch:id,bank_id,branch_name,branch_code',
            ]);

            return [
                'teacher' => $this->formatTeacher($teacher),
            ];
        });
    }

    /**
     * Change teacher password.
     */
    public function changePassword(
        int $teacherId,
        string $currentPassword,
        string $newPassword
    ): void {
        $teacher = Teacher::query()
            ->with('user')
            ->findOrFail($teacherId);

        if (!$teacher->user) {
            throw ValidationException::withMessages([
                'password' => [
                    'User account not found.',
                ],
            ]);
        }

        /*
         * Verify current password.
         */
        if (!Hash::check(
            $currentPassword,
            $teacher->user->password
        )) {
            throw ValidationException::withMessages([
                'current_password' => [
                    'Current password is incorrect.',
                ],
            ]);
        }

        /*
         * Prevent same password.
         */
        if (Hash::check(
            $newPassword,
            $teacher->user->password
        )) {
            throw ValidationException::withMessages([
                'new_password' => [
                    'New password must be different from the current password.',
                ],
            ]);
        }

        /*
         * Update password.
         *
         * User model mutator will hash the password.
         */
        $teacher->user->update([
            'password' => $newPassword,
        ]);
    }

    /**
     * Format teacher profile.
     */
    private function formatTeacher(Teacher $teacher): array
    {
        $birthday = null;

        if ($teacher->bday) {
            $birthday = $teacher->bday->format('Y-m-d');
        }

        return [
            'id' => $teacher->id,

            'custom_id' => $teacher->custom_id,

            'full_name' => $teacher->full_name,

            'initials' => $teacher->initials,

            'email' => $teacher->email,

            'mobile' => $teacher->mobile,

            'nic' => $teacher->nic,

            'birthday' => $birthday,

            'gender' => $teacher->gender,

            'address' => [
                'address1' => $teacher->address1,
                'address2' => $teacher->address2,
                'address3' => $teacher->address3,
            ],

            'graduation_details' => $teacher->graduation_details,

            'experience' => $teacher->experience,

            'bank' => [
                'account_number' => $teacher->account_number,

                'branch' => $teacher->bankBranch
                    ? [
                        'id' => $teacher->bankBranch->id,
                        'name' => $teacher->bankBranch->branch_name,
                        'code' => $teacher->bankBranch->branch_code,
                    ]
                    : null,
            ],

            'is_active' => (bool) $teacher->is_active,
        ];
    }
}
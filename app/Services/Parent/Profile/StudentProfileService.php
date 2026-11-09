<?php

namespace App\Services\Parent\Profile;

use App\Models\Student;
use App\Models\StudentClassEnrollment;
use App\Support\InstituteInfo;
use Carbon\Carbon;

/**
 * Everything the parent app shows about the student: the institute, the
 * details the institute registered (read only), the ID card / QR and the
 * classes. Only the password can be changed from the app.
 */
class StudentProfileService
{
    /** The institute this student belongs to (name is always set). */
    public static function institute(): array
    {
        return InstituteInfo::toArray();
    }

    /** Full URL of a stored photo, or '' when there is none. */
    public static function imageUrl(?string $imgUrl): string
    {
        if (empty($imgUrl)) {
            return '';
        }

        if (filter_var($imgUrl, FILTER_VALIDATE_URL)) {
            return $imgUrl;
        }

        return rtrim((string) config('app.url'), '/') . '/storage/' . ltrim($imgUrl, '/');
    }

    /**
     * The code on the student's card, as the staff app scans it:
     * the Student ID once the permanent card is active, otherwise the
     * temporary code while it has not expired.
     */
    public static function card(Student $student): array
    {
        $expires = $student->temporary_qr_code_expire_date;
        $tempValid = !empty($student->temporary_qr_code)
            && (!$expires || Carbon::now()->lte($expires));

        if ($student->permanent_qr_active && !empty($student->custom_id)) {
            return [
                'qr_code' => $student->custom_id,
                'qr_type' => 'permanent',
                'expires_at' => null,
            ];
        }

        if ($tempValid) {
            return [
                'qr_code' => $student->temporary_qr_code,
                'qr_type' => 'temporary',
                'expires_at' => $expires?->toISOString(),
            ];
        }

        return [
            'qr_code' => null,
            'qr_type' => null,
            'expires_at' => $expires?->toISOString(),
        ];
    }

    public function fetch(int $studentId): array
    {
        $student = Student::query()
            ->with(['grade:id,grade_name', 'branch', 'portalLogin'])
            ->find($studentId);

        if (!$student) {
            return ['status' => false, 'message' => 'Student not found'];
        }

        $branch = $student->branch;
        $login = $student->portalLogin;

        $classes = StudentClassEnrollment::query()
            ->where('student_id', $studentId)
            ->where('is_active', true)
            ->with([
                'branch:id,name',
                'studentClass:id,class_name,medium,class_type,teacher_id,subject_id,grade_id',
                'studentClass.teacher:id,full_name,initials',
                'studentClass.subject:id,subject_name',
                'studentClass.grade:id,grade_name',
                'classCategoryFee:id,class_category_id',
                'classCategoryFee.category:id,category_name',
                'classCategoryFeeOption',
            ])
            ->orderBy('id')
            ->get()
            ->map(fn (StudentClassEnrollment $e) => [
                'enrollment_id' => $e->id,
                'class_id' => $e->student_class_id,
                'class_name' => $e->studentClass?->class_name,
                'subject' => $e->studentClass?->subject?->subject_name,
                'teacher' => $e->studentClass?->teacher?->full_name,
                'grade' => $e->studentClass?->grade?->grade_name,
                'medium' => $e->studentClass?->medium,
                'class_type' => $e->studentClass?->class_type,
                'category' => $e->classCategoryFee?->category?->category_name,
                'branch' => $e->branch?->name,
                'monthly_fee' => $e->is_free_card ? 0.0 : round((float) ($e->classCategoryFeeOption?->fee ?? 0), 2),
                'is_free_card' => (bool) $e->is_free_card,
                'enrolled_at' => $e->enrolled_at?->format('Y-m-d'),
            ])
            ->values();

        $address = collect([$student->address1, $student->address2, $student->address3])
            ->filter(fn ($line) => trim((string) $line) !== '')
            ->implode(', ');

        $branchAddress = $branch
            ? collect([$branch->address1, $branch->address2, $branch->address3])
                ->filter(fn ($line) => trim((string) $line) !== '')
                ->implode(', ')
            : '';

        return [
            'status' => true,
            'message' => 'Profile fetched successfully',
            'data' => [
                'institute' => self::institute(),

                'branch' => $branch ? [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'address' => $branchAddress,
                    'mobile' => $branch->mobile,
                    'email' => $branch->email,
                ] : null,

                'student' => [
                    'student_id' => $student->id,
                    'custom_id' => $student->custom_id,
                    'full_name' => $student->full_name,
                    'initial_name' => $student->initial_name,
                    'gender' => $student->gender,
                    'bday' => $student->bday?->format('Y-m-d'),
                    'nic' => $student->nic,
                    'mobile' => $student->mobile,
                    'whatsapp_mobile' => $student->whatsapp_mobile,
                    'email' => $student->email,
                    'address' => $address,
                    'grade_name' => $student->grade?->grade_name,
                    'class_type' => $student->class_type,
                    'student_school' => $student->student_school,
                    'img_url' => self::imageUrl($student->img_url),
                    'admission' => (bool) $student->admission,
                    'is_active' => (bool) $student->is_active && !$student->student_disable,
                    'registered_at' => $student->created_at?->format('Y-m-d'),
                ],

                'guardian' => [
                    'name' => trim(($student->guardian_fname ?? '') . ' ' . ($student->guardian_lname ?? '')),
                    'nic' => $student->guardian_nic,
                    'mobile' => $student->guardian_mobile,
                ],

                'card' => self::card($student),

                'login' => [
                    'username' => $login?->username,
                    'last_login_at' => $login?->last_login_at?->toISOString(),
                ],

                'classes' => $classes,
            ],
        ];
    }
}

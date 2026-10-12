<?php

namespace App\Services\NewAttendance;

use App\Models\Student;
use App\Models\StudentClassEnrollment;
use Illuminate\Support\Collection;

class AttendanceStudentFinder
{
    /**
     * Find an active student using:
     * - Student custom ID
     * - Temporary QR code
     */
    public function findStudent(string $code): ?Student
    {
        return Student::query()
            ->where('is_active', true)
            ->where(function ($query) use ($code) {
                $query->where('custom_id', $code)
                    ->orWhere('temporary_qr_code', $code);
            })
            ->with([
                'grade',
            ])
            ->first();
    }

    /**
     * Get all active enrollments of the student.
     */
    public function getActiveEnrollments(
        Student $student
    ): Collection {
        return StudentClassEnrollment::query()
            ->where('student_id', $student->id)
            ->where('is_active', true)
            ->with([
                'studentClass',
                'classCategoryFee.category',
                'classCategoryFeeOption' => function ($query) {
                    $query->select([
                        'id',
                        'class_category_fee_id',
                        'label',
                        'fee',
                        'is_default',
                        'is_active',
                    ]);
                },
            ])
            ->get();
    }
}

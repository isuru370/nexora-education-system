<?php

namespace App\Services\Course;

use App\Models\Student;
use Illuminate\Validation\ValidationException;

/** Find a student by student ID or (temporary) QR code, in the user's branches. */
class CourseStudentLookup
{
    /** @param  int[]  $branchIds */
    public function find(string $code, array $branchIds, string $field = 'student_code'): Student
    {
        $code = trim($code);

        $student = $code === '' ? null : Student::query()
            ->where(fn ($q) => $q->where('custom_id', $code)->orWhere('temporary_qr_code', $code))
            ->first();

        if (!$student || !in_array((int) $student->branch_id, $branchIds, true)) {
            throw ValidationException::withMessages([$field => "No student found for \"{$code}\"."]);
        }

        if ($student->student_disable || !$student->is_active) {
            throw ValidationException::withMessages([$field => ($student->initial_name ?: $code) . ' is inactive.']);
        }

        return $student;
    }
}

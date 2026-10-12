<?php

namespace App\Services\Web\Student;

use App\Models\Grade;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class StudentCustomIdService
{
    /**
     * Generate custom ID for student.
     */
    public function generate(int $gradeId): string
    {
        return DB::transaction(function () use ($gradeId) {

            $grade = Grade::query()
                ->lockForUpdate()
                ->findOrFail($gradeId);

            $gradeName = trim($grade->grade_name);

            $gradeCode = $this->extractGradeCode(
                $gradeName,
                $gradeId
            );

            $lastStudent = Student::query()
                ->where('grade_id', $gradeId)
                ->where(
                    'custom_id',
                    'like',
                    'SA' . $gradeCode . '%'
                )
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            $lastNumber = 0;

            if (
                $lastStudent &&
                preg_match(
                    '/^SA' . preg_quote($gradeCode, '/') . '(\d+)$/',
                    $lastStudent->custom_id,
                    $matches
                )
            ) {
                $lastNumber = (int) $matches[1];
            }

            do {
                $lastNumber++;

                $customId = 'SA'
                    . $gradeCode
                    . str_pad(
                        $lastNumber,
                        3,
                        '0',
                        STR_PAD_LEFT
                    );
            } while (
                Student::query()
                ->where('custom_id', $customId)
                ->exists()
            );

            return $customId;
        });
    }

    /**
     * Extract grade code from grade name.
     */
    private function extractGradeCode(
        string $gradeName,
        int $gradeId
    ): string {

        // Grade 1, Grade 2, Grade 10, etc.
        if (
            preg_match(
                '/^Grade\s+(\d+)$/i',
                $gradeName,
                $matches
            )
        ) {
            return str_pad(
                $matches[1],
                2,
                '0',
                STR_PAD_LEFT
            );
        }

        // 2026 A/L or 2026 O/L
        if (
            preg_match(
                '/^(\d{4})\s+(A\/L|O\/L)$/i',
                $gradeName,
                $matches
            )
        ) {
            return substr($matches[1], -2);
        }

        // 2026
        if (
            preg_match(
                '/^\d{4}$/',
                $gradeName
            )
        ) {
            return substr($gradeName, -2);
        }

        // Any number found in grade name
        if (
            preg_match(
                '/(\d+)/',
                $gradeName,
                $matches
            )
        ) {
            $number = $matches[1];

            return strlen($number) === 4
                ? substr($number, -2)
                : str_pad(
                    $number,
                    2,
                    '0',
                    STR_PAD_LEFT
                );
        }

        // Fallback to grade ID
        return str_pad(
            $gradeId,
            2,
            '0',
            STR_PAD_LEFT
        );
    }
}

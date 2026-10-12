<?php

namespace App\Services\Teacher;

use App\Models\StudentClass;
use Illuminate\Support\Collection;

class MyStudentsService
{
    /**
     * Get students belonging to a teacher.
     *
     * @param int $teacherId
     * @param int|null $classId
     * @return array
     */
    public function getStudents(
        int $teacherId,
        ?int $classId = null
    ): array {
        $classes = StudentClass::query()
            ->where('teacher_id', $teacherId)
            ->where('is_active', true)

            // Optional class filter
            ->when(
                $classId !== null,
                function ($query) use ($classId) {
                    $query->where('id', $classId);
                }
            )

            ->with([

                // --------------------------------------------------
                // Subject
                // --------------------------------------------------
                'subject:id,subject_name',

                // --------------------------------------------------
                // Grade
                // --------------------------------------------------
                'grade:id,grade_name',

                // --------------------------------------------------
                // Categories
                // --------------------------------------------------
                'categories:id,category_name,code',

                // --------------------------------------------------
                // Enrolled Students
                // --------------------------------------------------
                'enrollments' => function ($query) {
                    $query
                        ->where('is_active', true)
                        ->select([
                            'id',
                            'student_id',
                            'student_class_id',
                            'class_category_fee_id',
                            'class_category_fee_option_id',
                            'is_active',
                            'is_free_card',
                        ]);
                },

                // --------------------------------------------------
                // Student
                // --------------------------------------------------
                'enrollments.student:id,custom_id,full_name,mobile,img_url,is_active',
            ])

            ->orderBy('id')
            ->get();

        return [
            'summary' => $this->buildSummary($classes),

            'classes' => $classes
                ->map(function (StudentClass $studentClass) {
                    return $this->formatClass(
                        $studentClass
                    );
                })
                ->values()
                ->toArray(),
        ];
    }

    /**
     * Build overall summary.
     */
    private function buildSummary(
        Collection $classes
    ): array {
        $totalStudents = $classes
            ->sum(function (StudentClass $studentClass) {
                return $studentClass
                    ->enrollments
                    ->filter(function ($enrollment) {
                        return $enrollment->student !== null
                            && $enrollment->student->is_active;
                    })
                    ->count();
            });

        return [
            'total_students' => $totalStudents,
            'total_classes' => $classes->count(),
        ];
    }

    /**
     * Format single class.
     */
    private function formatClass(
        StudentClass $studentClass
    ): array {
        $students = $studentClass
            ->enrollments
            ->filter(function ($enrollment) {
                return $enrollment->student !== null
                    && $enrollment->student->is_active;
            })
            ->map(function ($enrollment) {
                return $this->formatStudent(
                    $enrollment->student
                );
            })
            ->values();

        return [
            'id' => $studentClass->id,

            'class_name' => $studentClass->class_name,

            'class_type' => $studentClass->class_type,

            'medium' => $studentClass->medium,

            'subject' => $studentClass->subject
                ? [
                    'id' => $studentClass->subject->id,
                    'name' => $studentClass->subject->subject_name,
                ]
                : null,

            'grade' => $studentClass->grade
                ? [
                    'id' => $studentClass->grade->id,
                    'name' => $studentClass->grade->grade_name,
                ]
                : null,

            /*
             * A class can have multiple categories.
             */
            'categories' => $studentClass
                ->categories
                ->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->category_name,
                        'code' => $category->code,
                    ];
                })
                ->values()
                ->toArray(),

            'students_count' => $students->count(),

            'students' => $students->toArray(),
        ];
    }

    /**
     * Format student.
     */
    private function formatStudent(
        $student
    ): array {
        return [
            'id' => $student->id,

            'custom_id' => $student->custom_id,

            'full_name' => $student->full_name,

            'mobile' => $student->mobile,

            'img_url' => $student->img_url,

            'is_active' => (bool) $student->is_active,
        ];
    }
}
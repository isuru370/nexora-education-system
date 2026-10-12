<?php

namespace App\Services\Teacher;

use App\Models\StudentClass;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class MyClassesService
{
    /**
     * Get all classes assigned to teacher.
     */
    public function getMyClasses(int $teacherId): Collection
    {
        try {
            return StudentClass::query()
                ->where('teacher_id', $teacherId)

                ->with([

                    // =====================================================
                    // Subject
                    // =====================================================
                    'subject:id,subject_name',

                    // =====================================================
                    // Grade
                    // =====================================================
                    'grade:id,grade_name',

                    // =====================================================
                    // Categories
                    // =====================================================
                    // NOTE:
                    // Fee amount is NOT stored in class_category_fees.
                    // Fee amount is stored in class_category_fee_options.
                    'categories' => function ($query) {
                        $query->select([
                            'class_categories.id',
                            'class_categories.category_name',
                            'class_categories.code',
                        ]);
                    },

                    // =====================================================
                    // Payment Configuration
                    // =====================================================
                    'paymentConfig:id,student_class_id,teacher_id,teacher_percentage',

                    // =====================================================
                    // Students / Enrollments
                    // =====================================================
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
                                'enrolled_at',
                                'left_at',
                            ]);
                    },

                    // =====================================================
                    // Class Schedules
                    // =====================================================
                    'schedules' => function ($query) {
                        $query
                            ->select([
                                'id',
                                'student_class_id',
                                'class_category_fee_id',
                                'class_hall_id',
                                'class_date',
                                'start_time',
                                'end_time',
                                'day_of_week',
                                'status',
                                'note',
                            ])
                            ->where('is_active', true)
                            ->where(function ($query) {
                                $query
                                    ->whereNull('status')
                                    ->orWhere('status', '!=', 'cancelled');
                            })
                            ->orderBy('day_of_week')
                            ->orderBy('start_time');
                    },
                ])

                // =====================================================
                // Order Classes
                // =====================================================
                ->orderBy('class_name')

                ->get();

        } catch (Throwable $e) {

            Log::error('Teacher my classes fetch failed.', [
                'teacher_id' => $teacherId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw $e;
        }
    }
}
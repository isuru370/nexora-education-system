<?php

namespace App\Services\Web\ClassSchedule;

use App\Models\StudentClass;
use Carbon\Carbon;

class TodayClassesService
{
    protected ClassScheduleAccessService $accessService;

    public function __construct(
        ClassScheduleAccessService $accessService
    ) {
        $this->accessService = $accessService;
    }

    /*
    |--------------------------------------------------------------------------
    | Today's Classes
    |--------------------------------------------------------------------------
    */

    /**
     * Get today's schedules for accessible branches.
     *
     * Preserves the existing output structure used by the
     * web/mobile-compatible today classes page.
     */
    public function getTodayClasses(
        string $search = ''
    ): array {
        $today = Carbon::today();

        $classes = StudentClass::query()
            ->whereIn(
                'branch_id',
                $this->accessService->accessibleBranchIds()
            )
            ->with([
                'grade',
                'subject',
                'teacher',

                'categoryFees' => function ($query) {
                    $query->where('is_active', true)
                        ->with([
                            'category',

                            'activeFeeOptions' => function ($options) {
                                $options->where('is_active', true)
                                    ->orderByDesc('is_default')
                                    ->orderBy('id');
                            },
                        ]);
                },

                'schedules' => function ($query) use ($today) {
                    $query->whereDate(
                        'class_date',
                        $today->toDateString()
                    )
                    ->whereNotIn(
                        'status',
                        ['cancelled', 'completed']
                    )
                    ->with('hall')
                    ->orderBy('start_time');
                },
            ])
            ->where('is_active', true)
            ->where('is_ongoing', true)

            /*
             * Return only classes that have at least one
             * eligible schedule today.
             */
            ->whereHas('schedules', function ($query) use ($today) {
                $query->whereDate(
                    'class_date',
                    $today->toDateString()
                )
                ->whereNotIn(
                    'status',
                    ['cancelled', 'completed']
                );
            })

            /*
             * Search by class, grade, subject, teacher
             * or category name.
             */
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where(
                        'class_name',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'class_type',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'medium',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhereHas('grade', function ($grade) use ($search) {
                        $grade->where(
                            'grade_name',
                            'like',
                            "%{$search}%"
                        );
                    })
                    ->orWhereHas('subject', function ($subject) use ($search) {
                        $subject->where(
                            'subject_name',
                            'like',
                            "%{$search}%"
                        );
                    })
                    ->orWhereHas('teacher', function ($teacher) use ($search) {
                        $teacher->where(
                            'full_name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'mobile',
                            'like',
                            "%{$search}%"
                        );
                    })
                    ->orWhereHas(
                        'categoryFees.category',
                        function ($category) use ($search) {
                            $category->where(
                                'category_name',
                                'like',
                                "%{$search}%"
                            );
                        }
                    );
                });
            })
            ->get();

        /*
         * Flatten the classes into one row per
         * category fee and schedule.
         */
        $data = $classes->flatMap(function ($class) {
            return $class->categoryFees->flatMap(
                function ($categoryFee) use ($class) {
                    return $class->schedules
                        ->where(
                            'class_category_fee_id',
                            $categoryFee->id
                        )
                        ->map(
                            function ($schedule) use (
                                $class,
                                $categoryFee
                            ) {
                                /*
                                 * Active fee options.
                                 */
                                $feeOptions = $categoryFee
                                    ->activeFeeOptions
                                    ->map(function ($option) {
                                        return [
                                            'id' => $option->id,

                                            'class_category_fee_id'
                                                => $option->class_category_fee_id,

                                            'label' => $option->label,

                                            'fee' => (float) $option->fee,

                                            'is_default'
                                                => (bool) $option->is_default,

                                            'is_active'
                                                => (bool) $option->is_active,
                                        ];
                                    })
                                    ->values()
                                    ->toArray();

                                /*
                                 * Default active fee option.
                                 */
                                $defaultFeeOption = $categoryFee
                                    ->activeFeeOptions
                                    ->first(function ($option) {
                                        return (bool) $option->is_default;
                                    });

                                return [
                                    /*
                                     * Student class details.
                                     */
                                    'student_class' => [
                                        'id' => $class->id,

                                        'class_name' => $class->class_name,

                                        'class_type' => $class->class_type,

                                        'medium' => $class->medium,

                                        'grade' => $class->grade
                                            ? [
                                                'id' => $class->grade->id,

                                                'grade_name'
                                                    => $class->grade->grade_name,
                                            ]
                                            : null,

                                        'subject' => $class->subject
                                            ? [
                                                'id' => $class->subject->id,

                                                'subject_name'
                                                    => $class->subject->subject_name,
                                            ]
                                            : null,

                                        'teacher' => $class->teacher
                                            ? [
                                                'id' => $class->teacher->id,

                                                'full_name'
                                                    => $class->teacher->full_name,

                                                'mobile'
                                                    => $class->teacher->mobile,
                                            ]
                                            : null,
                                    ],

                                    /*
                                     * Category fee and fee options.
                                     */
                                    'category_fee' => [
                                        'id' => $categoryFee->id,

                                        'class_category_id'
                                            => $categoryFee->class_category_id,

                                        'category' => $categoryFee->category
                                            ? [
                                                'id' => $categoryFee->category->id,

                                                'category_name'
                                                    => $categoryFee->category->category_name,
                                            ]
                                            : null,

                                        'fee_options' => $feeOptions,

                                        'default_fee_option'
                                            => $defaultFeeOption
                                                ? [
                                                    'id' => $defaultFeeOption->id,

                                                    'label'
                                                        => $defaultFeeOption->label,

                                                    'fee'
                                                        => (float) $defaultFeeOption->fee,

                                                    'is_default'
                                                        => (bool) $defaultFeeOption->is_default,

                                                    'is_active'
                                                        => (bool) $defaultFeeOption->is_active,
                                                ]
                                                : null,
                                    ],

                                    /*
                                     * Schedule details.
                                     */
                                    'schedule' => [
                                        'id' => $schedule->id,

                                        'class_category_fee_id'
                                            => $schedule->class_category_fee_id,

                                        'class_schedule_pattern_id'
                                            => $schedule->class_schedule_pattern_id,

                                        'class_date' => $schedule->class_date,

                                        'start_time' => $schedule->start_time,

                                        'end_time' => $schedule->end_time,

                                        'status' => $schedule->status,

                                        'hall' => $schedule->hall
                                            ? [
                                                'id' => $schedule->hall->id,

                                                'hall_name'
                                                    => $schedule->hall->hall_name,

                                                'code'
                                                    => $schedule->hall->code,
                                            ]
                                            : null,
                                    ],
                                ];
                            }
                        );
                }
            );
        })
        ->sortBy(function ($row) {
            return $row['schedule']['start_time'] ?? '';
        })
        ->values();

        return [
            'classes' => $data,
            'today' => $today,
        ];
    }
}
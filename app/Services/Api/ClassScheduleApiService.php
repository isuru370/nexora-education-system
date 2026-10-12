<?php

namespace App\Services\Api;

use App\Models\ClassCategoryFee;
use App\Models\ClassSchedule;
use App\Models\StudentClass;
use App\Services\ClassScheduleService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ClassScheduleApiService
{
    protected $scheduleService;

    public function __construct(ClassScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    /**
     * Get today's ongoing classes and their active schedules.
     */
    public function getTodayClasses($search = '')
    {
        $today = Carbon::today();
        $search = trim($search);

        $classes = StudentClass::query()
            ->select([
                'id',
                'class_name',
                'class_type',
                'medium',
                'grade_id',
                'subject_id',
                'teacher_id',
                'is_active',
                'is_ongoing',
            ])
            ->with([
                'grade:id,grade_name',
                'subject:id,subject_name',
                'teacher:id,full_name,mobile',

                'categoryFees' => function ($query) {
                    $query->select([
                        'id',
                        'student_class_id',
                        'class_category_id',
                        'is_active',
                    ])
                    ->where('is_active', true)
                    ->with([
                        'category:id,category_name',
                        'activeFeeOptions:id,class_category_fee_id,label,fee,is_default,is_active',
                    ]);
                },

                'schedules' => function ($query) use ($today) {
                    $query->select([
                        'id',
                        'student_class_id',
                        'class_category_fee_id',
                        'class_schedule_pattern_id',
                        'class_date',
                        'start_time',
                        'end_time',
                        'status',
                        'class_hall_id',
                    ])
                    ->whereDate('class_date', $today)
                    ->whereNotIn('status', ['cancelled', 'completed'])
                    ->with([
                        'hall:id,hall_name,code',
                    ])
                    ->orderBy('start_time');
                },
            ])
            ->where('is_active', true)
            ->where('is_ongoing', true)
            ->whereHas('schedules', function ($query) use ($today) {
                $query->whereDate('class_date', $today)
                    ->whereNotIn('status', ['cancelled', 'completed']);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('class_name', 'like', "%{$search}%")
                        ->orWhere('class_type', 'like', "%{$search}%")
                        ->orWhere('medium', 'like', "%{$search}%")
                        ->orWhereHas('grade', function ($grade) use ($search) {
                            $grade->where('grade_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('subject', function ($subject) use ($search) {
                            $subject->where('subject_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('teacher', function ($teacher) use ($search) {
                            $teacher->where('full_name', 'like', "%{$search}%")
                                ->orWhere('mobile', 'like', "%{$search}%");
                        })
                        ->orWhereHas('categoryFees.category', function ($category) use ($search) {
                            $category->where('category_name', 'like', "%{$search}%");
                        });
                });
            })
            ->get();

        return $classes
            ->flatMap(function ($class) {
                return $class->categoryFees->flatMap(function ($categoryFee) use ($class) {
                    $matchedSchedules = $class->schedules->where(
                        'class_category_fee_id',
                        $categoryFee->id
                    );

                    return $matchedSchedules->map(function ($schedule) use ($class, $categoryFee) {
                        return [
                            'student_class' => $this->studentClassData($class),

                            'category_fee' => [
                                'id' => $categoryFee->id,
                                'class_category_id' => $categoryFee->class_category_id,
                                'category' => $categoryFee->category ? [
                                    'id' => $categoryFee->category->id,
                                    'category_name' => $categoryFee->category->category_name,
                                ] : null,
                                'fee_options' => $categoryFee->activeFeeOptions
                                    ->map(function ($option) {
                                        return [
                                            'id' => $option->id,
                                            'label' => $option->label,
                                            'fee' => (float) $option->fee,
                                            'is_default' => (bool) $option->is_default,
                                            'is_active' => (bool) $option->is_active,
                                        ];
                                    })
                                    ->values(),
                            ],

                            'schedule' => $this->scheduleData($schedule),
                        ];
                    });
                });
            })
            ->sortBy(function ($row) {
                return $row['schedule']['start_time'] ?? '';
            })
            ->values();
    }

    /**
     * Get active ongoing classes.
     */
    public function getOngoingClasses()
    {
        return StudentClass::query()
            ->with([
                'teacher:id,custom_id,initials',
                'subject:id,subject_name',
                'grade:id,grade_name',
            ])
            ->where('is_active', true)
            ->where('is_ongoing', true)
            ->get();
    }

    /**
     * Get active categories and fee options for a class.
     */
    public function getClassCategories($classId)
    {
        $studentClass = StudentClass::query()
            ->where('id', $classId)
            ->where('is_active', true)
            ->firstOrFail();

        return ClassCategoryFee::query()
            ->with([
                'category:id,category_name',
                'activeFeeOptions:id,class_category_fee_id,label,fee,is_default,is_active',
            ])
            ->where('student_class_id', $studentClass->id)
            ->where('is_active', true)
            ->get([
                'id',
                'student_class_id',
                'class_category_id',
                'is_active',
            ])
            ->map(function ($categoryFee) {
                return [
                    'id' => $categoryFee->id,
                    'student_class_id' => $categoryFee->student_class_id,
                    'class_category_id' => $categoryFee->class_category_id,
                    'category' => $categoryFee->category ? [
                        'id' => $categoryFee->category->id,
                        'category_name' => $categoryFee->category->category_name,
                    ] : null,
                    'fee_options' => $categoryFee->activeFeeOptions
                        ->map(function ($option) {
                            return [
                                'id' => $option->id,
                                'label' => $option->label,
                                'fee' => (float) $option->fee,
                                'is_default' => (bool) $option->is_default,
                                'is_active' => (bool) $option->is_active,
                            ];
                        })
                        ->values(),
                ];
            })
            ->values();
    }

    /**
     * Get schedules for previous, current and next month.
     */
    public function getClassSchedules($categoryFeeId)
    {
        $categoryFee = ClassCategoryFee::query()
            ->where('id', $categoryFeeId)
            ->where('is_active', true)
            ->firstOrFail();

        $now = now();

        $previousMonthStart = $now->copy()->subMonth()->startOfMonth();
        $previousMonthEnd = $now->copy()->subMonth()->endOfMonth();

        $currentMonthStart = $now->copy()->startOfMonth();
        $currentMonthEnd = $now->copy()->endOfMonth();

        $nextMonthStart = $now->copy()->addMonth()->startOfMonth();
        $nextMonthEnd = $now->copy()->addMonth()->endOfMonth();

        return ClassSchedule::query()
            ->with([
                'hall:id,hall_name,code',
                'classCategoryFee.category:id,category_name',
                'studentClass:id,class_name',
            ])
            ->where('class_category_fee_id', $categoryFee->id)
            ->where('is_active', true)
            ->where(function ($query) use (
                $previousMonthStart,
                $previousMonthEnd,
                $currentMonthStart,
                $currentMonthEnd,
                $nextMonthStart,
                $nextMonthEnd
            ) {
                $query->whereBetween('class_date', [
                    $previousMonthStart,
                    $previousMonthEnd,
                ])
                ->orWhereBetween('class_date', [
                    $currentMonthStart,
                    $currentMonthEnd,
                ])
                ->orWhereBetween('class_date', [
                    $nextMonthStart,
                    $nextMonthEnd,
                ]);
            })
            ->orderBy('class_date')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Create a single class schedule.
     */
    public function createSchedule(array $data)
    {
        $pattern = $this->scheduleService->getActivePattern(
            $data['student_class_id']
        );

        if (!$pattern) {
            throw new \RuntimeException(
                'No active schedule pattern found.'
            );
        }

        $this->validateCategoryFee(
            $data['student_class_id'],
            $data['class_category_fee_id']
        );

        if (!$this->scheduleService->validatePatternDateRange(
            $pattern,
            $data['class_date']
        )) {
            throw new \RuntimeException(
                "Class date should be between {$pattern->start_date->format('Y-m-d')} and {$pattern->end_date->format('Y-m-d')}"
            );
        }

        if ($this->scheduleService->hasDuplicateSchedule(
            $data['student_class_id'],
            $data['class_date']
        )) {
            throw new \RuntimeException(
                'A schedule already exists for the selected date.'
            );
        }

        return DB::transaction(function () use ($data, $pattern) {
            return ClassSchedule::create([
                'class_schedule_pattern_id' => $pattern->id,
                'student_class_id' => $data['student_class_id'],
                'class_category_fee_id' => $data['class_category_fee_id'],
                'class_hall_id' => $data['class_hall_id'],
                'class_date' => $data['class_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'day_of_week' => $data['day_of_week'],
                'status' => 'scheduled',
                'is_active' => true,
                'note' => isset($data['note']) ? $data['note'] : null,
            ]);
        });
    }

    /**
     * Update a class schedule.
     */
    public function updateSchedule(array $data)
    {
        $schedule = ClassSchedule::findOrFail($data['schedule_id']);

        if ($this->scheduleService->isLockedStatus($schedule->status)) {
            throw new \RuntimeException(
                "This class is {$schedule->status} and cannot be updated."
            );
        }

        if ($this->scheduleService->isPastSchedule($schedule)) {
            throw new \RuntimeException(
                'Past class schedules cannot be updated.'
            );
        }

        $this->validateCategoryFee(
            $schedule->student_class_id,
            $data['class_category_fee_id']
        );

        $pattern = $this->scheduleService->getActivePattern(
            $schedule->student_class_id
        );

        if (!$pattern) {
            throw new \RuntimeException(
                'No active schedule pattern found.'
            );
        }

        if (!$this->scheduleService->validatePatternDateRange(
            $pattern,
            $data['class_date']
        )) {
            throw new \RuntimeException(
                "Class date should be between {$pattern->start_date->format('Y-m-d')} and {$pattern->end_date->format('Y-m-d')}"
            );
        }

        if ($this->scheduleService->hasDuplicateSchedule(
            $schedule->student_class_id,
            $data['class_date'],
            $schedule->id
        )) {
            throw new \RuntimeException(
                'Another schedule already exists for this date.'
            );
        }

        return DB::transaction(function () use ($schedule, $data) {
            $schedule->update([
                'class_category_fee_id' => $data['class_category_fee_id'],
                'class_hall_id' => $data['class_hall_id'],
                'class_date' => $data['class_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'day_of_week' => $data['day_of_week'],
                'note' => isset($data['note']) ? $data['note'] : null,
            ]);

            return $schedule->fresh();
        });
    }

    /**
     * Cancel a class schedule.
     */
    public function cancelSchedule($scheduleId, $reason = null)
    {
        $schedule = ClassSchedule::findOrFail($scheduleId);

        if ($this->scheduleService->isLockedStatus($schedule->status)) {
            throw new \RuntimeException(
                "This class is {$schedule->status} and cannot be cancelled."
            );
        }

        if ($this->scheduleService->isPastSchedule($schedule)) {
            throw new \RuntimeException(
                'Past class schedules cannot be cancelled.'
            );
        }

        return DB::transaction(function () use ($schedule, $reason) {
            $schedule->cancel(
                auth()->id(),
                $reason
            );

            return $schedule->fresh();
        });
    }

    /**
     * Ensure category fee belongs to the selected class and is active.
     */
    protected function validateCategoryFee($classId, $categoryFeeId)
    {
        $exists = ClassCategoryFee::query()
            ->where('id', $categoryFeeId)
            ->where('student_class_id', $classId)
            ->where('is_active', true)
            ->exists();

        if (!$exists) {
            throw new \RuntimeException(
                'The selected category is not active for this class.'
            );
        }
    }

    protected function studentClassData($class)
    {
        return [
            'id' => $class->id,
            'class_name' => $class->class_name,
            'class_type' => $class->class_type,
            'medium' => $class->medium,

            'grade' => $class->grade ? [
                'id' => $class->grade->id,
                'grade_name' => $class->grade->grade_name,
            ] : null,

            'subject' => $class->subject ? [
                'id' => $class->subject->id,
                'subject_name' => $class->subject->subject_name,
            ] : null,

            'teacher' => $class->teacher ? [
                'id' => $class->teacher->id,
                'full_name' => $class->teacher->full_name,
                'mobile' => $class->teacher->mobile,
            ] : null,
        ];
    }

    protected function scheduleData($schedule)
    {
        return [
            'id' => $schedule->id,
            'class_category_fee_id' => $schedule->class_category_fee_id,
            'class_schedule_pattern_id' => $schedule->class_schedule_pattern_id,
            'class_date' => $schedule->class_date,
            'start_time' => $schedule->start_time,
            'end_time' => $schedule->end_time,
            'status' => $schedule->status,

            'hall' => $schedule->hall ? [
                'id' => $schedule->hall->id,
                'hall_name' => $schedule->hall->hall_name,
                'code' => $schedule->hall->code,
            ] : null,
        ];
    }
}

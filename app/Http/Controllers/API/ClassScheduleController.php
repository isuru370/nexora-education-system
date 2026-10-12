<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Api\ClassScheduleApiService;
use Illuminate\Http\Request;
use Throwable;

class ClassScheduleController extends Controller
{
    protected $scheduleApiService;

    public function __construct(
        ClassScheduleApiService $scheduleApiService
    ) {
        $this->scheduleApiService = $scheduleApiService;
    }

    /**
     * Get today's ongoing classes.
     */
    public function todayClasses(Request $request)
    {
        try {
            $data = $this->scheduleApiService->getTodayClasses(
                $request->input('search', '')
            );

            return $this->successResponse(
                'Today classes fetched successfully.',
                [
                    'date' => now()->toDateString(),
                    'count' => $data->count(),
                    'data' => $data,
                ]
            );
        } catch (Throwable $e) {
            report($e);

            return $this->errorResponse(
                'Failed to fetch today classes.',
                500
            );
        }
    }

    /**
     * Get ongoing classes.
     */
    public function fetchOngoingClass()
    {
        try {
            $classes = $this->scheduleApiService
                ->getOngoingClasses();

            return $this->successResponse(
                'Classes fetched successfully.',
                $classes
            );
        } catch (Throwable $e) {
            report($e);

            return $this->errorResponse(
                'Failed to fetch classes.',
                500
            );
        }
    }

    /**
     * Get class categories with fee options.
     */
    public function fetchClassCategory(Request $request)
    {
        $validated = $request->validate([
            'class_id' => [
                'required',
                'exists:student_classes,id',
            ],
        ]);

        try {
            $categories = $this->scheduleApiService
                ->getClassCategories($validated['class_id']);

            return $this->successResponse(
                'Categories fetched successfully.',
                $categories
            );
        } catch (Throwable $e) {
            report($e);

            return $this->errorResponse(
                'Failed to fetch categories.',
                500
            );
        }
    }

    /**
     * Get schedules for a category.
     */
    public function fetchClassSchedule(Request $request)
    {
        $validated = $request->validate([
            'class_category_fee_id' => [
                'required',
                'exists:class_category_fees,id',
            ],
        ]);

        try {
            $schedules = $this->scheduleApiService
                ->getClassSchedules(
                    $validated['class_category_fee_id']
                );

            return $this->successResponse(
                'Schedules fetched successfully.',
                $schedules
            );
        } catch (Throwable $e) {
            report($e);

            return $this->errorResponse(
                'Failed to fetch schedules.',
                500
            );
        }
    }

    /**
     * Add a new class day.
     */
    public function storeAddNewDay(Request $request)
    {
        $validated = $request->validate([
            'student_class_id' => [
                'required',
                'exists:student_classes,id',
            ],
            'class_category_fee_id' => [
                'required',
                'exists:class_category_fees,id',
            ],
            'class_hall_id' => [
                'required',
                'exists:class_halls,id',
            ],
            'class_date' => [
                'required',
                'date',
            ],
            'start_time' => [
                'required',
                'date_format:H:i',
            ],
            'end_time' => [
                'required',
                'date_format:H:i',
                'after:start_time',
            ],
            'day_of_week' => [
                'required',
                'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            ],
            'note' => [
                'nullable',
                'string',
            ],
        ]);

        try {
            $schedule = $this->scheduleApiService
                ->createSchedule($validated);

            return $this->successResponse(
                'New class day added successfully.',
                $schedule,
                201
            );
        } catch (Throwable $e) {
            report($e);

            return $this->errorResponse(
                $e->getMessage(),
                422
            );
        }
    }

    /**
     * Update a class schedule.
     */
    public function updateClassSchedule(Request $request)
    {
        $validated = $request->validate([
            'schedule_id' => [
                'required',
                'exists:class_schedules,id',
            ],
            'class_category_fee_id' => [
                'required',
                'exists:class_category_fees,id',
            ],
            'class_hall_id' => [
                'required',
                'exists:class_halls,id',
            ],
            'class_date' => [
                'required',
                'date',
            ],
            'start_time' => [
                'required',
                'date_format:H:i',
            ],
            'end_time' => [
                'required',
                'date_format:H:i',
                'after:start_time',
            ],
            'day_of_week' => [
                'required',
                'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            ],
            'note' => [
                'nullable',
                'string',
            ],
        ]);

        try {
            $schedule = $this->scheduleApiService
                ->updateSchedule($validated);

            return $this->successResponse(
                'Class schedule updated successfully.',
                $schedule
            );
        } catch (Throwable $e) {
            report($e);

            return $this->errorResponse(
                $e->getMessage(),
                422
            );
        }
    }

    /**
     * Cancel a class schedule.
     */
    public function classCancel(Request $request)
    {
        $validated = $request->validate([
            'schedule_id' => [
                'required',
                'exists:class_schedules,id',
            ],
            'cancel_reason' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        try {
            $schedule = $this->scheduleApiService
                ->cancelSchedule(
                    $validated['schedule_id'],
                    isset($validated['cancel_reason'])
                        ? $validated['cancel_reason']
                        : null
                );

            return $this->successResponse(
                'Class cancelled successfully.',
                $schedule
            );
        } catch (Throwable $e) {
            report($e);

            return $this->errorResponse(
                $e->getMessage(),
                422
            );
        }
    }

    protected function successResponse(
        $message,
        $data = null,
        $code = 200
    ) {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function errorResponse(
        $message,
        $code = 422
    ) {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
        ], $code);
    }
}

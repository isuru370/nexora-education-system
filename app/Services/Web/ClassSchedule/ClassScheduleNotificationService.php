<?php

namespace App\Services\Web\ClassSchedule;

use App\Jobs\SendClassScheduleSms;
use App\Models\ClassHall;
use App\Models\StudentClassEnrollment;

class ClassScheduleNotificationService
{
    /*
    |--------------------------------------------------------------------------
    | Send Class Schedule SMS
    |--------------------------------------------------------------------------
    */

    /**
     * Send schedule notifications to active enrolled students'
     * guardians.
     *
     * Supported actions:
     * created, updated, cancelled
     */
    public function sendClassScheduleSms(
        array $validated,
        string $action
    ): void {
        /*
         * Get the selected hall, if provided.
         */
        $hall = !empty($validated['class_hall_id'])
            ? ClassHall::find($validated['class_hall_id'])
            : null;

        /*
         * Get active enrollments and active students.
         */
        $students = StudentClassEnrollment::with([
            'student:id,guardian_mobile,is_active',
            'studentClass:id,class_name',
            'classCategoryFee.category:id,category_name',
        ])
            ->where(
                'class_category_fee_id',
                $validated['class_category_fee_id']
            )
            ->where('is_active', true)
            ->whereHas('student', function ($query) {
                $query->where('is_active', true);
            })
            ->get();

        /*
         * Notification title.
         */
        $title = match ($action) {
            'created' => 'New class scheduled.',
            'updated' => 'Class schedule has been updated.',
            'cancelled' => 'Class has been cancelled.',
            default => 'Class schedule notification.',
        };

        /*
         * Send one SMS job per guardian mobile number.
         */
        foreach ($students as $enrollment) {
            $student = $enrollment->student;

            if (
                !$student ||
                empty($student->guardian_mobile)
            ) {
                continue;
            }

            $studentClass = $enrollment->studentClass;

            $category = optional(
                optional(
                    $enrollment->classCategoryFee
                )->category
            );

            $message = "Dear Parent,\n\n"
                . "{$title}\n\n"
                . "Class : "
                . ($studentClass->class_name ?? 'N/A')
                . "\n"
                . "Category : "
                . ($category->category_name ?? 'N/A')
                . "\n";

            /*
             * Hall information.
             */
            if ($hall) {
                $message .= "Hall : {$hall->hall_name}\n";
            }

            /*
             * Schedule date.
             */
            if (!empty($validated['class_date'])) {
                $message .= "Date : "
                    . $validated['class_date']
                    . "\n";
            }

            /*
             * Schedule time.
             */
            if (
                !empty($validated['start_time']) &&
                !empty($validated['end_time'])
            ) {
                $message .= "Time : "
                    . $validated['start_time']
                    . " - "
                    . $validated['end_time']
                    . "\n";
            }

            /*
             * Cancellation reason.
             */
            if (
                $action === 'cancelled' &&
                !empty($validated['cancel_reason'])
            ) {
                $message .= "Reason : "
                    . $validated['cancel_reason']
                    . "\n";
            }

            $message .= "\nThank You.";

            /*
             * Dispatch the existing SMS job.
             */
            SendClassScheduleSms::dispatch(
                $student->guardian_mobile,
                $message
            );
        }
    }
}
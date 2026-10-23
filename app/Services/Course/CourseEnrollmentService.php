<?php

namespace App\Services\Course;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourseEnrollmentService
{
    /**
     * Enroll a student. "full" = one payment of the one-time fee,
     * "installment" = the full fee split into one installment per course month.
     */
    public function enroll(Course $course, Student $student, string $plan, ?int $userId = null, ?string $note = null): CourseEnrollment
    {
        if (!$course->is_active) {
            throw ValidationException::withMessages(['course' => 'This course is not active.']);
        }

        if ($student->student_disable || !$student->is_active) {
            throw ValidationException::withMessages(['student' => 'This student is inactive.']);
        }

        if (!in_array($plan, ['full', 'installment'], true)) {
            throw ValidationException::withMessages(['payment_plan' => 'Choose full payment or installments.']);
        }

        $existing = CourseEnrollment::query()
            ->where('course_id', $course->id)
            ->where('student_id', $student->id)
            ->first();

        if ($existing && $existing->isActive()) {
            throw ValidationException::withMessages(['student' => 'This student is already in this course.']);
        }

        return DB::transaction(function () use ($course, $student, $plan, $userId, $note, $existing) {
            $today = now()->toDateString();
            $total = $plan === 'full' ? (float) $course->one_time_fee : (float) $course->full_fee;

            $fields = [
                'branch_id' => $course->branch_id,
                'payment_plan' => $plan,
                'total_fee' => $total,
                'enrolled_at' => $today,
                'status' => 'active',
                'enrolled_by' => $userId,
                'note' => $note,
            ];

            // A cancelled enrollment without payments is opened again.
            if ($existing) {
                $existing->installments()->delete();
                $existing->update($fields);
                $enrollment = $existing;
            } else {
                $enrollment = CourseEnrollment::create($fields + [
                    'course_id' => $course->id,
                    'student_id' => $student->id,
                ]);
            }

            $this->createInstallments($enrollment, $course, $plan, $total);

            return $enrollment->fresh('installments');
        });
    }

    /** Only an enrollment without completed payments can be cancelled. */
    public function cancel(CourseEnrollment $enrollment): void
    {
        if ($enrollment->payments()->where('status', 'completed')->exists()) {
            throw ValidationException::withMessages([
                'enrollment' => 'This student has payments. Cancel the payments first.',
            ]);
        }

        $enrollment->update(['status' => 'cancelled']);
    }

    protected function createInstallments(CourseEnrollment $enrollment, Course $course, string $plan, float $total): void
    {
        $start = Carbon::parse($course->start_date);
        $firstDue = $start->lt(now()->startOfDay()) ? now()->startOfDay() : $start;

        if ($plan === 'full') {
            $enrollment->installments()->create([
                'installment_no' => 1,
                'amount' => $total,
                'due_date' => $firstDue->toDateString(),
            ]);

            return;
        }

        $count = max(1, (int) $course->installment_count);
        $each = floor($total / $count * 100) / 100;

        for ($i = 1; $i <= $count; $i++) {
            // The last one takes the cents left over (12000 / 7 ...)
            $amount = $i === $count ? round($total - $each * ($count - 1), 2) : $each;

            $enrollment->installments()->create([
                'installment_no' => $i,
                'amount' => $amount,
                'due_date' => $start->copy()->addMonthsNoOverflow($i - 1)->toDateString(),
            ]);
        }
    }
}

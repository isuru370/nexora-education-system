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
    public function __construct(private CourseFeeService $fees)
    {
    }

    /**
     * Enroll a student. "full" = one payment of the one-time fee,
     * "installment" = the full fee split into one installment per course month.
     *
     * A special fee (with a reason) replaces the course fee for this student
     * and is split the same way. 0 = free.
     */
    public function enroll(
        Course $course,
        Student $student,
        string $plan,
        ?int $userId = null,
        ?string $note = null,
        ?float $specialFee = null,
        ?string $feeReason = null
    ): CourseEnrollment {
        if (!$course->is_active) {
            throw ValidationException::withMessages(['course' => 'This course is not active.']);
        }

        if ($student->student_disable || !$student->is_active) {
            throw ValidationException::withMessages(['student' => 'This student is inactive.']);
        }

        if (!in_array($plan, ['full', 'installment'], true)) {
            throw ValidationException::withMessages(['payment_plan' => 'Choose full payment or installments.']);
        }

        $this->checkSpecialFee($specialFee, $feeReason);

        $existing = CourseEnrollment::query()
            ->where('course_id', $course->id)
            ->where('student_id', $student->id)
            ->first();

        if ($existing && $existing->isActive()) {
            throw ValidationException::withMessages(['student' => 'This student is already in this course.']);
        }

        return DB::transaction(function () use ($course, $student, $plan, $userId, $note, $existing, $specialFee, $feeReason) {
            $standard = $plan === 'full' ? (float) $course->one_time_fee : (float) $course->full_fee;
            $total = $specialFee !== null ? round($specialFee, 2) : $standard;

            $fields = [
                'branch_id' => $course->branch_id,
                'payment_plan' => $plan,
                'total_fee' => $total,
                'standard_fee' => $standard,
                'fee_reason' => $specialFee !== null ? trim((string) $feeReason) : null,
                'enrolled_at' => now()->toDateString(),
                'status' => 'active',
                'enrolled_by' => $userId,
                'note' => $note,
            ];

            // A cancelled enrollment is opened again.
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

    /**
     * New fee for an enrolled student (e.g. a scholarship from month 2).
     * What is already paid stays; the rest is spread over the installments
     * not yet paid. The new fee cannot be less than what is paid.
     */
    public function changeFee(CourseEnrollment $enrollment, float $newFee, string $reason): CourseEnrollment
    {
        $newFee = round($newFee, 2);

        if (!$enrollment->isActive()) {
            throw ValidationException::withMessages(['fee' => 'This enrollment is cancelled.']);
        }

        $this->checkSpecialFee($newFee, $reason, 'fee');

        return DB::transaction(function () use ($enrollment, $newFee, $reason) {
            CourseEnrollment::query()->whereKey($enrollment->id)->lockForUpdate()->first();

            $summary = $this->fees->summary($enrollment->fresh('installments'));

            if ($newFee < $summary['paid']) {
                throw ValidationException::withMessages([
                    'fee' => 'The new fee cannot be less than what is already paid (Rs. ' . number_format($summary['paid'], 2) . ').',
                ]);
            }

            // Installments fully paid stay as they are
            $kept = collect($summary['installments'])->filter(fn ($i) => $i['remaining'] <= 0);
            $open = collect($summary['installments'])->filter(fn ($i) => $i['remaining'] > 0)->values();
            $left = round($newFee - $kept->sum('amount'), 2);

            $enrollment->installments()->whereIn('id', $open->pluck('id'))->delete();

            if ($left > 0) {
                $dues = $open->pluck('due_date')->all() ?: [now()->toDateString()];
                $this->spread($enrollment, $left, $dues, ($kept->max('installment_no') ?? 0) + 1);
            }

            $enrollment->update([
                'total_fee' => $newFee,
                'fee_reason' => trim($reason),
            ]);

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

    protected function checkSpecialFee(?float $fee, ?string $reason, string $field = 'special_fee'): void
    {
        if ($fee === null) {
            return;
        }

        if ($fee < 0) {
            throw ValidationException::withMessages([$field => 'The fee cannot be less than 0.']);
        }

        if (trim((string) $reason) === '') {
            throw ValidationException::withMessages([$field . '_reason' => 'Give a reason for the special fee.']);
        }
    }

    protected function createInstallments(CourseEnrollment $enrollment, Course $course, string $plan, float $total): void
    {
        if ($total <= 0) {
            return; // free
        }

        $start = Carbon::parse($course->start_date);

        if ($plan === 'full') {
            $firstDue = $start->lt(now()->startOfDay()) ? now()->startOfDay() : $start;
            $this->spread($enrollment, $total, [$firstDue->toDateString()]);

            return;
        }

        $count = max(1, (int) $course->installment_count);
        $dues = [];
        for ($i = 0; $i < $count; $i++) {
            $dues[] = $start->copy()->addMonthsNoOverflow($i)->toDateString();
        }

        $this->spread($enrollment, $total, $dues);
    }

    /** $total split over the due dates; the last one takes the cents left over. */
    protected function spread(CourseEnrollment $enrollment, float $total, array $dues, int $firstNo = 1): void
    {
        $count = count($dues);
        $each = floor($total / $count * 100) / 100;

        foreach (array_values($dues) as $i => $due) {
            $enrollment->installments()->create([
                'installment_no' => $firstNo + $i,
                'amount' => $i === $count - 1 ? round($total - $each * ($count - 1), 2) : $each,
                'due_date' => $due,
            ]);
        }
    }
}

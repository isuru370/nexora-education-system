<?php

namespace App\Services\Course;

use App\Models\Course;
use App\Models\CoursePaymentSplit;
use App\Models\CourseTeacherPayout;
use App\Models\Teacher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Course teachers' money - separate from monthly teacher salaries.
 * Earned = their share of completed course payments; balance = earned - paid out.
 */
class CourseTeacherEarningService
{
    /**
     * One row per teacher of the course (and anyone who earned from it before).
     *
     * @return Collection<int, array>
     */
    public function forCourse(Course $course): Collection
    {
        $earned = CoursePaymentSplit::query()
            ->join('course_payments', 'course_payments.id', '=', 'course_payment_splits.course_payment_id')
            ->where('course_payment_splits.course_id', $course->id)
            ->where('course_payments.status', 'completed')
            ->whereNotNull('course_payment_splits.teacher_id')
            ->groupBy('course_payment_splits.teacher_id')
            ->selectRaw('course_payment_splits.teacher_id, SUM(course_payment_splits.amount) as total')
            ->pluck('total', 'teacher_id');

        $paid = CourseTeacherPayout::query()
            ->where('course_id', $course->id)
            ->where('status', 'paid')
            ->groupBy('teacher_id')
            ->selectRaw('teacher_id, SUM(amount) as total')
            ->pluck('total', 'teacher_id');

        $percentages = $course->courseTeachers()->pluck('percentage', 'teacher_id');

        $teacherIds = $percentages->keys()->merge($earned->keys())->merge($paid->keys())->unique();

        return Teacher::query()
            ->whereIn('id', $teacherIds)
            ->orderBy('full_name')
            ->get(['id', 'custom_id', 'full_name'])
            ->map(function (Teacher $teacher) use ($earned, $paid, $percentages) {
                $e = round((float) ($earned[$teacher->id] ?? 0), 2);
                $p = round((float) ($paid[$teacher->id] ?? 0), 2);

                return [
                    'teacher' => $teacher,
                    'percentage' => isset($percentages[$teacher->id]) ? (float) $percentages[$teacher->id] : null,
                    'earned' => $e,
                    'paid' => $p,
                    'balance' => round($e - $p, 2),
                ];
            })
            ->values();
    }

    /** Institute share of the course's completed payments. */
    public function instituteEarned(Course $course): float
    {
        return round((float) CoursePaymentSplit::query()
            ->join('course_payments', 'course_payments.id', '=', 'course_payment_splits.course_payment_id')
            ->where('course_payment_splits.course_id', $course->id)
            ->where('course_payments.status', 'completed')
            ->whereNull('course_payment_splits.teacher_id')
            ->sum('course_payment_splits.amount'), 2);
    }

    public function payout(Course $course, int $teacherId, float $amount, string $method, ?string $note, ?int $userId): CourseTeacherPayout
    {
        $amount = round($amount, 2);

        return DB::transaction(function () use ($course, $teacherId, $amount, $method, $note, $userId) {
            Course::query()->whereKey($course->id)->lockForUpdate()->first();

            $row = $this->forCourse($course)->first(fn ($r) => $r['teacher']->id === $teacherId);

            if (!$row) {
                throw ValidationException::withMessages(['teacher_id' => 'This teacher is not in this course.']);
            }

            if ($amount <= 0 || $amount > $row['balance']) {
                throw ValidationException::withMessages([
                    'amount' => 'Amount must be between 1 and the balance (Rs. ' . number_format(max(0, $row['balance']), 2) . ').',
                ]);
            }

            return CourseTeacherPayout::create([
                'branch_id' => $course->branch_id,
                'course_id' => $course->id,
                'teacher_id' => $teacherId,
                'amount' => $amount,
                'paid_on' => now()->toDateString(),
                'payment_method' => $method,
                'status' => 'paid',
                'user_id' => $userId,
                'note' => $note,
            ]);
        });
    }

    public function cancelPayout(CourseTeacherPayout $payout): void
    {
        $payout->update(['status' => 'cancelled']);
    }

    /**
     * All courses' teachers for the summary page.
     *
     * @param  int[]  $branchIds
     */
    public function summary(array $branchIds): Collection
    {
        return Course::query()
            ->whereIn('branch_id', $branchIds)
            ->orderByDesc('start_date')
            ->get()
            ->flatMap(fn (Course $course) => $this->forCourse($course)->map(fn ($row) => $row + ['course' => $course]))
            ->values();
    }
}

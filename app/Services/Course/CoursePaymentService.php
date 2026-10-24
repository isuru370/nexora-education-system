<?php

namespace App\Services\Course;

use App\Models\CourseEnrollment;
use App\Models\CoursePayment;
use App\Services\Web\Receipt\ReceiptNumberService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CoursePaymentService
{
    public function __construct(
        private CourseFeeService $fees,
        private ReceiptNumberService $receipts,
        private CourseNotificationService $notifications
    ) {
    }

    /**
     * Take a payment (any amount up to the balance). The teachers' and the
     * institute's shares are saved with the payment, using today's percentages.
     */
    public function pay(
        CourseEnrollment $enrollment,
        float $amount,
        string $paymentMethod = 'cash',
        string $markMethod = 'manual_web',
        ?int $userId = null,
        ?string $note = null
    ): CoursePayment {
        $amount = round($amount, 2);

        if (!$enrollment->isActive()) {
            throw ValidationException::withMessages(['enrollment' => 'This enrollment is cancelled.']);
        }

        if (!in_array($paymentMethod, CoursePayment::METHODS, true)) {
            throw ValidationException::withMessages(['payment_method' => 'Invalid payment method.']);
        }

        $payment = DB::transaction(function () use ($enrollment, $amount, $paymentMethod, $markMethod, $userId, $note) {
            // One payment at a time per enrollment
            CourseEnrollment::query()->whereKey($enrollment->id)->lockForUpdate()->first();

            $balance = $this->fees->summary($enrollment)['balance'];

            if ($balance <= 0) {
                throw ValidationException::withMessages(['amount' => 'This course is already fully paid.']);
            }

            if ($amount <= 0 || $amount > $balance) {
                throw ValidationException::withMessages([
                    'amount' => 'Amount must be between 1 and the balance (Rs. ' . number_format($balance, 2) . ').',
                ]);
            }

            $course = $enrollment->course()->with('courseTeachers')->firstOrFail();

            $payment = CoursePayment::create([
                'branch_id' => $enrollment->branch_id,
                'course_id' => $course->id,
                'course_enrollment_id' => $enrollment->id,
                'student_id' => $enrollment->student_id,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'mark_method' => $markMethod,
                'receipt_number' => $this->receipts->generate(),
                'paid_at' => now(),
                'status' => 'completed',
                'user_id' => $userId,
                'note' => $note,
            ]);

            $given = 0;
            foreach ($course->courseTeachers as $teacher) {
                $share = round($amount * (float) $teacher->percentage / 100, 2);
                $given += $share;

                $payment->splits()->create([
                    'course_id' => $course->id,
                    'teacher_id' => $teacher->teacher_id,
                    'percentage' => $teacher->percentage,
                    'amount' => $share,
                ]);
            }

            // Institute gets the rest (and any rounding cents)
            $payment->splits()->create([
                'course_id' => $course->id,
                'teacher_id' => null,
                'percentage' => $course->institutePercentage(),
                'amount' => round($amount - $given, 2),
            ]);

            return $payment->load('splits');
        });

        // Tell the parent (Settings > Notifications > Course fee payment)
        $this->notifications->paymentReceived($payment);

        return $payment;
    }

    public function cancel(CoursePayment $payment, string $reason, ?int $userId = null): void
    {
        if (!$payment->isCompleted()) {
            throw ValidationException::withMessages(['payment' => 'This payment is already cancelled.']);
        }

        $payment->update([
            'status' => 'cancelled',
            'cancelled_by' => $userId,
            'cancelled_at' => now(),
            'cancel_reason' => $reason,
        ]);
    }
}

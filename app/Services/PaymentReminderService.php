<?php

namespace App\Services;

use App\Jobs\SendPaymentReminderBulkSmsJob;
use App\Jobs\SendPaymentReminderSmsJob;
use App\Models\ClassCategoryFee;
use App\Models\ClassSchedule;
use App\Models\StudentAttendance;
use App\Models\StudentClass;
use App\Models\StudentClassEnrollment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PaymentReminderService
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Get all active enrollments with payment and attendance
     * status for selected class, category and month.
     */
    public function getEnrollmentsWithStatus(
        int $classId,
        int $categoryFeeId,
        string $paymentMonth
    ): Collection {

        /*
        |--------------------------------------------------------------------------
        | Normalize selected month
        |--------------------------------------------------------------------------
        */

        $monthStart = Carbon::parse($paymentMonth)
            ->startOfMonth();

        $monthEnd = $monthStart
            ->copy()
            ->endOfMonth();

        $monthStartDate = $monthStart->toDateString();

        /*
        |--------------------------------------------------------------------------
        | Get enrollments
        |--------------------------------------------------------------------------
        */

        $enrollments = StudentClassEnrollment::query()
            ->where(
                'student_class_id',
                $classId
            )
            ->where(
                'class_category_fee_id',
                $categoryFeeId
            )
            ->where(
                'is_active',
                true
            )

            /*
            |--------------------------------------------------------------------------
            | Student must be enrolled during selected month
            |--------------------------------------------------------------------------
            */

            ->where(function ($query) use ($monthStart) {

                $query
                    ->whereNull('left_at')
                    ->orWhereDate(
                        'left_at',
                        '>=',
                        $monthStart->toDateString()
                    );
            })

            /*
            |--------------------------------------------------------------------------
            | Selected class must be active
            |--------------------------------------------------------------------------
            */

            ->whereHas('studentClass', function ($query) {

                $query->where(
                    'is_active',
                    true
                );
            })

            /*
            |--------------------------------------------------------------------------
            | Relationships
            |--------------------------------------------------------------------------
            */

            ->with([

                'student',

                'studentClass',

                'classCategoryFee',

                'classCategoryFeeOption',

                'category',

                /*
                |--------------------------------------------------------------------------
                | Payment for selected month
                |--------------------------------------------------------------------------
                */

                'payments' => function ($query) use ($monthStartDate) {

                    $query
                        ->where(
                            'payment_month',
                            $monthStartDate
                        )
                        ->where(
                            'status',
                            'completed'
                        )
                        ->orderBy('id');
                },
            ])

            ->get();

        /*
        |--------------------------------------------------------------------------
        | No enrollments
        |--------------------------------------------------------------------------
        */

        if ($enrollments->isEmpty()) {
            return collect();
        }

        /*
        |--------------------------------------------------------------------------
        | Enrollment IDs
        |--------------------------------------------------------------------------
        */

        $enrollmentIds = $enrollments
            ->pluck('id')
            ->values()
            ->all();

        /*
        |--------------------------------------------------------------------------
        | Get valid schedules
        |--------------------------------------------------------------------------
        */

        $schedules = ClassSchedule::query()
            ->where(
                'student_class_id',
                $classId
            )
            ->where(
                'class_category_fee_id',
                $categoryFeeId
            )
            ->whereBetween(
                'class_date',
                [
                    $monthStartDate,
                    $monthEnd->toDateString(),
                ]
            )
            ->where(
                'is_active',
                true
            )
            ->where(
                'status',
                '!=',
                'cancelled'
            )
            ->get([
                'id',
                'student_class_id',
                'class_category_fee_id',
                'class_date',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Valid schedule IDs
        |--------------------------------------------------------------------------
        */

        $validScheduleIds = $schedules
            ->pluck('id')
            ->values()
            ->all();

        /*
        |--------------------------------------------------------------------------
        | Attendance map
        |--------------------------------------------------------------------------
        */

        $attendanceMap = collect();

        if (!empty($validScheduleIds)) {

            $attendances = StudentAttendance::query()
                ->whereIn(
                    'student_class_enrollment_id',
                    $enrollmentIds
                )
                ->whereIn(
                    'class_schedule_id',
                    $validScheduleIds
                )
                ->whereBetween(
                    'attended_at',
                    [
                        $monthStart->copy()->startOfDay(),
                        $monthEnd->copy()->endOfDay(),
                    ]
                )
                ->get([
                    'id',
                    'student_class_enrollment_id',
                    'class_schedule_id',
                ]);

            $attendanceMap = $attendances
                ->groupBy('student_class_enrollment_id')
                ->map(function ($records) {

                    return $records
                        ->pluck('class_schedule_id')
                        ->unique()
                        ->values();
                });
        }

        /*
        |--------------------------------------------------------------------------
        | Build final result
        |--------------------------------------------------------------------------
        */

        return $enrollments
            ->map(function ($enrollment) use (
                $attendanceMap,
                $validScheduleIds,
                $monthStartDate
            ) {

                /*
                |--------------------------------------------------------------------------
                | Payment
                |--------------------------------------------------------------------------
                */

                $payment = $enrollment
                    ->payments
                    ->sortByDesc('id')
                    ->first();

                $paidAmount = (float) $enrollment
                    ->payments
                    ->sum('amount');

                $isFreeCard = (bool) $enrollment->is_free_card;

                /*
                |--------------------------------------------------------------------------
                | Attendance
                |--------------------------------------------------------------------------
                */

                $attendedScheduleIds = $attendanceMap
                    ->get(
                        $enrollment->id,
                        collect()
                    );

                $attendanceCount = $attendedScheduleIds
                    ->intersect($validScheduleIds)
                    ->unique()
                    ->count();

                /*
                |--------------------------------------------------------------------------
                | Expected fee
                |--------------------------------------------------------------------------
                */

                $expectedFee = (float) (
                    $enrollment->final_fee ?? 0
                );

                $balance = max(
                    $expectedFee - $paidAmount,
                    0
                );

                /*
                |--------------------------------------------------------------------------
                | Payment status
                |--------------------------------------------------------------------------
                */

                if ($isFreeCard) {
                    $status = 'FREE CARD';
                    $isPaid = true;
                } elseif ($expectedFee <= 0) {
                    $status = 'PAID';
                    $isPaid = true;
                } elseif ($paidAmount >= $expectedFee) {
                    $status = 'PAID';
                    $isPaid = true;
                } else {
                    $status = 'NOT PAID';
                    $isPaid = false;
                }

                /*
                |--------------------------------------------------------------------------
                | Fee option
                |--------------------------------------------------------------------------
                */

                $feeOption = $enrollment->classCategoryFeeOption;

                /*
                |--------------------------------------------------------------------------
                | Final item
                |--------------------------------------------------------------------------
                */

                return [

                    'enrollment' =>
                    $enrollment,

                    'student' =>
                    $enrollment->student,

                    'student_class' =>
                    $enrollment->studentClass,

                    'category_fee' =>
                    $enrollment->classCategoryFee,

                    'category' =>
                    $enrollment->category,

                    'fee_option' =>
                    $feeOption
                        ? [
                            'id' => $feeOption->id,
                            'label' => $feeOption->label,
                            'fee' => (float) $feeOption->fee,
                        ]
                        : null,

                    'fee_option_id' =>
                    $feeOption ? $feeOption->id : null,

                    'fee_option_label' =>
                    $feeOption ? $feeOption->label : null,

                    'fee_option_fee' =>
                    $feeOption ? (float) $feeOption->fee : 0,

                    'expected_fee' =>
                    $expectedFee,

                    'final_fee' =>
                    $expectedFee,

                    'paid_amount' =>
                    $paidAmount,

                    'balance' =>
                    $balance,

                    'is_free_card' =>
                    $isFreeCard,

                    'is_paid' =>
                    $isPaid,

                    'status' =>
                    $status,

                    'payment' =>
                    $payment,

                    'attendance_count' =>
                    $attendanceCount,

                    'payment_month' =>
                    $monthStartDate,
                ];
            })
            ->values();
    }

    /**
     * Get only unpaid enrollments.
     */
    public function getUnpaidEnrollments(
        int $classId,
        int $categoryFeeId,
        string $paymentMonth
    ): Collection {

        return $this
            ->getEnrollmentsWithStatus(
                $classId,
                $categoryFeeId,
                $paymentMonth
            )
            ->where(
                'is_paid',
                false
            )
            ->values();
    }

    /**
     * Get only paid enrollments.
     */
    public function getPaidEnrollments(
        int $classId,
        int $categoryFeeId,
        string $paymentMonth
    ): Collection {

        return $this
            ->getEnrollmentsWithStatus(
                $classId,
                $categoryFeeId,
                $paymentMonth
            )
            ->where(
                'is_paid',
                true
            )
            ->values();
    }

    /**
     * Get payment and attendance summary.
     */
    public function getSummary(
        int $classId,
        int $categoryFeeId,
        string $paymentMonth
    ): array {

        $all = $this->getEnrollmentsWithStatus(
            $classId,
            $categoryFeeId,
            $paymentMonth
        );

        $freeCardCount = $all
            ->where('is_free_card', true)
            ->count();

        $paidCount = $all
            ->filter(function ($item) {
                return !$item['is_free_card']
                    && $item['is_paid'];
            })
            ->count();

        $unpaidCount = $all
            ->filter(function ($item) {
                return !$item['is_free_card']
                    && !$item['is_paid'];
            })
            ->count();

        return [
            'total_students' =>
            $all->count(),

            'paid_count' =>
            $paidCount,

            'unpaid_count' =>
            $unpaidCount,

            'free_card_count' =>
            $freeCardCount,

            'total_expected_amount' =>
            round(
                $all
                    ->reject(function ($item) {
                        return $item['is_free_card'];
                    })
                    ->sum('expected_fee'),
                2
            ),

            'total_paid_amount' =>
            round(
                $all->sum('paid_amount'),
                2
            ),

            'total_unpaid_amount' =>
            round(
                $all
                    ->filter(function ($item) {
                        return !$item['is_free_card'];
                    })
                    ->sum('balance'),
                2
            ),

            'total_attendance' =>
            $all->sum('attendance_count'),
        ];
    }

    /**
     * Get selected class with category and month details.
     */
    public function getClassWithMonth(
        int $classId,
        int $categoryFeeId,
        string $paymentMonth
    ): array {

        $month = Carbon::parse(
            $paymentMonth
        )->startOfMonth();

        $class = StudentClass::query()
            ->with([
                'teacher',
                'subject',
                'grade',
            ])
            ->where(
                'is_active',
                true
            )
            ->findOrFail($classId);

        $categoryFee = $class
            ->categoryFees()
            ->with([
                'category',
                'activeFeeOptions',
            ])
            ->where(
                'id',
                $categoryFeeId
            )
            ->where(
                'is_active',
                true
            )
            ->firstOrFail();

        $summary = $this->getSummary(
            $classId,
            $categoryFeeId,
            $month->toDateString()
        );

        return [

            'class' =>
            $class,

            'category_fee' =>
            $categoryFee,

            'category' =>
            $categoryFee->category,

            'month' =>
            $month->format('F Y'),

            'month_start' =>
            $month->copy()->startOfMonth(),

            'month_end' =>
            $month->copy()->endOfMonth(),

            'summary' =>
            $summary,
        ];
    }

    /**
     * Get export data.
     */
    public function getExportData(
        int $classId,
        int $categoryFeeId,
        string $paymentMonth
    ): Collection {

        $enrollments = $this->getEnrollmentsWithStatus(
            $classId,
            $categoryFeeId,
            $paymentMonth
        );

        return $enrollments->map(function ($item) {

            $student = $item['student'];

            return [

                'student_name' =>
                $student->full_name
                    ?: $student->initial_name,

                'student_id' =>
                $student->custom_id,

                'mobile' =>
                $student->mobile,

                'whatsapp_mobile' =>
                $student->whatsapp_mobile,

                'email' =>
                $student->email,

                'guardian_mobile' =>
                $student->guardian_mobile,

                'fee_option' =>
                $item['fee_option'],

                'fee_option_id' =>
                $item['fee_option_id'],

                'fee_option_label' =>
                $item['fee_option_label'],

                'fee_option_fee' =>
                $item['fee_option_fee'],

                'expected_fee' =>
                $item['expected_fee'],

                'final_fee' =>
                $item['final_fee'],

                'paid_amount' =>
                $item['paid_amount'],

                'balance' =>
                $item['balance'],

                'is_free_card' =>
                $item['is_free_card'],

                'status' =>
                $item['status'],

                'attendance_count' =>
                $item['attendance_count'],

                'payment_month' =>
                $item['payment_month'],
            ];
        });
    }

    /**
     * Get unpaid students with attendance details.
     */
    public function getUnpaidWithAttendance(
        int $classId,
        int $categoryFeeId,
        string $paymentMonth
    ): Collection {

        return $this
            ->getUnpaidEnrollments(
                $classId,
                $categoryFeeId,
                $paymentMonth
            )
            ->reject(function ($item) {
                return !empty($item['is_free_card']);
            })
            ->map(function ($item) {

                $student = $item['student'];

                return [

                    'student_id' =>
                    $student->id,

                    'student_name' =>
                    $student->full_name
                        ?: $student->initial_name,

                    'mobile' =>
                    $student->mobile
                        ?: $student->guardian_mobile,

                    'whatsapp_mobile' =>
                    $student->whatsapp_mobile
                        ?: $student->guardian_mobile,

                    'guardian_mobile' =>
                    $student->guardian_mobile,

                    'fee_option' =>
                    $item['fee_option'],

                    'fee_option_label' =>
                    $item['fee_option_label'],

                    'fee_option_fee' =>
                    $item['fee_option_fee'],

                    'expected_fee' =>
                    $item['expected_fee'],

                    'final_fee' =>
                    $item['final_fee'],

                    'paid_amount' =>
                    $item['paid_amount'],

                    'balance' =>
                    $item['balance'],

                    'is_free_card' =>
                    $item['is_free_card'],

                    'attendance_count' =>
                    $item['attendance_count'],

                    'class_name' =>
                    $item['student_class']->class_name,

                    'payment_month' =>
                    $item['payment_month'],

                    'student' =>
                    $student,

                    'student_class' =>
                    $item['student_class'],
                ];
            })
            ->values();
    }

    /**
     * Get active categories for class.
     */
    public function getCategories(int $classId)
    {
        return ClassCategoryFee::query()
            ->with([
                'category',
                'activeFeeOptions',
            ])
            ->where(
                'student_class_id',
                $classId
            )
            ->where(
                'is_active',
                true
            )
            ->get();
    }

    /**
     * Send payment reminders.
     *
     * Uses Queue Jobs.
     */
    public function sendReminders(array $data): array
    {
        /*
        |--------------------------------------------------------------------------
        | Get unpaid students
        |--------------------------------------------------------------------------
        */

        $unpaidStudents = $this->getUnpaidWithAttendance(
            (int) $data['class_id'],
            (int) $data['class_category_fee_id'],
            $data['payment_month']
        );

        /*
        |--------------------------------------------------------------------------
        | Selected students
        |--------------------------------------------------------------------------
        */

        $selectedStudentIds = collect(
            $data['student_ids'] ?? []
        )
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $students = $unpaidStudents
            ->whereIn(
                'student_id',
                $selectedStudentIds
            )
            ->values();

        if ($students->isEmpty()) {

            return [
                'success' => false,
                'message' => 'No unpaid students selected.',
                'sent_count' => 0,
                'failed_count' => 0,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Reminder type
        |--------------------------------------------------------------------------
        */

        $reminderType =
            $data['reminder_type'] ?? 'sms';

        if ($reminderType !== 'sms') {

            return [
                'success' => false,
                'message' =>
                'This reminder type is not supported yet.',
                'sent_count' => 0,
                'failed_count' => 0,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Student IDs
        |--------------------------------------------------------------------------
        */

        $studentIds = $students
            ->pluck('student_id')
            ->values()
            ->all();

        /*
        |--------------------------------------------------------------------------
        | Custom message
        |--------------------------------------------------------------------------
        */

        $customMessage =
            $data['message'] ?? null;

        /*
        |--------------------------------------------------------------------------
        | Single / Bulk
        |--------------------------------------------------------------------------
        */

        if (count($studentIds) === 1) {

            return $this->sendIndividualSms(
                (int) $data['class_id'],
                (int) $data['class_category_fee_id'],
                $data['payment_month'],
                $studentIds[0],
                $customMessage
            );
        }

        return $this->sendBulkSms(
            (int) $data['class_id'],
            (int) $data['class_category_fee_id'],
            $data['payment_month'],
            $studentIds,
            $customMessage
        );
    }

    /**
     * Send SMS to one student.
     *
     * SMS is queued.
     */
    public function sendIndividualSms(
        int $classId,
        int $categoryFeeId,
        string $paymentMonth,
        int $studentId,
        ?string $customMessage = null
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Get student
        |--------------------------------------------------------------------------
        */

        $students = $this->getUnpaidWithAttendance(
            $classId,
            $categoryFeeId,
            $paymentMonth
        );

        $studentData = $students
            ->firstWhere(
                'student_id',
                $studentId
            );

        if (!$studentData) {

            return [
                'success' => false,
                'message' =>
                'Student is already paid or not found.',
                'sent_count' => 0,
                'failed_count' => 1,
            ];
        }

        $student = $studentData['student'];

        /*
        |--------------------------------------------------------------------------
        | Guardian mobile only
        |--------------------------------------------------------------------------
        */

        $guardianMobile =
            $student->guardian_mobile;

        if (empty($guardianMobile)) {

            return [
                'success' => false,
                'message' =>
                'Guardian mobile number is not available.',
                'sent_count' => 0,
                'failed_count' => 1,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Dynamic data
        |--------------------------------------------------------------------------
        */

        $studentName =
            $student->full_name
            ?: $student->initial_name;

        $className =
            $studentData['class_name']
            ?? 'Class';

        $monthName =
            Carbon::parse($paymentMonth)
            ->format('F Y');

        $amountDue =
            number_format(
                (float) (
                    isset($studentData['balance'])
                    ? $studentData['balance']
                    : $studentData['expected_fee']
                ),
                2
            );

        $attendanceCount =
            (int) (
                $studentData['attendance_count']
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Default message
        |--------------------------------------------------------------------------
        */

        $defaultMessage =
            "Payment Reminder\n\n" .
            "Student: {$studentName}\n" .
            "Class: {$className}\n" .
            "Month: {$monthName}\n" .
            "Amount Due: Rs. {$amountDue}\n" .
            "Attendance: {$attendanceCount}\n" .
            "Please settle the payment. Thank you.";

        /*
        |--------------------------------------------------------------------------
        | Custom message or default
        |--------------------------------------------------------------------------
        */

        $message = trim(
            (string) $customMessage
        );

        if ($message === '') {
            $message = $defaultMessage;
        }

        /*
        |--------------------------------------------------------------------------
        | Replace placeholders
        |--------------------------------------------------------------------------
        |
        | Supported:
        |
        | [Student Name]
        | [Class]
        | [Month]
        | [Amount]
        | [Attendance]
        |
        */

        $message = str_replace(
            [
                '[Student Name]',
                '[Class]',
                '[Month]',
                '[Amount]',
                '[Attendance]',
            ],
            [
                $studentName,
                $className,
                $monthName,
                $amountDue,
                $attendanceCount,
            ],
            $message
        );

        /*
        |--------------------------------------------------------------------------
        | Dispatch Queue Job
        |--------------------------------------------------------------------------
        */

        SendPaymentReminderSmsJob::dispatch(
            $guardianMobile,
            $message,
            $student->id,
            $studentName
        );

        /*
        |--------------------------------------------------------------------------
        | Log
        |--------------------------------------------------------------------------
        */

        Log::info(
            'PAYMENT REMINDER SMS JOB DISPATCHED',
            [
                'student_id' =>
                $student->id,

                'student_name' =>
                $studentName,

                'guardian_mobile' =>
                $guardianMobile,

                'class_name' =>
                $className,

                'payment_month' =>
                $monthName,

                'expected_fee' =>
                (float) ($studentData['expected_fee'] ?? 0),

                'balance' =>
                (float) ($studentData['balance'] ?? 0),

                'attendance_count' =>
                $attendanceCount,

                'message' =>
                $message,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | This is QUEUED, not provider delivery result.
        |
        */

        return [

            'success' =>
            true,

            'message' =>
            'SMS job dispatched successfully.',

            'sent_count' =>
            1,

            'failed_count' =>
            0,

            'student_id' =>
            $student->id,

            'student_name' =>
            $studentName,

            'guardian_mobile' =>
            $guardianMobile,

            'queued' =>
            true,
        ];
    }

    /**
     * Send bulk SMS to selected unpaid students.
     *
     * Uses Queue Job.
     */
    public function sendBulkSms(
        int $classId,
        int $categoryFeeId,
        string $paymentMonth,
        array $studentIds,
        ?string $customMessage = null
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Get selected students
        |--------------------------------------------------------------------------
        */

        $students = $this->getUnpaidWithAttendance(
            $classId,
            $categoryFeeId,
            $paymentMonth
        );

        $selectedStudents = $students
            ->whereIn(
                'student_id',
                $studentIds
            )
            ->values();

        if ($selectedStudents->isEmpty()) {

            return [
                'success' => false,
                'message' =>
                'No unpaid students selected.',
                'sent_count' => 0,
                'failed_count' => 0,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Numbers
        |--------------------------------------------------------------------------
        */

        $numbers = [];

        $studentsWithoutMobile = [];

        foreach ($selectedStudents as $item) {

            $student = $item['student'];

            $guardianMobile =
                $student->guardian_mobile;

            if (empty($guardianMobile)) {

                $studentsWithoutMobile[] = [

                    'student_id' =>
                    $student->id,

                    'student_name' =>
                    $student->full_name
                        ?: $student->initial_name,

                    'reason' =>
                    'Guardian mobile number not available.',
                ];

                continue;
            }

            $numbers[] =
                $guardianMobile;
        }

        /*
        |--------------------------------------------------------------------------
        | Remove duplicate numbers
        |--------------------------------------------------------------------------
        */

        $numbers = array_values(
            array_unique($numbers)
        );

        if (empty($numbers)) {

            return [
                'success' => false,

                'message' =>
                'No guardian mobile numbers available.',

                'sent_count' => 0,

                'failed_count' =>
                count($studentsWithoutMobile),

                'failed_students' =>
                $studentsWithoutMobile,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Common bulk data
        |--------------------------------------------------------------------------
        */

        $monthName =
            Carbon::parse($paymentMonth)
            ->format('F Y');

        $className =
            $selectedStudents
                ->first()['class_name']
            ?? 'Class';

        /*
        |--------------------------------------------------------------------------
        | Default bulk message
        |--------------------------------------------------------------------------
        */

        $defaultMessage =
            "Payment Reminder\n\n" .
            "Class: {$className}\n" .
            "Month: {$monthName}\n\n" .
            "Your child's class payment is pending. " .
            "Please settle the payment as soon as possible.\n\n" .
            "Thank you.";

        /*
        |--------------------------------------------------------------------------
        | Custom message
        |--------------------------------------------------------------------------
        */

        $message = trim(
            (string) $customMessage
        );

        if ($message === '') {
            $message = $defaultMessage;
        }

        /*
        |--------------------------------------------------------------------------
        | Bulk placeholders
        |--------------------------------------------------------------------------
        |
        | For bulk SMS there is one common message.
        |
        | [Class] and [Month] can be replaced.
        |
        | Student-specific placeholders cannot safely be used
        | because one bulk message is sent to multiple numbers.
        |
        */

        $message = str_replace(
            [
                '[Class]',
                '[Month]',
            ],
            [
                $className,
                $monthName,
            ],
            $message
        );

        /*
        |--------------------------------------------------------------------------
        | Prevent student-specific placeholders in bulk
        |--------------------------------------------------------------------------
        */

        $message = str_replace(
            [
                '[Student Name]',
                '[Amount]',
                '[Attendance]',
            ],
            [
                'your child',
                'the pending amount',
                'the recorded',
            ],
            $message
        );

        /*
        |--------------------------------------------------------------------------
        | Dispatch bulk queue job
        |--------------------------------------------------------------------------
        */

        SendPaymentReminderBulkSmsJob::dispatch(
            $numbers,
            $message
        );

        /*
        |--------------------------------------------------------------------------
        | Log
        |--------------------------------------------------------------------------
        */

        Log::info(
            'PAYMENT REMINDER BULK SMS JOB DISPATCHED',
            [
                'number_count' =>
                count($numbers),

                'numbers' =>
                $numbers,

                'class_name' =>
                $className,

                'payment_month' =>
                $monthName,

                'message' =>
                $message,

                'students_without_mobile' =>
                $studentsWithoutMobile,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Return queued result
        |--------------------------------------------------------------------------
        */

        return [

            'success' =>
            true,

            'message' =>
            count($numbers)
                . ' SMS job(s) queued successfully.'
                . (
                    count($studentsWithoutMobile) > 0
                    ? ' '
                    . count($studentsWithoutMobile)
                    . ' student(s) have no guardian mobile.'
                    : ''
                ),

            'sent_count' =>
            count($numbers),

            'failed_count' =>
            count($studentsWithoutMobile),

            'total_numbers' =>
            count($numbers),

            'failed_students' =>
            $studentsWithoutMobile,

            'queued' =>
            true,
        ];
    }
}

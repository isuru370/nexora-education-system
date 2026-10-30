<?php

namespace App\Services\Web\PaymentReminder;

use App\Enums\NotificationType;
use App\Jobs\SendPaymentReminderBulkSmsJob;
use App\Jobs\SendPaymentReminderSmsJob;
use App\Models\ClassCategoryFee;
use App\Models\ClassSchedule;
use App\Models\StudentAttendance;
use App\Models\StudentClass;
use App\Models\StudentClassEnrollment;
use App\Services\Branch\BranchAccessService;
use App\Services\Notification\NotificationService;
use App\Services\Web\Report\PaymentReportQuery;
use Carbon\Carbon;
use App\Support\PaymentRules;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Payment status of one class + category for one fee month,
 * and SMS / app reminders to the students who have not paid.
 *
 * Rules:
 *  - Only classes of branches the user can access
 *  - A student belongs to the month when enrolled before the month ends and
 *    not left before the month starts (same rule as the Monthly Collection report)
 *  - Inactive / deleted students are not listed
 *  - Paid = completed payments for that fee month >= the enrollment's fee
 *  - Reminders are only sent to students that are still unpaid (checked again on send)
 */
class PaymentReminderService
{
    public const CHANNEL_SMS = 'sms';

    public const CHANNEL_NOTIFICATION = 'notification';

    public const STATUS_UNPAID = 'unpaid';

    public const STATUS_PAID = 'paid';

    public const STATUS_FREE_CARD = 'free_card';

    /** Payment Rules: the attendance gives a 0 % fee this month (no reminder) */
    public const STATUS_NO_FEE = 'no_fee';

    /** Placeholders that are filled for every student */
    public const PLACEHOLDERS = ['[Student Name]', '[Class]', '[Month]', '[Amount]', '[Attendance]'];

    /** Placeholders that are different for every student */
    protected const STUDENT_PLACEHOLDERS = ['[Student Name]', '[Amount]', '[Attendance]'];

    public function __construct(
        protected BranchAccessService $branchAccessService,
        protected NotificationService $notificationService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Filter options
    |--------------------------------------------------------------------------
    */

    /** Active classes in the user's branches */
    public function classOptions(): Collection
    {
        return StudentClass::query()
            ->with(['subject:id,subject_name', 'grade:id,grade_name', 'branch:id,name'])
            ->where('is_active', true)
            ->whereIn('branch_id', $this->branchIds())
            ->orderBy('class_name')
            ->get(['id', 'branch_id', 'class_name', 'subject_id', 'grade_id']);
    }

    /**
     * Active categories (with active fee options) of a class.
     *
     * @throws DomainException
     */
    public function categoryOptions(int $classId): Collection
    {
        $this->findClass($classId);

        return ClassCategoryFee::query()
            ->with(['category:id,category_name', 'activeFeeOptions'])
            ->where('student_class_id', $classId)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }

    /** Fee months that can be selected: this month and the 11 before it */
    public function monthOptions(): array
    {
        $months = [];

        for ($i = 0; $i < 12; $i++) {
            $month = now()->startOfMonth()->subMonths($i);
            $months[$month->format('Y-m')] = $month->format('F Y');
        }

        return $months;
    }

    /**
     * @throws DomainException
     */
    public function findClass(int $classId): StudentClass
    {
        $class = StudentClass::query()
            ->with(['teacher:id,custom_id,full_name', 'subject:id,subject_name', 'grade:id,grade_name', 'branch:id,name'])
            ->where('is_active', true)
            ->find($classId);

        if (!$class) {
            throw new DomainException('The selected class was not found or is not active.');
        }

        if (!$this->branchIds()->contains((int) $class->branch_id)) {
            throw new DomainException('You do not have access to this class.');
        }

        return $class;
    }

    /**
     * @throws DomainException
     */
    public function findCategory(int $classId, int $categoryFeeId): ClassCategoryFee
    {
        $category = ClassCategoryFee::query()
            ->with('category:id,category_name')
            ->where('id', $categoryFeeId)
            ->where('student_class_id', $classId)
            ->where('is_active', true)
            ->first();

        if (!$category) {
            throw new DomainException('The selected category does not belong to this class.');
        }

        return $category;
    }

    /*
    |--------------------------------------------------------------------------
    | Report
    |--------------------------------------------------------------------------
    */

    /**
     * @return array{class: StudentClass, category: ClassCategoryFee, month: Carbon, month_label: string, students: Collection, summary: array}
     *
     * @throws DomainException
     */
    public function report(int $classId, int $categoryFeeId, Carbon $month): array
    {
        $class = $this->findClass($classId);
        $category = $this->findCategory($classId, $categoryFeeId);
        $students = $this->students($class, $category, $month);

        return [
            'class' => $class,
            'category' => $category,
            'month' => $month,
            'month_label' => $month->format('F Y'),
            'students' => $students,
            'summary' => $this->summary($students),
        ];
    }

    /**
     * One row per enrollment: unpaid first (most attendance first), then free cards, then paid.
     */
    protected function students(StudentClass $class, ClassCategoryFee $category, Carbon $month): Collection
    {
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();

        $enrollments = StudentClassEnrollment::query()
            ->where('student_class_id', $class->id)
            ->where('class_category_fee_id', $category->id)
            ->where(function ($query) use ($monthEnd) {
                $query->whereNull('enrolled_at')
                    ->orWhereDate('enrolled_at', '<=', $monthEnd->toDateString());
            })
            ->where(function ($query) use ($monthStart) {
                $query->whereNull('left_at')
                    ->orWhereDate('left_at', '>=', $monthStart->toDateString());
            })
            ->whereHas('student', fn ($query) => $query->where('is_active', true))
            ->with([
                'student:id,custom_id,temporary_qr_code,permanent_qr_active,full_name,initial_name,mobile,guardian_mobile',
                'classCategoryFeeOption:id,label,fee',
                'payments' => fn ($query) => $query
                    ->where('status', 'completed')
                    ->whereDate('payment_month', $monthStart->toDateString()),
            ])
            ->get();

        if ($enrollments->isEmpty()) {
            return collect();
        }

        $attendance = $this->attendanceCounts($class->id, $category->id, $enrollments->pluck('id'), $monthStart, $monthEnd);

        return $enrollments
            ->map(function (StudentClassEnrollment $enrollment) use ($attendance) {
                $student = $enrollment->student;
                $option = $enrollment->classCategoryFeeOption;
                $fee = round((float) $enrollment->final_fee, 2);
                $paid = round((float) $enrollment->payments->sum('amount'), 2);
                $discount = round((float) $enrollment->payments->sum('discount_amount'), 2);
                $days = (int) $attendance->get($enrollment->id, 0);

                // Payment Rules: the month's fee may follow the attendance
                $due = PaymentRules::attendanceRule()
                    ? round($fee * PaymentRules::percentFor($days) / 100, 2)
                    : $fee;

                if ($enrollment->is_free_card) {
                    $status = self::STATUS_FREE_CARD;
                } elseif ($enrollment->payments->isNotEmpty() && $paid + $discount >= $fee) {
                    $status = self::STATUS_PAID;   // paid (a discount covers the rest)
                } elseif ($paid >= $due && $due > 0) {
                    $status = self::STATUS_PAID;
                } elseif ($due <= 0) {
                    $status = self::STATUS_NO_FEE;
                } else {
                    $status = self::STATUS_UNPAID;
                }

                return [
                    'enrollment_id' => $enrollment->id,
                    'student_id' => $student->id,
                    'student_code' => PaymentReportQuery::studentCode($student),
                    'student_name' => $student->full_name ?: $student->initial_name,
                    'initial_name' => $student->initial_name,
                    'mobile' => $student->mobile,
                    'guardian_mobile' => $student->guardian_mobile,
                    'fee_option' => $option ? $option->label : null,
                    'fee' => $fee,
                    'paid' => $paid,
                    'balance' => $status === self::STATUS_UNPAID ? round($due - $paid, 2) : 0.0,
                    'status' => $status,
                    'status_label' => $this->statusLabel($status),
                    'attendance' => (int) $attendance->get($enrollment->id, 0),
                    'left_at' => optional($enrollment->left_at)->format('Y-m-d'),
                ];
            })
            ->sort(function (array $a, array $b) {
                $order = [self::STATUS_UNPAID => 0, self::STATUS_NO_FEE => 1, self::STATUS_FREE_CARD => 2, self::STATUS_PAID => 3];

                return [$order[$a['status']], -$a['attendance'], $a['student_name']]
                    <=> [$order[$b['status']], -$b['attendance'], $b['student_name']];
            })
            ->values();
    }

    /**
     * Number of (non-cancelled) class days each enrollment attended in the month.
     */
    protected function attendanceCounts(int $classId, int $categoryFeeId, Collection $enrollmentIds, Carbon $monthStart, Carbon $monthEnd): Collection
    {
        $scheduleIds = ClassSchedule::query()
            ->where('student_class_id', $classId)
            ->where('class_category_fee_id', $categoryFeeId)
            ->where('is_active', true)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('class_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->pluck('id');

        if ($scheduleIds->isEmpty()) {
            return collect();
        }

        return StudentAttendance::query()
            ->whereIn('student_class_enrollment_id', $enrollmentIds)
            ->whereIn('class_schedule_id', $scheduleIds)
            ->groupBy('student_class_enrollment_id')
            ->selectRaw('student_class_enrollment_id, COUNT(DISTINCT class_schedule_id) as days')
            ->pluck('days', 'student_class_enrollment_id');
    }

    public function summary(Collection $students): array
    {
        $unpaid = $students->where('status', self::STATUS_UNPAID);

        return [
            'students' => $students->count(),
            'paid' => $students->where('status', self::STATUS_PAID)->count(),
            'unpaid' => $unpaid->count(),
            'free_card' => $students->where('status', self::STATUS_FREE_CARD)->count(),
            'no_fee' => $students->where('status', self::STATUS_NO_FEE)->count(),
            'expected' => round($students->sum('fee'), 2),
            'collected' => round($students->sum('paid'), 2),
            'balance' => round($unpaid->sum('balance'), 2),
            'unpaid_without_mobile' => $unpaid->filter(fn ($row) => empty($row['guardian_mobile']))->count(),
        ];
    }

    public function statusLabel(string $status): string
    {
        return [
            self::STATUS_UNPAID => 'Not Paid',
            self::STATUS_PAID => 'Paid',
            self::STATUS_FREE_CARD => 'Free Card',
            self::STATUS_NO_FEE => 'No Fee',
        ][$status] ?? $status;
    }

    /** Excel / PDF table definition */
    public function table(): array
    {
        return [
            'headings' => ['Student Code', 'Student', 'Mobile', 'Guardian Mobile', 'Fee Option', 'Fee (Rs.)', 'Paid (Rs.)', 'Balance (Rs.)', 'Status', 'Attendance', 'Left On'],
            'columns' => ['student_code', 'student_name', 'mobile', 'guardian_mobile', 'fee_option', 'fee', 'paid', 'balance', 'status_label', 'attendance', 'left_at'],
            'money' => ['fee', 'paid', 'balance'],
            'total' => 'balance',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Send reminders
    |--------------------------------------------------------------------------
    */

    public function defaultMessage(): string
    {
        return "Dear Parent/Guardian,\n"
            . "The class fee for [Month] is still pending.\n"
            . "Student: [Student Name]\n"
            . "Class: [Class]\n"
            . "Amount due: Rs. [Amount]\n"
            . "Attended: [Attendance] day(s)\n"
            . 'Please settle the payment. Thank you.';
    }

    /**
     * Queue SMS or app reminders for the selected students.
     * Students that are not unpaid any more are skipped.
     *
     * @return array{sent: int, skipped: int, failed: array<int, string>, message: string}
     *
     * @throws DomainException
     */
    public function send(int $classId, int $categoryFeeId, Carbon $month, array $studentIds, string $channel, ?string $message): array
    {
        $report = $this->report($classId, $categoryFeeId, $month);

        $selectedIds = collect($studentIds)->map(fn ($id) => (int) $id)->unique();
        $targets = $report['students']
            ->where('status', self::STATUS_UNPAID)
            ->whereIn('student_id', $selectedIds->all())
            ->unique('student_id')
            ->values();

        $skipped = $selectedIds->count() - $targets->count();

        if ($targets->isEmpty()) {
            throw new DomainException('None of the selected students have an unpaid fee for ' . $report['month_label'] . '.');
        }

        $template = trim((string) $message) !== '' ? trim((string) $message) : $this->defaultMessage();
        $common = [
            '[Class]' => $report['class']->class_name,
            '[Month]' => $report['month_label'],
        ];

        $result = $channel === self::CHANNEL_NOTIFICATION
            ? $this->sendNotifications($targets, $template, $common, $report)
            : $this->sendSms($targets, $template, $common);

        $result['skipped'] = $skipped;
        $result['message'] = $this->resultMessage($channel, $result);

        Log::info('Payment reminders queued.', [
            'channel' => $channel,
            'class_id' => $classId,
            'class_category_fee_id' => $categoryFeeId,
            'month' => $month->format('Y-m'),
            'sent' => $result['sent'],
            'skipped' => $skipped,
            'failed' => count($result['failed']),
            'user_id' => auth()->id(),
        ]);

        return $result;
    }

    protected function sendSms(Collection $targets, string $template, array $common): array
    {
        $failed = [];
        $recipients = collect();

        foreach ($targets as $row) {
            $mobile = $this->normalizeMobile($row['guardian_mobile']);

            if ($mobile === null) {
                $failed[] = $row['student_name'] . ': no guardian mobile number';

                continue;
            }

            $recipients->push($row + ['to' => $mobile]);
        }

        if ($recipients->isEmpty()) {
            return ['sent' => 0, 'failed' => $failed];
        }

        $personal = str_replace(self::STUDENT_PLACEHOLDERS, '', $template) !== $template;

        if (!$personal) {
            // Same text for everyone: one bulk SMS request
            SendPaymentReminderBulkSmsJob::dispatch(
                $recipients->pluck('to')->unique()->values()->all(),
                strtr($template, $common)
            );

            return ['sent' => $recipients->count(), 'failed' => $failed];
        }

        foreach ($recipients as $row) {
            SendPaymentReminderSmsJob::dispatch(
                $row['to'],
                $this->fill($template, $common, $row),
                $row['student_id'],
                $row['student_name']
            );
        }

        return ['sent' => $recipients->count(), 'failed' => $failed];
    }

    protected function sendNotifications(Collection $targets, string $template, array $common, array $report): array
    {
        $sent = 0;
        $failed = [];

        foreach ($targets as $row) {
            try {
                $notification = $this->notificationService->send([
                    'student_id' => $row['student_id'],
                    'title' => 'Payment Reminder',
                    'message' => $this->fill($template, $common, $row),
                    'type' => NotificationType::REMINDER,
                    'data' => [
                        'notification_type' => 'payment_reminder',
                        'class_id' => (string) $report['class']->id,
                        'class_category_fee_id' => (string) $report['category']->id,
                        'payment_month' => $report['month']->format('Y-m-01'),
                    ],
                ]);

                if ($notification->error_message) {
                    $failed[] = $row['student_name'] . ': ' . (str_contains($notification->error_message, 'FCM token')
                        ? 'not logged in to the student app'
                        : rtrim($notification->error_message, '.'));
                } else {
                    $sent++;
                }
            } catch (Throwable $e) {
                report($e);
                $failed[] = $row['student_name'] . ': could not be queued';
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    protected function fill(string $template, array $common, array $row): string
    {
        return strtr($template, $common + [
            '[Student Name]' => $row['student_name'],
            '[Amount]' => number_format($row['balance'], 2),
            '[Attendance]' => (string) $row['attendance'],
        ]);
    }

    protected function resultMessage(string $channel, array $result): string
    {
        $what = $channel === self::CHANNEL_NOTIFICATION ? 'app notification(s)' : 'SMS';
        $parts = [];

        if ($result['sent'] > 0) {
            $parts[] = $result['sent'] . ' ' . $what . ' queued.';
        }

        if ($result['skipped'] > 0) {
            $parts[] = $result['skipped'] . ' selected student(s) skipped (already paid, free card or not in this class).';
        }

        if (!empty($result['failed'])) {
            $parts[] = count($result['failed']) . ' not sent: ' . implode('; ', array_slice($result['failed'], 0, 5))
                . (count($result['failed']) > 5 ? '; ...' : '') . '.';
        }

        return implode(' ', $parts);
    }

    /** Digits only, at least 9 digits (07XXXXXXXX / 947XXXXXXXX) */
    protected function normalizeMobile(?string $mobile): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $mobile);

        return strlen($digits) >= 9 ? $digits : null;
    }

    protected function branchIds(): Collection
    {
        return $this->branchAccessService->getBranchIds()->map(fn ($id) => (int) $id)->values();
    }
}

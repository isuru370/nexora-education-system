<?php

namespace App\Services\Api\Offline;

use App\Models\ClassCategoryFeeOption;
use App\Models\ClassSchedule;
use App\Models\OfflineSyncItem;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentClassEnrollment;
use App\Services\Branch\BranchAccessService;
use App\Services\Notification\AttendanceNotificationService;
use App\Services\Web\Attendance\AttendanceMarker;
use App\Services\Web\Payment\PaymentMarker;
use App\Services\Web\Payment\PaymentService;
use App\Support\AttendanceRules;
use App\Support\PaymentRules;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Staff app offline mode (attendance + payments).
 *
 *  pack(): what the phone needs to work without internet today - today's
 *          classes, their students (codes, name, fee, months paid) and the
 *          rules. No phone numbers or other personal details.
 *
 *  sync(): what the phone recorded offline. Each item has a local_uuid from
 *          the phone, so sending it again returns the first result and saves
 *          nothing twice. Items keep the time they happened (attended_at /
 *          paid_at), which the teacher salary is made from.
 */
class OfflineStaffService
{
    /** months of "already paid" sent to the phone: this month, 6 back, 1 ahead */
    public const PAID_MONTHS_BACK = 6;

    /** an offline item older than this is refused (the phone was offline too long) */
    public const MAX_AGE_DAYS = 7;

    public function __construct(
        protected BranchAccessService $branchAccess,
        protected AttendanceMarker $attendanceMarker,
        protected PaymentService $paymentService,
        protected PaymentMarker $paymentMarker,
        protected AttendanceNotificationService $attendanceNotification,
    ) {}

    /* ================================================================ pack */

    public function pack(?Carbon $day = null): array
    {
        $day = ($day ?? now())->copy()->startOfDay();
        $branchIds = $this->branchAccess->getBranchIds()->map(fn ($id) => (int) $id)->all();
        $rules = AttendanceRules::all();

        $schedules = ClassSchedule::query()
            ->with([
                'branch:id,name,code',
                'hall:id,hall_name',
                'studentClass:id,class_name,teacher_id,grade_id,subject_id',
                'studentClass.subject:id,subject_name',
                'studentClass.teacher:id,initials,full_name',
                'studentClass.grade:id,grade_name',
                'classCategoryFee:id,student_class_id,class_category_id',
                'classCategoryFee.category:id,category_name',
            ])
            ->whereDate('class_date', $day->toDateString())
            ->whereIn('branch_id', $branchIds)
            ->where('is_active', true)
            ->whereIn('status', ['scheduled', 'ongoing', 'completed'])
            ->orderBy('start_time')
            ->get();

        $pairs = $schedules->map(fn ($s) => [(int) $s->student_class_id, (int) $s->class_category_fee_id])
            ->unique(fn ($p) => $p[0] . '|' . $p[1])
            ->values();

        $enrollments = $pairs->isEmpty() ? collect() : StudentClassEnrollment::query()
            ->with([
                'student:id,custom_id,temporary_qr_code,temporary_qr_code_expire_date,permanent_qr_active,initial_name,full_name,grade_id,is_active,student_disable',
                'student.grade:id,grade_name',
                'classCategoryFeeOption:id,class_category_fee_id,label,fee,is_default,is_active',
            ])
            ->where('is_active', true)
            ->whereIn('branch_id', $branchIds)
            ->where(function ($query) use ($pairs) {
                foreach ($pairs as [$classId, $feeId]) {
                    $query->orWhere(fn ($q) => $q->where('student_class_id', $classId)->where('class_category_fee_id', $feeId));
                }
            })
            ->get();

        $paidMonths = $this->paidMonths($enrollments, $day);

        $students = [];
        $enrollmentRows = [];

        foreach ($enrollments as $enrollment) {
            $student = $enrollment->student;

            if (!$student || !$student->is_active || $student->student_disable) {
                continue;
            }

            $students[$student->id] ??= [
                'id' => $student->id,
                'custom_id' => $student->custom_id,
                // what a card may carry: the permanent ID, or a temporary QR not expired yet
                'codes' => array_values(array_filter([
                    $student->permanent_qr_active ? $student->custom_id : null,
                    $this->temporaryCodeValid($student, $day) ? $student->temporary_qr_code : null,
                    // the ID typed by hand always finds the student (as the online scan)
                    $student->custom_id,
                ], fn ($code) => $code !== null && $code !== '')),
                'name' => $student->initial_name ?: $student->full_name,
                'initial_name' => $student->initial_name,
                'full_name' => $student->full_name,
                'grade' => optional($student->grade)->grade_name,
            ];
            $students[$student->id]['codes'] = array_values(array_unique($students[$student->id]['codes']));

            $hallFee = round($this->paymentMarker->hallFee($enrollment), 2);
            $gross = round($this->paymentMarker->grossFee($enrollment), 2);

            $enrollmentRows[] = [
                'id' => $enrollment->id,
                'student_id' => $student->id,
                'student_class_id' => (int) $enrollment->student_class_id,
                'class_category_fee_id' => (int) $enrollment->class_category_fee_id,
                'is_free_card' => (bool) $enrollment->is_free_card,
                'fee_option' => $enrollment->classCategoryFeeOption
                    ? [
                        'id' => $enrollment->classCategoryFeeOption->id,
                        'class_category_fee_id' => (int) $enrollment->classCategoryFeeOption->class_category_fee_id,
                        'label' => $enrollment->classCategoryFeeOption->label,
                        'fee' => (float) $enrollment->classCategoryFeeOption->fee,
                        'is_default' => (bool) $enrollment->classCategoryFeeOption->is_default,
                        'is_active' => (bool) $enrollment->classCategoryFeeOption->is_active,
                    ]
                    : null,
                'class_fee' => round($gross - $hallFee, 2),
                'hall_fee' => $hallFee,
                'payable' => $gross,
                'paid_months' => $paidMonths[$this->enrollmentKey($enrollment)] ?? [],
            ];
        }

        $feeOptions = ClassCategoryFeeOption::query()
            ->whereIn('class_category_fee_id', $pairs->pluck(1))
            ->get(['id', 'class_category_fee_id', 'label', 'fee'])
            ->groupBy('class_category_fee_id')
            ->map(fn ($rows) => $rows->map(fn ($o) => ['id' => $o->id, 'label' => $o->label, 'fee' => (float) $o->fee])->values());

        $scheduleRows = $schedules->map(function (ClassSchedule $schedule) use ($day, $rules) {
            $start = Carbon::parse($day->toDateString() . ' ' . $schedule->start_time);
            $end = Carbon::parse($day->toDateString() . ' ' . $schedule->end_time);
            if ($end->lessThanOrEqualTo($start)) {
                $end->addDay();   // a class ending after midnight
            }

            $teacher = optional($schedule->studentClass)->teacher;

            return [
                'id' => $schedule->id,
                'branch' => ['id' => (int) $schedule->branch_id, 'name' => optional($schedule->branch)->name ?? '', 'code' => optional($schedule->branch)->code ?? ''],
                'class_date' => $day->toDateString(),
                'start_time' => (string) $schedule->start_time,
                'end_time' => (string) $schedule->end_time,
                'hall' => optional($schedule->hall)->hall_name ?? '',
                'subject' => optional(optional($schedule->studentClass)->subject)->subject_name ?? '',
                'teacher_name' => optional($teacher)->full_name ?: (optional($teacher)->initials ?? ''),
                'student_class_id' => (int) $schedule->student_class_id,
                'class_category_fee_id' => (int) $schedule->class_category_fee_id,
                'class_name' => optional($schedule->studentClass)->class_name,
                'category' => optional(optional($schedule->classCategoryFee)->category)->category_name,
                'grade' => optional(optional($schedule->studentClass)->grade)->grade_name,
                'teacher' => optional($teacher)->initials ?: optional($teacher)->full_name,
                'start_at' => $start->toIso8601String(),
                'end_at' => $end->toIso8601String(),
                // when the phone may mark attendance (Payment & Attendance Rules)
                'scan_from' => $rules['no_time_limit'] ? $day->copy()->startOfDay()->toIso8601String() : $start->copy()->subMinutes($rules['before_minutes'])->toIso8601String(),
                'scan_until' => $rules['no_time_limit'] ? $day->copy()->endOfDay()->toIso8601String() : $end->copy()->addMinutes($rules['after_minutes'])->toIso8601String(),
            ];
        })->values();

        $lastEnd = $scheduleRows->max('end_at');

        return [
            'date' => $day->toDateString(),
            'generated_at' => now()->toIso8601String(),
            // the phone syncs whenever it is online, and at the latest here
            'sync_at' => $lastEnd,
            'rules' => [
                'attendance' => $rules,
                'payment' => PaymentRules::all(),
                'max_offline_days' => self::MAX_AGE_DAYS,
            ],
            'schedules' => $scheduleRows,
            'students' => array_values($students),
            'enrollments' => $enrollmentRows,
            'fee_options' => $feeOptions,
        ];
    }

    /**
     * Months already paid (YYYY-MM) per student + class + category, from
     * PAID_MONTHS_BACK months ago to next month (a fee option change makes a
     * new enrollment row; a month paid on the old one is still paid).
     */
    protected function paidMonths(Collection $enrollments, Carbon $day): array
    {
        if ($enrollments->isEmpty()) {
            return [];
        }

        $from = $day->copy()->startOfMonth()->subMonths(self::PAID_MONTHS_BACK);
        $to = $day->copy()->startOfMonth()->addMonth()->endOfMonth();

        $rows = Payment::query()
            ->join('student_class_enrollments as e', 'e.id', '=', 'payments.student_class_enrollment_id')
            ->whereIn('e.student_id', $enrollments->pluck('student_id')->unique())
            ->where('payments.status', 'completed')
            ->whereNull('payments.deleted_at')
            ->whereBetween('payments.payment_month', [$from->toDateString(), $to->toDateString()])
            ->get(['e.student_id', 'e.student_class_id', 'e.class_category_fee_id', 'payments.payment_month']);

        $months = [];
        foreach ($rows as $row) {
            $key = $row->student_id . '|' . $row->student_class_id . '|' . $row->class_category_fee_id;
            $months[$key][] = Carbon::parse($row->payment_month)->format('Y-m');
        }

        return array_map(fn ($list) => array_values(array_unique($list)), $months);
    }

    protected function enrollmentKey(StudentClassEnrollment $enrollment): string
    {
        return $enrollment->student_id . '|' . $enrollment->student_class_id . '|' . $enrollment->class_category_fee_id;
    }

    protected function temporaryCodeValid(Student $student, Carbon $day): bool
    {
        if (!$student->temporary_qr_code) {
            return false;
        }

        return !$student->temporary_qr_code_expire_date
            || Carbon::parse($student->temporary_qr_code_expire_date)->endOfDay()->greaterThanOrEqualTo($day);
    }

    /* ================================================================ sync */

    /**
     * @param  array<int, array>  $items  validated items (see SyncOfflineRequest)
     * @return array<int, array{local_uuid: string, status: string, message: ?string, receipt_number?: ?string}>
     */
    public function sync(string $deviceId, array $items): array
    {
        // in the order they happened (attendance before the payment after it)
        usort($items, fn ($a, $b) => strcmp((string) $a['happened_at'], (string) $b['happened_at']));

        $results = [];

        foreach ($items as $item) {
            $results[] = $this->syncOne($deviceId, $item);
        }

        return $results;
    }

    protected function syncOne(string $deviceId, array $item): array
    {
        $existing = OfflineSyncItem::query()->where('local_uuid', $item['local_uuid'])->first();

        if ($existing) {
            return $this->result($existing);
        }

        $happenedAt = Carbon::parse($item['happened_at'])->setTimezone(config('app.timezone'));

        try {
            if ($happenedAt->greaterThan(now()->addMinutes(10))) {
                throw new \InvalidArgumentException('The phone time is ahead of the server. Check the phone date and time.');
            }

            if ($happenedAt->lessThan(now()->subDays(self::MAX_AGE_DAYS))) {
                throw new \InvalidArgumentException('Recorded more than ' . self::MAX_AGE_DAYS . ' days ago. Enter it on the web.');
            }

            [$status, $message, $attendanceId, $paymentId, $branchId] = $item['type'] === 'attendance'
                ? $this->syncAttendance($item, $happenedAt)
                : $this->syncPayment($item, $happenedAt);
        } catch (\RuntimeException $e) {
            // e.g. "Payment already completed for this class and month." - saved meanwhile
            [$status, $message, $attendanceId, $paymentId, $branchId] = [
                str_contains(strtolower($e->getMessage()), 'already') ? 'conflict' : 'failed',
                $e->getMessage(), null, null, null,
            ];
        } catch (\InvalidArgumentException $e) {
            [$status, $message, $attendanceId, $paymentId, $branchId] = ['failed', $e->getMessage(), null, null, null];
        } catch (Throwable $e) {
            report($e);
            // not saved: the phone keeps it and sends it again
            return [
                'local_uuid' => $item['local_uuid'],
                'status' => 'retry',
                'message' => 'Server error. It will be sent again.',
            ];
        }

        try {
            $row = OfflineSyncItem::create([
                'local_uuid' => $item['local_uuid'],
                'device_id' => $deviceId,
                'user_id' => auth()->id(),
                'branch_id' => $branchId,
                'type' => $item['type'],
                'status' => $status,
                'message' => $message ? mb_substr($message, 0, 255) : null,
                'happened_at' => $happenedAt,
                'payload' => $item,
                'student_attendance_id' => $attendanceId,
                'payment_id' => $paymentId,
            ]);
        } catch (QueryException $e) {
            // the same item sent twice at the same moment: the first one won
            $row = OfflineSyncItem::query()->where('local_uuid', $item['local_uuid'])->firstOrFail();
        }

        return $this->result($row);
    }

    /** @return array{0: string, 1: ?string, 2: ?int, 3: ?int, 4: ?int} */
    protected function syncAttendance(array $item, Carbon $at): array
    {
        $done = StudentAttendance::query()->where('local_uuid', $item['local_uuid'])->first();
        if ($done) {
            return ['duplicate', 'Already synced.', $done->id, null, (int) $done->branch_id];
        }

        $enrollment = StudentClassEnrollment::query()->with('student')->find($item['enrollment_id']);
        $schedule = ClassSchedule::query()->find($item['schedule_id']);

        if (!$enrollment || !$enrollment->student || (int) $enrollment->student_id !== (int) $item['student_id']) {
            throw new \InvalidArgumentException('Student class enrollment not found.');
        }

        if (!$schedule
            || (int) $schedule->student_class_id !== (int) $enrollment->student_class_id
            || (int) $schedule->class_category_fee_id !== (int) $enrollment->class_category_fee_id) {
            throw new \InvalidArgumentException('This class is not the student\'s class.');
        }

        $result = $this->attendanceMarker->mark(
            $enrollment->student,
            $enrollment,
            $schedule,
            $item['mark_method'] ?? 'qr_mobile',
            $at,
            $item['local_uuid']
        );

        if (!empty($result['already_marked'])) {
            return ['duplicate', 'Attendance was already marked for this class.', optional($result['attendance'])->id, null, (int) $schedule->branch_id];
        }

        if (empty($result['marked'])) {
            throw new \InvalidArgumentException($result['message'] ?? 'Unable to mark attendance.');
        }

        // the parent message, only while it is still news (same day)
        if ($at->isSameDay(now())) {
            try {
                $this->attendanceNotification->sendSuccess($enrollment->student, $result['attendance'], $enrollment);
            } catch (Throwable $e) {
                report($e);
            }
        }

        return ['synced', null, $result['attendance']->id, null, (int) $result['attendance']->branch_id];
    }

    /** @return array{0: string, 1: ?string, 2: ?int, 3: ?int, 4: ?int} */
    protected function syncPayment(array $item, Carbon $at): array
    {
        $done = Payment::withTrashed()->where('local_uuid', $item['local_uuid'])->first();
        if ($done) {
            return ['duplicate', 'Already synced.', null, $done->id, (int) $done->branch_id];
        }

        $enrollment = StudentClassEnrollment::query()->with('student:id,custom_id')->find($item['enrollment_id']);
        if (!$enrollment || !$enrollment->student || (int) $enrollment->student_id !== (int) $item['student_id']) {
            throw new \InvalidArgumentException('Student class enrollment not found.');
        }

        // PaymentService: the same rules as online (amount, month paid, branch) + the parent SMS
        try {
            $result = $this->paymentService->pay([
                'code' => $enrollment->student->custom_id,
                'enrollment_id' => $enrollment->id,
                'amount' => $item['amount'],
                'discount_amount' => $item['discount_amount'] ?? 0,
                'payment_month' => $item['payment_month'] . '-01',
                'fee_option_id' => $item['fee_option_id'] ?? null,
                'payment_method' => 'cash',
                'mark_method' => $item['mark_method'] ?? 'qr_mobile',
                'paid_at' => $at,
                'receipt_number' => $item['receipt_number'] ?? null,
                'local_uuid' => $item['local_uuid'],
                'note' => trim('Offline (staff app). ' . ($item['note'] ?? '')),
            ]);
        } catch (\RuntimeException $e) {
            // The online call reached the server but the answer was lost
            // (timeout), so the phone also saved it offline: same payment.
            $online = $this->sameOnlinePayment($enrollment, $item, $at);
            if ($online) {
                return ['duplicate', 'Already saved online (receipt ' . $online->receipt_number . ').', null, $online->id, (int) $online->branch_id];
            }

            throw $e;
        }

        $payment = $result['payment'] ?? $result['data'] ?? null;
        $payment = $payment instanceof Payment ? $payment : Payment::withTrashed()->where('local_uuid', $item['local_uuid'])->first();

        return ['synced', null, null, optional($payment)->id, optional($payment)->branch_id];
    }

    /**
     * A payment the same user saved online for the same class and month,
     * within 10 minutes of the offline one, with the same amount.
     */
    protected function sameOnlinePayment(StudentClassEnrollment $enrollment, array $item, Carbon $at): ?Payment
    {
        return Payment::query()
            ->where('student_class_enrollment_id', $enrollment->id)
            ->where('user_id', auth()->id())
            ->whereDate('payment_month', $item['payment_month'] . '-01')
            ->whereBetween('paid_at', [$at->copy()->subMinutes(10), $at->copy()->addMinutes(10)])
            ->whereBetween('amount', [(float) $item['amount'] - 0.005, (float) $item['amount'] + 0.005])
            ->whereNull('local_uuid')
            ->latest('id')
            ->first();
    }

    protected function result(OfflineSyncItem $row): array
    {
        $payment = $row->payment_id ? Payment::withTrashed()->find($row->payment_id) : null;

        return [
            'local_uuid' => $row->local_uuid,
            'status' => $row->status,
            'message' => $row->message,
            'receipt_number' => optional($payment)->receipt_number,
        ];
    }
}

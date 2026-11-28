<?php

namespace App\Services\Api\Offline;

use App\Http\Requests\Api\Student\MobileCardStudentRequest;
use App\Http\Requests\Api\Student\MobileTemporaryStudentRequest;
use App\Models\Admission;
use App\Models\ClassCategoryFeeOption;
use App\Models\ClassSchedule;
use App\Models\Grade;
use App\Models\OfflineSyncItem;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentCard;
use App\Models\StudentClassEnrollment;
use App\Models\TemporaryIdCard;
use App\Services\Api\StudentClass\StudentClassService;
use App\Services\Branch\BranchAccessService;
use App\Services\Notification\AttendanceNotificationService;
use App\Services\Web\Attendance\AttendanceMarker;
use App\Services\Web\Payment\PaymentMarker;
use App\Services\Web\Payment\PaymentService;
use App\Services\Web\Student\Registtration\StudentCardRegistrationService;
use App\Services\Web\Student\Registtration\StudentTemporaryRegistrationService;
use App\Services\Web\StudentClassEnrollment\StudentClassEnrollmentService;
use App\Support\AttendanceRules;
use App\Support\CardMode;
use App\Support\HallFee;
use App\Support\PaymentRules;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Staff app offline mode: attendance, payments, new students, class enrollments.
 *
 *  pack(): what the phone needs to work without internet today - today's
 *          classes, the branch's students (codes, name, grade), fees and
 *          months paid, the classes a student can join, the cards that can
 *          be given and the rules. No phone numbers or other personal details.
 *
 *  sync(): what the phone recorded offline, in the order it happened. Each
 *          item has a local_uuid from the phone, so sending it again returns
 *          the first result and saves nothing twice. Items keep the time
 *          they happened (attended_at / paid_at), which the teacher salary is
 *          made from. A student or class added offline has no server id on
 *          the phone: later items point to it with student_ref / enrollment_ref.
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
        protected StudentClassService $studentClassService,
        protected StudentClassEnrollmentService $enrollmentService,
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

        // every active class of the student in these branches, as the online
        // payment read (a fee is paid for any class, not only today's)
        $enrollments = StudentClassEnrollment::query()
            ->with([
                'student:id,custom_id,temporary_qr_code,temporary_qr_code_expire_date,permanent_qr_active,initial_name,full_name,grade_id,is_active,student_disable',
                'student.grade:id,grade_name',
                'classCategoryFeeOption:id,class_category_fee_id,label,fee,is_default,is_active',
            ])
            ->where('is_active', true)
            ->whereIn('branch_id', $branchIds)
            ->get();

        $paidMonths = $this->paidMonths($enrollments, $day);

        $students = [];
        $enrollmentRows = [];
        $hallFees = [];

        foreach ($enrollments as $enrollment) {
            $student = $enrollment->student;

            if (!$student || !$student->is_active || $student->student_disable) {
                continue;
            }

            $students[$student->id] ??= $this->studentRow($student, $day);

            // one hall fee query per class + fee category, not per student
            $hallKey = $enrollment->student_class_id . '|' . $enrollment->class_category_fee_id;
            $hallFees[$hallKey] ??= round($this->paymentMarker->hallFee($enrollment), 2);
            $hallFee = $hallFees[$hallKey];
            $gross = round((float) $enrollment->final_fee + $hallFee, 2);

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

        // the branch's other students: to add a class to them offline
        Student::query()
            ->with('grade:id,grade_name')
            ->whereIn('branch_id', $branchIds)
            ->where('is_active', true)
            ->whereNotIn('id', array_keys($students))
            ->get(['id', 'custom_id', 'temporary_qr_code', 'temporary_qr_code_expire_date', 'permanent_qr_active', 'initial_name', 'full_name', 'grade_id', 'is_active', 'student_disable'])
            ->reject(fn (Student $student) => (bool) $student->student_disable)
            ->each(function (Student $student) use (&$students, $day) {
                $students[$student->id] = $this->studentRow($student, $day);
            });

        $feeOptions = ClassCategoryFeeOption::query()
            ->whereIn('class_category_fee_id', $enrollments->pluck('class_category_fee_id')->unique())
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
            // add a class offline: as GET /api/student-classes/{grade} (+ hall fee)
            'classes' => $this->classRows($branchIds),
            // register a student offline
            'grades' => Grade::query()->where('is_active', true)->orderByDesc('id')->get(['id', 'grade_name']),
            'admissions' => Admission::active()->forRegistration()->orderBy('name')->get(['id', 'name', 'amount']),
            'registration' => $this->registrationPack($branchIds),
        ];
    }

    /** What the phone knows of a student: the codes a card may carry, name, grade. */
    protected function studentRow(Student $student, Carbon $day): array
    {
        return [
            'id' => $student->id,
            'custom_id' => $student->custom_id,
            'temporary_qr_code' => $student->temporary_qr_code,
            // what a card may carry: the permanent ID, or a temporary QR not expired yet
            'codes' => array_values(array_unique(array_filter([
                $student->permanent_qr_active ? $student->custom_id : null,
                $this->temporaryCodeValid($student, $day) ? $student->temporary_qr_code : null,
                // the ID typed by hand always finds the student (as the online scan)
                $student->custom_id,
            ], fn ($code) => $code !== null && $code !== ''))),
            'name' => $student->initial_name ?: $student->full_name,
            'initial_name' => $student->initial_name,
            'full_name' => $student->full_name,
            'grade_id' => $student->grade_id ? (int) $student->grade_id : null,
            'grade' => optional($student->grade)->grade_name,
        ];
    }

    /** Active classes of the branches, each fee category with its hall fee. */
    protected function classRows(array $branchIds): array
    {
        return array_map(function (array $class) {
            $class['category_fees'] = array_map(function (array $categoryFee) use ($class) {
                $categoryFee['hall_fee'] = HallFee::forClass((int) $class['class_id'], (int) $categoryFee['class_category_fee_id'])['fee'];

                return $categoryFee;
            }, $class['category_fees']);

            return $class;
        }, $this->studentClassService->classesForBranches(collect($branchIds)));
    }

    /**
     * Cards that can be given to a new student offline:
     *  permanent - the branch's printed cards not given yet
     *  temporary - the issued temporary cards not given yet (TMP045 ...)
     */
    protected function registrationPack(array $branchIds): array
    {
        $mode = CardMode::get();

        if ($mode === CardMode::TEMPORARY) {
            return [
                'card_mode' => $mode,
                'cards' => TemporaryIdCard::query()
                    ->where('status', 'issued')
                    ->whereNull('student_id')
                    ->orderByRaw('LENGTH(temporary_id_number)')
                    ->orderBy('temporary_id_number')
                    ->pluck('temporary_id_number')
                    ->map(fn ($code) => ['qr_code' => $code])
                    ->values(),
            ];
        }

        return [
            'card_mode' => $mode,
            'cards' => StudentCard::query()
                ->with('branch:id,name')
                ->where('status', 'available')
                ->whereNull('student_id')
                ->whereIn('branch_id', $branchIds)
                ->get(['id', 'qr_code', 'card_number', 'branch_id'])
                ->map(fn (StudentCard $card) => [
                    'qr_code' => $card->qr_code,
                    'card_number' => $card->card_number,
                    'branch_id' => (int) $card->branch_id,
                    'branch' => optional($card->branch)->name,
                ])
                ->values(),
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
            ->whereIn('e.student_class_id', $enrollments->pluck('student_class_id')->unique())
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
     * @return array<int, array{local_uuid: string, status: string, message: ?string}>
     */
    public function sync(string $deviceId, array $items): array
    {
        // in the order they happened (student before its class, attendance before the payment after it)
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

            $done = match ($item['type']) {
                'attendance' => $this->syncAttendance($item, $happenedAt),
                'payment' => $this->syncPayment($item, $happenedAt),
                'enrollment' => $this->syncEnrollment($item, $happenedAt),
                'student' => $this->syncStudent($item, $happenedAt),
            };
        } catch (WaitForEarlierItem $e) {
            // the student / class it belongs to has not reached the server yet
            return ['local_uuid' => $item['local_uuid'], 'status' => 'retry', 'message' => $e->getMessage()];
        } catch (QueryException $e) {
            report($e);

            return $this->retry($item);
        } catch (\RuntimeException $e) {
            // e.g. "Payment already completed for this class and month." - saved meanwhile
            $done = ['status' => $this->isConflict($e->getMessage()) ? 'conflict' : 'failed', 'message' => $e->getMessage()];
        } catch (\InvalidArgumentException $e) {
            $done = ['status' => 'failed', 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            report($e);

            return $this->retry($item);
        }

        try {
            $row = OfflineSyncItem::create([
                'local_uuid' => $item['local_uuid'],
                'device_id' => $deviceId,
                'user_id' => auth()->id(),
                'branch_id' => $done['branch_id'] ?? null,
                'type' => $item['type'],
                'status' => $done['status'],
                'message' => !empty($done['message']) ? mb_substr($done['message'], 0, 255) : null,
                'happened_at' => $happenedAt,
                'payload' => $this->storedPayload($item, $done),
                'student_attendance_id' => $done['attendance_id'] ?? null,
                'payment_id' => $done['payment_id'] ?? null,
                'student_id' => $done['student_id'] ?? null,
                'student_class_enrollment_id' => $done['enrollment_id'] ?? null,
            ]);
        } catch (QueryException $e) {
            // the same item sent twice at the same moment: the first one won
            $row = OfflineSyncItem::query()->where('local_uuid', $item['local_uuid'])->firstOrFail();
        }

        return $this->result($row);
    }

    /** not saved: the phone keeps it and sends it again */
    protected function retry(array $item): array
    {
        return [
            'local_uuid' => $item['local_uuid'],
            'status' => 'retry',
            'message' => 'Server error. It will be sent again.',
        ];
    }

    /** Refused because of something saved meanwhile (not wrong data). */
    protected function isConflict(string $message): bool
    {
        return (bool) preg_match('/already|taken|not available/i', $message);
    }

    /**
     * What is kept of the item for the Offline Sync page: no phone numbers or
     * other personal details of a new student (they are on the student).
     */
    protected function storedPayload(array $item, array $done): array
    {
        if (isset($item['student']) && is_array($item['student'])) {
            $item['student'] = array_intersect_key($item['student'], array_flip([
                'initial_name', 'grade_id', 'branch_id', 'temporary_qr_code', 'card_qr_code', 'admission_id',
            ]));
        }

        // the server ids it was saved with (an item that pointed to an offline student)
        foreach (['student_id', 'enrollment_id'] as $key) {
            if (!empty($done[$key])) {
                $item[$key] = $done[$key];
            }
        }

        return $item;
    }

    /* ---------------------------------------------------- offline refs */

    protected function studentIdOf(array $item): int
    {
        if (!empty($item['student_ref'])) {
            return $this->savedId($item['student_ref'], 'student', 'student_id', 'The new student');
        }

        return (int) ($item['student_id'] ?? 0);
    }

    protected function enrollmentIdOf(array $item): int
    {
        if (!empty($item['enrollment_ref'])) {
            return $this->savedId($item['enrollment_ref'], 'enrollment', 'student_class_enrollment_id', 'The class added');
        }

        return (int) ($item['enrollment_id'] ?? 0);
    }

    /** The server id made from an earlier offline item of the phone. */
    protected function savedId(string $ref, string $type, string $column, string $what): int
    {
        $row = OfflineSyncItem::query()->where('local_uuid', $ref)->where('type', $type)->first();

        if (!$row) {
            throw new WaitForEarlierItem(strtolower($what) . ' on this phone is not on the server yet.');
        }

        if (!$row->{$column}) {
            throw new \InvalidArgumentException($what . ' on the phone was not saved (' . ($row->message ?: $row->status) . ').');
        }

        return (int) $row->{$column};
    }

    /* ------------------------------------------------------ attendance */

    protected function syncAttendance(array $item, Carbon $at): array
    {
        $done = StudentAttendance::query()->where('local_uuid', $item['local_uuid'])->first();
        if ($done) {
            return ['status' => 'duplicate', 'message' => 'Already synced.', 'attendance_id' => $done->id, 'branch_id' => (int) $done->branch_id];
        }

        $studentId = $this->studentIdOf($item);
        $enrollment = StudentClassEnrollment::query()->with('student')->find($this->enrollmentIdOf($item));
        $schedule = ClassSchedule::query()->find($item['schedule_id']);

        if (!$enrollment || !$enrollment->student || (int) $enrollment->student_id !== $studentId) {
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

        $ids = ['student_id' => $studentId, 'enrollment_id' => $enrollment->id];

        if (!empty($result['already_marked'])) {
            return ['status' => 'duplicate', 'message' => 'Attendance was already marked for this class.', 'attendance_id' => optional($result['attendance'])->id, 'branch_id' => (int) $schedule->branch_id] + $ids;
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

        return ['status' => 'synced', 'attendance_id' => $result['attendance']->id, 'branch_id' => (int) $result['attendance']->branch_id] + $ids;
    }

    /* --------------------------------------------------------- payment */

    protected function syncPayment(array $item, Carbon $at): array
    {
        $done = Payment::withTrashed()->where('local_uuid', $item['local_uuid'])->first();
        if ($done) {
            return ['status' => 'duplicate', 'message' => 'Already synced.', 'payment_id' => $done->id, 'branch_id' => (int) $done->branch_id];
        }

        $studentId = $this->studentIdOf($item);
        $enrollment = StudentClassEnrollment::query()->with('student:id,custom_id')->find($this->enrollmentIdOf($item));
        if (!$enrollment || !$enrollment->student || (int) $enrollment->student_id !== $studentId) {
            throw new \InvalidArgumentException('Student class enrollment not found.');
        }

        $ids = ['student_id' => $studentId, 'enrollment_id' => $enrollment->id];

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
                return ['status' => 'duplicate', 'message' => 'Already saved online (receipt ' . $online->receipt_number . ').', 'payment_id' => $online->id, 'branch_id' => (int) $online->branch_id] + $ids;
            }

            throw $e;
        }

        $payment = $result['payment'] ?? $result['data'] ?? null;
        $payment = $payment instanceof Payment ? $payment : Payment::withTrashed()->where('local_uuid', $item['local_uuid'])->first();

        return ['status' => 'synced', 'payment_id' => optional($payment)->id, 'branch_id' => optional($payment)->branch_id] + $ids;
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

    /* ------------------------------------------------------ enrollment */

    /** A class added to a student offline (StudentClassEnrollmentService: the same rules as online). */
    protected function syncEnrollment(array $item, Carbon $at): array
    {
        $studentId = $this->studentIdOf($item);

        if (!Student::query()->whereKey($studentId)->exists()) {
            throw new \InvalidArgumentException('Student not found.');
        }

        try {
            $enrollment = $this->enrollmentService->create([
                'student_id' => $studentId,
                'student_class_id' => (int) $item['student_class_id'],
                'class_category_fee_id' => (int) $item['class_category_fee_id'],
                'class_category_fee_option_id' => (int) $item['class_category_fee_option_id'],
                'is_free_card' => !empty($item['is_free_card']),
                'enrolled_at' => $at->toDateString(),
                'note' => trim('Offline (staff app). ' . ($item['note'] ?? '')),
            ]);
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? 'Unable to add the class.';

            // added meanwhile (another phone / the web / an online call whose answer was lost)
            if (str_contains(strtolower($message), 'already enrolled')) {
                $existing = StudentClassEnrollment::query()
                    ->where('student_id', $studentId)
                    ->where('student_class_id', (int) $item['student_class_id'])
                    ->where('class_category_fee_id', (int) $item['class_category_fee_id'])
                    ->where('is_active', true)
                    ->first();

                return ['status' => 'duplicate', 'message' => $message, 'student_id' => $studentId, 'enrollment_id' => optional($existing)->id, 'branch_id' => optional($existing)->branch_id];
            }

            throw new \InvalidArgumentException($message);
        }

        return ['status' => 'synced', 'student_id' => $studentId, 'enrollment_id' => $enrollment->id, 'branch_id' => (int) $enrollment->branch_id];
    }

    /* --------------------------------------------------------- student */

    /**
     * A student registered offline: the same form rules and registration as
     * online (card mode of the institute), the admission payment with the
     * time and receipt of the phone.
     */
    protected function syncStudent(array $item, Carbon $at): array
    {
        $form = (array) $item['student'];
        $temporary = CardMode::get() === CardMode::TEMPORARY;
        $cardKey = $temporary ? 'temporary_qr_code' : 'card_qr_code';
        $form[$cardKey] = strtoupper(trim((string) ($form[$cardKey] ?? '')));

        if ($form[$cardKey] === '') {
            // the server would give "the next card" - not the one in the student's hand
            throw new \InvalidArgumentException('The card given to the student is missing.');
        }

        // saved already (online call whose answer was lost, or a sync cut off)
        $same = $this->sameStudent($form, $cardKey, $at);
        if ($same) {
            return ['status' => 'duplicate', 'message' => 'Already registered (' . $same->custom_id . ').', 'student_id' => $same->id, 'branch_id' => (int) $same->branch_id];
        }

        $validated = $this->validatedForm($temporary ? MobileTemporaryStudentRequest::class : MobileCardStudentRequest::class, $form);
        $service = app($temporary ? StudentTemporaryRegistrationService::class : StudentCardRegistrationService::class);

        try {
            $result = $service->register($validated, [
                'paid_at' => $at,
                'receipt_number' => $item['admission_receipt_number'] ?? null,
            ]);
        } catch (QueryException $e) {
            throw $e;
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            throw $e;
        } catch (\Exception $e) {
            // e.g. "This student card is not available." (StudentCardService)
            throw new \RuntimeException($e->getMessage(), 0, $e);
        }

        $student = $result['student'];

        return ['status' => 'synced', 'student_id' => $student->id, 'branch_id' => (int) $student->branch_id];
    }

    /** The student already made with this card and name, within 10 minutes (of the phone or of now). */
    protected function sameStudent(array $form, string $cardKey, Carbon $at): ?Student
    {
        $student = Student::query()
            ->where($cardKey === 'temporary_qr_code' ? 'temporary_qr_code' : 'custom_id', $form[$cardKey])
            ->first();

        if (!$student || !$student->created_at) {
            return null;
        }

        $sameName = mb_strtolower(trim((string) $student->initial_name)) === mb_strtolower(trim((string) ($form['initial_name'] ?? '')));
        $near = $student->created_at->diffInMinutes($at, true) <= 10 || $student->created_at->diffInMinutes(now(), true) <= 10;

        return $sameName && $near ? $student : null;
    }

    /** The registration form checked by the same FormRequest as the online call. */
    protected function validatedForm(string $requestClass, array $form): array
    {
        /** @var \Illuminate\Foundation\Http\FormRequest $request */
        $request = $requestClass::create('/api/offline/sync', 'POST', $form);
        $request->setContainer(app())->setRedirector(app('redirect'));
        $request->setUserResolver(fn () => auth()->user());

        try {
            $request->validateResolved();
        } catch (HttpResponseException $e) {
            $message = $e->getResponse()->getData(true)['message'] ?? 'The student details are not valid.';

            throw new \RuntimeException($message);
        } catch (ValidationException $e) {
            throw new \RuntimeException(collect($e->errors())->flatten()->first() ?? 'The student details are not valid.');
        }

        return $request->validated();
    }

    protected function result(OfflineSyncItem $row): array
    {
        $payment = $row->payment_id ? Payment::withTrashed()->find($row->payment_id) : null;
        $student = $row->type === 'student' && $row->student_id
            ? Student::query()->find($row->student_id, ['id', 'custom_id', 'temporary_qr_code'])
            : null;

        return [
            'local_uuid' => $row->local_uuid,
            'status' => $row->status,
            'message' => $row->message,
            'receipt_number' => optional($payment)->receipt_number,
            // a student / class added offline: its server ids (photo upload, later items)
            'student_id' => $row->student_id,
            'custom_id' => optional($student)->custom_id,
            'enrollment_id' => $row->student_class_enrollment_id,
        ];
    }
}

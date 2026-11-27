<?php

namespace App\Services\Web\MonthlyReport;

use App\Models\StudentClass;
use App\Support\MonthFee;
use App\Models\Teacher;
use App\Models\TeacherPayment;
use App\Models\User;
use App\Services\Branch\BranchAccessService;
use App\Services\Web\TeacherSalary\TeacherSalaryCalculator;
use App\Services\Web\TeacherSalary\TeacherSalaryService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Monthly reports:
 *  - Teacher salary summary (all teachers of one branch)
 *  - Teacher's students payment report (by fee month or by collection date)
 *
 * Salary numbers come from TeacherSalaryService, so this report always
 * matches the Teacher Salary pages.
 */
class MonthlyReportService
{
    /** payments.payment_month is in the selected month */
    public const BASIS_FEE_MONTH = 'fee-month';

    /** payments.paid_at is in the selected month */
    public const BASIS_PAID_DATE = 'paid-date';

    public const BASES = [
        self::BASIS_FEE_MONTH => 'Fee Month',
        self::BASIS_PAID_DATE => 'Collection Date',
    ];

    public function __construct(
        protected TeacherSalaryService $salaryService,
        protected TeacherSalaryCalculator $calculator,
        protected BranchAccessService $branchAccessService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Options for the page
    |--------------------------------------------------------------------------
    */

    /**
     * Active teachers in the branches the user can access.
     * A TEACHER user only gets himself.
     */
    public function teacherOptions(): Collection
    {
        $ownTeacherId = $this->ownTeacherId();

        return Teacher::query()
            ->select('id', 'custom_id', 'initials', 'full_name')
            ->where('is_active', true)
            ->when($ownTeacherId !== null, fn ($query) => $query->whereKey($ownTeacherId))
            ->whereHas('branches', function ($query) {
                $query->whereIn('branches.id', $this->branchAccessService->getBranchIds());
            })
            ->orderBy('custom_id')
            ->get();
    }

    public function branchOptions(): Collection
    {
        return $this->salaryService->branchOptions();
    }

    /**
     * @throws DomainException
     */
    public function resolveBranchId(?int $branchId): int
    {
        return $this->salaryService->resolveBranchId($branchId);
    }

    /*
    |--------------------------------------------------------------------------
    | Teacher salary summary
    |--------------------------------------------------------------------------
    */

    /**
     * One row per teacher for one branch and month.
     *
     * @return array{rows: array<int, array>, branch: \App\Models\Branch|null, summary: array}
     */
    public function teacherSalaryReport(int $branchId, int $year, int $month): array
    {
        $data = $this->salaryService->monthlyIncome($branchId, $year, $month);

        $initials = Teacher::withTrashed()
            ->whereIn('id', collect($data['data'])->pluck('teacher_id'))
            ->pluck('initials', 'id');

        $rows = collect($data['data'])
            ->map(function (array $row) use ($initials) {
                $totalDeductions = round($row['advance'] + $row['deduction'] + $row['other'] + ($row['welfare'] ?? 0), 2);

                return [
                    'teacher_id' => $row['teacher_id'],
                    'custom_id' => $row['teacher_custom_id'],
                    'initials' => $initials->get($row['teacher_id']),
                    'full_name' => $row['teacher_name'],

                    'gross_income' => $row['gross_earnings'],
                    'advance' => $row['advance'],
                    'deduction' => $row['deduction'],
                    'other' => $row['other'],
                    'welfare' => $row['welfare'] ?? 0,

                    // Old key name kept for the PDF view: all deductions together
                    'advance_deduction' => $totalDeductions,

                    'net_salary' => $row['net_payable'],
                    'salary_paid_status' => $row['status'],
                ];
            })
            ->values()
            ->all();

        return [
            'rows' => $rows,
            'branch' => $data['branch'],
            'summary' => $data['summary'],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Teacher's students payment report
    |--------------------------------------------------------------------------
    */

    /**
     * @param  string  $basis  self::BASIS_FEE_MONTH or self::BASIS_PAID_DATE
     * @param  int|null  $branchId  only this branch (the salary email); null = every branch the user can see
     *
     * @throws DomainException
     */
    public function teacherStudentPaymentReport(int $teacherId, int $year, int $month, string $basis, ?int $branchId = null): array
    {
        if (!array_key_exists($basis, self::BASES)) {
            throw new DomainException('Invalid report type.');
        }

        $this->assertTeacherAccess($teacherId);

        $teacher = Teacher::withTrashed()
            ->select('id', 'custom_id', 'initials', 'full_name')
            ->findOrFail($teacherId);

        [$start, $end] = $this->calculator->monthRange($year, $month);
        $byCollection = $basis === self::BASIS_PAID_DATE;

        $branchIds = $this->branchAccessService->getBranchIds()
            ->map(fn ($id) => (int) $id)
            ->when($branchId !== null, fn ($ids) => $ids->filter(fn ($id) => $id === $branchId))
            ->values();

        // Collection date = the payments the teacher's salary of the month is
        // made of (per branch, as the Teacher Salary page): received in the
        // month, plus earlier months' fees received after their salary.
        $salaryPayments = collect();
        if ($byCollection) {
            $salaryBranches = DB::table('teacher_branches')
                ->where('teacher_id', $teacherId)
                ->whereIn('branch_id', $branchIds)
                ->pluck('branch_id');

            foreach ($salaryBranches as $salaryBranch) {
                $salaryPayments = $salaryPayments->concat(
                    $this->salaryService->salaryPayments($teacherId, (int) $salaryBranch, $year, $month)['in_salary']
                );
            }
        }
        $collectedBy = $salaryPayments->groupBy('student_class_enrollment_id');

        // Fee month = completed payments FOR the month (whatever day received)
        $feeMonthPayments = function ($query) use ($start, $end) {
            $query->where('status', 'completed')
                ->whereBetween('payment_month', [$start->toDateString(), $end->toDateString()]);
        };
        $withMoney = $byCollection
            ? fn ($query) => $query->whereIn('id', $collectedBy->keys())
            : fn ($query) => $query->whereHas('payments', $feeMonthPayments);

        // Active classes and students, plus any with money in this report
        $classes = StudentClass::query()
            ->withTrashed()
            ->select('id', 'branch_id', 'class_name', 'grade_id', 'teacher_id', 'is_active', 'deleted_at')
            ->where('teacher_id', $teacherId)
            ->whereIn('branch_id', $branchIds)
            ->where(function ($query) use ($withMoney) {
                $query->where(fn ($q) => $q->where('is_active', true)->whereNull('deleted_at'))
                    ->orWhereHas('enrollments', $withMoney);
            })
            ->with([
                'grade:id,grade_name',
                'categoryFees' => fn ($query) => $query->select('id', 'student_class_id', 'class_category_id')
                    ->with('category:id,category_name')
                    ->orderBy('id'),
                'enrollments' => fn ($query) => $query
                    ->select('id', 'student_id', 'student_class_id', 'class_category_fee_id', 'class_category_fee_option_id', 'is_active', 'is_free_card')
                    ->where(fn ($q) => $q->where('is_active', true)->orWhere($withMoney))
                    ->with([
                        'student:id,permanent_qr_active,custom_id,temporary_qr_code,initial_name,guardian_mobile',
                        'classCategoryFeeOption:id,class_category_fee_id,label,fee',
                        'payments' => fn ($payments) => $payments
                            ->select('id', 'student_class_enrollment_id', 'amount', 'discount_amount', 'status', 'payment_month', 'paid_at')
                            ->where($feeMonthPayments)
                            ->with('splitSnapshot:id,payment_id,hall_fee,teacher_amount'),
                    ]),
            ])
            ->orderBy('class_name')
            ->get();

        $showPhones = $this->ownTeacherId() === null;
        $hallFeeNow = MonthFee::hallFeeCache();

        $summary = [
            'total_classes' => $classes->count(),
            'total_students' => 0,
            'paid_students' => 0,
            'partial_students' => 0,
            'unpaid_students' => 0,
            'freecard_students' => 0,
            'noattend_students' => 0,
            'total_collected' => 0.0,
            'total_payable' => 0.0,
            'total_balance' => 0.0,
            'hall_collected' => 0.0,
            'class_collected' => 0.0,
            'teacher_share' => 0.0,
            'payments' => 0,
        ];

        $reportClasses = [];

        foreach ($classes as $class) {
            $classCategories = [];

            foreach ($class->categoryFees as $fee) {
                $categoryEnrollments = $class->enrollments->where('class_category_fee_id', $fee->id)->values();

                // partly paid students are in "unpaid" (they still owe), status "partial"
                $groups = ['paid' => [], 'unpaid' => [], 'freecard' => [], 'noattend' => []];
                $totals = ['payable' => 0.0, 'collected' => 0.0, 'hall' => 0.0, 'class' => 0.0, 'teacher' => 0.0, 'balance' => 0.0];

                foreach ($categoryEnrollments as $enrollment) {
                    $row = $this->studentRow(
                        $enrollment,
                        $start,
                        $hallFeeNow($enrollment),
                        $byCollection ? $collectedBy->get($enrollment->id, collect()) : null,
                        $showPhones
                    );

                    $group = $row['status'] === MonthFee::PARTIAL ? 'unpaid' : $row['status'];
                    $groups[$group][] = $row;

                    $summary['total_students']++;
                    $summary[$row['status'] . '_students']++;
                    $summary['payments'] += $row['payment_count'];

                    $totals['payable'] += $row['payable'];
                    $totals['collected'] += $row['paid_amount'];
                    $totals['hall'] += $row['hall_collected'];
                    $totals['class'] += $row['class_collected'];
                    $totals['teacher'] += $row['teacher_amount'];
                    $totals['balance'] += $row['balance'];
                }

                $summary['total_payable'] += $totals['payable'];
                $summary['total_collected'] += $totals['collected'];
                $summary['hall_collected'] += $totals['hall'];
                $summary['class_collected'] += $totals['class'];
                $summary['teacher_share'] += $totals['teacher'];
                $summary['total_balance'] += $totals['balance'];

                $classCategories[] = [
                    'category_fee_id' => $fee->id,
                    'category_id' => $fee->class_category_id,
                    'category_name' => optional($fee->category)->category_name,
                    'fee_options' => $categoryEnrollments
                        ->map(fn ($enrollment) => $this->feeOption($enrollment))
                        ->filter()
                        ->unique('id')
                        ->values()
                        ->all(),
                    'total_students' => $categoryEnrollments->count(),
                    'paid_count' => count($groups['paid']),
                    'unpaid_count' => count($groups['unpaid']),
                    'partial_count' => collect($groups['unpaid'])->where('status', MonthFee::PARTIAL)->count(),
                    'freecard_count' => count($groups['freecard']),
                    // Payment Rules > no attendance (not counted as unpaid)
                    'noattend_count' => count($groups['noattend']),
                    'totals' => array_map(fn ($v) => round($v, 2), $totals),
                    'students' => $groups,
                ];
            }

            $reportClasses[] = [
                'class_id' => $class->id,
                'class_name' => $class->class_name,
                'grade_name' => optional($class->grade)->grade_name,
                'is_active' => (bool) $class->is_active && !$class->trashed(),
                'categories' => $classCategories,
            ];
        }

        foreach (['total_collected', 'total_payable', 'total_balance', 'hall_collected', 'class_collected', 'teacher_share'] as $key) {
            $summary[$key] = round($summary[$key], 2);
        }

        return [
            'teacher' => [
                'id' => $teacher->id,
                'custom_id' => $teacher->custom_id,
                'initials' => $teacher->initials,
                'full_name' => $teacher->full_name,
            ],
            'filter' => [
                'year' => $year,
                'month' => $month,
                'basis' => $basis,
                'basis_label' => self::BASES[$basis],
                'branch_id' => $branchId,
            ],
            'show_phones' => $showPhones,
            'summary' => $summary,
            'classes' => $reportClasses,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Teacher expenses (advance / deduction / other) for a month
    |--------------------------------------------------------------------------
    | (Replaces Admin\TeacherReportController teacher expense report.)
    | Cancelled records are listed for audit but not counted in the totals.
    */

    /**
     * @throws DomainException
     */
    public function teacherExpenseReport(int $teacherId, int $year, int $month): array
    {
        $this->assertTeacherAccess($teacherId);

        $teacher = Teacher::withTrashed()
            ->select('id', 'custom_id', 'initials', 'full_name')
            ->findOrFail($teacherId);

        [$start, $end] = $this->calculator->monthRange($year, $month);

        $payments = TeacherPayment::query()
            ->with(['createdBy:id,name', 'branch:id,name'])
            ->where('teacher_id', $teacherId)
            ->whereIn('branch_id', $this->branchAccessService->getBranchIds())
            ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();

        $rows = $payments->map(fn (TeacherPayment $payment) => [
            'payment_date' => optional($payment->payment_date)->format('Y-m-d'),
            'branch' => optional($payment->branch)->name,
            'payment_type' => ucfirst($payment->payment_type),
            'reason' => $payment->reason,
            'note' => $payment->note,
            'status' => ucfirst($payment->status),
            'created_by' => optional($payment->createdBy)->name,
            // Cancelled rows show their amount but count as 0 in the total column
            'amount' => (float) $payment->amount,
            'counted_amount' => $payment->status === 'cancelled' ? 0.0 : (float) $payment->amount,
        ]);

        $counted = $payments->where('status', '!=', 'cancelled');

        return [
            'teacher' => $teacher,
            'month_label' => $start->format('F Y'),
            'rows' => $rows,
            'summary' => [
                'advance' => round((float) $counted->where('payment_type', 'advance')->sum('amount'), 2),
                'deduction' => round((float) $counted->where('payment_type', 'deduction')->sum('amount'), 2),
                'other' => round((float) $counted->where('payment_type', 'other')->sum('amount'), 2),
                'total' => round((float) $counted->sum('amount'), 2),
                'cancelled' => $payments->where('status', 'cancelled')->count(),
            ],
        ];
    }

    public function teacherExpenseTable(): array
    {
        return [
            'headings' => ['Date', 'Branch', 'Type', 'Reason', 'Note', 'Status', 'Added By', 'Amount (Rs.)', 'Counted (Rs.)'],
            'columns' => ['payment_date', 'branch', 'payment_type', 'reason', 'note', 'status', 'created_by', 'amount', 'counted_amount'],
            'money' => ['amount', 'counted_amount'],
            'total' => 'counted_amount',
            // "Amount" includes cancelled rows, so only "Counted" is totalled
            'no_total' => ['amount'],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Access
    |--------------------------------------------------------------------------
    */

    /**
     * A TEACHER can only see his own report.
     * Other users can only see teachers of branches they can access.
     *
     * @throws DomainException
     */
    public function assertTeacherAccess(int $teacherId): void
    {
        $ownTeacherId = $this->ownTeacherId();

        if ($ownTeacherId !== null && $ownTeacherId !== $teacherId) {
            throw new DomainException('You can only view your own report.');
        }

        $hasAccess = DB::table('teacher_branches')
            ->where('teacher_id', $teacherId)
            ->whereIn('branch_id', $this->branchAccessService->getBranchIds())
            ->exists();

        if (!$hasAccess) {
            throw new DomainException('You do not have access to this teacher.');
        }
    }

    /**
     * Teacher id of the logged-in user when the user is a TEACHER, otherwise null.
     */
    protected function ownTeacherId(): ?int
    {
        $user = auth()->user();

        if (!$user instanceof User || !$user->userType) {
            return null;
        }

        if (strtoupper($user->userType->code) !== 'TEACHER') {
            return null;
        }

        // -1 never matches, so a TEACHER without a profile sees nothing
        return optional($user->teacher)->id ?? -1;
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * One student: this month's fee (class fee + hall fee, paid or not) and
     * the money in the report - the month's payments (fee month) or the
     * salary's payments (collection date, any fee month).
     *
     * @param  Collection<int, \App\Models\PaymentSplitSnapshot>|null  $collected  salary payments (collection date)
     */
    protected function studentRow($enrollment, Carbon $month, float $hallFeeNow, ?Collection $collected, bool $showPhones): array
    {
        $student = $enrollment->student;
        $fee = MonthFee::forEnrollment($enrollment, $enrollment->payments, $hallFeeNow);
        $status = $fee['status'];
        $attendance = null;

        // Payment Rules > no attendance: not attended this month, so no payment is taken
        if ($status === MonthFee::UNPAID && \App\Support\PaymentRules::noAttendance()) {
            $attendance = \App\Services\Web\Payment\PaymentMonthInfoService::attendanceCount($enrollment->id, $month);

            if ($attendance === 0) {
                $status = 'noattend';
            }
        }

        if ($collected !== null) {
            // salary payments: amount, hall part, teacher's share, any fee month
            $amount = round((float) $collected->sum('payment_amount'), 2);
            $hall = round($collected->sum(fn ($s) => MonthFee::hallPart((float) $s->payment_amount, $s->hall_fee)), 2);
            $teacher = round((float) $collected->sum('teacher_amount'), 2);
            $paidFor = $collected->map(fn ($s) => optional($s->payment)->payment_month);
            $paidOn = $collected->pluck('payment_date');
            $count = $collected->count();
            $carried = $collected->contains(fn ($s) => $s->is_carried ?? false);
        } else {
            $amount = $fee['paid'];
            $hall = $fee['hall_paid'];
            $teacher = round((float) $enrollment->payments->sum(fn ($p) => (float) optional($p->splitSnapshot)->teacher_amount), 2);
            $paidFor = $enrollment->payments->pluck('payment_month');
            $paidOn = $enrollment->payments->pluck('paid_at');
            $count = $enrollment->payments->count();
            $carried = false;
        }

        return [
            'student_id' => optional($student)->id,
            'student_code' => optional($student)->permanent_qr_active
                ? optional($student)->custom_id
                : optional($student)->temporary_qr_code,
            'initial_name' => optional($student)->initial_name,
            'guardian_mobile' => $showPhones ? optional($student)->guardian_mobile : null,
            'is_free_card' => (bool) $enrollment->is_free_card,
            'fee_option' => $this->feeOption($enrollment),

            // this month's fee
            'class_fee' => $fee['class_fee'],
            'hall_fee' => $fee['hall_fee'],
            'payable' => $fee['payable'],
            'final_fee' => $fee['payable'],
            'month_paid' => $fee['paid'],
            'discount' => $fee['discount'],
            'balance' => $status === 'noattend' ? 0.0 : $fee['balance'],
            'status' => $status,
            'attendance' => $attendance,

            // money in this report
            'paid_amount' => $amount,
            'hall_collected' => $hall,
            'class_collected' => round($amount - $hall, 2),
            'teacher_amount' => $teacher,
            'payment_count' => $count,
            'carried' => $carried,
            'paid_for' => $paidFor->filter()
                ->map(fn ($m) => Carbon::parse($m)->startOfMonth())
                ->sort()
                ->map(fn (Carbon $m) => $m->format('M Y'))
                ->unique()
                ->implode(', '),
            'paid_on' => $paidOn->filter()
                ->map(fn ($d) => Carbon::parse($d))
                ->sort()
                ->map(fn (Carbon $d) => $d->format('d M Y'))
                ->unique()
                ->implode(', '),
        ];
    }

    protected function feeOption($enrollment): ?array
    {
        $option = $enrollment->classCategoryFeeOption;

        if (!$option) {
            return null;
        }

        return [
            'id' => $option->id,
            'label' => $option->label,
            'fee' => (float) $option->fee,
        ];
    }
}
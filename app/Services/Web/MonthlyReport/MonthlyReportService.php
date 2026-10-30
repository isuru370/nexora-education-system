<?php

namespace App\Services\Web\MonthlyReport;

use App\Models\StudentClass;
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
                $totalDeductions = round($row['advance'] + $row['deduction'] + $row['other'], 2);

                return [
                    'teacher_id' => $row['teacher_id'],
                    'custom_id' => $row['teacher_custom_id'],
                    'initials' => $initials->get($row['teacher_id']),
                    'full_name' => $row['teacher_name'],

                    'gross_income' => $row['gross_earnings'],
                    'advance' => $row['advance'],
                    'deduction' => $row['deduction'],
                    'other' => $row['other'],

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
     *
     * @throws DomainException
     */
    public function teacherStudentPaymentReport(int $teacherId, int $year, int $month, string $basis): array
    {
        if (!array_key_exists($basis, self::BASES)) {
            throw new DomainException('Invalid report type.');
        }

        $this->assertTeacherAccess($teacherId);

        $teacher = Teacher::withTrashed()
            ->select('id', 'custom_id', 'initials', 'full_name')
            ->findOrFail($teacherId);

        [$start, $end] = $this->calculator->monthRange($year, $month);

        // Completed payments that belong to this report (by fee month or by collection date)
        $inPeriod = function ($paymentQuery) use ($basis, $start, $end) {
            $paymentQuery->where('status', 'completed');

            if ($basis === self::BASIS_FEE_MONTH) {
                $paymentQuery->whereBetween('payment_month', [$start->toDateString(), $end->toDateString()]);
            } else {
                $paymentQuery->whereBetween('paid_at', [$start, $end]);
            }
        };

        // Active classes always; an inactive class only for a month it still has
        // payments in (e.g. the month it was closed), so later months skip it.
        // Students: active enrollments, plus anyone who paid in this period.
        $classes = StudentClass::query()
            ->select('id', 'branch_id', 'class_name', 'grade_id', 'teacher_id', 'is_active')
            ->where('teacher_id', $teacherId)
            ->whereIn('branch_id', $this->branchAccessService->getBranchIds())
            ->where(function ($query) use ($inPeriod) {
                $query->where('is_active', true)
                    ->orWhereHas('enrollments.payments', $inPeriod);
            })
            ->with([
                'grade:id,grade_name',
                'categoryFees' => function ($query) {
                    $query->select('id', 'student_class_id', 'class_category_id')
                        ->with('category:id,category_name')
                        ->orderBy('id');
                },
                'enrollments' => function ($query) use ($inPeriod) {
                    $query->select(
                        'id',
                        'student_id',
                        'student_class_id',
                        'class_category_fee_id',
                        'class_category_fee_option_id',
                        'is_active',
                        'is_free_card'
                    )
                        ->where(function ($enrollmentQuery) use ($inPeriod) {
                            $enrollmentQuery->where('is_active', true)
                                ->orWhereHas('payments', $inPeriod);
                        })
                        ->with([
                            'student:id,permanent_qr_active,custom_id,temporary_qr_code,initial_name,guardian_mobile',
                            'classCategoryFeeOption:id,class_category_fee_id,label,fee',
                            'payments' => function ($paymentQuery) use ($inPeriod) {
                                $paymentQuery->select('id', 'student_class_enrollment_id', 'amount', 'discount_amount', 'status', 'payment_month', 'paid_at');
                                $inPeriod($paymentQuery);
                            },
                        ]);
                },
            ])
            ->orderBy('class_name')
            ->get();

        $summary = [
            'total_classes' => $classes->count(),
            'total_students' => 0,
            'paid_students' => 0,
            'unpaid_students' => 0,
            'freecard_students' => 0,
            'nofee_students' => 0,
            'total_collected' => 0,
        ];

        $reportClasses = [];

        foreach ($classes as $class) {
            $classCategories = [];

            foreach ($class->categoryFees as $fee) {
                $categoryEnrollments = $class->enrollments
                    ->where('class_category_fee_id', $fee->id)
                    ->values();

                $groups = ['paid' => [], 'unpaid' => [], 'freecard' => [], 'nofee' => []];

                foreach ($categoryEnrollments as $enrollment) {
                    $studentRow = $this->studentRow($enrollment, $start);

                    $groups[$studentRow['status']][] = $studentRow;

                    $summary['total_students']++;
                    $summary[$studentRow['status'] . '_students']++;
                    $summary['total_collected'] += $studentRow['paid_amount'];
                }

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
                    'freecard_count' => count($groups['freecard']),
                    // Payment Rules: attendance gives a 0 % fee for this month
                    'nofee_count' => count($groups['nofee']),

                    'students' => $groups,
                ];
            }

            $reportClasses[] = [
                'class_id' => $class->id,
                'class_name' => $class->class_name,
                'grade_name' => optional($class->grade)->grade_name,
                'is_active' => (bool) $class->is_active,
                'categories' => $classCategories,
            ];
        }

        $summary['total_collected'] = round($summary['total_collected'], 2);

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
            ],
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

    protected function studentRow($enrollment, ?\Carbon\CarbonInterface $month = null): array
    {
        $student = $enrollment->student;
        $paidAmount = round((float) $enrollment->payments->sum('amount'), 2);
        $finalFee = (float) $enrollment->final_fee;
        $attendance = null;

        if ($enrollment->is_free_card) {
            $status = 'freecard';
        } elseif ($finalFee <= 0 || $paidAmount >= $finalFee || $this->paidWithDiscount($enrollment, $finalFee)) {
            $status = 'paid';
        } else {
            $status = 'unpaid';

            // Payment Rules: no fee this month for this attendance (e.g. 0 times)
            if ($month && \App\Support\PaymentRules::attendanceRule()) {
                $attendance = \App\Services\Web\Payment\PaymentMonthInfoService::attendanceCount($enrollment->id, $month);

                if (\App\Support\PaymentRules::percentFor($attendance) === 0) {
                    $status = 'nofee';
                }
            }
        }

        return [
            'student_id' => optional($student)->id,
            'student_code' => optional($student)->permanent_qr_active
                ? optional($student)->custom_id
                : optional($student)->temporary_qr_code,
            'initial_name' => optional($student)->initial_name,
            'guardian_mobile' => optional($student)->guardian_mobile,
            'is_free_card' => (bool) $enrollment->is_free_card,
            'fee_option' => $this->feeOption($enrollment),
            'final_fee' => $finalFee,
            'paid_amount' => $paidAmount,
            'balance' => max(round($finalFee - $paidAmount, 2), 0),
            'status' => $status,

            // Which fee month(s) the payments were for, and when they were paid
            'paid_for' => $enrollment->payments
                ->pluck('payment_month')
                ->filter()
                ->map(fn ($month) => Carbon::parse($month)->startOfMonth())
                ->sort()
                ->map(fn (Carbon $month) => $month->format('M Y'))
                ->unique()
                ->implode(', '),
            'paid_on' => $enrollment->payments
                ->pluck('paid_at')
                ->filter()
                ->map(fn ($date) => Carbon::parse($date))
                ->sort()
                ->map(fn (Carbon $date) => $date->format('d M Y'))
                ->unique()
                ->implode(', '),
        ];
    }

    /**
     * A payment with a discount (custom amount / attendance rule) pays the
     * month even though the amount is below the fee.
     */
    protected function paidWithDiscount($enrollment, float $finalFee): bool
    {
        return $enrollment->payments->contains(
            fn ($payment) => (float) $payment->amount + (float) ($payment->discount_amount ?? 0) >= $finalFee
        );
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
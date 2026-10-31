<?php

namespace App\Services\Web\TeacherSalary;

use App\Models\Branch;
use App\Models\PaymentSplitSnapshot;
use App\Models\StudentClass;
use App\Models\Teacher;
use App\Models\TeacherPayment;
use App\Models\TeacherSalary;
use App\Services\Branch\BranchAccessService;
use App\Services\Web\Common\AmountInWords;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read side of teacher salaries (pages, slip, summary).
 * Used by the web controller and can be used by the mobile (AdminApp) controller.
 *
 * Writes (pay salary, add/delete advance or deduction) are in TeacherSalaryPaymentService.
 */
class TeacherSalaryService
{
    public function __construct(
        protected TeacherSalaryCalculator $calculator,
        protected BranchAccessService $branchAccessService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Branch
    |--------------------------------------------------------------------------
    */

    /**
     * Branches the logged-in user can choose.
     * When a teacher is given, only branches of that teacher are returned.
     */
    public function branchOptions(?int $teacherId = null): Collection
    {
        $branches = $this->branchAccessService->getAccessibleBranches();

        if ($teacherId === null) {
            return $branches;
        }

        $teacherBranchIds = $this->teacherBranchIds($teacherId);

        return $branches
            ->filter(fn(Branch $branch) => $teacherBranchIds->contains((int) $branch->id))
            ->values();
    }

    /**
     * Returns a branch id the user can access.
     * When $branchId is null, the first allowed branch (of the teacher, if given) is used.
     *
     * @throws DomainException
     */
    public function resolveBranchId(?int $branchId, ?int $teacherId = null): int
    {
        $allowedIds = $this->branchAccessService
            ->getBranchIds()
            ->map(fn($id) => (int) $id);

        if ($branchId !== null) {
            if (!$allowedIds->contains($branchId)) {
                throw new DomainException('You do not have access to this branch.');
            }

            return $branchId;
        }

        $candidates = $teacherId !== null
            ? $allowedIds->intersect($this->teacherBranchIds($teacherId))
            : $allowedIds;

        $firstId = $candidates->sort()->first();

        if ($firstId === null) {
            throw new DomainException(
                $teacherId !== null
                    ? 'This teacher is not assigned to any branch you can access.'
                    : 'You do not have access to any branch.'
            );
        }

        return (int) $firstId;
    }

    /**
     * @throws DomainException
     */
    public function assertBranchAccess(int $branchId): void
    {
        if (!$this->branchAccessService->canAccessBranch($branchId)) {
            throw new DomainException('You do not have access to this branch.');
        }
    }

    protected function teacherBranchIds(int $teacherId): Collection
    {
        return DB::table('teacher_branches')
            ->where('teacher_id', $teacherId)
            ->pluck('branch_id')
            ->map(fn($id) => (int) $id);
    }

    /*
    |--------------------------------------------------------------------------
    | Index: all teachers of a branch for a month
    |--------------------------------------------------------------------------
    */

    public function monthlyIncome(int $branchId, int $year, int $month): array
    {
        $this->assertBranchAccess($branchId);

        [$start] = $this->calculator->monthRange($year, $month);

        // 3 grouped queries instead of 3 queries per teacher
        $grossByTeacher = $this->calculator->grossByTeacher($branchId, $year, $month);
        $deductionsByTeacher = $this->calculator->deductionsByTeacher($branchId, $year, $month);

        $salaryRecords = TeacherSalary::query()
            ->where('branch_id', $branchId)
            ->where('salary_year', $year)
            ->where('salary_month', $month)
            ->get()
            ->keyBy('teacher_id');

        // Active teachers of this branch + any teacher who has money in this branch this month
        $teacherIds = DB::table('teacher_branches')
            ->join('teachers', 'teachers.id', '=', 'teacher_branches.teacher_id')
            ->where('teacher_branches.branch_id', $branchId)
            ->where('teachers.is_active', true)
            ->whereNull('teachers.deleted_at')
            ->pluck('teacher_branches.teacher_id')
            ->merge($grossByTeacher->keys())
            ->merge($deductionsByTeacher->keys())
            ->merge($salaryRecords->keys())
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        $teachers = Teacher::withTrashed()
            ->whereIn('id', $teacherIds)
            ->orderBy('full_name')
            ->get(['id', 'custom_id', 'full_name']);

        $rows = [];

        $summary = [
            'gross_earnings' => 0,
            'total_advance' => 0,
            'total_deduction' => 0,
            'total_other' => 0,
            'total_welfare' => 0,
            'calculated_net_total' => 0,
            'db_net_total' => 0,
            'net_difference_total' => 0,
            'net_payable' => 0,
            'total_teachers' => 0,
            'paid_teachers' => 0,
            'pending_teachers' => 0,
        ];

        foreach ($teachers as $teacher) {
            $totals = $this->calculator->buildTotals(
                (float) $grossByTeacher->get($teacher->id, 0.0),
                $deductionsByTeacher->get($teacher->id, $this->calculator->emptyDeductions())
            );

            /** @var TeacherSalary|null $record */
            $record = $salaryRecords->get($teacher->id);

            $isPaid = $record && $record->status === 'paid';
            $dbNet = $record ? (float) $record->net_amount : null;
            $netPayable = $isPaid ? $dbNet : $totals['net'];
            $netDifference = $isPaid ? round($totals['net'] - $dbNet, 2) : 0;

            $rows[] = [
                'teacher_id' => $teacher->id,
                'teacher_custom_id' => $teacher->custom_id,
                'teacher_name' => $teacher->full_name,

                'gross_earnings' => $totals['gross'],
                'advance' => $totals['advance'],
                'deduction' => $totals['deduction'],
                'other' => $totals['other'],
                'welfare' => $totals['welfare'],

                'calculated_net' => $totals['net'],
                'net_payable' => $netPayable,

                'status' => $record ? $record->status : 'pending',
                'net_difference' => $netDifference,
            ];

            $summary['gross_earnings'] += $totals['gross'];
            $summary['total_advance'] += $totals['advance'];
            $summary['total_deduction'] += $totals['deduction'];
            $summary['total_other'] += $totals['other'];
            $summary['total_welfare'] += $totals['welfare'];
            $summary['calculated_net_total'] += $totals['net'];
            $summary['db_net_total'] += $dbNet ?? 0;
            $summary['net_difference_total'] += $netDifference;
            $summary['net_payable'] += $netPayable;
            $summary['total_teachers']++;
            $summary[$isPaid ? 'paid_teachers' : 'pending_teachers']++;
        }

        return [
            'data' => $rows,
            'summary' => array_map(
                fn($value) => is_float($value) ? round($value, 2) : $value,
                $summary
            ),
            'month_name' => $start->format('F'),
            'branch' => Branch::query()->find($branchId),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Show: one teacher, one branch, one month
    |--------------------------------------------------------------------------
    */

    public function getTeacherSalaryDetails(int $teacherId, int $branchId, int $year, int $month): array
    {
        $this->assertBranchAccess($branchId);

        $teacher = Teacher::withTrashed()->findOrFail($teacherId);

        [$start, $end] = $this->calculator->monthRange($year, $month);

        $snapshotTotals = PaymentSplitSnapshot::query()
            ->where('branch_id', $branchId)
            ->where('teacher_id', $teacherId)
            ->whereBetween('payment_date', [$start, $end])
            ->selectRaw('
                COALESCE(SUM(payment_amount), 0) as class_income,
                COALESCE(SUM(institution_amount), 0) as institute_amount,
                COALESCE(SUM(organizer_amount), 0) as organizer_amount
            ')
            ->first();

        $totals = $this->calculator->forTeacher($teacherId, $branchId, $year, $month);
        $record = $this->calculator->salaryRecord($teacherId, $branchId, $year, $month);
        $isPaid = $record && $record->status === 'paid';

        $paymentDetails = $this->paymentsQuery($teacherId, $branchId, $year, $month)
            ->get()
            ->map(fn(TeacherPayment $payment) => [
                'id' => $payment->id,
                'type' => $payment->payment_type,
                'amount' => (float) $payment->amount,
                'date' => $payment->payment_date,
                'reason' => $payment->reason,
            ])
            ->values();

        $payBlockReason = $this->calculator->payBlockReason($year, $month, $record, $totals);
        $paymentBlockReason = $this->calculator->paymentBlockReason($year, $month, $record);

        return [
            'teacher' => $teacher,
            'branch' => Branch::query()->find($branchId),
            'branch_id' => $branchId,
            'year' => $year,
            'month' => $month,
            'period' => $start->format('Y-m'),
            'month_start' => $start->toDateString(),
            'month_end' => min($end, now()->endOfDay())->toDateString(),

            'total_class_income' => round((float) $snapshotTotals->class_income, 2),
            'teacher_earnings' => $totals['gross'],
            'institute_earnings' => round((float) $snapshotTotals->institute_amount, 2),
            'organizer_earnings' => round((float) $snapshotTotals->organizer_amount, 2),

            'advance' => $totals['advance'],
            'deduction' => $totals['deduction'],
            'other' => $totals['other'],
            'welfare' => $totals['welfare'],
            'total_deductions' => $totals['total_deductions'],

            // When paid, show what was actually paid
            'net_payable' => $isPaid ? (float) $record->net_amount : $totals['net'],
            'calculated_net' => $totals['net'],
            'available_limit' => $totals['available_limit'],

            'salary_paid' => $isPaid ? (float) $record->net_amount : 0,
            'salary_status' => $record ? $record->status : 'pending',
            'paid_at' => $isPaid ? $record->paid_at : null,

            'can_pay' => $payBlockReason === null,
            'pay_block_reason' => $payBlockReason,
            'can_add_payment' => $paymentBlockReason === null,
            'payment_block_reason' => $paymentBlockReason,

            'payment_details' => $paymentDetails,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Payment details page (advance / deduction / other list)
    |--------------------------------------------------------------------------
    */

    public function teacherAllPayments(int $teacherId, int $branchId, int $year, int $month): array
    {
        $details = $this->getTeacherSalaryDetails($teacherId, $branchId, $year, $month);

        $payments = $this->paymentsQuery($teacherId, $branchId, $year, $month)
            ->with('createdBy:id,name')
            ->orderByDesc('payment_date')
            ->orderBy('payment_type')
            ->get();

        $record = $this->calculator->salaryRecord($teacherId, $branchId, $year, $month);

        return [
            'teacher' => $details['teacher'],
            'branch' => $details['branch'],
            'branch_id' => $branchId,
            'year' => $year,
            'month' => $month,
            'month_name' => $this->calculator->monthRange($year, $month)[0]->format('F'),

            'payments' => $payments,
            'payments_count' => $payments->count(),

            'totals' => [
                'advance' => $details['advance'],
                'deduction' => $details['deduction'],
                'other' => $details['other'],
                'welfare' => $details['welfare'],
                'all' => $details['total_deductions'],
            ],

            'is_salary_paid' => $details['salary_status'] === 'paid',
            'salary_status' => $details['salary_status'],
            'salary_paid_amount' => $details['salary_paid'],
            'salary_record' => $record,
            'has_salary_record' => $record !== null,
            'has_any_payments' => $payments->isNotEmpty(),

            // Income breakdown + net payable used by the page
            'data' => $details,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Salary slip
    |--------------------------------------------------------------------------
    */

    public function salarySlip(int $teacherId, int $branchId, int $year, int $month): array
    {
        $this->assertBranchAccess($branchId);

        $teacher = Teacher::withTrashed()->findOrFail($teacherId);

        [$start, $end] = $this->calculator->monthRange($year, $month);

        $snapshots = PaymentSplitSnapshot::query()
            ->with([
                'studentClass' => fn($query) => $query->withTrashed(),
                'studentClass.subject:id,subject_name',
                'studentClass.grade:id,grade_name',
            ])
            ->where('branch_id', $branchId)
            ->where('teacher_id', $teacherId)
            ->whereBetween('payment_date', [$start, $end])
            ->get();

        $earnings = [];
        $totalClassAmount = 0;
        $totalOrganizeCut = 0;
        $totalInstitutionCut = 0;

        foreach ($snapshots->groupBy('student_class_id') as $items) {
            $first = $items->first();
            $class = $first->studentClass;

            $subject = optional(optional($class)->subject)->subject_name;
            $grade = optional(optional($class)->grade)->grade_name;

            $className = $subject
                ? $subject . ($grade ? ' - Grade ' . $grade : '')
                : (optional($class)->class_name ?: 'Unknown Class');

            $classTotal = round((float) $items->sum('payment_amount'), 2);
            $teacherAmount = round((float) $items->sum('teacher_amount'), 2);
            $organizerAmount = round((float) $items->sum('organizer_amount'), 2);
            $institutionAmount = round((float) $items->sum('institution_amount'), 2);

            $earnings[] = [
                'description' => $className . ' Teacher Salary',
                'class_total' => $classTotal,

                'teacher_percentage' => (float) ($first->teacher_percentage ?? 0),
                'teacher_share' => $teacherAmount,

                'organize_percentage' => (float) ($first->organizer_percentage ?? 0),
                'organize' => $organizerAmount,

                'institution_percentage' => (float) ($first->institution_percentage ?? 0),
                'institution_cut' => $institutionAmount,

                'amount' => $teacherAmount,
            ];

            $totalClassAmount += $classTotal;
            $totalOrganizeCut += $organizerAmount;
            $totalInstitutionCut += $institutionAmount;
        }

        $totals = $this->calculator->forTeacher($teacherId, $branchId, $year, $month);
        $record = $this->calculator->salaryRecord($teacherId, $branchId, $year, $month);
        $isPaid = $record && $record->status === 'paid';

        $deductions = [];

        foreach (['advance' => 'Advance', 'deduction' => 'Deduction', 'other' => 'Other Expenses', 'welfare' => 'Welfare'] as $key => $label) {
            if ($totals[$key] > 0) {
                $deductions[] = ['description' => $label, 'amount' => $totals[$key]];
            }
        }

        // A paid slip must show the amounts that were actually paid
        $totalAddition = $isPaid ? (float) $record->gross_amount : $totals['gross'];
        $totalDeductions = $isPaid
            ? round((float) $record->advance_deduction + (float) $record->other_deduction + (float) $record->welfare_deduction, 2)
            : $totals['total_deductions'];
        $netSalary = $isPaid ? (float) $record->net_amount : $totals['net'];

        return [
            'status' => 'success',

            'teacher_id' => $teacher->id,
            'teacher_custom_id' => $teacher->custom_id,
            'teacher_name' => $teacher->full_name,
            'branch_name' => optional(Branch::query()->find($branchId))->name,

            'month_year_display' => $start->format('F Y'),
            'date_generated' => now()->format('d M Y, h:i A'),
            'paid_at' => $isPaid && $record->paid_at ? $record->paid_at->format('d M Y') : null,

            'total_class_amount' => round($totalClassAmount, 2),
            'total_teacher_earnings' => $totalAddition,
            'total_organize_cut' => round($totalOrganizeCut, 2),
            'total_institution_cut' => round($totalInstitutionCut, 2),

            'total_addition' => $totalAddition,
            'total_deductions' => $totalDeductions,
            'net_salary' => $netSalary,
            'net_salary_words' => AmountInWords::rupees($netSalary),

            'is_salary_paid' => $isPaid,

            'earnings' => $earnings,
            'deductions' => $deductions,

            'payment_method' => 'Cash / Bank Transfer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Class-wise payment summary
    |--------------------------------------------------------------------------
    */

    public function teacherPaymentAndClassSummary(int $teacherId, int $branchId, int $year, int $month): array
    {
        $this->assertBranchAccess($branchId);

        Teacher::withTrashed()->findOrFail($teacherId);

        $teacherTotal = (float) $this->calculator
            ->grossByTeacher($branchId, $year, $month, [$teacherId])
            ->get($teacherId, 0.0);

        $classes = StudentClass::query()
            ->where('branch_id', $branchId)
            ->where('teacher_id', $teacherId)
            ->where('is_active', true)
            ->with([
                'categoryFees.category:id,category_name',
                'categoryFees.feeOptions' => function ($query) {
                    $query->where('is_active', true)
                        ->orderByDesc('is_default')
                        ->orderBy('id');
                },
                'grade:id,grade_name',
                'enrollments' => function ($query) use ($year, $month) {
                    $query->where('is_active', true)
                        ->with('classCategoryFeeOption')
                        ->withSum([
                            'payments as monthly_paid_amount' => function ($paymentQuery) use ($year, $month) {
                                $paymentQuery->where('status', 'completed')
                                    ->whereYear('paid_at', $year)
                                    ->whereMonth('paid_at', $month);
                            },
                        ], 'amount');
                },
            ])
            ->orderBy('class_name')
            ->get();

        $classSummaries = [];
        $grandStudentCount = 0;

        foreach ($classes as $class) {
            $classEnrollments = $class->enrollments;
            $classStudentCount = $classEnrollments->count();
            $grandStudentCount += $classStudentCount;

            $classTotal = 0;
            $categorySummaries = [];

            foreach ($class->categoryFees as $categoryFee) {
                $enrollments = $classEnrollments->where('class_category_fee_id', $categoryFee->id);

                $paidStudents = 0;
                $partialStudents = 0;
                $unpaidStudents = 0;
                $freeCardStudents = 0;
                $categoryTotal = 0;

                foreach ($enrollments as $enrollment) {
                    if ($enrollment->is_free_card) {
                        $freeCardStudents++;

                        continue;
                    }

                    $paidAmount = (float) ($enrollment->monthly_paid_amount ?? 0);
                    $finalFee = (float) $enrollment->final_fee;

                    if ($paidAmount <= 0) {
                        $unpaidStudents++;
                    } elseif ($paidAmount < $finalFee) {
                        $partialStudents++;
                    } else {
                        $paidStudents++;
                    }

                    $categoryTotal += $paidAmount;
                }

                $classTotal += $categoryTotal;

                $categorySummaries[] = [
                    'category_fee_id' => $categoryFee->id,
                    'category_id' => optional($categoryFee->category)->id,
                    'category_name' => optional($categoryFee->category)->category_name,
                    'fee' => null,
                    'fee_options' => $categoryFee->feeOptions
                        ->map(fn($option) => [
                            'id' => $option->id,
                            'label' => $option->label,
                            'fee' => (float) $option->fee,
                            'is_default' => (bool) $option->is_default,
                        ])
                        ->values()
                        ->all(),
                    'expected_total' => round(
                        $enrollments->sum(fn($enrollment) => $enrollment->is_free_card ? 0 : (float) $enrollment->final_fee),
                        2
                    ),
                    'student_count' => $enrollments->count(),
                    'paid_students' => $paidStudents,
                    'partial_students' => $partialStudents,
                    'unpaid_students' => $unpaidStudents,
                    'free_card_students' => $freeCardStudents,
                    'category_total' => round($categoryTotal, 2),
                ];
            }

            $classSummaries[] = [
                'class_id' => $class->id,
                'class_name' => $class->class_name,
                'grade_name' => optional($class->grade)->grade_name,
                'total_students' => $classStudentCount,
                'class_total' => round($classTotal, 2),
                'categories' => $categorySummaries,
            ];
        }

        return [
            'teacher_id' => $teacherId,
            'branch_id' => $branchId,
            'year' => $year,
            'month' => $month,
            'teacher_total' => round($teacherTotal, 2),
            'total_student_count' => $grandStudentCount,
            'total_class_count' => $classes->count(),
            'classes' => $classSummaries,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function paymentsQuery(int $teacherId, int $branchId, int $year, int $month)
    {
        [$start, $end] = $this->calculator->monthRange($year, $month);

        return TeacherPayment::query()
            ->where('branch_id', $branchId)
            ->where('teacher_id', $teacherId)
            ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
            ->where('status', '!=', 'cancelled');
    }
}

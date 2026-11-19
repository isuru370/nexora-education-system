<?php

namespace App\Services\Web\TeacherSalary;

use App\Models\Branch;
use App\Models\PaymentSplitSnapshot;
use Carbon\Carbon;
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

        // Earlier months' fees paid after their salary: into this month's salary
        $carryByTeacher = $this->calculator->carryByTeacher($branchId, $year, $month);

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
            ->merge($carryByTeacher->keys())
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
            /** @var TeacherSalary|null $record */
            $record = $salaryRecords->get($teacher->id);

            $isPaid = $record && $record->status === 'paid';

            $totals = $this->calculator->buildTotals(
                (float) $grossByTeacher->get($teacher->id, 0.0),
                $deductionsByTeacher->get($teacher->id, $this->calculator->emptyDeductions()),
                // a paid salary already took its balance (kept on the record)
                $isPaid
                    ? ['amount' => (float) $record->carried_amount, 'snapshot_ids' => [], 'months' => []]
                    : $carryByTeacher->get($teacher->id)
            );
            $dbNet = $record ? (float) $record->net_amount : null;
            $netPayable = $isPaid ? $dbNet : $totals['net'];
            $netDifference = $isPaid ? round($totals['net'] - $dbNet, 2) : 0;

            $rows[] = [
                'teacher_id' => $teacher->id,
                'teacher_custom_id' => $teacher->custom_id,
                'teacher_name' => $teacher->full_name,

                'gross_earnings' => $totals['gross'],
                'carried' => $totals['carried'],
                'advance' => $totals['advance'],
                'deduction' => $totals['deduction'],
                'other' => $totals['other'],
                'welfare' => $totals['welfare'],

                'calculated_net' => $totals['net'],
                'net_payable' => $netPayable,

                'status' => $record ? $record->status : 'pending',
                'net_difference' => $netDifference,
            ];

            $summary['gross_earnings'] += $totals['gross'] + $totals['carried'];
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
            // paid: what was paid for this month's fees
            'teacher_earnings' => $isPaid ? (float) $record->gross_amount : $totals['gross'],
            // paid: fees of this month that came after the salary -> next salary
            'paid_after_salary' => $isPaid ? max(round($totals['gross'] - (float) $record->gross_amount, 2), 0) : 0.0,
            // earlier months' fees paid after their salary (paid: what this salary took)
            'carried' => $isPaid ? (float) $record->carried_amount : $totals['carried'],
            'carried_months' => $isPaid ? $this->carriedMonths((int) $record->id) : $totals['carried_months'],
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

    /**
     * Balance a paid salary took from earlier months, per month ("2026-10" => amount).
     *
     * @return array<string, float>
     */
    public function carriedMonths(int $salaryId): array
    {
        return PaymentSplitSnapshot::query()
            ->where('carried_to_salary_id', $salaryId)
            ->get(['payment_date', 'teacher_amount'])
            ->groupBy(fn ($snapshot) => Carbon::parse($snapshot->payment_date)->format('Y-m'))
            ->map(fn ($rows) => round((float) $rows->sum('teacher_amount'), 2))
            ->sortKeys()
            ->all();
    }

    public function salarySlip(int $teacherId, int $branchId, int $year, int $month): array
    {
        $this->assertBranchAccess($branchId);

        $teacher = Teacher::withTrashed()->findOrFail($teacherId);

        [$start, $end] = $this->calculator->monthRange($year, $month);

        $record = $this->calculator->salaryRecord($teacherId, $branchId, $year, $month);
        $isPaid = $record && $record->status === 'paid';

        $snapshots = PaymentSplitSnapshot::query()
            ->with([
                'studentClass' => fn($query) => $query->withTrashed(),
                'studentClass.subject:id,subject_name',
                'studentClass.grade:id,grade_name',
            ])
            ->where('branch_id', $branchId)
            ->where('teacher_id', $teacherId)
            ->whereBetween('payment_date', [$start, $end])
            // A paid slip lists the payments that were in that salary, so the
            // class rows add up to the paid amount (later payments are not in it)
            ->when($isPaid && $record->paid_at, fn ($query) => $query->where('payment_date', '<=', $record->paid_at))
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

        // Earlier months' fees paid after their salary
        $carriedMonths = $isPaid ? $this->carriedMonths((int) $record->id) : $totals['carried_months'];

        foreach ($carriedMonths as $period => $amount) {
            $earnings[] = [
                'description' => 'Balance from ' . Carbon::parse($period . '-01')->format('F Y') . ' (paid after that salary)',
                'amount' => $amount,
            ];
        }

        $deductions = [];

        foreach (['advance' => 'Advance', 'deduction' => 'Deduction', 'other' => 'Other Expenses', 'welfare' => 'Welfare'] as $key => $label) {
            if ($totals[$key] > 0) {
                $deductions[] = ['description' => $label, 'amount' => $totals[$key]];
            }
        }

        // A paid slip must show the amounts that were actually paid
        $totalAddition = $isPaid
            ? round((float) $record->gross_amount + (float) $record->carried_amount, 2)
            : round($totals['gross'] + $totals['carried'], 2);
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

    /**
     * The class fee payments one salary (teacher + branch + month) is made of,
     * by the day the money was received (payment_split_snapshots.payment_date):
     *
     *  - fees received in the month (when the salary is paid: up to the time
     *    it was paid; later ones go into the next salary)
     *  - earlier months' fees received after their salary was paid (carried)
     *
     * The teacher amounts add up to the salary page's teacher earnings + balance
     * from earlier months (TeacherSalaryCalculator), whatever month the fee was for.
     *
     * @return array{in_salary: Collection<int, PaymentSplitSnapshot>, after_salary: Collection<int, PaymentSplitSnapshot>, record: TeacherSalary|null}
     */
    public function salaryPayments(int $teacherId, int $branchId, int $year, int $month): array
    {
        [$start, $end] = $this->calculator->monthRange($year, $month);

        $record = $this->calculator->salaryRecord($teacherId, $branchId, $year, $month);
        $isPaid = $record && $record->status === 'paid';

        $with = [
            'payment' => fn ($query) => $query->withTrashed()->select('id', 'student_id', 'student_class_enrollment_id', 'amount', 'discount_amount', 'payment_month', 'paid_at', 'receipt_number'),
            'payment.student:id,custom_id,temporary_qr_code,permanent_qr_active,initial_name',
            'enrollment' => fn ($query) => $query->withTrashed()->select('id', 'student_class_id', 'class_category_fee_id', 'class_category_fee_option_id'),
        ];

        $monthRows = PaymentSplitSnapshot::query()
            ->with($with)
            ->where('branch_id', $branchId)
            ->where('teacher_id', $teacherId)
            ->whereBetween('payment_date', [$start, $end])
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();

        $paidAt = $isPaid ? $record->paid_at : null;
        $inMonth = $paidAt ? $monthRows->filter(fn ($s) => !$s->payment_date->greaterThan($paidAt)) : $monthRows;
        $after = $paidAt ? $monthRows->filter(fn ($s) => $s->payment_date->greaterThan($paidAt)) : collect();

        $carriedIds = $isPaid
            ? PaymentSplitSnapshot::query()->where('carried_to_salary_id', $record->id)->pluck('id')->all()
            : ($this->calculator->carryByTeacher($branchId, $year, $month, [$teacherId])->get($teacherId)['snapshot_ids'] ?? []);

        $carried = PaymentSplitSnapshot::query()
            ->with($with)
            ->whereIn('id', $carriedIds)
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get()
            ->each(fn ($snapshot) => $snapshot->setAttribute('is_carried', true));

        return [
            'in_salary' => $carried->concat($inMonth)->values(),
            'after_salary' => $after->values(),
            'record' => $record,
        ];
    }

    /**
     * One payment of a salary as a table row.
     */
    public function salaryPaymentRow(PaymentSplitSnapshot $snapshot): array
    {
        $payment = $snapshot->payment;
        $student = optional($payment)->student;
        $amount = round((float) $snapshot->payment_amount, 2);
        $hall = \App\Support\MonthFee::hallPart($amount, $snapshot->hall_fee);

        return [
            'student_code' => $student
                ? ($student->permanent_qr_active ? $student->custom_id : ($student->temporary_qr_code ?: $student->custom_id))
                : '-',
            'student_name' => optional($student)->initial_name ?? '-',
            'paid_for' => optional($payment)->payment_month ? Carbon::parse($payment->payment_month)->format('M Y') : '-',
            'paid_on' => $snapshot->payment_date->format('d M Y'),
            'receipt' => optional($payment)->receipt_number,
            'amount' => $amount,
            'hall_fee' => $hall,
            'class_fee' => round($amount - $hall, 2),
            'teacher_percentage' => (float) $snapshot->teacher_percentage,
            'teacher_amount' => round((float) $snapshot->teacher_amount, 2),
            'carried' => (bool) ($snapshot->is_carried ?? false),
        ];
    }

    /**
     * Totals of some salary payments.
     *
     * @param  Collection<int, PaymentSplitSnapshot>  $snapshots
     */
    public function salaryPaymentTotals(Collection $snapshots): array
    {
        $hall = $snapshots->sum(fn ($s) => \App\Support\MonthFee::hallPart((float) $s->payment_amount, $s->hall_fee));
        $collected = (float) $snapshots->sum('payment_amount');

        return [
            'payments' => $snapshots->count(),
            'collected' => round($collected, 2),
            'hall_fee' => round($hall, 2),
            'class_fee' => round($collected - $hall, 2),
            'teacher' => round((float) $snapshots->sum('teacher_amount'), 2),
            'organizer' => round((float) $snapshots->sum('organizer_amount'), 2),
            'institute' => round((float) $snapshots->sum('institution_amount'), 2),
        ];
    }

    /**
     * How a teacher's salary of a month was made: every class fee payment in
     * it (by the day it was received, whatever month it was for), per class
     * and fee category, with the hall fee and the class fee apart. Also this
     * month's fee status of each category (paid / unpaid / free card).
     */
    public function teacherPaymentAndClassSummary(int $teacherId, int $branchId, int $year, int $month): array
    {
        $this->assertBranchAccess($branchId);

        $teacher = Teacher::withTrashed()->findOrFail($teacherId);
        [$start, $end] = $this->calculator->monthRange($year, $month);

        $salary = $this->salaryPayments($teacherId, $branchId, $year, $month);
        $inSalary = $salary['in_salary'];
        $record = $salary['record'];
        $isPaid = $record && $record->status === 'paid';

        // classes: active ones of the teacher in the branch + any with money in this salary
        $classes = StudentClass::query()
            ->withTrashed()
            ->with([
                'grade:id,grade_name',
                'categoryFees' => fn ($query) => $query->with('category:id,category_name')->orderBy('id'),
                'enrollments' => fn ($query) => $query->where('is_active', true)
                    ->select('id', 'student_id', 'student_class_id', 'class_category_fee_id', 'class_category_fee_option_id', 'is_active', 'is_free_card')
                    ->with([
                        'classCategoryFeeOption:id,class_category_fee_id,label,fee',
                        'payments' => fn ($payments) => $payments->where('status', 'completed')
                            ->whereBetween('payment_month', [$start->toDateString(), $end->toDateString()])
                            ->with('splitSnapshot:id,payment_id,hall_fee'),
                    ]),
            ])
            ->where('branch_id', $branchId)
            ->where('teacher_id', $teacherId)
            ->where(function ($query) use ($inSalary) {
                $query->where(fn ($q) => $q->where('is_active', true)->whereNull('deleted_at'))
                    ->orWhereIn('id', $inSalary->pluck('student_class_id')->filter()->unique()->values());
            })
            ->orderBy('class_name')
            ->get();

        $hallFeeNow = \App\Support\MonthFee::hallFeeCache();
        $byClass = $inSalary->groupBy('student_class_id');

        $classRows = $classes->map(function (StudentClass $class) use ($byClass, $hallFeeNow) {
            $classPayments = $byClass->get($class->id, collect());
            $byCategory = $classPayments->groupBy(fn ($s) => optional($s->enrollment)->class_category_fee_id);

            $categories = $class->categoryFees->map(function ($categoryFee) use ($class, $byCategory, $hallFeeNow) {
                $payments = $byCategory->get($categoryFee->id, collect());

                $status = ['paid' => 0, 'partial' => 0, 'unpaid' => 0, 'freecard' => 0, 'payable' => 0.0, 'paid_amount' => 0.0];
                foreach ($class->enrollments->where('class_category_fee_id', $categoryFee->id) as $enrollment) {
                    $fee = \App\Support\MonthFee::forEnrollment($enrollment, $enrollment->payments, $hallFeeNow($enrollment));
                    $status[$fee['status']]++;
                    $status['payable'] += $fee['payable'];
                    $status['paid_amount'] += $fee['paid'];
                }

                return [
                    'category_fee_id' => $categoryFee->id,
                    'category_name' => optional($categoryFee->category)->category_name ?? 'Category',
                    'students' => $class->enrollments->where('class_category_fee_id', $categoryFee->id)->count(),
                    'fee_status' => $status,
                    'totals' => $this->salaryPaymentTotals($payments),
                    'payments' => $payments->map(fn ($s) => $this->salaryPaymentRow($s))->values()->all(),
                ];
            })
                // categories with students or with money in this salary
                ->filter(fn ($row) => $row['students'] > 0 || $row['totals']['payments'] > 0)
                ->values()
                ->all();

            return [
                'class_id' => $class->id,
                'class_name' => $class->class_name,
                'grade_name' => optional($class->grade)->grade_name,
                'is_active' => (bool) $class->is_active && !$class->trashed(),
                'totals' => $this->salaryPaymentTotals($classPayments),
                'categories' => $categories,
            ];
        })->values();

        $totals = $this->salaryPaymentTotals($inSalary);
        $carried = $inSalary->filter(fn ($s) => $s->is_carried ?? false);

        return [
            'teacher' => $teacher,
            'branch' => Branch::query()->find($branchId),
            'teacher_id' => $teacherId,
            'branch_id' => $branchId,
            'year' => $year,
            'month' => $month,
            'month_label' => $start->format('F Y'),
            'is_paid' => $isPaid,
            'paid_at' => $isPaid ? $record->paid_at : null,
            'totals' => $totals,
            'carried' => $this->salaryPaymentTotals($carried),
            'after_salary' => [
                'totals' => $this->salaryPaymentTotals($salary['after_salary']),
                'payments' => $salary['after_salary']->map(fn ($s) => $this->salaryPaymentRow($s))->values()->all(),
            ],
            'student_count' => $classes->sum(fn ($class) => $class->enrollments->count()),
            'classes' => $classRows->all(),
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

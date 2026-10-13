<?php

namespace App\Services\Web\Report;

use App\Models\AdmissionPayment;
use App\Models\ExtraIncome;
use App\Models\InstitutePayment;
use App\Models\OrganizerPayment;
use App\Models\Payment;
use App\Models\PaymentSplitSnapshot;
use App\Models\TeacherPayment;
use App\Models\TeacherSalary;
use App\Models\User;
use App\Services\Branch\BranchAccessService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * ONE place that decides which money records belong to a report.
 * Every payment report uses these queries, so totals are the same everywhere.
 *
 * Rules:
 *  - Only branches the user can access (records with no branch are shared
 *    institute records and are only shown to ADMIN / SUPER_ADMIN)
 *  - Soft-deleted rows are never counted (Eloquent does this automatically)
 *  - Only finished records: payments "completed", admissions "paid",
 *    extra income "received", expenses "paid"
 *  - Every record is filtered by its real money date
 *    (paid_at / income_date / payment_date), never by created_at
 */
class PaymentReportQuery
{
    /** Longest date range allowed in one report */
    public const MAX_RANGE_DAYS = 366;

    public const PAYMENT_METHODS = ['cash', 'card', 'bank_transfer', 'online', 'cheque', 'other'];

    /** Teacher payment types that are money given to the teacher (not penalties) */
    public const TEACHER_CASH_TYPES = ['advance', 'other'];

    /** Organizer payment types that are money given to the organizer */
    public const ORGANIZER_CASH_TYPES = ['salary', 'advance', 'other'];

    public function __construct(
        protected BranchAccessService $branchAccessService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Filter
    |--------------------------------------------------------------------------
    */

    /**
     * @throws DomainException
     */
    public function filter(
        ?string $from,
        ?string $to,
        ?int $branchId = null,
        ?int $collectedBy = null,
        ?string $paymentMethod = null
    ): ReportFilter {
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : now()->startOfDay();
        $toDate = $to ? Carbon::parse($to)->endOfDay() : $fromDate->copy()->endOfDay();

        if ($toDate->lessThan($fromDate)) {
            [$fromDate, $toDate] = [$toDate->copy()->startOfDay(), $fromDate->copy()->endOfDay()];
        }

        if ($fromDate->diffInDays($toDate) >= self::MAX_RANGE_DAYS) {
            throw new DomainException('Please select a date range of one year or less.');
        }

        $allowed = $this->branchAccessService->getBranchIds()->map(fn ($id) => (int) $id)->values();

        if ($allowed->isEmpty()) {
            throw new DomainException('You do not have access to any branch.');
        }

        if ($branchId !== null && !$allowed->contains($branchId)) {
            throw new DomainException('You do not have access to this branch.');
        }

        if ($paymentMethod !== null && !in_array($paymentMethod, self::PAYMENT_METHODS, true)) {
            $paymentMethod = null;
        }

        return new ReportFilter(
            from: $fromDate,
            to: $toDate,
            branchIds: $branchId !== null ? collect([$branchId]) : $allowed,
            branchId: $branchId,
            collectedBy: $collectedBy,
            paymentMethod: $paymentMethod,
        );
    }

    /**
     * Shared institute records (branch_id NULL) are only for ADMIN / SUPER_ADMIN
     * viewing all branches.
     */
    public function includesSharedRecords(ReportFilter $filter): bool
    {
        if ($filter->branchId !== null) {
            return false;
        }

        $user = auth()->user();
        $role = $user instanceof User && $user->userType ? strtoupper($user->userType->code) : '';

        return in_array($role, ['ADMIN', 'SUPER_ADMIN'], true);
    }

    /*
    |--------------------------------------------------------------------------
    | Income
    |--------------------------------------------------------------------------
    */

    /** Completed student class-fee payments, by paid_at */
    public function studentPayments(ReportFilter $filter): Builder
    {
        return Payment::query()
            ->where('status', 'completed')
            ->whereIn('branch_id', $filter->branchIds)
            ->whereBetween('paid_at', [$filter->from, $filter->to])
            ->when($filter->collectedBy, fn ($query, $userId) => $query->where('user_id', $userId))
            ->when($filter->paymentMethod, fn ($query, $method) => $query->where('payment_method', $method));
    }

    /**
     * Completed class fee payments of one teacher's classes.
     * Uses the teacher saved on the payment's split snapshot (the teacher at
     * payment time); payments without a snapshot fall back to the class teacher.
     */
    public function teacherStudentPayments(ReportFilter $filter, int $teacherId): Builder
    {
        return $this->studentPayments($filter)
            ->where(function ($query) use ($teacherId) {
                $query->whereHas('splitSnapshot', fn ($snapshot) => $snapshot->where('teacher_id', $teacherId))
                    ->orWhere(function ($fallback) use ($teacherId) {
                        $fallback->whereDoesntHave('splitSnapshot')
                            ->whereHas('enrollment.studentClass', fn ($class) => $class->withTrashed()->where('teacher_id', $teacherId));
                    });
            });
    }

    /** Paid admission fees, by paid_at */
    public function admissionPayments(ReportFilter $filter): Builder
    {
        return AdmissionPayment::query()
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$filter->from, $filter->to])
            ->tap(fn ($query) => $this->applyBranch($query, $filter))
            ->when($filter->collectedBy, fn ($query, $userId) => $query->where('user_id', $userId))
            ->when($filter->paymentMethod, fn ($query, $method) => $query->where('payment_method', $method));
    }

    /** Received extra income (hall rent etc.), by income_date */
    public function extraIncomes(ReportFilter $filter): Builder
    {
        return ExtraIncome::query()
            ->where('status', 'received')
            ->whereBetween('income_date', [$filter->from->toDateString(), $filter->to->toDateString()])
            ->tap(fn ($query) => $this->applyBranch($query, $filter));
    }

    /** Teacher / organizer / institute share of each student payment */
    public function splitSnapshots(ReportFilter $filter): Builder
    {
        return PaymentSplitSnapshot::query()
            ->whereIn('branch_id', $filter->branchIds)
            ->whereBetween('payment_date', [$filter->from, $filter->to])
            ->whereIn('payment_id', $this->studentPayments($filter)->select('id'));
    }

    /*
    |--------------------------------------------------------------------------
    | Expenses (money going out)
    |--------------------------------------------------------------------------
    */

    /** Advances / other money given to teachers (deductions are not cash) */
    public function teacherCashPayments(ReportFilter $filter): Builder
    {
        return TeacherPayment::query()
            ->where('status', 'paid')
            ->whereIn('payment_type', self::TEACHER_CASH_TYPES)
            ->whereIn('branch_id', $filter->branchIds)
            ->whereBetween('payment_date', [$filter->from->toDateString(), $filter->to->toDateString()]);
    }

    /** Monthly teacher salaries paid, by paid_at */
    public function teacherSalaries(ReportFilter $filter): Builder
    {
        return TeacherSalary::query()
            ->where('status', 'paid')
            ->whereIn('branch_id', $filter->branchIds)
            ->whereBetween('paid_at', [$filter->from, $filter->to]);
    }

    /** Salary / advance / other paid to organizers */
    public function organizerPayments(ReportFilter $filter): Builder
    {
        return OrganizerPayment::query()
            ->where('status', 'paid')
            ->whereIn('payment_type', self::ORGANIZER_CASH_TYPES)
            ->whereBetween('payment_date', [$filter->from->toDateString(), $filter->to->toDateString()])
            ->tap(fn ($query) => $this->applyBranch($query, $filter));
    }

    /** Institute running expenses */
    public function instituteExpenses(ReportFilter $filter): Builder
    {
        return InstitutePayment::query()
            ->where('status', 'paid')
            ->whereBetween('payment_date', [$filter->from->toDateString(), $filter->to->toDateString()])
            ->tap(fn ($query) => $this->applyBranch($query, $filter));
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * For tables where branch_id can be NULL (shared institute records).
     */
    protected function applyBranch(Builder $query, ReportFilter $filter): void
    {
        $includeShared = $this->includesSharedRecords($filter);

        $query->where(function ($inner) use ($filter, $includeShared) {
            $inner->whereIn('branch_id', $filter->branchIds);

            if ($includeShared) {
                $inner->orWhereNull('branch_id');
            }
        });
    }

    /**
     * Users who collected payments in the given branches (for the "Collected by" filter).
     */
    public function collectorOptions(Collection $branchIds): Collection
    {
        $userIds = Payment::query()
            ->whereIn('branch_id', $branchIds)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id')
            ->merge(
                AdmissionPayment::query()
                    ->whereIn('branch_id', $branchIds)
                    ->whereNotNull('user_id')
                    ->distinct()
                    ->pluck('user_id')
            )
            ->unique();

        return User::query()
            ->whereIn('id', $userIds)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public static function studentCode($student): ?string
    {
        if (!$student) {
            return null;
        }

        return $student->permanent_qr_active ? $student->custom_id : $student->temporary_qr_code;
    }
}

<?php

namespace App\Services\Web\Payment;

use App\Models\Payment;
use App\Services\Branch\BranchAccessService;
use App\Services\Web\Report\PaymentReportQuery;
use App\Services\Web\Report\ReportFilter;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Class fee payments list (Payments History), one payment, and its receipt.
 * (Replaces the old admin/payments/today-receipt page, which used the mobile
 *  API, and the unused Admin\StudentPaymentController.)
 *
 * Status:
 *  - active   live payments (default)
 *  - deleted  soft deleted payments, with who deleted them and why
 *  - all      both
 */
class PaymentHistoryService
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_DELETED = 'deleted';

    public const STATUS_ALL = 'all';

    public const STATUSES = [
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_DELETED => 'Deleted',
        self::STATUS_ALL => 'All',
    ];

    public function __construct(
        protected BranchAccessService $branchAccess
    ) {}

    /**
     * Payments in the date range (by paid date, or created date when not paid yet).
     */
    public function query(ReportFilter $filter, ?string $search, string $status): Builder
    {
        $query = Payment::query()
            ->with([
                'student' => fn ($q) => $q->withTrashed()
                    ->select('id', 'custom_id', 'temporary_qr_code', 'permanent_qr_active', 'initial_name', 'guardian_mobile'),
                'enrollment' => fn ($q) => $q->withTrashed()
                    ->select('id', 'student_class_id', 'class_category_fee_id', 'class_category_fee_option_id'),
                'enrollment.studentClass' => fn ($q) => $q->withTrashed()->select('id', 'class_name', 'grade_id', 'teacher_id'),
                'enrollment.studentClass.grade:id,grade_name',
                'enrollment.studentClass.teacher' => fn ($q) => $q->withTrashed()->select('id', 'full_name'),
                'enrollment.classCategoryFee' => fn ($q) => $q->withTrashed()->select('id', 'class_category_id'),
                'enrollment.classCategoryFee.category:id,category_name',
                'collectedBy:id,name',
                'deletedBy:id,name',
                'branch:id,name',
            ])
            ->whereIn('branch_id', $filter->branchIds)
            ->where(function ($q) use ($filter) {
                $q->whereBetween('paid_at', [$filter->from, $filter->to])
                    ->orWhere(function ($notPaid) use ($filter) {
                        $notPaid->whereNull('paid_at')
                            ->whereBetween('created_at', [$filter->from, $filter->to]);
                    });
            })
            ->when($filter->collectedBy, fn ($q, $userId) => $q->where('user_id', $userId))
            ->when($filter->paymentMethod, fn ($q, $method) => $q->where('payment_method', $method));

        if ($status === self::STATUS_DELETED) {
            $query->onlyTrashed();
        } elseif ($status === self::STATUS_ALL) {
            $query->withTrashed();
        }

        $search = trim((string) $search);

        if ($search !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';

            $query->where(function ($q) use ($like) {
                $q->where('receipt_number', 'like', $like)
                    ->orWhereHas('student', fn ($student) => $student->withTrashed()
                        ->where(function ($s) use ($like) {
                            $s->where('custom_id', 'like', $like)
                                ->orWhere('temporary_qr_code', 'like', $like)
                                ->orWhere('initial_name', 'like', $like)
                                ->orWhere('full_name', 'like', $like)
                                ->orWhere('guardian_mobile', 'like', $like);
                        }))
                    ->orWhereHas('enrollment.studentClass', fn ($class) => $class->withTrashed()->where('class_name', 'like', $like));
            });
        }

        return $query->orderByDesc('paid_at')->orderByDesc('id');
    }

    /**
     * Totals of the filtered list. Money only counts live completed payments.
     */
    public function summary(Builder $query): array
    {
        $rows = (clone $query)
            ->setEagerLoads([])
            ->reorder()
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN deleted_at IS NULL AND status = ? THEN 1 ELSE 0 END) as completed,
                COALESCE(SUM(CASE WHEN deleted_at IS NULL AND status = ? THEN amount ELSE 0 END), 0) as amount,
                SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END) as deleted
            ', ['completed', 'completed'])
            ->first();

        return [
            'total' => (int) $rows->total,
            'completed' => (int) $rows->completed,
            'amount' => round((float) $rows->amount, 2),
            'deleted' => (int) $rows->deleted,
        ];
    }

    public function row(Payment $payment): array
    {
        $enrollment = $payment->enrollment;
        $class = optional($enrollment)->studentClass;

        return [
            'id' => $payment->id,
            'receipt_number' => $payment->receipt_number,
            'paid_at' => optional($payment->paid_at)->format('Y-m-d H:i'),
            'student_code' => PaymentReportQuery::studentCode($payment->student),
            'student_name' => optional($payment->student)->initial_name,
            'class_name' => optional($class)->class_name,
            'grade_name' => optional(optional($class)->grade)->grade_name,
            'teacher_name' => optional(optional($class)->teacher)->full_name,
            'category_name' => optional(optional(optional($enrollment)->classCategoryFee)->category)->category_name,
            'fee_month' => $payment->payment_month ? $payment->payment_month->format('M Y') : null,
            'payment_method' => $this->label($payment->payment_method),
            'collected_by' => optional($payment->collectedBy)->name,
            'branch' => optional($payment->branch)->name,
            'status' => $payment->trashed() ? 'Deleted' : $this->label($payment->status),
            'deleted_by' => optional($payment->deletedBy)->name,
            'delete_reason' => $payment->delete_reason,
            'amount' => round((float) $payment->amount, 2),
            'can_delete' => $this->canDelete($payment),
        ];
    }

    public function table(): array
    {
        return [
            'headings' => ['Receipt No', 'Paid At', 'Student Code', 'Student', 'Class', 'Category', 'Fee Month', 'Method', 'Collected By', 'Status', 'Amount (Rs.)'],
            'columns' => ['receipt_number', 'paid_at', 'student_code', 'student_name', 'class_name', 'category_name', 'fee_month', 'payment_method', 'collected_by', 'status', 'amount'],
            'money' => ['amount'],
            'total' => null,
        ];
    }

    /**
     * One payment (deleted ones too) of a branch the user can access.
     *
     * @throws DomainException
     */
    public function find(int $paymentId): Payment
    {
        $payment = Payment::withTrashed()
            ->with([
                'student' => fn ($q) => $q->withTrashed(),
                'enrollment' => fn ($q) => $q->withTrashed(),
                'enrollment.studentClass' => fn ($q) => $q->withTrashed(),
                'enrollment.studentClass.grade',
                'enrollment.studentClass.subject',
                'enrollment.studentClass.teacher' => fn ($q) => $q->withTrashed(),
                'enrollment.classCategoryFee' => fn ($q) => $q->withTrashed(),
                'enrollment.classCategoryFee.category',
                'enrollment.classCategoryFeeOption',
                'splitSnapshot' => fn ($q) => $q->withTrashed(),
                'splitSnapshot.organizer' => fn ($q) => $q->withTrashed(),
                'collectedBy:id,name',
                'deletedBy:id,name',
                'branch:id,name',
            ])
            ->find($paymentId);

        if (!$payment || !$this->branchAccess->canAccessBranch((int) $payment->branch_id)) {
            throw new DomainException('Payment not found.');
        }

        return $payment;
    }

    /** Same rule as PaymentDeleteService */
    public function canDelete(Payment $payment): bool
    {
        return !$payment->trashed()
            && $payment->created_at
            && $payment->created_at->gte(now()->subDays(PaymentDeleteService::DELETE_WINDOW_DAYS));
    }

    public function deleteUntil(Payment $payment): ?Carbon
    {
        return $payment->created_at ? $payment->created_at->copy()->addDays(PaymentDeleteService::DELETE_WINDOW_DAYS) : null;
    }

    public function label(?string $value): string
    {
        return $value ? ucwords(str_replace('_', ' ', $value)) : '-';
    }

    public function statusOf(?string $status): string
    {
        return array_key_exists((string) $status, self::STATUSES) ? $status : self::STATUS_ACTIVE;
    }

    /** Rows for Excel / PDF (all rows, no paging) */
    public function exportRows(Builder $query): Collection
    {
        return $query->get()->map(fn (Payment $payment) => $this->row($payment));
    }
}

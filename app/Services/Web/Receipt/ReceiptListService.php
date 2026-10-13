<?php

namespace App\Services\Web\Receipt;

use App\Models\ActivityLog;
use App\Models\AdmissionPayment;
use App\Models\ExtraIncome;
use App\Models\Payment;
use App\Models\StudentClassEnrollment;
use App\Services\Branch\BranchAccessService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * Receipts list (student payments, admission payments, extra income).
 *
 * Status filter:
 * - active  (default) : only live receipts  -> totals are real money
 * - deleted           : soft deleted + force deleted (audit view)
 * - all               : everything
 *
 * Filtering is done in SQL, not on a full in-memory list.
 */
class ReceiptListService
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_DELETED = 'deleted';
    public const STATUS_ALL = 'all';

    public const TYPE_STUDENT = 'Student Payment';
    public const TYPE_ADMISSION = 'Admission Payment';
    public const TYPE_EXTRA = 'Extra Income';

    public function __construct(
        protected BranchAccessService $branchAccess
    ) {}

    /**
     * @param array{receipt_number?:?string,type?:?string,from_date?:?string,to_date?:?string,status?:?string} $filters
     */
    public function list(array $filters): Collection
    {
        $filters = $this->normalizeFilters($filters);
        $branchIds = $this->branchAccess->getBranchIds();

        $receipts = collect();

        if ($this->wantsType($filters, self::TYPE_STUDENT)) {
            $receipts = $receipts
                ->merge($this->studentPayments($filters, $branchIds))
                ->merge($this->forceDeletedStudentPayments($filters, $branchIds));
        }

        if ($this->wantsType($filters, self::TYPE_ADMISSION)) {
            $receipts = $receipts->merge($this->admissionPayments($filters, $branchIds));
        }

        if ($this->wantsType($filters, self::TYPE_EXTRA)) {
            $receipts = $receipts->merge($this->extraIncomes($filters, $branchIds));
        }

        return $receipts
            ->sortByDesc(fn($item) => $item['date'] ? $item['date']->timestamp : 0)
            ->values();
    }

    /**
     * Totals for the page header. Money totals count ACTIVE receipts only.
     */
    public function summary(Collection $receipts): array
    {
        $active = $receipts->where('status', 'Active');

        return [
            'totalReceipts' => $receipts->count(),
            'activeReceipts' => $active->count(),
            'deletedReceipts' => $receipts->count() - $active->count(),
            'totalAmount' => (float) $active->sum('amount'),
            'deletedAmount' => (float) $receipts->where('status', '!=', 'Active')->sum('amount'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Sources
    |--------------------------------------------------------------------------
    */

    protected function studentPayments(array $filters, Collection $branchIds): Collection
    {
        $query = Payment::query()
            ->with([
                'student:id,custom_id,initial_name,permanent_qr_active,temporary_qr_code',
                'enrollment:id,student_class_id,class_category_fee_id',
                'enrollment.classCategoryFee:id,class_category_id',
                'enrollment.classCategoryFee.category:id,category_name',
                'enrollment.studentClass:id,class_name,grade_id,teacher_id',
                'enrollment.studentClass.grade:id,grade_name',
                'enrollment.studentClass.teacher:id,full_name',
                'deletedBy:id,name',
            ])
            ->select([
                'id',
                'branch_id',
                'student_id',
                'student_class_enrollment_id',
                'receipt_number',
                'amount',
                'payment_month',
                'paid_at',
                'created_at',
                'deleted_at',
                'deleted_by',
                'delete_reason',
            ])
            ->whereIn('branch_id', $branchIds);

        $this->applyCommonFilters($query, $filters);

        return $query->get()->map(function (Payment $item) {
            $enrollment = $item->enrollment;
            $class = optional($enrollment)->studentClass;

            return $this->row([
                'id' => $item->id,
                'receipt_number' => $item->receipt_number,
                'type' => self::TYPE_STUDENT,
                'student_id' => $this->studentCode($item->student),
                'student_name' => optional($item->student)->initial_name ?? 'N/A',
                'grade' => optional(optional($class)->grade)->grade_name,
                'category' => optional(optional(optional($enrollment)->classCategoryFee)->category)->category_name,
                'class_name' => optional($class)->class_name,
                'teacher_name' => optional(optional($class)->teacher)->full_name,
                'payment_month' => $item->payment_month ? Carbon::parse($item->payment_month) : null,
                'paid_at' => $item->paid_at ? Carbon::parse($item->paid_at) : null,
                'amount' => (float) $item->amount,
                'date' => $item->created_at,
                'status' => $item->deleted_at ? 'Deleted' : 'Active',
                'deleted_at' => $item->deleted_at,
                'deleted_by' => optional($item->deletedBy)->name,
                'delete_reason' => $item->delete_reason,
                'url' => $item->deleted_at ? null : $this->safeRoute('students-payments.show', $item->id),
            ]);
        });
    }

    /**
     * Payments removed with forceDelete() in the past (before soft delete).
     * Only visible in the "deleted" / "all" views.
     */
    protected function forceDeletedStudentPayments(array $filters, Collection $branchIds): Collection
    {
        if ($filters['status'] === self::STATUS_ACTIVE) {
            return collect();
        }

        $logs = ActivityLog::query()
            ->where('table_name', 'payments')
            ->where('action', 'force_deleted')
            ->when($filters['from_date'], fn($q) => $q->where('created_at', '>=', Carbon::parse($filters['from_date'])->startOfDay()))
            ->when($filters['to_date'], fn($q) => $q->where('created_at', '<=', Carbon::parse($filters['to_date'])->endOfDay()))
            ->latest()
            ->limit(500)
            ->get();

        if ($logs->isEmpty()) {
            return collect();
        }

        $enrollments = StudentClassEnrollment::withTrashed()
            ->with([
                'student:id,custom_id,initial_name,permanent_qr_active,temporary_qr_code',
                'studentClass:id,class_name,grade_id,teacher_id',
                'studentClass.grade:id,grade_name',
                'studentClass.teacher:id,full_name',
                'classCategoryFee:id,class_category_id',
                'classCategoryFee.category:id,category_name',
            ])
            ->whereIn(
                'id',
                $logs->pluck('old_values.payment.student_class_enrollment_id')->filter()->unique()->values()
            )
            ->get()
            ->keyBy('id');

        return $logs
            ->map(function (ActivityLog $log) use ($enrollments, $branchIds, $filters) {
                $payment = data_get($log->old_values, 'payment', []);
                $enrollment = $enrollments->get($payment['student_class_enrollment_id'] ?? null);

                // Branch access: use the payment's branch, else the enrollment's
                $branchId = $payment['branch_id'] ?? optional($enrollment)->branch_id;

                if ($branchId && !$branchIds->contains((int) $branchId)) {
                    return null;
                }

                $receiptNumber = $payment['receipt_number'] ?? 'N/A';

                if ($filters['receipt_number'] && stripos($receiptNumber, $filters['receipt_number']) === false) {
                    return null;
                }

                $class = optional($enrollment)->studentClass;

                return $this->row([
                    'id' => $log->record_id,
                    'receipt_number' => $receiptNumber,
                    'type' => self::TYPE_STUDENT,
                    'student_id' => $this->studentCode(optional($enrollment)->student),
                    'student_name' => optional(optional($enrollment)->student)->initial_name ?? 'N/A',
                    'grade' => optional(optional($class)->grade)->grade_name ?? 'N/A',
                    'category' => optional(optional(optional($enrollment)->classCategoryFee)->category)->category_name ?? 'N/A',
                    'class_name' => optional($class)->class_name ?? 'N/A',
                    'teacher_name' => optional(optional($class)->teacher)->full_name ?? 'N/A',
                    'payment_month' => !empty($payment['payment_month']) ? Carbon::parse($payment['payment_month']) : null,
                    'paid_at' => !empty($payment['paid_at']) ? Carbon::parse($payment['paid_at']) : null,
                    'amount' => (float) ($payment['amount'] ?? 0),
                    'date' => $log->created_at,
                    'status' => 'Force Deleted',
                    'deleted_at' => $log->created_at,
                    'deleted_by' => null,
                    'delete_reason' => 'Permanently deleted',
                    'url' => null,
                ]);
            })
            ->filter()
            ->values();
    }

    protected function admissionPayments(array $filters, Collection $branchIds): Collection
    {
        $query = AdmissionPayment::query()
            ->with([
                'student:id,custom_id,initial_name,permanent_qr_active,temporary_qr_code',
            ])
            ->select([
                'id',
                'branch_id',
                'student_id',
                'receipt_number',
                'amount',
                'paid_at',
                'created_at',
                'deleted_at',
            ])
            ->whereIn('branch_id', $branchIds);

        $this->applyCommonFilters($query, $filters);

        return $query->get()->map(function (AdmissionPayment $item) {
            return $this->row([
                'id' => $item->id,
                'receipt_number' => $item->receipt_number,
                'type' => self::TYPE_ADMISSION,
                'student_id' => $this->studentCode($item->student),
                'student_name' => optional($item->student)->initial_name ?? 'N/A',
                'paid_at' => $item->paid_at ? Carbon::parse($item->paid_at) : null,
                'amount' => (float) $item->amount,
                'date' => $item->created_at,
                'status' => $item->deleted_at ? 'Deleted' : 'Active',
                'deleted_at' => $item->deleted_at,
                'url' => $item->deleted_at ? null : $this->safeRoute('admission-payments.show', $item->id),
            ]);
        });
    }

    protected function extraIncomes(array $filters, Collection $branchIds): Collection
    {
        $query = ExtraIncome::query()
            ->select([
                'id',
                'branch_id',
                'receipt_number',
                'amount',
                'created_at',
                'deleted_at',
            ])
            ->whereIn('branch_id', $branchIds);

        $this->applyCommonFilters($query, $filters);

        return $query->get()->map(function (ExtraIncome $item) {
            return $this->row([
                'id' => $item->id,
                'receipt_number' => $item->receipt_number,
                'type' => self::TYPE_EXTRA,
                'amount' => (float) $item->amount,
                'date' => $item->created_at,
                'status' => $item->deleted_at ? 'Deleted' : 'Active',
                'deleted_at' => $item->deleted_at,
                'url' => $item->deleted_at ? null : $this->safeRoute('extra-incomes.show', $item->id),
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Status (soft delete), receipt number and date filters in SQL.
     */
    protected function applyCommonFilters(Builder $query, array $filters): void
    {
        if ($filters['status'] === self::STATUS_DELETED) {
            $query->onlyTrashed();
        } elseif ($filters['status'] === self::STATUS_ALL) {
            $query->withTrashed();
        }
        // STATUS_ACTIVE: default SoftDeletes scope hides deleted rows

        if ($filters['receipt_number']) {
            $query->where('receipt_number', 'like', '%' . $filters['receipt_number'] . '%');
        }

        if ($filters['from_date']) {
            $query->where('created_at', '>=', Carbon::parse($filters['from_date'])->startOfDay());
        }

        if ($filters['to_date']) {
            $query->where('created_at', '<=', Carbon::parse($filters['to_date'])->endOfDay());
        }
    }

    protected function normalizeFilters(array $filters): array
    {
        $status = strtolower(trim((string) ($filters['status'] ?? '')));

        if (!in_array($status, [self::STATUS_ACTIVE, self::STATUS_DELETED, self::STATUS_ALL], true)) {
            $status = self::STATUS_ACTIVE;
        }

        $date = function ($value) {
            $value = trim((string) $value);

            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
        };

        return [
            'receipt_number' => trim((string) ($filters['receipt_number'] ?? '')) ?: null,
            'type' => trim((string) ($filters['type'] ?? '')) ?: null,
            'from_date' => $date($filters['from_date'] ?? null),
            'to_date' => $date($filters['to_date'] ?? null),
            'status' => $status,
        ];
    }

    protected function wantsType(array $filters, string $type): bool
    {
        return $filters['type'] === null || $filters['type'] === $type;
    }

    /**
     * Same keys for every receipt row (views, Excel and PDF use them).
     */
    protected function row(array $values): array
    {
        return array_merge([
            'id' => null,
            'receipt_number' => null,
            'type' => null,
            'student_id' => 'N/A',
            'student_name' => 'N/A',
            'grade' => 'N/A',
            'category' => 'N/A',
            'class_name' => 'N/A',
            'teacher_name' => 'N/A',
            'payment_month' => null,
            'paid_at' => null,
            'amount' => 0,
            'date' => null,
            'status' => 'Active',
            'deleted_at' => null,
            'deleted_by' => null,
            'delete_reason' => null,
            'url' => null,
        ], $values);
    }

    protected function studentCode($student): string
    {
        if (!$student) {
            return 'N/A';
        }

        return $student->permanent_qr_active
            ? ($student->custom_id ?? 'N/A')
            : ($student->temporary_qr_code ?? $student->custom_id ?? 'N/A');
    }

    /**
     * First route name that exists. A missing route must not
     * break the whole receipts page.
     */
    protected function safeRoute(array $names, $parameter): ?string
    {
        foreach ($names as $name) {
            if (Route::has($name)) {
                return route($name, $parameter);
            }
        }

        return null;
    }
}

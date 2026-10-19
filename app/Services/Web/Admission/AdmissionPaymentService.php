<?php

namespace App\Services\Web\Admission;

use App\Models\Admission;
use App\Models\AdmissionPayment;
use App\Models\Student;
use App\Services\Branch\BranchAccessService;
use App\Services\Notification\AdmissionPaymentNotificationService;
use App\Services\Web\Receipt\ReceiptNumberService;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AdmissionPaymentService
{
    public function __construct(
        protected BranchAccessService $branchAccess,
        protected ReceiptNumberService $receiptNumberService,
        protected AdmissionPaymentNotificationService $admissionNotification
    ) {}

    /*
    |--------------------------------------------------------------------------
    | List
    |--------------------------------------------------------------------------
    */

    /**
     * Payments in the branches the user can access.
     */
    public function paginate(
        ?string $search = null,
        ?string $status = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $search = trim((string) $search);
        $status = trim((string) $status);

        return AdmissionPayment::query()
            ->with([
                'student:id,custom_id,initial_name',
                'admission:id,name',
                'collectedBy:id,name',
                'branch:id,name',
            ])
            ->whereIn('branch_id', $this->branchAccess->getBranchIds())
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('receipt_number', 'like', "%{$search}%")
                        ->orWhereHas('student', function ($studentQuery) use ($search) {
                            $studentQuery->where('initial_name', 'like', "%{$search}%")
                                ->orWhere('custom_id', 'like', "%{$search}%");
                        })
                        ->orWhereHas('admission', function ($admissionQuery) use ($search) {
                            $admissionQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate($perPage)
            ->appends(array_filter([
                'search' => $search,
                'status' => $status,
            ]));
    }

    /*
    |--------------------------------------------------------------------------
    | Form Data
    |--------------------------------------------------------------------------
    */

    /**
     * Dropdown data for create / edit forms.
     *
     * Only active students in the user's branches.
     * On edit, the payment's current student/admission is always included.
     */
    public function formOptions(?AdmissionPayment $payment = null): array
    {
        $students = Student::query()
            ->select('id', 'custom_id', 'initial_name')
            ->whereIn('branch_id', $this->branchAccess->getBranchIds())
            ->where(function ($query) use ($payment) {
                $query->where('is_active', true);

                if ($payment) {
                    $query->orWhere('id', $payment->student_id);
                }
            })
            ->orderBy('initial_name')
            ->get();

        $admissions = Admission::query()
            ->select('id', 'name', 'amount')
            ->where(function ($query) use ($payment) {
                $query->where('is_active', true);

                if ($payment) {
                    $query->orWhere('id', $payment->admission_id);
                }
            })
            ->orderBy('name')
            ->get();

        return [
            'students' => $students,
            'admissions' => $admissions,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    */

    public function getShowData(AdmissionPayment $payment): AdmissionPayment
    {
        $this->assertBranchAccess((int) $payment->branch_id);

        return $payment->load([
            'student',
            'admission',
            'collectedBy',
            'branch',
        ]);
    }

    public function assertCanView(AdmissionPayment $payment): void
    {
        $this->assertBranchAccess((int) $payment->branch_id);
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    /**
     * @throws DomainException
     */
    public function create(array $data): AdmissionPayment
    {
        return DB::transaction(function () use ($data) {
            $student = Student::query()
                ->lockForUpdate()
                ->findOrFail($data['student_id']);

            $branchId = (int) $student->branch_id;

            $this->assertBranchAccess($branchId);

            $this->assertNotAlreadyPaid(
                (int) $student->id,
                (int) $data['admission_id'],
                $data['status']
            );

            $isPaid = $data['status'] === AdmissionPayment::STATUS_PAID;

            $payment = AdmissionPayment::create([
                'branch_id' => $branchId,
                'student_id' => $student->id,
                'admission_id' => (int) $data['admission_id'],
                'user_id' => auth()->id(),
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'status' => $data['status'],
                'paid_at' => $isPaid ? now() : null,
                'receipt_number' => $isPaid ? $this->receiptNumberService->generate() : null,
                'note' => $data['note'] ?? null,
            ]);

            $this->syncStudentAdmissionFlag($student);

            // Parent notification (Settings > Notifications), after commit
            $this->admissionNotification->sendPaid($payment);

            return $payment;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    /**
     * @throws DomainException
     */
    public function update(AdmissionPayment $payment, array $data): AdmissionPayment
    {
        return DB::transaction(function () use ($payment, $data) {
            $payment = AdmissionPayment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            // Must be able to see the payment as it is now ...
            $this->assertBranchAccess((int) $payment->branch_id);

            $oldStudentId = (int) $payment->student_id;
            $wasPaid = $payment->status === AdmissionPayment::STATUS_PAID;

            $student = Student::query()
                ->lockForUpdate()
                ->findOrFail($data['student_id']);

            // ... and the branch it is moving to.
            $branchId = (int) $student->branch_id;
            $this->assertBranchAccess($branchId);

            $this->assertNotAlreadyPaid(
                (int) $student->id,
                (int) $data['admission_id'],
                $data['status'],
                (int) $payment->id
            );

            $becomesPaid = $data['status'] === AdmissionPayment::STATUS_PAID;

            $payment->update([
                'branch_id' => $branchId,
                'student_id' => $student->id,
                'admission_id' => (int) $data['admission_id'],
                // user_id is NOT changed: it records who collected the money.
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'status' => $data['status'],
                'paid_at' => $becomesPaid
                    ? ($payment->paid_at ?? now())
                    : $payment->paid_at,
                'receipt_number' => $becomesPaid && !$payment->receipt_number
                    ? $this->receiptNumberService->generate()
                    : $payment->receipt_number,
                'note' => $data['note'] ?? null,
            ]);

            $this->syncStudentAdmissionFlag($student);

            if ($oldStudentId !== (int) $student->id) {
                $this->syncStudentAdmissionFlag(Student::findOrFail($oldStudentId));
            }

            // Pending -> paid: notify the parent once
            if (!$wasPaid && $becomesPaid) {
                $this->admissionNotification->sendPaid($payment);
            }

            return $payment;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    /**
     * Paid payments are financial records: cancel or refund them first.
     *
     * @throws DomainException
     */
    public function delete(AdmissionPayment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $payment = AdmissionPayment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            $this->assertBranchAccess((int) $payment->branch_id);

            if ($payment->status === AdmissionPayment::STATUS_PAID) {
                throw new DomainException(
                    'A paid admission payment cannot be deleted. Change its status to cancelled or refunded first.'
                );
            }

            $student = $payment->student;

            $payment->delete();

            if ($student) {
                $this->syncStudentAdmissionFlag($student);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * @throws DomainException
     */
    protected function assertBranchAccess(int $branchId): void
    {
        if (!$this->branchAccess->canAccessBranch($branchId)) {
            throw new DomainException('You do not have access to this branch.');
        }
    }

    /**
     * A student can have only one PAID payment per admission type.
     *
     * @throws DomainException
     */
    protected function assertNotAlreadyPaid(
        int $studentId,
        int $admissionId,
        string $status,
        ?int $excludeId = null
    ): void {
        if ($status !== AdmissionPayment::STATUS_PAID) {
            return;
        }

        $exists = AdmissionPayment::query()
            ->where('student_id', $studentId)
            ->where('admission_id', $admissionId)
            ->where('status', AdmissionPayment::STATUS_PAID)
            ->when($excludeId, function ($query) use ($excludeId) {
                $query->where('id', '!=', $excludeId);
            })
            ->exists();

        if ($exists) {
            throw new DomainException(
                'This student has already paid for this admission.'
            );
        }
    }

    /**
     * students.admission = true when the student has any paid admission payment.
     */
    protected function syncStudentAdmissionFlag(Student $student): void
    {
        $hasPaid = AdmissionPayment::query()
            ->where('student_id', $student->id)
            ->where('status', AdmissionPayment::STATUS_PAID)
            ->exists();

        if ((bool) $student->admission !== $hasPaid) {
            $student->update(['admission' => $hasPaid]);
        }
    }

        /**
     * Totals for the stats cards (all payments in the user's branches,
     * not only the current page).
     */
    public function summary(): array
    {
        $rows = AdmissionPayment::query()
            ->whereIn('branch_id', $this->branchAccess->getBranchIds())
            ->select([
                'status',
                DB::raw('COUNT(*) as total_count'),
                DB::raw('SUM(amount) as total_amount'),
            ])
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $count = fn (string $status) => (int) optional($rows->get($status))->total_count;
        $amount = fn (string $status) => (float) optional($rows->get($status))->total_amount;

        $todayPaid = (float) AdmissionPayment::query()
            ->whereIn('branch_id', $this->branchAccess->getBranchIds())
            ->where('status', AdmissionPayment::STATUS_PAID)
            ->whereBetween('paid_at', [now()->startOfDay(), now()->endOfDay()])
            ->sum('amount');

        return [
            'total' => (int) $rows->sum('total_count'),
            'paid' => $count(AdmissionPayment::STATUS_PAID),
            'pending' => $count(AdmissionPayment::STATUS_PENDING),
            'cancelled' => $count(AdmissionPayment::STATUS_CANCELLED),
            'refunded' => $count(AdmissionPayment::STATUS_REFUNDED),

            // Money actually collected (paid only)
            'paid_amount' => $amount(AdmissionPayment::STATUS_PAID),
            'pending_amount' => $amount(AdmissionPayment::STATUS_PENDING),
            'today_paid_amount' => $todayPaid,
        ];
    }
}
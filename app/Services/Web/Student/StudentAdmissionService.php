<?php

namespace App\Services\Web\Student;

use App\Models\Admission;
use App\Models\AdmissionPayment;
use App\Models\Student;
use App\Services\Branch\BranchAccessService;
use App\Services\Web\Receipt\ReceiptNumberService;
use Illuminate\Support\Facades\DB;

class StudentAdmissionService
{
    public function __construct(
        private BranchAccessService $branchAccessService,
        private ReceiptNumberService $receiptNumberService
    ) {
    }

    /**
     * Create admission payment for a student.
     *
     * The student's primary branch is used
     * as the admission payment branch.
     */
    public function createAdmissionPayment(
        Student $student,
        int $admissionId
    ): ?AdmissionPayment {
        $branchId = (int) $student->branch_id;

        if (!$this->branchAccessService->canAccessBranch($branchId)) {
            abort(403, 'You do not have access to this branch.');
        }

        return DB::transaction(function () use (
            $student,
            $admissionId,
            $branchId
        ) {
            $student = Student::query()
                ->lockForUpdate()
                ->findOrFail($student->id);

            $admission = Admission::query()
                ->active()
                ->findOrFail($admissionId);

            $payment = AdmissionPayment::create([
                'branch_id' => $branchId,
                'student_id' => $student->id,
                'admission_id' => $admission->id,
                'amount' => $admission->amount,
                'payment_method' => 'cash',
                'status' => AdmissionPayment::STATUS_PAID,
                'paid_at' => now(),
                'receipt_number' => $this->receiptNumberService->generate(),
                'user_id' => auth()->id(),
            ]);

            $student->update([
                'admission' => true,
            ]);

            return $payment->load([
                'admission',
                'student',
                'branch',
            ]);
        });
    }

    /**
     * Sync admission payment for student update.
     *
     * The student's primary branch is used
     * as the admission payment branch.
     */
    public function syncAdmissionPayment(
        Student $student,
        bool $hasAdmission,
        ?int $admissionId
    ): void {
        $branchId = (int) $student->branch_id;

        if (!$this->branchAccessService->canAccessBranch($branchId)) {
            abort(403, 'You do not have access to this branch.');
        }

        DB::transaction(function () use (
            $student,
            $hasAdmission,
            $admissionId,
            $branchId
        ) {
            $student = Student::query()
                ->lockForUpdate()
                ->findOrFail($student->id);

            $latestPayment = AdmissionPayment::query()
                ->where('student_id', $student->id)
                ->where('branch_id', $branchId)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($hasAdmission && $admissionId) {
                $admission = Admission::query()
                    ->active()
                    ->findOrFail($admissionId);

                if ($latestPayment) {
                    $latestPayment->update([
                        'admission_id' => $admission->id,
                        'amount' => $admission->amount,
                        'payment_method' => 'cash',
                        'status' => AdmissionPayment::STATUS_PAID,
                        'paid_at' => now(),
                        'user_id' => auth()->id(),
                    ]);
                } else {
                    AdmissionPayment::create([
                        'branch_id' => $branchId,
                        'student_id' => $student->id,
                        'admission_id' => $admission->id,
                        'amount' => $admission->amount,
                        'payment_method' => 'cash',
                        'status' => AdmissionPayment::STATUS_PAID,
                        'paid_at' => now(),
                        'receipt_number' => $this->receiptNumberService->generate(),
                        'user_id' => auth()->id(),
                    ]);
                }

                $student->update([
                    'admission' => true,
                ]);

                return;
            }

            if (
                $latestPayment &&
                $latestPayment->status !== AdmissionPayment::STATUS_CANCELLED
            ) {
                $latestPayment->update([
                    'status' => AdmissionPayment::STATUS_CANCELLED,
                ]);
            }

            $student->update([
                'admission' => false,
            ]);
        });
    }
}
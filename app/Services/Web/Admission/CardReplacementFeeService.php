<?php

namespace App\Services\Web\Admission;

use App\Models\Admission;
use App\Models\AdmissionPayment;
use App\Models\Student;
use App\Models\StudentIdCardDownload;
use App\Services\Web\Receipt\ReceiptNumberService;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Fee for a new ID card (lost / damaged): one of the Admissions items ticked
 * "Use for card replacement" (a free one is Rs. 0). Saved as a paid
 * admission payment with a receipt number, so it shows in Admission
 * Payments and the income reports.
 *
 *  - permanent cards: when a card is re-assigned (staff app)
 *  - temporary cards: from the 2nd ID card download (Generate Student ID)
 */
class CardReplacementFeeService
{
    public const METHODS = ['cash', 'card', 'bank_transfer', 'online'];

    public function __construct(protected ReceiptNumberService $receiptNumbers) {}

    /** The fee items to choose from */
    public function items(): Collection
    {
        return Admission::query()
            ->active()
            ->forCardReplacement()
            ->orderBy('amount')
            ->orderBy('name')
            ->get(['id', 'name', 'amount']);
    }

    /**
     * @throws DomainException when the item is not a card replacement fee
     */
    public function charge(Student $student, int $admissionId, string $method, string $note, ?int $userId): AdmissionPayment
    {
        $admission = Admission::query()->active()->forCardReplacement()->find($admissionId);

        if (!$admission) {
            throw new DomainException('Choose a card replacement fee (Admissions items ticked "Use for card replacement").');
        }

        if (!in_array($method, self::METHODS, true)) {
            $method = 'cash';
        }

        return AdmissionPayment::create([
            'branch_id' => $student->branch_id,
            'student_id' => $student->id,
            'admission_id' => $admission->id,
            'user_id' => $userId,
            'amount' => $admission->amount,
            'payment_method' => $method,
            'status' => AdmissionPayment::STATUS_PAID,
            'paid_at' => now(),
            'receipt_number' => $this->receiptNumbers->generate(),
            'note' => mb_substr($note, 0, 2000),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Temporary cards: ID card downloads (Generate Student ID)
    |--------------------------------------------------------------------------
    */

    /** student_id => [count, last download] for these students */
    public function downloadInfo(array $studentIds): Collection
    {
        if (!$studentIds) {
            return collect();
        }

        return StudentIdCardDownload::query()
            ->whereIn('student_id', $studentIds)
            ->groupBy('student_id')
            ->selectRaw('student_id, COUNT(*) as times, MAX(created_at) as last_at')
            ->get()
            ->keyBy('student_id');
    }

    /**
     * Record a download. The first one is free; from the 2nd one a fee item
     * must be chosen. Returns [download, payment|null].
     *
     * @throws DomainException 'payment_required' when no fee item was chosen
     */
    public function download(Student $student, ?int $admissionId, ?string $method, ?int $userId): array
    {
        return DB::transaction(function () use ($student, $admissionId, $method, $userId) {
            Student::query()->whereKey($student->id)->lockForUpdate()->first();

            $times = StudentIdCardDownload::query()->where('student_id', $student->id)->count();

            $payment = null;
            if ($times > 0) {
                if (!$admissionId) {
                    throw new DomainException('payment_required');
                }

                $payment = $this->charge(
                    $student,
                    $admissionId,
                    (string) $method,
                    'ID card replacement - download #' . ($times + 1),
                    $userId
                );
            }

            $download = StudentIdCardDownload::create([
                'student_id' => $student->id,
                'branch_id' => $student->branch_id,
                'user_id' => $userId,
                'admission_payment_id' => $payment?->id,
            ]);

            return [$download, $payment];
        });
    }

    /**
     * Bulk download: only students never downloaded before are allowed
     * (and recorded); the others must be downloaded one by one with a fee.
     *
     * @return array{allowed: int[], skipped: int[]}
     */
    public function bulk(array $studentIds, ?int $userId): array
    {
        $ids = array_values(array_unique(array_map('intval', $studentIds)));

        return DB::transaction(function () use ($ids, $userId) {
            $done = StudentIdCardDownload::query()->whereIn('student_id', $ids)->distinct()->pluck('student_id')->all();
            $students = Student::query()->whereIn('id', array_diff($ids, $done))->get(['id', 'branch_id']);

            foreach ($students as $student) {
                StudentIdCardDownload::create([
                    'student_id' => $student->id,
                    'branch_id' => $student->branch_id,
                    'user_id' => $userId,
                ]);
            }

            return [
                'allowed' => $students->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                'skipped' => array_values(array_map('intval', $done)),
            ];
        });
    }
}

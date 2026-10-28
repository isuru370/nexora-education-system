<?php

namespace App\Http\Controllers\Api\TeacherAdvance;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Teacher;
use App\Services\Branch\BranchAccessService;
use App\Services\Web\TeacherSalary\TeacherSalaryPaymentService;
use App\Services\Web\TeacherSalary\TeacherSalaryService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Staff app: pay a teacher an advance (ADMIN / BRANCH_ADMIN, as on the web).
 * Only paying: list / delete / salary payment stay on the web.
 *
 * Uses the web's TeacherSalaryPaymentService, so the same rules apply:
 * not more than this month's available amount, not after the month's salary
 * is paid, and two people paying at once cannot go over the limit.
 */
class TeacherAdvanceController extends Controller
{
    public function __construct(
        private TeacherSalaryService $salaryService,
        private TeacherSalaryPaymentService $paymentService,
        private BranchAccessService $branchAccess
    ) {
    }

    /**
     * GET /api/teacher-advances/teachers?branch_id=
     * Teachers of the branch with this month's earnings, advances and the
     * amount that can still be given.
     */
    public function teachers(Request $request): JsonResponse
    {
        $request->validate(['branch_id' => ['nullable', 'integer']]);

        $branchId = $this->branchId($request->input('branch_id'));
        if ($branchId instanceof JsonResponse) {
            return $branchId;
        }

        $now = now();

        try {
            $income = $this->salaryService->monthlyIncome($branchId, $now->year, $now->month);
        } catch (DomainException $e) {
            return $this->fail($e->getMessage(), 403);
        }

        $teachers = collect($income['data'] ?? [])->map(fn (array $row) => [
            'id' => $row['teacher_id'],
            'custom_id' => $row['teacher_custom_id'],
            'name' => $row['teacher_name'],
            'gross' => round((float) $row['gross_earnings'], 2),
            'advance' => round((float) $row['advance'], 2),
            'deductions' => round((float) $row['deduction'] + (float) $row['other'], 2),
            'salary_paid' => $row['status'] === 'paid',
            'available' => $row['status'] === 'paid' ? 0.0 : round(max((float) $row['calculated_net'], 0), 2),
        ])->values();

        return response()->json([
            'success' => true,
            'data' => [
                'branch' => $this->branchInfo($branchId),
                'month' => $now->format('F Y'),
                'date' => $now->toDateString(),
                'teachers' => $teachers,
            ],
        ]);
    }

    /**
     * POST /api/teacher-advances
     * teacher_id, branch_id, amount, reason?, note?  (date = today)
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'amount' => ['required', 'numeric', 'min:1', 'max:10000000'],
            'reason' => ['nullable', 'string', 'max:150'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $payment = $this->paymentService->storePayment((int) $data['teacher_id'], (int) $data['branch_id'], [
                'payment_type' => 'advance',
                'amount' => $data['amount'],
                'payment_date' => now()->toDateString(),
                'reason' => $data['reason'] ?? null,
                'note' => trim(($data['note'] ?? '') . ' (mobile app)'),
            ]);
        } catch (DomainException $e) {
            return $this->fail($e->getMessage(), 422);
        } catch (Throwable $e) {
            report($e);

            return $this->fail('Failed to save the advance. Please try again.', 500);
        }

        $teacher = Teacher::withTrashed()->find($payment->teacher_id, ['id', 'custom_id', 'full_name']);
        $now = now();
        $income = collect($this->salaryService->monthlyIncome((int) $payment->branch_id, $now->year, $now->month)['data'] ?? [])
            ->firstWhere('teacher_id', $payment->teacher_id);

        return response()->json([
            'success' => true,
            'message' => 'Advance paid.',
            'data' => [
                'id' => $payment->id,
                'reference' => 'ADV-' . str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT),
                'teacher' => [
                    'id' => $teacher->id,
                    'custom_id' => $teacher->custom_id,
                    'name' => $teacher->full_name,
                ],
                'branch' => $this->branchInfo((int) $payment->branch_id),
                'amount' => round((float) $payment->amount, 2),
                'reason' => $payment->reason,
                'note' => $data['note'] ?? null,
                'date' => $payment->payment_date instanceof \DateTimeInterface
                    ? $payment->payment_date->format('Y-m-d')
                    : substr((string) $payment->payment_date, 0, 10),
                'time' => $now->format('h:i A'),
                'month' => $now->format('F Y'),
                'paid_by' => optional($request->user())->name,
                'month_advance_total' => round((float) ($income['advance'] ?? $payment->amount), 2),
                'available_after' => round(max((float) ($income['calculated_net'] ?? 0), 0), 2),
            ],
        ], 201);
    }

    /** The branch to use: the one asked for, or the user's only branch. */
    private function branchId($requested): int|JsonResponse
    {
        if ($requested) {
            $id = (int) $requested;

            return $this->branchAccess->canAccessBranch($id)
                ? $id
                : $this->fail('You do not have access to this branch.', 403);
        }

        $branches = $this->branchAccess->getAccessibleBranches();

        if ($branches->count() === 1) {
            return (int) $branches->first()->id;
        }

        return response()->json([
            'success' => false,
            'message' => 'Select a branch.',
            'data' => ['branches' => $branches->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])->values()],
        ], 422);
    }

    private function branchInfo(int $branchId): array
    {
        $branch = Branch::find($branchId, ['id', 'name']);

        return ['id' => $branchId, 'name' => $branch->name ?? null];
    }

    private function fail(string $message, int $status): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }
}

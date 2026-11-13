<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Services\Api\Teacher\TeacherPaymentService;
use App\Services\Web\MonthlyReport\MonthlyReportService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Staff app, teacher: own earnings, salary and student payments.
 * Routes: api.v1.teacher.payments.* (teacher.rule:payments)
 */
class TeacherPaymentController extends Controller
{
    public function __construct(private TeacherPaymentService $service) {}

    public function branches(): JsonResponse
    {
        return $this->ok(['branches' => $this->service->branches($this->teacher())]);
    }

    public function month(Request $request): JsonResponse
    {
        [$branchId, $year, $month] = $this->period($request);

        return $this->run(fn () => $this->service->month($this->teacher(), $branchId, $year, $month));
    }

    public function deductions(Request $request): JsonResponse
    {
        [$branchId, $year, $month] = $this->period($request);

        return $this->run(fn () => $this->service->deductions($this->teacher(), $branchId, $year, $month));
    }

    public function history(Request $request): JsonResponse
    {
        [$branchId] = $this->period($request);

        return $this->run(fn () => $this->service->history($this->teacher(), $branchId));
    }

    public function students(Request $request): JsonResponse
    {
        [$branchId, $year, $month] = $this->period($request);

        $basis = $request->validate([
            'basis' => ['nullable', Rule::in([MonthlyReportService::BASIS_FEE_MONTH, MonthlyReportService::BASIS_PAID_DATE])],
        ])['basis'] ?? MonthlyReportService::BASIS_FEE_MONTH;

        return $this->run(fn () => $this->service->students($this->teacher(), $branchId, $year, $month, $basis));
    }

    public function courses(): JsonResponse
    {
        return $this->ok($this->service->courses($this->teacher()));
    }

    /** @return array{0: ?int, 1: int, 2: int} */
    protected function period(Request $request): array
    {
        $data = $request->validate([
            'branch_id' => ['nullable', 'integer'],
            'year' => ['nullable', 'integer', 'min:2020', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        return [
            isset($data['branch_id']) ? (int) $data['branch_id'] : null,
            (int) ($data['year'] ?? now()->year),
            (int) ($data['month'] ?? now()->month),
        ];
    }

    protected function run(callable $fn): JsonResponse
    {
        try {
            return $this->ok($fn());
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        }
    }

    protected function teacher(): Teacher
    {
        $teacher = auth()->user()?->teacher;
        abort_unless($teacher, 404, 'Teacher profile not found.');

        return $teacher;
    }

    protected function ok(array $data): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Loaded.', 'data' => $data]);
    }
}

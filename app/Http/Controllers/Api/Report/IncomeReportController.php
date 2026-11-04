<?php

namespace App\Http\Controllers\Api\Report;

use App\Http\Controllers\Controller;
use App\Services\Api\Report\IncomeReportService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/reports/income?from=2026-11-01&to=2026-11-30[&branch_id=1]
 * Staff app Income Report (all staff; the user's branches only).
 */
class IncomeReportController extends Controller
{
    public function __construct(protected IncomeReportService $service) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'branch_id' => ['nullable', 'integer'],
        ]);

        try {
            $filter = $this->service->filter(
                $validated['from'] ?? now()->startOfMonth()->toDateString(),
                $validated['to'] ?? (isset($validated['from']) ? null : now()->toDateString()),
                isset($validated['branch_id']) ? (int) $validated['branch_id'] : null
            );
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'data' => $this->service->build($filter)]);
    }
}

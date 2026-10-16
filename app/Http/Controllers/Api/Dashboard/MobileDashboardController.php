<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Api\Dashboard\MobileDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class MobileDashboardController extends Controller
{
    public function __construct(
        protected MobileDashboardService $dashboardService
    ) {}

    /**
     * GET /api/mobile-dashboard?branch_id=3
     *
     * branch_id empty -> all branches the user can access
     *                    (Branch Admin / User with one branch -> that branch)
     */
    public function mobileDashboardDetails(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $branchId = isset($validated['branch_id']) ? (int) $validated['branch_id'] : null;

        $branchIds = $this->dashboardService->resolveBranchIds($branchId);

        if ($branchIds === null) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this branch.',
            ], 403);
        }

        if ($branchIds->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No branch is assigned to your account.',
            ], 403);
        }

        // Only one branch -> show it as the selected branch
        $selectedBranchId = $branchId ?? ($branchIds->count() === 1 ? $branchIds->first() : null);

        try {
            return response()->json([
                'success' => true,
                'message' => 'Dashboard details fetched successfully',
                'data' => $this->dashboardService->build(
                    $request->user(),
                    $branchIds,
                    $selectedBranchId
                ),
            ], 200);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load the dashboard. Please try again.',
            ], 500);
        }
    }
}
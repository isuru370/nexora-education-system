<?php

namespace App\Http\Controllers\Api\StudentClass;

use App\Http\Controllers\Controller;
use App\Services\Api\StudentClass\StudentClassService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class StudentClassController extends Controller
{
    public function __construct(
        protected StudentClassService $studentClassService
    ) {
    }

    /**
     * GET /api/student-classes/{gradeId}?branch_id=3
     *
     * branch_id empty -> classes of every branch the user can access.
     */
    public function fetchStudentClass(Request $request, int $gradeId): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $branchIds = $this->studentClassService->resolveBranchIds(
            isset($validated['branch_id']) ? (int) $validated['branch_id'] : null
        );

        if ($branchIds === null) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this branch.',
            ], 403);
        }

        try {

            $result = $this->studentClassService
                ->fetchStudentClasses($gradeId, $branchIds);

            return response()->json([
                'success' => true,
                'message' => 'Student classes fetched successfully.',
                'grade' => $result['grade'],
                'data' => $result['data'],
            ]);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch student classes.',
            ], 500);
        }
    }
}
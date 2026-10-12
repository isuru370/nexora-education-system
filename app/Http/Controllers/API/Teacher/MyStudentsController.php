<?php

namespace App\Http\Controllers\API\Teacher;

use App\Http\Controllers\Controller;
use App\Services\Teacher\MyStudentsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class MyStudentsController extends Controller
{
    public function __construct(
        private  MyStudentsService $myStudentsService
    ) {}

    /**
     * Get teacher's students.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // ------------------------------------------------------
            // Get authenticated user
            // ------------------------------------------------------

            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            // ------------------------------------------------------
            // Get teacher profile
            // ------------------------------------------------------

            $teacher = $user->teacher;

            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher profile not found.',
                ], 404);
            }

            // ------------------------------------------------------
            // Check teacher status
            // ------------------------------------------------------

            if (!$teacher->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher account is inactive.',
                ], 403);
            }

            // ------------------------------------------------------
            // Validate request
            // ------------------------------------------------------

            $validated = $request->validate([
                'class_id' => [
                    'nullable',
                    'integer',
                ],
            ]);

            $classId = $validated['class_id'] ?? null;

            // ------------------------------------------------------
            // Get students
            // ------------------------------------------------------

            $students = $this->myStudentsService->getStudents(
                teacherId: $teacher->id,
                classId: $classId,
            );

            // ------------------------------------------------------
            // Success response
            // ------------------------------------------------------

            return response()->json([
                'success' => true,
                'message' => 'Teacher students loaded successfully.',
                'data' => $students,
            ], 200);

        } catch (Throwable $e) {

            // ------------------------------------------------------
            // Log error
            // ------------------------------------------------------

            Log::error(
                'Teacher students controller failed.',
                [
                    'user_id' => auth()->id(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );

            // ------------------------------------------------------
            // Error response
            // ------------------------------------------------------

            return response()->json([
                'success' => false,
                'message' => 'Unable to load teacher students.',
            ], 500);
        }
    }
}
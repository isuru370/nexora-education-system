<?php

namespace App\Http\Controllers\API\Teacher;

use App\Http\Controllers\Controller;
use App\Services\Teacher\MyClassesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class TeacherClassesController extends Controller
{
    public function __construct(
        private  MyClassesService $myClassesService
    ) {
    }

    /**
     * Get all classes assigned to logged-in teacher.
     */
    public function index(): JsonResponse
    {
        try {
            $user = auth()->user();

            $teacher = $user?->teacher;

            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher profile not found.',
                ], 404);
            }

            if (!$teacher->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher account is inactive.',
                ], 403);
            }

            $classes = $this->myClassesService->getMyClasses(
                teacherId: $teacher->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Teacher classes loaded successfully.',
                'data' => $classes,
            ], 200);

        } catch (Throwable $e) {

            Log::error('Teacher classes fetch failed.', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load teacher classes.',
            ], 500);
        }
    }
}
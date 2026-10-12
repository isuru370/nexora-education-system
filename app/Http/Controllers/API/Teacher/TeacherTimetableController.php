<?php

namespace App\Http\Controllers\API\Teacher;

use App\Http\Controllers\Controller;
use App\Services\Teacher\MyTimetableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class TeacherTimetableController extends Controller
{
    public function __construct(
        private  MyTimetableService $timetableService
    ) {
    }

    /**
     * Get teacher timetable.
     *
     * GET /api/v1/teacher/timetable
     * GET /api/v1/teacher/timetable?date=2026-09-11
     */
    public function index(Request $request): JsonResponse
    {
        try {
            /*
             * Get authenticated user.
             */
            $user = auth()->user();

            /*
             * Get teacher profile.
             */
            $teacher = $user?->teacher;

            /*
             * Teacher profile not found.
             */
            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher profile not found.',
                ], 404);
            }

            /*
             * Check teacher account status.
             */
            if (!$teacher->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher account is inactive.',
                ], 403);
            }

            /*
             * Validate date.
             *
             * Expected:
             * YYYY-MM-DD
             *
             * Example:
             * 2026-09-11
             */
            $validated = $request->validate([
                'date' => [
                    'nullable',
                    'date_format:Y-m-d',
                ],
            ]);

            /*
             * Get timetable from service.
             *
             * If date is null,
             * service will use today.
             */
            $timetable = $this->timetableService->getTimetable(
                teacherId: $teacher->id,
                date: $validated['date'] ?? null,
            );

            /*
             * Success response.
             */
            return response()->json([
                'success' => true,
                'message' => 'Teacher timetable loaded successfully.',
                'data' => $timetable,
            ], 200);

        } catch (Throwable $e) {

            /*
             * Log actual exception.
             *
             * Do NOT expose exception details
             * to the mobile application.
             */
            Log::error('Teacher timetable controller failed.', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            /*
             * Generic error response.
             */
            return response()->json([
                'success' => false,
                'message' => 'Unable to load teacher timetable.',
            ], 500);
        }
    }
}
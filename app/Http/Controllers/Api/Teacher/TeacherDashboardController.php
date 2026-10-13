<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Services\Teacher\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class TeacherDashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    public function index(): JsonResponse
    {
        try {

            $teacher = auth()->user()?->teacher;

            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher profile not found.',
                ], 404);
            }

            $dashboard = $this->dashboardService
                ->getDashboard($teacher->id);

            return response()->json([
                'success' => true,
                'message' => 'Teacher dashboard loaded successfully.',
                'data' => $dashboard,
            ]);

        } catch (Throwable $e) {

            Log::error('Teacher dashboard API error', [
                'user_id' => auth()->id(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load teacher dashboard.',
            ], 500);
        }
    }
}
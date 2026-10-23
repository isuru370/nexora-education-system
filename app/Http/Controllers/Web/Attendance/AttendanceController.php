<?php

namespace App\Http\Controllers\Web\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Attendance\ScanAttendanceRequest;
use App\Services\Web\Attendance\AttendanceService;
use Illuminate\Http\JsonResponse;
use Throwable;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService
    ) {
    }

    public function index()
    {
        return view('new-attendance.index');
    }

    public function scan(ScanAttendanceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $result = $this->attendanceService->scan(
                $validated['code'],
                $validated['mark_method'],
                isset($validated['schedule_id']) ? (int) $validated['schedule_id'] : null
            );

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result['data'],
            ], $result['status_code']);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while processing attendance.',
                'data' => null,
            ], 500);
        }
    }
}
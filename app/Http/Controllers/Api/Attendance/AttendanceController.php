<?php

namespace App\Http\Controllers\Api\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Attendance\ScanAttendanceRequest;
use App\Services\Web\Attendance\AttendanceService;
use Illuminate\Http\JsonResponse;
use Throwable;

class AttendanceController extends Controller
{
    protected $attendanceService;

    /**
     * AttendanceController constructor.
     */
    public function __construct(
        AttendanceService $attendanceService
    ) {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Scan student QR / ID and mark attendance.
     */
    public function scan(ScanAttendanceRequest $request): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | Validated Request
        |--------------------------------------------------------------------------
        |
        | Validation + trimming is done in ScanAttendanceRequest.
        |
        */

        $validated = $request->validated();

        try {

            /*
            |--------------------------------------------------------------------------
            | Process Attendance
            |--------------------------------------------------------------------------
            */

            $result = $this->attendanceService->scan(
                $validated['code'],
                $validated['mark_method'],
                isset($validated['schedule_id']) ? (int) $validated['schedule_id'] : null
            );

            /*
            |--------------------------------------------------------------------------
            | Return API Response
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result['data'],
            ], $result['status_code']);
        } catch (Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Log Exception
            |--------------------------------------------------------------------------
            */

            report($e);

            /*
            |--------------------------------------------------------------------------
            | Error Response
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while processing attendance.',
                'data' => null,
            ], 500);
        }
    }
}

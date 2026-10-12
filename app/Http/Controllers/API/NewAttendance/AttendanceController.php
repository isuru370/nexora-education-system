<?php

namespace App\Http\Controllers\API\NewAttendance;

use App\Http\Controllers\Controller;
use App\Services\NewAttendance\AttendanceService;
use Illuminate\Http\Request;
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
    public function scan(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validate Request
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:255',
            ],

            'mark_method' => [
                'required',
                'in:qr_mobile,qr_web,manual_mobile,manual_web',
            ],
        ]);

        try {

            /*
            |--------------------------------------------------------------------------
            | Process Attendance
            |--------------------------------------------------------------------------
            */

            $result = $this->attendanceService->scan(
                trim($validated['code']),
                $validated['mark_method']
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
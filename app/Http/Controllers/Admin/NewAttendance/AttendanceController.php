<?php

namespace App\Http\Controllers\Admin\NewAttendance;

use App\Http\Controllers\Controller;
use App\Services\NewAttendance\AttendanceService;
use Illuminate\Http\Request;
use Throwable;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService
    ) {
    }

    public function index()
    {
        return view('admin.new-attendance.index');
    }

    public function scan(Request $request)
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:255',
            ],

            'mark_method' => [
                'required',
                'in:qr_web,manual_web',
            ],
        ]);

        try {
            $result = $this->attendanceService->scan(
                trim($validated['code']),
                $validated['mark_method']
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
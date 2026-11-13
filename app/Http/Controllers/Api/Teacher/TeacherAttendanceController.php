<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Services\Api\Teacher\TeacherAttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Staff app, teacher: mark attendance in own classes / courses (today).
 * Routes: api.v1.teacher.attendance.* (teacher.rule:attendance)
 */
class TeacherAttendanceController extends Controller
{
    public function __construct(private TeacherAttendanceService $service) {}

    public function today(): JsonResponse
    {
        return $this->ok('Today\'s classes loaded.', ['date' => now()->toDateString(), 'classes' => $this->service->today($this->teacher())]);
    }

    public function students(string $type, int $id): JsonResponse
    {
        return $this->ok('Students loaded.', ['students' => $this->service->students($this->teacher(), $type, $id)]);
    }

    public function mark(Request $request, string $type, int $id): JsonResponse
    {
        $data = $request->validate([
            'student_ids' => ['required', 'array', 'min:1', 'max:500'],
            'student_ids.*' => ['integer'],
        ]);

        $result = $this->service->markList($this->teacher(), $type, $id, $data['student_ids']);

        return $this->ok("{$result['marked']} marked, {$result['already']} already marked.", $result);
    }

    public function scan(Request $request, string $type, int $id): JsonResponse
    {
        $data = $request->validate(['qr_code' => ['required', 'string', 'max:255']]);

        $result = $this->service->scan($this->teacher(), $type, $id, $data['qr_code']);

        return response()->json([
            'success' => $result['ok'],
            'message' => $result['message'],
            'data' => ['already_marked' => $result['already'], 'student' => $result['student']],
        ], $result['ok'] ? 200 : 422);
    }

    protected function teacher(): Teacher
    {
        $teacher = auth()->user()?->teacher;
        abort_unless($teacher, 404, 'Teacher profile not found.');

        return $teacher;
    }

    protected function ok(string $message, array $data): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data]);
    }
}

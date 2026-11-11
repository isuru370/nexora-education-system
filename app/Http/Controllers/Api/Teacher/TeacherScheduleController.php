<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Services\Api\Teacher\TeacherScheduleService;
use App\Support\TeacherAppRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Staff app, teacher: change / add / cancel own class days.
 * Routes: api.v1.teacher.schedule.* (teacher.rule:schedule)
 */
class TeacherScheduleController extends Controller
{
    public function __construct(private TeacherScheduleService $service) {}

    public function index(): JsonResponse
    {
        return $this->ok('Class days loaded.', $this->service->upcoming($this->teacher()));
    }

    public function options(): JsonResponse
    {
        return $this->ok('Options loaded.', $this->service->options($this->teacher()));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in(['class', 'course'])],
            'action' => ['required', Rule::in(['create', 'update', 'cancel'])],
            'id' => ['required', 'integer'],
            'class_category_fee_id' => ['nullable', 'integer'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'class_hall_id' => ['nullable', 'integer'],
            'reason' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $result = $this->service->submit($this->teacher(), $request->user(), $data);

        $message = $result->status === 'approved'
            ? 'Done. Parents are told.'
            : 'Sent to the admin for approval.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'auto_apply' => TeacherAppRules::allows('schedule_auto'),
                'request' => $this->service->requestRow($result),
            ],
        ], 201);
    }

    public function requests(): JsonResponse
    {
        return $this->ok('Requests loaded.', ['requests' => $this->service->requests($this->teacher())]);
    }

    public function withdraw(int $id): JsonResponse
    {
        $this->service->withdraw($this->teacher(), $id);

        return $this->ok('Request withdrawn.', []);
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

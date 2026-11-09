<?php

namespace App\Http\Controllers\Api\Parent\Course;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parent\Payment\StudentPaymentRequest;
use App\Services\Parent\Course\StudentCourseService;
use Illuminate\Http\JsonResponse;

/** POST /api/parent/v1/courses - the student's courses (read only). */
class CourseController extends Controller
{
    public function __construct(
        private StudentCourseService $courses
    ) {}

    public function index(StudentPaymentRequest $request): JsonResponse
    {
        return response()->json(
            $this->courses->fetch((int) $request->validated()['student_id'])
        );
    }
}

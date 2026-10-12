<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Api\StudentClassService;
use Illuminate\Http\JsonResponse;
use Throwable;

class StudentClassController extends Controller
{
    public function __construct(
        protected StudentClassService $studentClassService
    ) {
    }

    public function fetchStudentClass(int $gradeId): JsonResponse
    {
        try {

            $result = $this->studentClassService
                ->fetchStudentClasses($gradeId);

            return response()->json([
                'success' => true,
                'message' => 'Student classes fetched successfully.',
                'grade' => $result['grade'],
                'data' => $result['data'],
            ]);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch student classes.',
            ], 500);
        }
    }
}
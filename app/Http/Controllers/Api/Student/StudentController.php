<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Services\Api\Student\StudentApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class StudentController extends Controller
{
    public function __construct(
        private StudentApiService $studentApiService
    ) {
    }

    /**
     * Fetch all accessible students.
     */
    public function fetchAllStudent(): JsonResponse
    {
        try {
            $students = $this->studentApiService->fetchAllStudents();

            return response()->json([
                'success' => true,
                'data' => $students,
            ], 200);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load students.',
            ], 500);
        }
    }

    /**
     * Get student details using QR code.
     */
    public function studentDetailsSearch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'qr_code' => [
                'required',
                'string',
                'max:150',
            ],
        ]);

        try {
            $student = $this->studentApiService->findStudentByQr(
                $validated['qr_code']
            );

            return response()->json([
                'success' => true,
                'message' => 'Student details loaded successfully',
                'student' => $student,
            ], 200);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Search students.
     */
    public function searchStudent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => [
                'required',
                'string',
                'min:1',
            ],
        ]);

        try {
            $students = $this->studentApiService->searchStudents(
                $validated['search']
            );

            return response()->json([
                'success' => true,
                'message' => $students->isNotEmpty()
                    ? 'Students found successfully'
                    : 'No students found',
                'data' => $students,
            ], 200);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
            ], 500);
        }
    }

    /**
     * Update student image using a QuickPhoto.
     */
    public function updateStudentImage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => [
                'required',
                'integer',
                'exists:students,id',
            ],
            'custom_id' => [
                'required',
                'string',
                'exists:quick_photos,custom_id',
            ],
        ]);

        try {
            $responseData = $this->studentApiService->updateStudentImage(
                (int) $validated['student_id'],
                $validated['custom_id']
            );

            return response()->json([
                'success' => true,
                'message' => 'Student image updated successfully',
                'data' => $responseData,
            ], 200);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
                    ?: 'Failed to update student image',
            ], 422);
        }
    }
}
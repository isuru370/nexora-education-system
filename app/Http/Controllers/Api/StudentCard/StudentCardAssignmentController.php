<?php

namespace App\Http\Controllers\Api\StudentCard;

use App\Http\Controllers\Controller;
use App\Services\Web\StudentCard\StudentCardAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StudentCardAssignmentController extends Controller
{
    public function __construct(
        protected StudentCardAssignmentService $studentCardAssignmentService
    ) {}

    /**
     * Search Student
     */
    public function searchStudent(
        Request $request
    ): JsonResponse {

        $request->validate([
            'code' => ['required', 'string'],
        ]);

        try {

            $result = $this->studentCardAssignmentService
                ->searchStudent(
                    $request->code
                );

            return response()->json([

                'success' => true,

                'message' => 'Student retrieved successfully.',

                'data' => $result,

            ]);
        } catch (\Throwable $e) {

            return response()->json([

                'success' => false,

                'message' => \App\Support\ErrorDetail::safe($e),

                'data' => null,

            ], 422);
        }
    }

    /**
     * Search Available Card
     */
    public function searchAvailableCard(
        Request $request
    ): JsonResponse {

        $request->validate([
            'qr_code' => ['required', 'string'],
        ]);

        try {

            $card = $this->studentCardAssignmentService
                ->searchAvailableCard(
                    $request->qr_code
                );

            return response()->json([

                'success' => true,

                'message' => 'Available student card found.',

                'data' => $card,

            ]);
        } catch (\Throwable $e) {

            return response()->json([

                'success' => false,

                'message' => \App\Support\ErrorDetail::safe($e),

                'data' => null,

            ], 422);
        }
    }

    /**
     * Assign Card
     */

    public function assignCard(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'student_id' => [
                    'required',
                    'integer',
                    'exists:students,id',
                ],
                'card_qr_code' => [
                    'required',
                    'string',
                    'exists:student_cards,qr_code',
                ],
            ]);

            $card = $this->studentCardAssignmentService->assignCard(
                (int) $validated['student_id'],
                $validated['card_qr_code']
            );

            return response()->json([
                'success' => true,
                'message' => 'Student card assigned successfully.',
                'data' => $card,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
                'data' => null,
            ], 422);
        } catch (\Throwable $e) {
            Log::error(
                'Mobile student card assignment failed.',
                [
                    'student_id' => $request->input('student_id'),
                    'card_qr_code' => $request->input('card_qr_code'),
                    'error' => $e->getMessage(),
                ]
            );

            $isExpectedError = $e instanceof \Exception
                && !($e instanceof \Illuminate\Database\QueryException);

            return response()->json([
                'success' => false,
                'message' => $isExpectedError
                    ? \App\Support\ErrorDetail::safe($e)
                    : 'Unable to assign student card. Please try again.',
                'data' => null,
            ], $isExpectedError ? 422 : 500);
        }
    }
}

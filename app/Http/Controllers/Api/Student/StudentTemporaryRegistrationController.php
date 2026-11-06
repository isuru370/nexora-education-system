<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Student\MobileTemporaryStudentRequest;
use App\Services\Web\Student\Registtration\StudentTemporaryRegistrationService;
use App\Services\Web\Student\StudentTemporaryCardService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Throwable;

/**
 * POST /api/student - register a student with a temporary ID card (mobile app).
 * GET  /api/temporary-cards/next - the card the next student will get.
 */
class StudentTemporaryRegistrationController extends Controller
{
    use StudentRegistrationResponse;

    public function __construct(
        private StudentTemporaryRegistrationService $registrationService
    ) {}

    public function next(StudentTemporaryCardService $cards): JsonResponse
    {
        $next = $cards->nextIssuedCard();

        return response()->json([
            'success' => true,
            'data' => [
                'next' => $next?->temporary_id_number,
                'issued_left' => $cards->issuedLeftCount(),
                'message' => $next ? null : StudentTemporaryCardService::NONE_LEFT_MESSAGE,
            ],
        ]);
    }

    public function store(MobileTemporaryStudentRequest $request): JsonResponse
    {
        try {
            return $this->registeredResponse(
                $this->registrationService->register($request->validated())
            );
        } catch (QueryException $e) {
            // Database error - never show SQL to the user
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Student registration failed. Please try again.',
            ], 500);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => \App\Support\ErrorDetail::safe($e) ?: 'Student registration failed.',
            ], 422);
        }
    }
}

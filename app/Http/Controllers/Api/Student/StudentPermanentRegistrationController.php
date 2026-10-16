<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Student\MobileCardStudentRequest;
use App\Services\Web\Student\Registtration\StudentCardRegistrationService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Throwable;

/**
 * POST /api/student-new - register a student with a permanent student card (mobile app).
 */
class StudentPermanentRegistrationController extends Controller
{
    use StudentRegistrationResponse;

    public function __construct(
        private StudentCardRegistrationService $registrationService
    ) {}

    public function store(MobileCardStudentRequest $request): JsonResponse
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
                'message' => $e->getMessage() ?: 'Student registration failed.',
            ], 422);
        }
    }
}

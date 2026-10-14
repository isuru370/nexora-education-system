<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Student\StoreStudentCardRegistrationRequest;
use App\Services\Web\Student\Registtration\StudentCardRegistrationService;
use Illuminate\Http\JsonResponse;
use Throwable;

class StudentPermanentRegistrationController extends Controller
{
    public function __construct(
        private StudentCardRegistrationService $registrationService
    ) {
    }

    /**
     * Register a student with a permanent physical card.
     */
    public function store(
        StoreStudentCardRegistrationRequest $request
    ): JsonResponse {
        try {
            $result = $this->registrationService->register(
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Student registered successfully.',
                'student' => $result['student'],
                'admission_payment' => $result['admission_payment'] ?? null,
                'portal' => [
                    'username' => $result['portal_username'] ?? null,
                    'password' => $result['portal_password'] ?? null,
                ],
            ], 201);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
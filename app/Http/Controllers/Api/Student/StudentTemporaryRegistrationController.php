<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Services\Web\Student\Registtration\StudentTemporaryRegistrationService;
use App\Http\Requests\Web\Student\StoreStudentRequest;
use Illuminate\Http\JsonResponse;
use Throwable;

class StudentTemporaryRegistrationController extends Controller
{
    public function __construct(
        private StudentTemporaryRegistrationService $registrationService
    ) {}

    public function store(
        StoreStudentRequest $request
    ): JsonResponse {
        try {

            $result = $this->registrationService->register(
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Student registered successfully.',

                'student' => $result['student'],

                'admission_payment' =>
                $result['admission_payment'],

                'portal' => [
                    'username' =>
                    $result['portal_username'],

                    'password' =>
                    $result['portal_password'],
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

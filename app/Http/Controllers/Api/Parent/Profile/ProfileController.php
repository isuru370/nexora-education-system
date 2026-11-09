<?php

namespace App\Http\Controllers\Api\Parent\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parent\Payment\StudentPaymentRequest;
use App\Services\Parent\Profile\StudentProfileService;
use Illuminate\Http\JsonResponse;

/**
 * POST /api/parent/v1/profile - the student's details, institute, card and
 * classes. Read only: the institute keeps these details.
 */
class ProfileController extends Controller
{
    public function __construct(
        private StudentProfileService $profileService
    ) {}

    public function show(StudentPaymentRequest $request): JsonResponse
    {
        $response = $this->profileService->fetch((int) $request->validated()['student_id']);

        return response()->json($response, $response['status'] ? 200 : 404);
    }
}

<?php

namespace App\Http\Controllers\API\Teacher;

use App\Http\Controllers\Controller;
use App\Services\Teacher\TeacherProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class TeacherProfileController extends Controller
{
    public function __construct(
        private  TeacherProfileService $teacherProfileService
    ) {}

    /**
     * Get teacher profile.
     */
    public function show(): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            $teacher = $user->teacher;

            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher profile not found.',
                ], 404);
            }

            if (!$teacher->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher account is inactive.',
                ], 403);
            }

            $profile = $this->teacherProfileService->getProfile(
                teacherId: $teacher->id,
            );

            return response()->json([
                'success' => true,
                'message' => 'Teacher profile loaded successfully.',
                'data' => $profile,
            ], 200);

        } catch (Throwable $e) {

            Log::error(
                'Teacher profile load failed.',
                [
                    'user_id' => auth()->id(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Unable to load teacher profile.',
            ], 500);
        }
    }

    /**
     * Update teacher profile.
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            $teacher = $user->teacher;

            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher profile not found.',
                ], 404);
            }

            if (!$teacher->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher account is inactive.',
                ], 403);
            }

            $validated = $request->validate([
                'full_name' => [
                    'sometimes',
                    'string',
                    'max:255',
                ],

                'initials' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'max:100',
                ],

                'email' => [
                    'sometimes',
                    'nullable',
                    'email',
                    'max:255',
                ],

                'mobile' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'max:30',
                ],

                'nic' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'max:30',
                ],

                'bday' => [
                    'sometimes',
                    'nullable',
                    'date',
                ],

                'gender' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'max:30',
                ],

                'address1' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'max:255',
                ],

                'address2' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'max:255',
                ],

                'address3' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'max:255',
                ],

                'graduation_details' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'max:1000',
                ],

                'experience' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'max:255',
                ],

                'account_number' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'max:100',
                ],

                'bank_branch_id' => [
                    'sometimes',
                    'nullable',
                    'integer',
                    'exists:bank_branches,id',
                ],
            ]);

            $profile = $this->teacherProfileService->updateProfile(
                teacherId: $teacher->id,
                data: $validated,
            );

            return response()->json([
                'success' => true,
                'message' => 'Teacher profile updated successfully.',
                'data' => $profile,
            ], 200);

        } catch (ValidationException $e) {

            throw $e;

        } catch (Throwable $e) {

            Log::error(
                'Teacher profile update failed.',
                [
                    'user_id' => auth()->id(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Unable to update teacher profile.',
            ], 500);
        }
    }

    /**
     * Change teacher password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            $teacher = $user->teacher;

            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher profile not found.',
                ], 404);
            }

            if (!$teacher->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher account is inactive.',
                ], 403);
            }

            $validated = $request->validate([
                'current_password' => [
                    'required',
                    'string',
                ],

                'new_password' => [
                    'required',
                    'string',
                    'min:8',
                    'confirmed',
                ],
            ]);

            $this->teacherProfileService->changePassword(
                teacherId: $teacher->id,
                currentPassword: $validated['current_password'],
                newPassword: $validated['new_password'],
            );

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully.',
            ], 200);

        } catch (ValidationException $e) {

            throw $e;

        } catch (Throwable $e) {

            Log::error(
                'Teacher password change failed.',
                [
                    'user_id' => auth()->id(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Unable to change password.',
            ], 500);
        }
    }
}
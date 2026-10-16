<?php

namespace App\Http\Requests\Api\Student;

use App\Services\Branch\BranchAccessService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * The mobile app does not send branch_id when it registers a student.
 * Pick the branch for it:
 *   1. branch_id sent by the app (newer app versions)
 *   2. the given branch (e.g. the branch of the scanned permanent card)
 *   3. the user's only branch
 * If none of these apply (a user with several branches and a temporary
 * card), branch_id stays empty and validation asks for it.
 *
 * Validation errors are returned with the first error as "message",
 * because the app shows only "message" to the user.
 */
trait MobileRegistrationRules
{
    protected function mobileBranchId(?int $preferredBranchId = null): ?int
    {
        if ($this->filled('branch_id')) {
            return (int) $this->input('branch_id');
        }

        $branchIds = app(BranchAccessService::class)->getBranchIds()->map(fn ($id) => (int) $id);

        if ($preferredBranchId !== null && $branchIds->contains($preferredBranchId)) {
            return $preferredBranchId;
        }

        return $branchIds->count() === 1 ? $branchIds->first() : null;
    }

    /** Mobile sends admission_id only - having one means an admission fee is paid */
    protected function mobileHasAdmission(): bool
    {
        return $this->boolean('admission') || $this->filled('admission_id');
    }

    protected function mobileMessages(): array
    {
        return [
            'branch_id.required' => 'Please select a branch. Your account has access to more than one branch.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422));
    }
}
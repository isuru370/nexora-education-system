<?php

namespace App\Http\Requests\Api\Student;

use App\Http\Requests\Web\Student\StoreStudentRequest;

/**
 * POST /api/student - mobile app, temporary ID card (TMP001...)
 *
 * Same rules as the web form, plus:
 *   - branch_id is filled in when the app does not send it
 *   - admission_id alone turns on the admission payment
 *   - quick_image_id (photo taken earlier with "Capture Image") is accepted
 */
class MobileTemporaryStudentRequest extends StoreStudentRequest
{
    use MobileRegistrationRules;

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge([
            'branch_id' => $this->mobileBranchId(),
            'admission' => $this->mobileHasAdmission(),
        ]);
    }

    public function rules(): array
    {
        return parent::rules() + [
            'quick_image_id' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return $this->mobileMessages() + parent::messages();
    }
}
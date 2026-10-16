<?php

namespace App\Http\Requests\Api\Student;

use App\Http\Requests\Web\Student\StoreStudentCardRegistrationRequest;
use App\Models\StudentCard;

/**
 * POST /api/student-new - mobile app, permanent student card
 *
 * Same rules as the web form, plus:
 *   - branch_id defaults to the branch of the scanned card
 *   - admission_id alone turns on the admission payment
 *   - quick_image_id (photo taken earlier with "Capture Image") is accepted
 */
class MobileCardStudentRequest extends StoreStudentCardRegistrationRequest
{
    use MobileRegistrationRules;

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $cardBranchId = null;

        if ($this->filled('card_qr_code')) {
            $cardBranchId = StudentCard::query()
                ->where('qr_code', strtoupper(trim((string) $this->input('card_qr_code'))))
                ->value('branch_id');
        }

        $this->merge([
            'branch_id' => $this->mobileBranchId($cardBranchId !== null ? (int) $cardBranchId : null),
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
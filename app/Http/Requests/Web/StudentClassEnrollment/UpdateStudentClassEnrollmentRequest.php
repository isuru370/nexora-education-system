<?php

namespace App\Http\Requests\Web\StudentClassEnrollment;

use App\Models\ClassCategoryFee;
use App\Models\ClassCategoryFeeOption;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateStudentClassEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'class_category_fee_id' => [
                'required',
                'integer',
                'exists:class_category_fees,id',
            ],

            'class_category_fee_option_id' => [
                'required',
                'integer',
                'exists:class_category_fee_options,id',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'is_free_card' => [
                'nullable',
                'boolean',
            ],

            'enrolled_at' => [
                'nullable',
                'date',
            ],

            'left_at' => [
                'nullable',
                'date',
                'after_or_equal:enrolled_at',
            ],

            'note' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * Cross-field validation.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {

            $categoryFeeId = $this->input('class_category_fee_id');
            $feeOptionId = $this->input('class_category_fee_option_id');

            if (!$categoryFeeId || !$feeOptionId) {
                return;
            }

            // ✅ 1. Category fee එක active ද
            $categoryFee = ClassCategoryFee::query()
                ->where('id', $categoryFeeId)
                ->where('is_active', true)
                ->first();

            if (!$categoryFee) {
                $validator->errors()->add(
                    'class_category_fee_id',
                    'Selected category fee is invalid or inactive.'
                );
                return;
            }

            // ✅ 2. Fee option එක category fee එකට අයිතිද
            $feeOption = ClassCategoryFeeOption::query()
                ->where('id', $feeOptionId)
                ->where('class_category_fee_id', $categoryFee->id)
                ->where('is_active', true)
                ->first();

            if (!$feeOption) {
                $validator->errors()->add(
                    'class_category_fee_option_id',
                    'Selected fee option is invalid or does not belong to the selected category.'
                );
            }

            // ✅ 3. is_active = false නම් left_at අනිවාර්යයි
            $isActive = $this->input('is_active');

            if ($isActive !== null && !(bool) $isActive) {
                if (empty($this->input('left_at')) && !empty($this->input('enrolled_at'))) {
                    // Service එකේ auto-set කරන නිසා warning එකක් අවශ්ය නැහැ
                    // ඒත් validation error එකක් දෙන්න ඕන නම්:
                    $validator->errors()->add('left_at', 'Left date is required when enrollment is inactive.');
                }
            }
        });
    }
}
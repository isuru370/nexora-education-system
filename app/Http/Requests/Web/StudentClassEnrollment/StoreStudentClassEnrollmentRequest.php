<?php

namespace App\Http\Requests\Web\StudentClassEnrollment;

use App\Models\ClassCategoryFee;
use App\Models\ClassCategoryFeeOption;
use App\Models\StudentClass;
use App\Services\Branch\BranchAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreStudentClassEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => [
                'required',
                'integer',
                'exists:students,id',
            ],

            'student_class_id' => [
                'required',
                'integer',
                'exists:student_classes,id',
            ],

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

            'is_free_card' => [
                'nullable',
                'boolean',
            ],

            'enrolled_at' => [
                'nullable',
                'date',
            ],

            'note' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * Cross-field validation + branch access.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {

            // ✅ Branch access check
            $branchAccessService = app(BranchAccessService::class);
            $accessibleBranchIds = $branchAccessService->getBranchIds();

            $studentClassId = $this->input('student_class_id');
            $categoryFeeId = $this->input('class_category_fee_id');
            $feeOptionId = $this->input('class_category_fee_option_id');

            if (!$studentClassId || !$categoryFeeId || !$feeOptionId) {
                return;
            }

            // ✅ 1. Class එකට access තියෙනවද
            $studentClass = StudentClass::query()
                ->where('id', $studentClassId)
                ->whereIn('branch_id', $accessibleBranchIds)
                ->first();

            if (!$studentClass) {
                $validator->errors()->add(
                    'student_class_id',
                    'Selected class is invalid or you do not have access to it.'
                );
                return;
            }

            // ✅ 2. Category fee එක class එකට අයිතිද
            $categoryFee = ClassCategoryFee::query()
                ->where('id', $categoryFeeId)
                ->where('student_class_id', $studentClassId)
                ->where('is_active', true)
                ->first();

            if (!$categoryFee) {
                $validator->errors()->add(
                    'class_category_fee_id',
                    'Selected category fee is not assigned to this class or is inactive.'
                );
                return;
            }

            // ✅ 3. Fee option එක category fee එකට අයිතිද
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
        });
    }
}
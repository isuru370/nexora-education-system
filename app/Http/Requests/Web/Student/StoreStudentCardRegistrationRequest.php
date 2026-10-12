<?php

namespace App\Http\Requests\Web\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentCardRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'admission' => $this->boolean('admission'),
        ]);
    }

    public function rules(): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | Main Branch
            |--------------------------------------------------------------------------
            |
            | This is the student's main / controlling branch.
            | The student can still enroll in classes belonging to other branches.
            |
            */
            'branch_id' => [
                'required',
                'integer',
                'exists:branches,id',
            ],

            /*
            |--------------------------------------------------------------------------
            | Permanent Student Card
            |--------------------------------------------------------------------------
            */
            'card_qr_code' => [
                'required',
                'string',
            ],

            /*
            |--------------------------------------------------------------------------
            | Student Personal Details
            |--------------------------------------------------------------------------
            */
            'full_name' => [
                'nullable',
                'string',
                'max:150',
            ],

            'initial_name' => [
                'required',
                'string',
                'max:100',
            ],

            'mobile' => [
                'nullable',
                'string',
                'max:15',
            ],

            'whatsapp_mobile' => [
                'nullable',
                'string',
                'max:15',
            ],

            'email' => [
                'nullable',
                'email',
                'max:150',
                'unique:students,email',
            ],

            'nic' => [
                'nullable',
                'string',
                'max:20',
                'unique:students,nic',
            ],

            'bday' => [
                'nullable',
                'date',
                'before:today',
            ],

            'gender' => [
                'required',
                'in:male,female,other',
            ],

            /*
            |--------------------------------------------------------------------------
            | Address
            |--------------------------------------------------------------------------
            */
            'address1' => [
                'nullable',
                'string',
                'max:150',
            ],

            'address2' => [
                'nullable',
                'string',
                'max:150',
            ],

            'address3' => [
                'nullable',
                'string',
                'max:150',
            ],

            /*
            |--------------------------------------------------------------------------
            | Guardian Details
            |--------------------------------------------------------------------------
            */
            'guardian_fname' => [
                'nullable',
                'string',
                'max:100',
            ],

            'guardian_lname' => [
                'nullable',
                'string',
                'max:100',
            ],

            'guardian_nic' => [
                'nullable',
                'string',
                'max:20',
            ],

            'guardian_mobile' => [
                'required',
                'string',
                'max:15',
            ],

            /*
            |--------------------------------------------------------------------------
            | Academic Details
            |--------------------------------------------------------------------------
            */
            'grade_id' => [
                'required',
                'exists:grades,id',
            ],

            'class_type' => [
                'nullable',
                'string',
                'max:50',
            ],

            /*
            |--------------------------------------------------------------------------
            | Admission
            |--------------------------------------------------------------------------
            */
            'admission' => [
                'boolean',
            ],

            'admission_id' => [
                Rule::requiredIf($this->boolean('admission')),
                'nullable',
                'exists:admissions,id',
            ],

            /*
            |--------------------------------------------------------------------------
            | Other Details
            |--------------------------------------------------------------------------
            */
            'student_school' => [
                'nullable',
                'string',
                'max:150',
            ],

            /*
            |--------------------------------------------------------------------------
            | Student Image
            |--------------------------------------------------------------------------
            */
            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' =>
                'Please select the student main branch.',

            'branch_id.exists' =>
                'Selected main branch does not exist.',

            'card_qr_code.required' =>
                'Student card QR code is required.',

            'admission_id.required' =>
                'Please select an admission type.',

            'grade_id.required' =>
                'Grade is required.',
        ];
    }
}
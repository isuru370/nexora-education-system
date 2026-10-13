<?php

namespace App\Http\Requests\Web\Admission;


class UpdateAdmissionPaymentRequest extends StoreAdmissionPaymentRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        $payment = $this->route('admissionPayment');

        /*
         * Allow keeping the payment's current admission even if that
         * admission was deactivated later. Any other choice must be active.
         */
        $rules['admission_id'] = [
            'required',
            'integer',
            function ($attribute, $value, $fail) use ($payment) {
                if ($payment && (int) $value === (int) $payment->admission_id) {
                    return;
                }

                $isActive = \App\Models\Admission::query()
                    ->whereKey($value)
                    ->where('is_active', true)
                    ->exists();

                if (!$isActive) {
                    $fail('Selected admission is not active.');
                }
            },
        ];

        return $rules;
    }
}
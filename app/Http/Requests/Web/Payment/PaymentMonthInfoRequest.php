<?php

namespace App\Http\Requests\Web\Payment;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Payment screen: one class + one month (attendance, fee after the
 * Payment Rules, paid or not, tute).
 */
class PaymentMonthInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:100'],
            'enrollment_id' => ['required', 'integer', 'min:1'],
            'payment_month' => ['required', 'date'],
        ];
    }
}

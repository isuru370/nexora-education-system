<?php

namespace App\Http\Controllers\Web\PaymentRules;

use App\Http\Controllers\Controller;
use App\Support\AttendanceRules;
use App\Support\MultiClassDiscount;
use App\Support\PaymentRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Payment Rules (Super Admin): per-institute class fee payment options.
 * See App\Support\PaymentRules.
 */
class PaymentRulesController extends Controller
{
    public function index(): View
    {
        return view('payment-rules.index', [
            'rules' => PaymentRules::all(),
            'multiClass' => MultiClassDiscount::all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fee_option' => ['nullable', 'boolean'],
            'custom_amount' => ['nullable', 'boolean'],
            'no_attendance' => ['nullable', 'boolean'],
            'tute' => ['nullable', 'boolean'],
            'print_receipt' => ['nullable', 'boolean'],
            'before_minutes' => ['nullable', 'integer', 'min:0', 'max:' . AttendanceRules::MAX_MINUTES],
            'after_minutes' => ['nullable', 'integer', 'min:0', 'max:' . AttendanceRules::MAX_MINUTES],
            'no_time_limit' => ['nullable', 'boolean'],
            'multi_class_enabled' => ['nullable', 'boolean'],
            'multi_class_min' => ['nullable', 'integer', 'min:2', 'max:20', 'required_if:multi_class_enabled,1'],
            'multi_class_amount' => ['nullable', 'numeric', 'min:1', 'max:100000', 'required_if:multi_class_enabled,1'],
        ], [
            'multi_class_min.required_if' => 'Enter the number of classes for the multi-class discount.',
            'multi_class_amount.required_if' => 'Enter the discount amount for the multi-class discount.',
        ]);

        MultiClassDiscount::save([
            'enabled' => (bool) ($validated['multi_class_enabled'] ?? false),
            'min_classes' => (int) ($validated['multi_class_min'] ?? MultiClassDiscount::DEFAULT_MIN),
            'amount' => (float) ($validated['multi_class_amount'] ?? 0),
        ]);

        PaymentRules::save([
            'fee_option' => (bool) ($validated['fee_option'] ?? false),
            'custom_amount' => (bool) ($validated['custom_amount'] ?? false),
            'no_attendance' => (bool) ($validated['no_attendance'] ?? false),
            'tute' => (bool) ($validated['tute'] ?? false),
            'print_receipt' => (bool) ($validated['print_receipt'] ?? false),
        ]);

        AttendanceRules::save([
            'before_minutes' => $validated['before_minutes'] ?? AttendanceRules::DEFAULT_BEFORE,
            'after_minutes' => $validated['after_minutes'] ?? AttendanceRules::DEFAULT_AFTER,
            'no_time_limit' => (bool) ($validated['no_time_limit'] ?? false),
        ]);

        return redirect()->route('payment-rules.index')->with('success', 'Payment and attendance rules saved.');
    }
}

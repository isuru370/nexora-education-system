<?php

namespace App\Http\Controllers\Web\PaymentRules;

use App\Http\Controllers\Controller;
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
        return view('payment-rules.index', ['rules' => PaymentRules::all()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fee_option' => ['nullable', 'boolean'],
            'custom_amount' => ['nullable', 'boolean'],
            'no_attendance' => ['nullable', 'boolean'],
            'tute' => ['nullable', 'boolean'],
        ]);

        PaymentRules::save(array_map(fn ($value) => (bool) $value, $validated));

        return redirect()->route('payment-rules.index')->with('success', 'Payment rules saved.');
    }
}

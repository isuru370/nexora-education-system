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
            'custom_amount' => ['nullable', 'boolean'],
            'attendance_rule' => ['nullable', 'boolean'],
            'tute' => ['nullable', 'boolean'],
            'tiers' => ['nullable', 'array', 'max:20'],
            'tiers.*.attendance' => ['required_with:tiers', 'integer', 'min:0', 'max:60', 'distinct'],
            'tiers.*.percent' => ['required_with:tiers', 'integer', 'min:0', 'max:100'],
        ], [
            'tiers.*.attendance.distinct' => 'Each attendance count can be listed only once.',
        ]);

        $tiers = collect($validated['tiers'] ?? [])
            ->mapWithKeys(fn ($row) => [(int) $row['attendance'] => (int) $row['percent']])
            ->all();

        if (!empty($validated['attendance_rule']) && $tiers === []) {
            return back()->withInput()->withErrors(['tiers' => 'Add at least one attendance row.']);
        }

        PaymentRules::save([
            'custom_amount' => (bool) ($validated['custom_amount'] ?? false),
            'attendance_rule' => (bool) ($validated['attendance_rule'] ?? false),
            'tute' => (bool) ($validated['tute'] ?? false),
            'tiers' => $tiers ?: PaymentRules::DEFAULT_TIERS,
        ]);

        return redirect()->route('payment-rules.index')->with('success', 'Payment rules saved.');
    }
}

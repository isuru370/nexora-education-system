<?php

namespace App\Http\Controllers\Web\ReceiptSettings;

use App\Http\Controllers\Controller;
use App\Support\ReceiptRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Settings > Receipt (Admin / Super Admin): what the class payment receipt
 * shows. See App\Support\ReceiptRules.
 */
class ReceiptSettingsController extends Controller
{
    public function index(): View
    {
        return view('setting.receipt.index', [
            'rules' => ReceiptRules::all(),
            'fields' => ReceiptRules::FIELDS,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = collect(ReceiptRules::FIELDS)->keys()
            ->mapWithKeys(fn ($name) => [$name => ['nullable', 'boolean']])
            ->all();

        $validated = $request->validate($rules + [
            'footer' => ['nullable', 'string', 'max:' . ReceiptRules::FOOTER_MAX],
        ]);

        ReceiptRules::save(
            collect(ReceiptRules::FIELDS)->keys()
                ->mapWithKeys(fn ($name) => [$name => (bool) ($validated[$name] ?? false)])
                ->all(),
            $validated['footer'] ?? null
        );

        return redirect()->route('admin.setting.receipt')->with('success', 'Receipt settings saved.');
    }
}

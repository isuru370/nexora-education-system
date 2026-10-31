<?php

namespace App\Http\Controllers\Web\CardDesigner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Support\CardDesign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Card Designer (Super Admin): this institute's ID card layouts.
 * See App\Support\CardDesign.
 */
class CardDesignerController extends Controller
{
    public function index(Request $request): View
    {
        $type = in_array($request->query('type'), CardDesign::TYPES, true)
            ? $request->query('type')
            : CardDesign::PERMANENT;

        $design = CardDesign::get($type);

        // Every field is drawn so it can be switched on in the page
        $allOn = $design;
        foreach ($allOn['fields'] as $key => $field) {
            $allOn['fields'][$key]['on'] = true;
        }

        return view('card-designer.index', [
            'type' => $type,
            'design' => $design,
            'card' => CardDesign::card($type, $this->sample($type), false, $allOn),
            'defaults' => CardDesign::defaults($type),
        ]);
    }

    public function update(Request $request, string $type): RedirectResponse
    {
        $this->checkType($type);

        $validated = $request->validate([
            'fields' => ['required', 'string', 'max:20000'],
            'background' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:5120'],
            'remove_background' => ['nullable', 'boolean'],
        ]);

        $fields = json_decode($validated['fields'], true);
        if (!is_array($fields)) {
            throw ValidationException::withMessages(['fields' => 'The card layout could not be read. Please try again.']);
        }

        $current = CardDesign::get($type)['background'];
        $background = $current;

        if ($request->hasFile('background')) {
            $background = $request->file('background')->store(CardDesign::BACKGROUND_DIR, 'public');
            $this->deleteBackground($current);
        } elseif ($request->boolean('remove_background')) {
            $this->deleteBackground($current);
            $background = null;
        }

        CardDesign::save($type, $fields, $background, auth()->id());

        return redirect()
            ->route('card-designer.index', ['type' => $type])
            ->with('success', CardDesign::LABELS[$type] . ' design saved.');
    }

    public function reset(string $type): RedirectResponse
    {
        $this->checkType($type);

        $this->deleteBackground(CardDesign::get($type)['background']);
        CardDesign::reset($type);

        return redirect()
            ->route('card-designer.index', ['type' => $type])
            ->with('success', CardDesign::LABELS[$type] . ': back to the default design.');
    }

    private function checkType(string $type): void
    {
        abort_unless(in_array($type, CardDesign::TYPES, true), 404);
    }

    /** Only files this page uploaded are deleted */
    private function deleteBackground(?string $path): void
    {
        if ($path && str_starts_with($path, CardDesign::BACKGROUND_DIR . '/')) {
            Storage::disk('public')->delete($path);
        }
    }

    /** Made-up student shown on the designer */
    private function sample(string $type): array
    {
        if ($type === CardDesign::TEMPORARY) {
            return ['code' => 'TMP001', 'qr' => 'TMP001'];
        }

        return [
            'student_id' => 'SA26001',
            'name' => 'K.M.S.PERERA',
            'branch' => optional(Branch::query()->orderBy('id')->first())->name ?? 'Main Branch',
            'address' => 'No. 12, Temple Road, Colombo',
            'grade' => 'Grade 10',
            'mobile' => '0771234567',
            'qr' => 'SA26001',
            'photo' => null,
        ];
    }
}

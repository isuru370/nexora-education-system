<?php

namespace App\Http\Controllers\Web\CardDesigner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\StudentCard;
use App\Support\CardDesign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Card Designer (Super Admin): this institute's ID card layouts.
 * Temporary-card institutes: student card + temporary card.
 * Permanent-card institutes: the pre-printed card stock, one design per branch.
 * See App\Support\CardDesign.
 */
class CardDesignerController extends Controller
{
    public function index(Request $request): View
    {
        $types = CardDesign::typesForMode();
        $type = in_array($request->query('type'), $types, true)
            ? $request->query('type')
            : $types[0];

        $branches = $this->branches($type);
        $branchId = $this->branchId($type, $request->query('branch_id'), $branches);

        $design = CardDesign::get($type, $branchId);

        // Every field is drawn so it can be switched on in the page
        $allOn = $design;
        foreach ($allOn['fields'] as $key => $field) {
            $allOn['fields'][$key]['on'] = true;
        }

        return view('card-designer.index', [
            'type' => $type,
            'types' => $types,
            'branches' => $branches,
            'branchId' => $branchId,
            'design' => $design,
            'card' => CardDesign::card($type, $this->sample($type, $branchId), false, $allOn),
            'defaults' => CardDesign::defaults($type, $branchId),
        ]);
    }

    public function update(Request $request, string $type): RedirectResponse
    {
        $this->checkType($type);
        $branchId = $this->branchId($type, $request->input('branch_id'), $this->branches($type), true);

        $validated = $request->validate([
            'fields' => ['required', 'string', 'max:20000'],
            'background' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:5120'],
            'remove_background' => ['nullable', 'boolean'],
        ]);

        $fields = json_decode($validated['fields'], true);
        if (!is_array($fields)) {
            throw ValidationException::withMessages(['fields' => 'The card layout could not be read. Please try again.']);
        }

        $current = CardDesign::get($type, $branchId)['background'];
        $background = $current;

        if ($request->hasFile('background')) {
            $background = $request->file('background')->store(CardDesign::BACKGROUND_DIR, 'public');
            $this->deleteBackground($current);
        } elseif ($request->boolean('remove_background')) {
            $this->deleteBackground($current);
            $background = null;
        }

        CardDesign::save($type, $fields, $background, auth()->id(), $branchId);

        return redirect()
            ->route('card-designer.index', array_filter(['type' => $type, 'branch_id' => $branchId]))
            ->with('success', CardDesign::LABELS[$type] . $this->branchLabel($branchId) . ' design saved.');
    }

    public function reset(Request $request, string $type): RedirectResponse
    {
        $this->checkType($type);
        $branchId = $this->branchId($type, $request->input('branch_id'), $this->branches($type), true);

        $this->deleteBackground(CardDesign::get($type, $branchId)['background']);
        CardDesign::reset($type, $branchId);

        return redirect()
            ->route('card-designer.index', array_filter(['type' => $type, 'branch_id' => $branchId]))
            ->with('success', CardDesign::LABELS[$type] . $this->branchLabel($branchId) . ': back to the default design.');
    }

    /** Only the designs of this institute's card mode */
    private function checkType(string $type): void
    {
        abort_unless(in_array($type, CardDesign::typesForMode(), true), 404);
    }

    /** Branches to choose from (per-branch designs only) */
    private function branches(string $type)
    {
        if (!in_array($type, CardDesign::BRANCH_TYPES, true)) {
            return collect();
        }

        return Branch::query()->orderBy('id')->get(['id', 'name']);
    }

    /** The chosen branch; a per-branch design always has one */
    private function branchId(string $type, $requested, $branches, bool $strict = false): ?int
    {
        if (!in_array($type, CardDesign::BRANCH_TYPES, true)) {
            return null;
        }

        abort_if($branches->isEmpty(), 404, 'Add a branch first.');

        $id = (int) $requested;
        if ($branches->contains('id', $id)) {
            return $id;
        }

        abort_if($strict, 422, 'Choose a branch.');

        return (int) $branches->first()->id;
    }

    private function branchLabel(?int $branchId): string
    {
        if (!$branchId) {
            return '';
        }

        return ' (' . (Branch::query()->whereKey($branchId)->value('name') ?? 'Branch ' . $branchId) . ')';
    }

    /** Only files this page uploaded are deleted */
    private function deleteBackground(?string $path): void
    {
        if ($path && str_starts_with($path, CardDesign::BACKGROUND_DIR . '/')) {
            Storage::disk('public')->delete($path);
        }
    }

    /** Made-up student shown on the designer */
    private function sample(string $type, ?int $branchId = null): array
    {
        if ($type === CardDesign::TEMPORARY) {
            return ['code' => 'TMP001', 'qr' => 'TMP001'];
        }

        if ($type === CardDesign::STOCK) {
            // a real card number of this branch, so the size looks right
            $code = StudentCard::query()->where('branch_id', $branchId)->orderBy('id')->value('qr_code') ?: 'SA11045';

            return ['code' => $code, 'qr' => $code];
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

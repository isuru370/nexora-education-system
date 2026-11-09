<?php

namespace App\Http\Requests\Web\StudentClass;

/**
 * "Categories & Fees" on the class form: at least one category with at
 * least one active fee option.
 *
 * fees[i][category_id], fees[i][is_active], fees[i][default] (option index),
 * fees[i][options][j][id|label|fee|is_active]
 */
trait ValidatesClassFees
{
    protected function feeRules(): array
    {
        return [
            'fees' => ['required', 'array', 'min:1'],
            'fees.*.category_id' => ['required', 'integer', 'distinct', 'exists:class_categories,id'],
            'fees.*.is_active' => ['sometimes', 'boolean'],
            'fees.*.default' => ['nullable', 'integer', 'min:0'],
            'fees.*.options' => ['required', 'array', 'min:1'],
            'fees.*.options.*.id' => ['nullable', 'integer'],
            'fees.*.options.*.label' => ['required', 'string', 'max:100'],
            'fees.*.options.*.fee' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'fees.*.options.*.is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function feeMessages(): array
    {
        return [
            'fees.required' => 'Add at least one category with a fee.',
            'fees.*.category_id.required' => 'Choose a category.',
            'fees.*.category_id.distinct' => 'The same category is added twice.',
            'fees.*.options.required' => 'Each category needs at least one fee option.',
            'fees.*.options.*.label.required' => 'Each fee option needs a name (e.g. Normal).',
            'fees.*.options.*.fee.required' => 'Each fee option needs a fee.',
            'fees.*.options.*.fee.numeric' => 'The fee must be a number.',
        ];
    }

    protected function checkFees($validator): void
    {
        $blocks = (array) $this->input('fees', []);
        $activeOptions = 0;

        foreach ($blocks as $i => $block) {
            $labels = [];
            $blockActive = (bool) ($block['is_active'] ?? true);

            foreach ((array) ($block['options'] ?? []) as $j => $option) {
                $label = mb_strtolower(trim((string) ($option['label'] ?? '')));

                if ($label !== '' && isset($labels[$label])) {
                    $validator->errors()->add("fees.$i.options.$j.label", 'Two fee options in one category have the same name.');
                }
                $labels[$label] = true;

                if ($blockActive && (bool) ($option['is_active'] ?? true)) {
                    $activeOptions++;
                }
            }
        }

        if ($blocks !== [] && $activeOptions === 0) {
            $validator->errors()->add('fees', 'At least one fee option must be active.');
        }
    }
}

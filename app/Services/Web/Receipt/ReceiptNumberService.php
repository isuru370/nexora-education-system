<?php

namespace App\Services\Web\Receipt;

use App\Models\ReceiptCounter;

class ReceiptNumberService
{
    public function generate(): string
    {
        $counter = ReceiptCounter::query()
            ->lockForUpdate()
            ->first();

        if (!$counter) {
            $counter = ReceiptCounter::create([
                'last_number' => 0,
            ]);

            $counter = ReceiptCounter::query()
                ->lockForUpdate()
                ->findOrFail($counter->id);
        }

        $nextNumber = $counter->last_number + 1;

        $counter->update([
            'last_number' => $nextNumber,
        ]);

        return 'REC-' . str_pad(
            (string) $nextNumber,
            6,
            '0',
            STR_PAD_LEFT
        );
    }
}
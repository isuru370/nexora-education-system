<?php

namespace App\Services\Web\TemporaryCard;

use App\Models\Student;
use App\Models\TemporaryIdCard;
use Barryvdh\Snappy\Facades\SnappyPdf as Pdf;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TemporaryCardService
{
    /*
    |--------------------------------------------------------------------------
    | INDEX - Search, Filter, Pagination & Counts
    |--------------------------------------------------------------------------
    */

    /**
     * Get paginated temporary ID cards with filters.
     */
    public function getIndexData(?string $search, ?string $status): array
    {
        $search = $search !== null ? trim($search) : null;
        $status = $status !== null ? trim($status) : null;

        $temporaryIdCards = TemporaryIdCard::with(['student.grade'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('temporary_id_number', 'like', '%' . $search . '%')
                        ->orWhere('card_number', 'like', '%' . $search . '%');
                });
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderBy('temporary_id_number', 'desc')
            ->paginate(20);

        $counts = TemporaryIdCard::selectRaw("
            COUNT(*) as total_count,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'downloaded' THEN 1 ELSE 0 END) as downloaded_count,
            SUM(CASE WHEN status = 'issued' THEN 1 ELSE 0 END) as issued_count,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
            SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired_count
        ")->first();

        return [
            'temporaryIdCards' => $temporaryIdCards,
            'search'           => $search,
            'status'           => $status,
            'counts'           => $counts,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | STORE - Save cards to DB
    |--------------------------------------------------------------------------
    */

    /**
     * Create a range of temporary ID cards.
     *
     * @throws RuntimeException
     */
    public function storeCards(string $start, string $end): void
    {
        $startInput = strtoupper(trim($start));
        $endInput   = strtoupper(trim($end));

        $startNum = intval(preg_replace('/^TMP/i', '', $startInput));
        $endNum   = intval(preg_replace('/^TMP/i', '', $endInput));

        if ($startNum <= 0 || $endNum <= 0) {
            throw new RuntimeException(
                'Please enter valid numbers (e.g., 001 or TMP001)'
            );
        }

        if ($startNum > $endNum) {
            throw new RuntimeException(
                'Start code must be smaller than End code'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | CHECK EXISTING DATA HAS NO GAPS
        |--------------------------------------------------------------------------
        */

        $existingNumbers = TemporaryIdCard::orderBy('temporary_id_number')
            ->pluck('temporary_id_number')
            ->map(function ($value) {
                return intval(preg_replace('/^TMP/i', '', $value));
            })
            ->sort()
            ->values();

        if ($existingNumbers->count() > 0) {
            $expected = 1;

            foreach ($existingNumbers as $num) {
                if ($num !== $expected) {
                    throw new RuntimeException(
                        'Existing temporary ID numbers have a gap at TMP' .
                            str_pad($expected, 3, '0', STR_PAD_LEFT)
                    );
                }
                $expected++;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | CHECK NEW RANGE MUST CONTINUE SEQUENTIALLY
        |--------------------------------------------------------------------------
        */

        $nextExpected = $existingNumbers->count() > 0
            ? ($existingNumbers->last() + 1)
            : 1;

        if ($startNum !== $nextExpected) {
            throw new RuntimeException(
                'Next available number is TMP' .
                    str_pad($nextExpected, 3, '0', STR_PAD_LEFT) .
                    '. You cannot skip numbers.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | CHECK DUPLICATES INSIDE REQUESTED RANGE
        |--------------------------------------------------------------------------
        */

        for ($i = $startNum; $i <= $endNum; $i++) {
            $temporaryIdNumber = 'TMP' . str_pad($i, 3, '0', STR_PAD_LEFT);
            $cardNumber        = 'CARD' . str_pad($i, 3, '0', STR_PAD_LEFT);

            $exists = TemporaryIdCard::where('temporary_id_number', $temporaryIdNumber)
                ->orWhere('card_number', $cardNumber)
                ->exists();

            if ($exists) {
                throw new RuntimeException(
                    "Duplicate found: {$temporaryIdNumber} or {$cardNumber} already exists."
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | SAVE DATA
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use ($startNum, $endNum) {
            for ($i = $startNum; $i <= $endNum; $i++) {
                $temporaryIdNumber = 'TMP' . str_pad($i, 3, '0', STR_PAD_LEFT);
                $cardNumber        = 'CARD' . str_pad($i, 3, '0', STR_PAD_LEFT);

                TemporaryIdCard::create([
                    'temporary_id_number' => $temporaryIdNumber,
                    'card_number'         => $cardNumber,
                    'status'              => 'pending',
                ]);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | PREVIEW - Generate preview data
    |--------------------------------------------------------------------------
    */

    /**
     * Get preview data for a range of temporary ID cards.
     *
     * @throws RuntimeException
     */
    public function getPreviewData(string $start, string $end): array
    {
        $startInput = strtoupper(trim($start));
        $endInput   = strtoupper(trim($end));

        $startNum = (int) preg_replace('/^TMP/i', '', $startInput);
        $endNum   = (int) preg_replace('/^TMP/i', '', $endInput);

        if ($startNum < 1 || $endNum < 1) {
            throw new RuntimeException(
                'Please enter valid TMP numbers like TMP001 and TMP120.'
            );
        }

        if ($startNum > $endNum) {
            throw new RuntimeException(
                'Start TMP number must be smaller than End TMP number.'
            );
        }

        $totalCards = ($endNum - $startNum) + 1;

        if ($totalCards > 120) {
            throw new RuntimeException(
                'You can preview maximum 120 cards at once.'
            );
        }

        $tmpNumbers = collect(range($startNum, $endNum))->map(function ($num) {
            return 'TMP' . str_pad($num, 3, '0', STR_PAD_LEFT);
        });

        $cards = TemporaryIdCard::with(['student.grade'])
            ->whereIn('temporary_id_number', $tmpNumbers)
            ->where('status', 'pending')
            ->orderByRaw("CAST(SUBSTRING(temporary_id_number, 4) AS UNSIGNED)")
            ->get();

        if ($cards->count() !== $tmpNumbers->count()) {
            $existingCount = TemporaryIdCard::whereIn('temporary_id_number', $tmpNumbers)->count();

            if ($existingCount !== $tmpNumbers->count()) {
                throw new RuntimeException(
                    'Some TMP numbers are missing in the database.'
                );
            }

            throw new RuntimeException(
                'Some TMP numbers are not pending, so they cannot be previewed.'
            );
        }

        $codes = $cards->map(function ($card) {
            return [
                'id'          => $card->id,
                'code'        => $card->temporary_id_number,
                'card_number' => $card->card_number,
                'status'      => $card->status,
                'student'     => $card->student,
                'qr_base64'   => $card->qr_base64,
            ];
        });

        return [
            'codes' => $codes,
            'start' => $startInput,
            'end'   => $endInput,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE STATUS - Change card statuses
    |--------------------------------------------------------------------------
    */

    /**
     * Change status of a range of temporary ID cards.
     *
     * @throws RuntimeException
     */
    public function changeStatus(string $start, ?string $end, string $status): array
    {
        $startInput = strtoupper(trim($start));
        $endInput   = $end ? strtoupper(trim($end)) : $startInput;

        $startNum = (int) preg_replace('/^TMP/i', '', $startInput);
        $endNum   = (int) preg_replace('/^TMP/i', '', $endInput);

        if ($startNum < 1 || $endNum < 1) {
            throw new RuntimeException(
                'Please enter valid TMP numbers like TMP001.'
            );
        }

        if ($startNum > $endNum) {
            throw new RuntimeException(
                'Start TMP number must be smaller than End TMP number.'
            );
        }

        $tmpNumbers = collect(range($startNum, $endNum))->map(function ($num) {
            return 'TMP' . str_pad($num, 3, '0', STR_PAD_LEFT);
        });

        $cards = TemporaryIdCard::whereIn('temporary_id_number', $tmpNumbers)
            ->get()
            ->keyBy('temporary_id_number');

        $updatedCards   = collect();
        $updatedCount   = 0;
        $skippedCount   = 0;
        $missingNumbers = [];

        foreach ($tmpNumbers as $tmpNumber) {
            $card = $cards->get($tmpNumber);

            if (!$card) {
                $missingNumbers[] = $tmpNumber;
                continue;
            }

            if ($status === 'issued' && $card->status !== 'downloaded') {
                $skippedCount++;
                continue;
            }

            $card->status     = $status;
            $card->updated_at = now();
            $card->save();

            $updatedCards->push($card->fresh());
            $updatedCount++;
        }

        $message = "{$updatedCount} Temporary ID cards updated successfully.";

        if ($skippedCount > 0) {
            $message .= " {$skippedCount} cards were skipped because they were not downloaded.";
        }

        if (!empty($missingNumbers)) {
            $message .= " Missing cards: " . implode(', ', $missingNumbers);
        }

        return [
            'updatedCards'   => $updatedCards,
            'start'          => $startInput,
            'end'            => $endInput,
            'selectedStatus' => $status,
            'message'        => $message,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | DOWNLOAD PDF - Range-based
    |--------------------------------------------------------------------------
    */

    /**
     * Generate PDF for a range of temporary ID cards.
     *
     * @throws RuntimeException
     */
    public function generateRangePdf(string $start, string $end)
    {
        $startNum = (int) preg_replace('/^TMP/i', '', strtoupper(trim($start)));
        $endNum   = (int) preg_replace('/^TMP/i', '', strtoupper(trim($end)));

        if ($startNum > $endNum) {
            throw new RuntimeException(
                'Start TMP number must be smaller than End TMP number.'
            );
        }

        if (($endNum - $startNum + 1) > 120) {
            throw new RuntimeException(
                'You can download maximum 120 cards at once.'
            );
        }

        $tmpNumbers = collect(range($startNum, $endNum))->map(function ($num) {
            return 'TMP' . str_pad($num, 3, '0', STR_PAD_LEFT);
        });

        $cards = TemporaryIdCard::whereIn('temporary_id_number', $tmpNumbers)
            ->orderByRaw("CAST(SUBSTRING(temporary_id_number, 4) AS UNSIGNED)")
            ->get();

        $codes = $cards->map(function ($card, $index) {
            return [
                'id'        => $card->id,
                'number'    => $index + 1,
                'code'      => $card->temporary_id_number,
                'qr_base64' => $card->qr_base64,
            ];
        });

        $pdf = Pdf::loadView('temporary-id-cards.pdf', [
            'codes' => $codes,
        ])
            ->setPaper('a4', 'landscape')
            ->setOption('encoding', 'UTF-8')
            ->setOption('enable-local-file-access', true)
            ->setOption('print-media-type', true)
            ->setOption('background', true)
            ->setOption('disable-smart-shrinking', true)
            ->setOption('dpi', 300)
            ->setOption('zoom', 1)
            ->setOption('margin-top', 0)
            ->setOption('margin-right', 0)
            ->setOption('margin-bottom', 0)
            ->setOption('margin-left', 0);

        // Update status to downloaded
        TemporaryIdCard::whereIn('id', $cards->pluck('id'))
            ->update([
                'status'     => 'downloaded',
                'updated_at' => now(),
            ]);

        return $pdf;
    }

    /*
    |--------------------------------------------------------------------------
    | DOWNLOAD ISSUED CARDS PDF - Statistics Report
    |--------------------------------------------------------------------------
    */

    /**
     * Generate issued cards report PDF with student statistics.
     *
     * @throws RuntimeException
     */
    public function generateIssuedCardsPdf()
    {
        // Get all issued cards with student relationship
        $cards = TemporaryIdCard::with(['student'])
            ->issued()
            ->orderBy('temporary_id_number')
            ->get();

        if ($cards->isEmpty()) {
            throw new RuntimeException(
                'No issued temporary ID cards found.'
            );
        }

        // ====== Card Statistics ======
        $totalIssued     = $cards->count();
        $totalCards      = TemporaryIdCard::count();
        $pendingCount    = TemporaryIdCard::pending()->count();
        $downloadedCount = TemporaryIdCard::downloaded()->count();
        $issuedCount     = TemporaryIdCard::issued()->count();
        $activeCount     = TemporaryIdCard::active()->count();
        $expiredCount    = TemporaryIdCard::expired()->count();
        $assignedToStudents = TemporaryIdCard::active()->count();

        // ====== Student Statistics ======
        $totalStudents    = Student::count();
        $activeStudents   = Student::where('is_active', true)->count();
        $inactiveStudents = Student::where('is_active', false)->count();
        $studentsWithTempQr = Student::whereNotNull('temporary_qr_code')->count();

        // ====== Additional Details ======
        $firstIssued = $cards->first()->created_at ?? null;
        $lastIssued  = $cards->last()->created_at ?? null;

        $cardsWithNumbers    = $cards->whereNotNull('card_number')->count();
        $cardsWithoutNumbers = $totalIssued - $cardsWithNumbers;

        // ====== Get student details for each card ======
        $cardDetails = $cards->map(function ($card) {
            return [
                'temporary_id_number' => $card->temporary_id_number,
                'card_number'         => $card->card_number,
                'student_name'        => $card->student
                    ? ($card->student->initial_name ?? $card->student->full_name)
                    : null,
                'student_id'          => $card->student ? $card->student->custom_id : null,
                'guardian_mobile'     => $card->student
                    ? ($card->student->guardian_mobile ?? $card->student->mobile)
                    : null,
                'student_mobile'      => $card->student ? $card->student->mobile : null,
                'student_email'       => $card->student ? $card->student->email : null,
                'grade_name'          => $card->student && $card->student->grade
                    ? $card->student->grade->grade_name
                    : null,
                'activated_at'        => $card->activated_at,
                'created_at'          => $card->created_at,
                'is_assigned'         => $card->student ? true : false,
            ];
        });

        return Pdf::loadView(
            'pdf.temporary-card.index',
            compact(
                'cards',
                'cardDetails',
                'totalIssued',
                'totalCards',
                'pendingCount',
                'downloadedCount',
                'issuedCount',
                'activeCount',
                'expiredCount',
                'assignedToStudents',
                'totalStudents',
                'activeStudents',
                'inactiveStudents',
                'studentsWithTempQr',
                'firstIssued',
                'lastIssued',
                'cardsWithNumbers',
                'cardsWithoutNumbers'
            )
        );
    }
}
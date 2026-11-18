<?php

namespace App\Services\Web\TemporaryCard;

use App\Models\Student;
use App\Models\TemporaryIdCard;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TemporaryCardService
{
    /** Cards in one PDF (dompdf memory) and shown in the preview. */
    public const PDF_CHUNK = 120;

    /** Most cards in one download (several PDFs in a ZIP). */
    public const RANGE_MAX = 1000;

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

        if ($totalCards > self::RANGE_MAX) {
            throw new RuntimeException(
                'You can download maximum ' . self::RANGE_MAX . ' cards at once.'
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

        // Only the first PDF's cards are drawn on the page; the download has all
        $codes = $cards->take(self::PDF_CHUNK)->map(function ($card) {
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
            'total' => $cards->count(),
            'files' => (int) ceil($cards->count() / self::PDF_CHUNK),
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
        $cards = $this->rangeCards($start, $end);

        if ($cards->count() > self::PDF_CHUNK) {
            throw new RuntimeException('More than ' . self::PDF_CHUNK . ' cards: download them as a ZIP.');
        }

        $pdf = $this->cardsPdf($cards);
        $this->markDownloaded($cards);

        return $pdf;
    }

    /**
     * Download a TMP range: one PDF up to 120 cards, otherwise a ZIP with
     * a PDF for every 120 cards (TMP100-TMP219.pdf, ...).
     *
     * @return array{path: string, name: string, type: string}  a temp file, delete after sending
     */
    public function downloadRange(string $start, string $end): array
    {
        $cards = $this->rangeCards($start, $end);
        $label = fn ($chunk) => $chunk->first()->temporary_id_number . '-' . $chunk->last()->temporary_id_number;

        // dompdf keeps a lot in memory; each PDF is freed before the next
        @ini_set('memory_limit', '1024M');
        @set_time_limit(600);

        if ($cards->count() <= self::PDF_CHUNK) {
            $path = tempnam(sys_get_temp_dir(), 'tmpcards');
            file_put_contents($path, $this->cardsPdf($cards)->output());
            $this->markDownloaded($cards);

            return ['path' => $path, 'name' => 'temporary-id-cards-' . $label($cards) . '.pdf', 'type' => 'application/pdf'];
        }

        if (!class_exists(\ZipArchive::class)) {
            throw new RuntimeException('ZIP is not available on the server. Download 120 cards at a time.');
        }

        $zipPath = tempnam(sys_get_temp_dir(), 'tmpcards');
        $zip = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not make the ZIP file.');
        }

        $parts = [];

        foreach ($cards->chunk(self::PDF_CHUNK) as $chunk) {
            $chunk = $chunk->values();
            $part = tempnam(sys_get_temp_dir(), 'tmpcards');
            $pdf = $this->cardsPdf($chunk);
            file_put_contents($part, $pdf->output());
            unset($pdf);
            gc_collect_cycles();

            $zip->addFile($part, 'temporary-id-cards-' . $label($chunk) . '.pdf');
            $parts[] = $part;
        }

        $zip->close();

        foreach ($parts as $part) {
            @unlink($part);
        }

        $this->markDownloaded($cards);

        return ['path' => $zipPath, 'name' => 'temporary-id-cards-' . $label($cards) . '.zip', 'type' => 'application/zip'];
    }

    /** Cards of a TMP range (up to RANGE_MAX), in number order. */
    protected function rangeCards(string $start, string $end)
    {
        $startNum = (int) preg_replace('/^TMP/i', '', strtoupper(trim($start)));
        $endNum   = (int) preg_replace('/^TMP/i', '', strtoupper(trim($end)));

        if ($startNum < 1 || $startNum > $endNum) {
            throw new RuntimeException(
                'Start TMP number must be smaller than End TMP number.'
            );
        }

        if (($endNum - $startNum + 1) > self::RANGE_MAX) {
            throw new RuntimeException(
                'You can download maximum ' . self::RANGE_MAX . ' cards at once.'
            );
        }

        $tmpNumbers = collect(range($startNum, $endNum))->map(function ($num) {
            return 'TMP' . str_pad($num, 3, '0', STR_PAD_LEFT);
        });

        $cards = TemporaryIdCard::whereIn('temporary_id_number', $tmpNumbers)->get()
            ->sortBy(fn ($card) => (int) preg_replace('/\D/', '', $card->temporary_id_number))
            ->values();

        if ($cards->isEmpty()) {
            throw new RuntimeException('No cards found in this range.');
        }

        return $cards;
    }

    protected function cardsPdf($cards)
    {
        $codes = $cards->values()->map(function ($card, $index) {
            return [
                'id'        => $card->id,
                'number'    => $index + 1,
                'code'      => $card->temporary_id_number,
                'qr_base64' => $card->qr_base64,
            ];
        });

        // dompdf (wkhtmltopdf is not installed on the servers)
        return Pdf::loadView('temporary_id_cards.pdf', [
            'codes' => $codes,
        ])
            ->setPaper('a4', 'landscape')
            ->setOption('dpi', 300)
            ->setOption('isRemoteEnabled', false);
    }

    protected function markDownloaded($cards): void
    {
        foreach ($cards->pluck('id')->chunk(500) as $ids) {
            TemporaryIdCard::whereIn('id', $ids)
                ->where('status', 'pending')
                ->update([
                    'status'     => 'downloaded',
                    'updated_at' => now(),
                ]);
        }
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
        // Thousands of rows: dompdf needs more than the default 256 MB
        @ini_set('memory_limit', '1024M');
        @set_time_limit(300);

        // Get all issued cards with student relationship
        $cards = TemporaryIdCard::with(['student.grade'])
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
        $cardDetails = $cards->values()->map(function ($card) {
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
                'activated_at'        => $card->activated_at ? $card->activated_at->format('d M Y') : null,
                'is_assigned'         => $card->student ? true : false,
            ];
        });

        return Pdf::loadView(
            'pdf.temporary-card.index',
            compact(
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
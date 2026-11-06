<?php

namespace App\Services\Web\Student;

use App\Models\Student;
use App\Models\TemporaryIdCard;

class StudentTemporaryCardService
{
    public const NONE_LEFT_MESSAGE = 'No issued temporary ID cards are left. Go to Temporary ID Cards, '
        . 'download the next cards and mark them as Issued, then register again.';

    /**
     * The next card to give: the lowest-numbered ISSUED card
     * (TMP045 before TMP046, TMP999 before TMP1000).
     *
     * $lock = true inside the registration transaction, so two staff
     * registering at the same time never get the same card.
     */
    public function nextIssuedCard(bool $lock = false): ?TemporaryIdCard
    {
        $query = TemporaryIdCard::query()
            ->where('status', 'issued')
            ->whereNull('student_id')
            ->orderByRaw('LENGTH(temporary_id_number)')
            ->orderBy('temporary_id_number');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    public function issuedLeftCount(): int
    {
        return TemporaryIdCard::query()
            ->where('status', 'issued')
            ->whereNull('student_id')
            ->count();
    }

    /**
     * Assign temporary card to student.
     */
    public function assignTemporaryCard(
        Student $student,
        string $temporaryQrCode
    ): void {

        $temporaryCard = TemporaryIdCard::query()
            ->where(
                'temporary_id_number',
                $temporaryQrCode
            )
            ->lockForUpdate()
            ->first();

        if ($temporaryCard) {
            $temporaryCard->update([
                'student_id' => $student->id,
                'status' => 'active',
                'activated_at' => now(),
            ]);
        }
    }

    /**
     * Validate temporary QR code.
     */
    public function validateTemporaryQrCode(
        string $temporaryQrCode
    ): ?string {

        if ((int) substr($temporaryQrCode, 3) < 1) {
            return 'The temporary QR code must start from TMP001.';
        }

        $card = TemporaryIdCard::query()
            ->where(
                'temporary_id_number',
                $temporaryQrCode
            )
            ->first();

        if (!$card) {
            return 'Temporary QR card not found.';
        }

        if ($card->status === 'active') {
            return 'This temporary QR code is already active.';
        }

        if ($card->status === 'expired') {
            return 'This temporary QR code is expired.';
        }

        if ($card->status !== 'issued') {
            return 'This temporary QR code must be in ISSUED status before assigning to a student.';
        }

        return null;
    }
}
<?php

namespace App\Services\Web\StudentCard;

use App\Models\Student;
use App\Models\StudentCard;
use Illuminate\Support\Facades\Log;

class StudentCardAssignmentService
{
    public function __construct(
        protected StudentCardService $studentCardService
    ) {}

    /**
     * Search student by temporary QR or current custom ID.
     */
    public function searchStudent(string $code): array
    {
        try {
            $code = trim($code);

            $student = Student::query()
                ->with([
                    'grade:id,grade_name',
                    'currentCard:id,student_id,card_number,qr_code,status,is_current,issued_at',
                ])
                ->where(function ($query) use ($code) {
                    $query->where('temporary_qr_code', $code)
                        ->orWhere('custom_id', $code);
                })
                ->where('is_active', true)
                ->where('student_disable', false)
                ->first();

            if (!$student) {
                throw new \Exception('Student not found.');
            }

            $currentCard = $this->studentCardService
                ->getCurrentCard($student);

            return [
                'student' => [
                    'id' => $student->id,
                    'custom_id' => $student->custom_id,
                    'initial_name' => $student->initial_name,
                    'img_url' => $student->img_url,
                    'grade' => [
                        'id' => optional($student->grade)->id,
                        'grade_name' => optional($student->grade)->grade_name,
                    ],
                ],

                'current_card' => $currentCard
                    ? [
                        'id' => $currentCard->id,
                        'card_number' => $currentCard->card_number,
                        'qr_code' => $currentCard->qr_code,
                        'status' => $currentCard->status,
                    ]
                    : null,

                'has_card' => $currentCard !== null,

                'message' => $currentCard
                    ? 'Student already has an active card.'
                    : 'Student is ready for card assignment.',
            ];
        } catch (\Throwable $e) {
            Log::error('Student search for card assignment failed.', [
                'search_code' => $code,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Search for an available student card by QR code.
     */
    public function searchAvailableCard(string $qrCode): StudentCard
    {
        try {
            return $this->studentCardService
                ->findAvailableCardByQr($qrCode);
        } catch (\Throwable $e) {
            Log::error('Available student card search failed.', [
                'qr_code' => $qrCode,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Assign an available card to a student.
     */
    public function assignCard(
        int $studentId,
        string $cardQrCode
    ): StudentCard {
        try {
            // Reuse existing StudentCardService lookup.
            $card = $this->studentCardService
                ->findByQrCode($cardQrCode);

            if (!$card) {
                throw new \Exception('Student card not found.');
            }

            // The main business logic and transaction stay
            // inside StudentCardService.
            return $this->studentCardService->assignCard(
                $studentId,
                $card->id
            );
        } catch (\Throwable $e) {
            Log::error('Student card assignment failed.', [
                'student_id' => $studentId,
                'card_qr_code' => $cardQrCode,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

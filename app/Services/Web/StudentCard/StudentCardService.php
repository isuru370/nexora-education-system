<?php

namespace App\Services\Web\StudentCard;

use App\Models\Branch;
use App\Models\Student;
use App\Models\StudentCard;
use App\Models\StudentCardHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentCardService
{
    /**
     * =========================================================
     * 1. GENERATE CARDS (Branch filtered)
     * =========================================================
     */

    public function generateCards(int $branchId, int $quantity): void
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException(
                'Quantity must be greater than zero.'
            );
        }

        if ($branchId <= 0) {
            throw new \InvalidArgumentException(
                'Branch ID is required.'
            );
        }

        try {
            $cardPrefix = trim((string) config('student_card.card_prefix', 'MASTER'));
            $qrPrefix = trim((string) config('student_card.qr_prefix', 'NST'));

            if ($cardPrefix === '') {
                throw new \RuntimeException(
                    'Student card prefix is not configured.'
                );
            }

            if ($qrPrefix === '') {
                throw new \RuntimeException(
                    'Student card QR prefix is not configured.'
                );
            }

            DB::transaction(function () use (
                $branchId,
                $quantity,
                $cardPrefix,
                $qrPrefix
            ) {
                // Serialize card generation transactions.
                Branch::query()
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get(['id']);

                $branch = Branch::findOrFail($branchId);

                if (!$branch->is_active) {
                    throw new \RuntimeException(
                        "Cannot generate cards for inactive branch: {$branch->name}"
                    );
                }

                $branchCode = strtoupper(trim($branch->code ?? ''));

                if ($branchCode === '') {
                    throw new \RuntimeException(
                        "Branch {$branch->name} does not have a valid code."
                    );
                }

                // Example: MASTERGMP-
                $branchCardPrefix = $cardPrefix . $branchCode . '-';

                // Branch-specific card sequence.
                $lastCardSequence = (int) (
                    StudentCard::where('branch_id', $branchId)
                    ->max('card_sequence') ?? 0
                );

                // Global QR sequence across every branch.
                $lastGlobalQrSequence = (int) (
                    StudentCard::max('global_qr_sequence') ?? 0
                );

                $cards = [];
                $now = now();

                for ($i = 1; $i <= $quantity; $i++) {
                    $cardSequence = $lastCardSequence + $i;
                    $globalQrSequence = $lastGlobalQrSequence + $i;

                    $cards[] = [
                        'branch_id' => $branchId,
                        'student_id' => null,

                        'card_sequence' => $cardSequence,
                        'global_qr_sequence' => $globalQrSequence,

                        // Example: MASTERGMP-001
                        'card_number' => $branchCardPrefix
                            . str_pad(
                                (string) $cardSequence,
                                3,
                                '0',
                                STR_PAD_LEFT
                            ),

                        // Example: NST001
                        'qr_code' => $qrPrefix
                            . str_pad(
                                (string) $globalQrSequence,
                                3,
                                '0',
                                STR_PAD_LEFT
                            ),

                        'status' => 'available',
                        'is_current' => false,
                        'issued_at' => null,
                        'deactivated_at' => null,
                        'remarks' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                StudentCard::insert($cards);

                Log::info('Student cards generated successfully.', [
                    'branch_id' => $branchId,
                    'branch_name' => $branch->name,
                    'branch_code' => $branchCode,
                    'quantity' => $quantity,
                    'first_card_sequence' => $lastCardSequence + 1,
                    'last_card_sequence' => $lastCardSequence + $quantity,
                    'first_global_qr_sequence' => $lastGlobalQrSequence + 1,
                    'last_global_qr_sequence' => $lastGlobalQrSequence + $quantity,
                    'performed_by' => auth()->id(),
                ]);
            }, 3);
        } catch (\Throwable $e) {
            Log::error('Student card generation failed.', [
                'branch_id' => $branchId,
                'quantity' => $quantity,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }



    /**
     * =========================================================
     * 2. GET CARDS (Branch filtered)
     * =========================================================
     */
    public function getCards(array $filters = [])
    {
        try {
            $query = StudentCard::query()->with(['student', 'branch']);

            // Branch filter
            if (!empty($filters['branch_id'])) {
                $query->where('branch_id', $filters['branch_id']);
            }

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (!empty($filters['search'])) {
                $search = trim($filters['search']);
                $query->where(function ($q) use ($search) {
                    $q->where('card_number', 'like', "%{$search}%")
                        ->orWhere('qr_code', 'like', "%{$search}%");
                });
            }

            if (isset($filters['assigned'])) {
                if ($filters['assigned']) {
                    $query->whereNotNull('student_id');
                } else {
                    $query->whereNull('student_id');
                }
            }

            $perPage = $filters['per_page'] ?? 20;

            return $query
                ->orderBy('branch_id')
                ->orderBy('card_sequence')
                ->paginate($perPage);
        } catch (\Throwable $e) {
            Log::error('Failed to retrieve student cards.', [
                'filters' => $filters,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            throw $e;
        }
    }


    /**
     * =========================================================
     * 3. GET AVAILABLE CARDS (Branch filtered)
     * =========================================================
     */
    public function getAvailableCards(?int $branchId = null, $keyword = null)
    {
        return StudentCard::where('status', 'available')
            ->whereNull('student_id')
            ->when($branchId, function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->when($keyword, function ($query) use ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('card_number', 'like', "%{$keyword}%")
                        ->orWhere('qr_code', 'like', "%{$keyword}%");
                });
            })
            ->orderBy('branch_id')
            ->orderBy('card_sequence')
            ->paginate(20);
    }


    /**
     * =========================================================
     * Find card by ID (with branch loaded)
     * =========================================================
     */
    public function findCard(int $id): ?StudentCard
    {
        try {
            return StudentCard::with(['student', 'branch'])->find($id);
        } catch (\Throwable $e) {
            Log::error('Failed to retrieve student card.', [
                'card_id' => $id,
                'error'   => $e->getMessage(),
            ]);

            throw $e;
        }
    }


    /**
     * Find card by card number.
     */
    public function findByCardNumber(string $cardNumber): ?StudentCard
    {
        try {
            return StudentCard::query()
                ->with(['student', 'branch'])
                ->where('card_number', trim($cardNumber))
                ->first();
        } catch (\Throwable $e) {
            Log::error('Failed to retrieve card by card number.', [
                'card_number' => $cardNumber,
                'error'       => $e->getMessage(),
            ]);

            throw $e;
        }
    }


    /**
     * Find card by QR code.
     */
    public function findByQrCode(string $qrCode): ?StudentCard
    {
        try {
            return StudentCard::query()
                ->with(['student', 'branch'])
                ->where('qr_code', trim($qrCode))
                ->first();
        } catch (\Throwable $e) {
            Log::error('Failed to retrieve card by QR code.', [
                'qr_code' => $qrCode,
                'error'   => $e->getMessage(),
            ]);

            throw $e;
        }
    }


    /**
     * =========================================================
     * 4. ASSIGN CARD (Branch validated)
     * =========================================================
     *
     * Assign a card to a student only if:
     * - Card belongs to the same branch as the student
     * - Card is available
     * - Student doesn't have an active card
     */
    public function assignCard(int $studentId, int $cardId): StudentCard
    {
        return DB::transaction(function () use ($studentId, $cardId) {

            // Lock card
            $card = StudentCard::lockForUpdate()->findOrFail($cardId);

            // ✅ Check branch_id exists
            if ($card->branch_id === null) {
                throw new \Exception(
                    'This card has no branch assigned. Please regenerate the card with a valid branch.'
                );
            }

            // Check availability
            if ($card->status !== 'available') {
                throw new \Exception('This card is not available for assignment.');
            }

            if ($card->student_id !== null) {
                throw new \Exception('This card is already assigned to a student.');
            }

            // Lock student
            $student = Student::lockForUpdate()->findOrFail($studentId);

            // ✅ Branch validation
            if ((int) $card->branch_id !== (int) $student->branch_id) {
                $cardBranch = Branch::find($card->branch_id)?->name ?? 'Unknown';
                $studentBranch = Branch::find($student->branch_id)?->name ?? 'Unknown';

                throw new \Exception(
                    "Branch mismatch! Card belongs to '{$cardBranch}' but student belongs to '{$studentBranch}'. " .
                        "Please assign a card from the student's branch."
                );
            }

            // Check student doesn't already have an active card
            $currentCard = StudentCard::where('student_id', $studentId)
                ->where('is_current', true)
                ->first();

            if ($currentCard) {
                throw new \Exception(
                    'This student already has an active student card.'
                );
            }

            // Assign card
            $card->update([
                'student_id'     => $studentId,
                'status'         => 'assigned',
                'is_current'     => true,
                'issued_at'      => Carbon::now(),
                'deactivated_at' => null,
            ]);

            // Update student
            $student->update([
                'custom_id'           => $card->qr_code,
                'permanent_qr_active' => true,
                'student_disable'     => false,
            ]);

            // Log history
            $this->logHistory(
                $studentId,
                $card->id,
                'assigned',
                null,
                null,
                null,
                'Card assigned to student'
            );

            Log::info('Student card assigned successfully.', [
                'student_id'        => $studentId,
                'card_id'           => $card->id,
                'branch_id'         => $card->branch_id,
                'card_number'       => $card->card_number,
                'custom_id_updated' => $card->qr_code,
                'performed_by'      => auth()->id(),
            ]);

            return $card->fresh();
        });
    }


    /**
     * =========================================================
     * 5. REPLACE CARD (Branch validated)
     * =========================================================
     */
    public function replaceCard(
        string $oldQrCode,
        string $newQrCode,
        ?string $reason = null,
        ?string $remarks = null
    ): StudentCard {

        return DB::transaction(function () use (
            $oldQrCode,
            $newQrCode,
            $reason,
            $remarks
        ) {

            $oldCard = StudentCard::lockForUpdate()
                ->where('qr_code', strtoupper(trim($oldQrCode)))
                ->firstOrFail();

            $newCard = StudentCard::lockForUpdate()
                ->where('qr_code', strtoupper(trim($newQrCode)))
                ->firstOrFail();

            // Old card validation
            if (!in_array($oldCard->status, ['assigned', 'lost'])) {
                throw new \Exception('Only assigned or lost cards can be replaced.');
            }

            if ($oldCard->student_id === null) {
                throw new \Exception('Old card is not assigned to any student.');
            }

            // New card validation
            if ($newCard->status !== 'available') {
                throw new \Exception('Selected card is not available.');
            }

            if ($newCard->student_id !== null) {
                throw new \Exception('Selected card is already assigned.');
            }

            // Lock student
            $student = Student::lockForUpdate()->findOrFail($oldCard->student_id);

            // ✅ Branch validation - New card must belong to the same branch as the student
            if ((int) $newCard->branch_id !== (int) $student->branch_id) {
                $cardBranch = Branch::find($newCard->branch_id)?->name ?? 'Unknown';
                $studentBranch = Branch::find($student->branch_id)?->name ?? 'Unknown';

                throw new \Exception(
                    "Branch mismatch! New card belongs to '{$cardBranch}' but student belongs to '{$studentBranch}'. " .
                        "Replacement card must be from the student's branch."
                );
            }

            // Deactivate old card
            if ($oldCard->status === 'assigned') {
                $oldCard->update([
                    'status'         => 'inactive',
                    'is_current'     => false,
                    'deactivated_at' => now(),
                ]);
            } else {
                $oldCard->update([
                    'is_current'     => false,
                    'deactivated_at' => now(),
                ]);
            }

            // Assign new card (branch_id stays the same)
            $newCard->update([
                'student_id'     => $student->id,
                'status'         => 'assigned',
                'is_current'     => true,
                'issued_at'      => now(),
                'deactivated_at' => null,
            ]);

            // Update student
            $student->update([
                'custom_id'           => $newCard->qr_code,
                'permanent_qr_active' => true,
                'student_disable'     => false,
            ]);

            // History
            $this->logHistory(
                $student->id,
                $newCard->id,
                'replaced',
                $oldCard->id,
                $newCard->id,
                $reason,
                $remarks ?? 'Card replaced'
            );

            Log::info('Student card replaced successfully.', [
                'student_id'       => $student->id,
                'branch_id'        => $student->branch_id,
                'old_qr_code'      => $oldCard->qr_code,
                'new_qr_code'      => $newCard->qr_code,
                'reason'           => $reason,
                'performed_by'     => auth()->id(),
            ]);

            return $newCard->fresh();
        });
    }


    /**
     * Mark card as lost.
     */
    public function markLost(StudentCard $card): StudentCard
    {
        try {
            if ($card->student_id === null) {
                throw new \Exception('Cannot mark an unassigned card as lost.');
            }

            DB::transaction(function () use (&$card) {
                $card = StudentCard::lockForUpdate()->findOrFail($card->id);
                $student = Student::lockForUpdate()->findOrFail($card->student_id);

                $card->update([
                    'status'         => 'lost',
                    'is_current'     => false,
                    'deactivated_at' => now(),
                ]);

                $student->update([
                    'student_disable'     => true,
                    'permanent_qr_active' => false,
                    'custom_id'           => null,
                ]);

                $this->logHistory(
                    $student->id,
                    $card->id,
                    'lost',
                    null,
                    null,
                    'lost',
                    'Card marked as lost'
                );

                Log::info('Student card marked as lost.', [
                    'student_id'   => $student->id,
                    'card_id'      => $card->id,
                    'performed_by' => auth()->id(),
                ]);
            });

            return $card->fresh();
        } catch (\Throwable $e) {
            Log::error('Failed to mark card as lost.', [
                'card_id' => $card->id,
                'error'   => $e->getMessage(),
            ]);

            throw $e;
        }
    }


    /**
     * Mark card as damaged.
     */
    public function markDamaged(StudentCard $card): StudentCard
    {
        try {
            if ($card->student_id === null) {
                throw new \Exception('Cannot mark an unassigned card as damaged.');
            }

            $card->update([
                'status'         => 'damaged',
                'is_current'     => false,
                'deactivated_at' => now(),
            ]);

            $this->logHistory(
                $card->student_id,
                $card->id,
                'damaged',
                null,
                null,
                'damaged',
                'Card marked as damaged'
            );

            return $card->fresh();
        } catch (\Throwable $e) {
            Log::error('Failed to mark card as damaged.', [
                'card_id' => $card->id,
                'error'   => $e->getMessage(),
            ]);

            throw $e;
        }
    }


    /**
     * Deactivate card.
     */
    public function deactivateCard(StudentCard $card): StudentCard
    {
        try {
            if ($card->student_id === null) {
                throw new \Exception('Cannot deactivate an unassigned card.');
            }

            $card->update([
                'status'         => 'inactive',
                'is_current'     => false,
                'deactivated_at' => now(),
            ]);

            $this->logHistory(
                $card->student_id,
                $card->id,
                'deactivated',
                null,
                null,
                null,
                'Card deactivated'
            );

            return $card->fresh();
        } catch (\Throwable $e) {
            Log::error('Failed to deactivate card.', [
                'card_id' => $card->id,
                'error'   => $e->getMessage(),
            ]);

            throw $e;
        }
    }


    /**
     * Get student's current card.
     */
    public function getCurrentCard(Student $student): ?StudentCard
    {
        return $student->currentCard()->with(['student', 'branch'])->first();
    }


    /**
     * Get card history.
     */
    public function getCardHistory(Student $student, int $perPage = 20)
    {
        return $student->cardHistories()
            ->with([
                'card:id,card_number,qr_code,status,branch_id',
                'oldCard:id,card_number,qr_code,status',
                'newCard:id,card_number,qr_code,status',
                'performedBy:id,name',
            ])
            ->orderByDesc('performed_at')
            ->paginate($perPage);
    }


    /**
     * Check if card is available.
     */
    public function isCardAvailable(StudentCard $card): bool
    {
        return $card->status === 'available' && $card->student_id === null;
    }


    /**
     * Get next card sequence for a branch.
     */
    public function getNextSequence(int $branchId): int
    {
        return (int) StudentCard::where('branch_id', $branchId)
            ->max('card_sequence') + 1;
    }


    /**
     * Log card history.
     */
    private function logHistory(
        int $studentId,
        int $cardId,
        string $action,
        ?int $oldCardId = null,
        ?int $newCardId = null,
        ?string $reason = null,
        ?string $remarks = null
    ): void {
        StudentCardHistory::create([
            'student_id'   => $studentId,
            'card_id'      => $cardId,
            'old_card_id'  => $oldCardId,
            'new_card_id'  => $newCardId,
            'action'       => $action,
            'reason'       => $reason,
            'remarks'      => $remarks,
            'performed_by' => auth()->id(),
            'performed_at' => now(),
        ]);
    }


    /**
     * Find available card by QR code.
     */
    public function findAvailableCardByQr(string $qrCode): StudentCard
    {
        $card = StudentCard::query()
            ->where('qr_code', trim($qrCode))
            ->lockForUpdate()
            ->first();

        if (!$card) {
            throw new \Exception('Student card not found.');
        }

        if ($card->status !== 'available') {
            throw new \Exception('This student card is not available.');
        }

        if ($card->student_id !== null) {
            throw new \Exception('This student card is already assigned.');
        }

        return $card;
    }


    /**
     * Search card by QR code.
     */
    public function searchCard(string $qrCode): array
    {
        $card = StudentCard::with([
            'student.grade:id,grade_name',
            'branch:id,name,code',
        ])->where('qr_code', strtoupper(trim($qrCode)))
            ->first();

        if (!$card) {
            return [
                'status'  => false,
                'message' => 'Card not found',
            ];
        }

        if ($card->status === 'available') {
            return [
                'status'  => true,
                'message' => 'Card is available',
                'card'    => [
                    'id'          => $card->id,
                    'card_number' => $card->card_number,
                    'qr_code'     => $card->qr_code,
                    'branch'      => $card->branch?->name,
                    'branch_id'   => $card->branch_id,
                    'branch_code' => $card->branch?->code,
                ],
            ];
        }

        $student = Student::with([
            'grade:id,grade_name',
            'studentCards' => function ($q) {
                $q->orderBy('issued_at')->orderBy('id');
            }
        ])->find($card->student_id);

        if (!$student) {
            return [
                'status'  => false,
                'message' => 'Student not found',
            ];
        }

        $currentCard = $student->studentCards->firstWhere('is_current', true);

        return [
            'status'  => false,
            'message' => $card->is_current
                ? 'Card already assigned'
                : 'This card is no longer active.',

            'student' => [
                'id'              => $student->id,
                'initial_name'    => $student->initial_name,
                'img_url'         => $student->img_url,
                'grade'           => $student->grade?->grade_name,
                'guardian_mobile' => $student->guardian_mobile,
            ],

            'current_card' => $currentCard ? [
                'id'          => $currentCard->id,
                'card_number' => $currentCard->card_number,
                'qr_code'     => $currentCard->qr_code,
                'status'      => $currentCard->status,
                'is_current'  => $currentCard->is_current,
                'issued_at'   => optional($currentCard->issued_at)->format('Y-m-d H:i:s'),
            ] : null,

            'cards' => $student->studentCards
                ->map(function ($c) {
                    return [
                        'id'             => $c->id,
                        'card_number'    => $c->card_number,
                        'qr_code'        => $c->qr_code,
                        'status'         => $c->status,
                        'is_current'     => $c->is_current,
                        'issued_at'      => optional($c->issued_at)->format('Y-m-d H:i:s'),
                        'deactivated_at' => optional($c->deactivated_at)->format('Y-m-d H:i:s'),
                    ];
                })
                ->values(),
        ];
    }


    /**
     * ID card details with branch.
     */
    public function idCardDetails($perPage = 12)
    {
        return StudentCard::select([
            'id',
            'branch_id',
            'card_number',
            'qr_code',
            'status',
        ])
            ->with('branch:id,name,code')
            ->orderBy('branch_id')
            ->orderBy('id', 'asc')
            ->paginate($perPage);
    }


    /**
     * =========================================================
     * 6. GET AVAILABLE CARDS FOR FORM (Branch filtered)
     * =========================================================
     */
    public function getAvailableCardsForForm(?int $branchId = null)
    {
        return StudentCard::where('status', 'available')
            ->whereNull('student_id')
            ->when($branchId, function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->orderBy('branch_id')
            ->orderBy('card_sequence')
            ->get();
    }


    /**
     * Get assign form data (branch aware).
     */
    public function getAssignFormData(?int $cardId = null, ?int $studentId = null): array
    {
        $card = $cardId ? StudentCard::with(['student', 'branch'])->find($cardId) : null;
        $student = $studentId ? Student::with(['currentCard', 'branch'])->find($studentId) : null;

        // Determine branch context
        $branchId = null;
        if ($student) {
            $branchId = $student->branch_id;
        } elseif ($card) {
            $branchId = $card->branch_id;
        }

        $availableCards = $this->getAvailableCardsForForm($branchId);

        $students = Student::where('student_disable', false)
            ->whereDoesntHave('currentCard')
            ->when($branchId, function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->orderBy('full_name')
            ->get();

        return compact('card', 'student', 'availableCards', 'students', 'branchId');
    }


    /**
     * Get available page students.
     */
    public function getAvailablePageStudents(?int $branchId = null)
    {
        return Student::with(['currentCard', 'branch'])
            ->where('student_disable', false)
            ->when($branchId, function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->orderBy('full_name')
            ->get();
    }


    /**
     * Load card with student and active enrollment details.
     */
    public function getCardDetails(StudentCard $card): StudentCard
    {
        return $card->load([
            'branch',
            'student.grade',
            'student.branch',
            'student.enrollments' => function ($query) {
                $query->where('is_active', true);
            },
            'student.enrollments.studentClass',
            'student.enrollments.category',
        ]);
    }


    /**
     * Find card by number or QR.
     */
    public function findByNumberOrQr(string $keyword): ?StudentCard
    {
        $keyword = trim($keyword);

        $card = $this->findByCardNumber($keyword);

        if (!$card) {
            $card = $this->findByQrCode($keyword);
        }

        return $card;
    }


    /**
     * Find student with current card.
     */
    public function findStudentWithCurrentCard(int $studentId): ?Student
    {
        return Student::with(['currentCard', 'branch'])->find($studentId);
    }


    /**
     * =========================================================
     * 7. GET BRANCH CARD SUMMARY (Correct inventory count)
     * =========================================================
     *
     * Now counts cards by their OWN branch_id (inventory),
     * not by student's branch_id.
     */
    public function getBranchCardSummary(): array
    {
        $branches = Branch::query()
            ->orderBy('name')
            ->get();

        return $branches->map(function ($branch) {

            // Count cards by card's own branch_id
            $cardCounts = StudentCard::query()
                ->where('branch_id', $branch->id)
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            return [
                'branch_id'   => $branch->id,
                'branch_name' => $branch->name,
                'branch_code' => $branch->code,
                'total_cards' => (int) $cardCounts->sum(),
                'assigned'    => (int) ($cardCounts['assigned'] ?? 0),
                'available'   => (int) ($cardCounts['available'] ?? 0),
                'lost'        => (int) ($cardCounts['lost'] ?? 0),
                'damaged'     => (int) ($cardCounts['damaged'] ?? 0),
                'inactive'    => (int) ($cardCounts['inactive'] ?? 0),
            ];
        })->values()->all();
    }


    public function getBranchSequences(int $branchId): array
    {
        $branch = Branch::findOrFail($branchId);

        $lastCardSequence = StudentCard::where('branch_id', $branchId)
            ->max('card_sequence') ?? 0;

        $lastQrSequence = StudentCard::max('global_qr_sequence') ?? 0;

        return [
            'last_card_sequence' => (int) $lastCardSequence,
            'last_qr_sequence' => (int) $lastQrSequence,
            'branch_name' => $branch->name,
        ];
    }
}

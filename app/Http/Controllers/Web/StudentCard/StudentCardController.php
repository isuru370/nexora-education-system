<?php

namespace App\Http\Controllers\Web\StudentCard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StudentCard\AssignStudentCardRequest;
use App\Http\Requests\Web\StudentCard\GenerateStudentCardsRequest;
use App\Http\Requests\Web\StudentCard\ReplaceStudentCardRequest;
use App\Models\Student;
use App\Models\StudentCard;
use App\Services\Web\StudentCard\StudentCardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StudentCardController extends Controller
{
    public function __construct(
        private StudentCardService $studentCardService
    ) {}

    /**
     * Display card inventory.
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->only([
                'branch_id',
                'status',
                'search',
                'assigned',
                'per_page',
            ]);

            // Card inventory list
            $cards = $this->studentCardService->getCards($filters);

            // Branch-wise card inventory summary
            $branchCardSummary = $this->studentCardService
                ->getBranchCardSummary();

            return view(
                'student-cards.index',
                compact('cards', 'branchCardSummary')
            );
        } catch (\Throwable $e) {
            Log::error('Failed to load student card inventory.', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return redirect()->back()->with(
                'error',
                'Unable to load student card inventory.'
            );
        }
    }

    /**
     * Show generate card form.
     */

    public function generate()
    {
        $branches = app(\App\Services\Branch\BranchAccessService::class)
            ->getAccessibleBranches();

        return view('student-cards.generate', compact('branches'));
    }


    /**
     * Generate new student cards.
     */

    public function generateCards(GenerateStudentCardsRequest $request)
    {
        try {
            $data = $request->validated();

            $branchId = (int) $data['branch_id'];
            $quantity = (int) $data['quantity'];

            $this->studentCardService->generateCards(
                $branchId,
                $quantity
            );

            return redirect()
                ->route('student-cards.index')
                ->with(
                    'success',
                    $quantity . ' cards generated successfully.'
                );
        } catch (\Throwable $e) {
            Log::error('Failed to generate student cards.', [
                'branch_id' => $request->input('branch_id'),
                'quantity' => $request->input('quantity'),
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }


    /**
     * Show assign card form.
     */
    public function assignForm(Request $request)
    {
        try {
            $cardId = $request->filled('card_id')
                ? (int) $request->query('card_id')
                : null;

            $studentId = $request->filled('student_id')
                ? (int) $request->query('student_id')
                : null;

            $data = $this->studentCardService->getAssignFormData(
                $cardId,
                $studentId
            );

            return view('student-cards.assign', $data);
        } catch (\Throwable $e) {
            Log::error('Failed to load assign card form.', [
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('student-cards.available')
                ->with('error', 'Unable to load the card assignment form.');
        }
    }

    public function show($id)
    {
        $card = StudentCard::with([
            'student.grade',
            'student.branch',
            'student.enrollments.studentClass',
            'student.enrollments.category',
            'branch',
        ])->findOrFail($id);

        return view('student-cards.show', compact('card'));
    }

    /**
     * Assign a card to a student.
     */
    public function assign(AssignStudentCardRequest $request)
    {
        try {
            $data = $request->validated();

            $this->studentCardService->assignCard(
                (int) $data['student_id'],
                (int) $data['card_id']
            );

            return redirect()
                ->route('student-cards.available')
                ->with(
                    'success',
                    'Student card assigned successfully. Student account has been activated.'
                );
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Show replace card form.
     */
    public function replaceForm(StudentCard $card)
    {
        try {
            if (
                !in_array($card->status, ['assigned', 'lost'], true)
                || $card->student_id === null
            ) {
                return redirect()
                    ->route('student-cards.show', $card->id)
                    ->with(
                        'error',
                        'Only assigned or lost cards linked to a student can be replaced.'
                    );
            }

            $availableCards = $this->studentCardService
                ->getAvailableCardsForForm();

            if ($availableCards->isEmpty()) {
                return redirect()
                    ->route('student-cards.show', $card->id)
                    ->with(
                        'error',
                        'No available cards found for replacement.'
                    );
            }

            return view(
                'student-cards.replace',
                compact('card', 'availableCards')
            );
        } catch (\Throwable $e) {
            Log::error('Failed to load replace card form.', [
                'card_id' => $card->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('student-cards.show', $card->id)
                ->with('error', 'Unable to load the card replacement form.');
        }
    }

    /**
     * Replace a student's current card.
     */
    public function replace(
        ReplaceStudentCardRequest $request,
        StudentCard $card
    ) {
        try {
            $data = $request->validated();

            $newCard = $this->studentCardService->replaceCard(
                $card->qr_code,
                $data['new_qr_code'],
                $data['reason'],
                $data['remarks'] ?? null
            );

            return redirect()
                ->route('student-cards.show', $newCard)
                ->with(
                    'success',
                    'Student card replaced successfully.'
                );
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Mark a card as lost.
     */
    public function markLost(StudentCard $card)
    {
        try {
            $this->studentCardService->markLost($card);

            return redirect()
                ->route('student-cards.show', $card->id)
                ->with(
                    'success',
                    'Student card marked as lost successfully.'
                );
        } catch (\Throwable $e) {
            Log::error('Failed to mark student card as lost.', [
                'card_id' => $card->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('student-cards.show', $card->id)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Mark a card as damaged.
     */
    public function markDamaged(StudentCard $card)
    {
        try {
            $this->studentCardService->markDamaged($card);

            return redirect()
                ->route('student-cards.show', $card->id)
                ->with(
                    'success',
                    'Student card marked as damaged successfully.'
                );
        } catch (\Throwable $e) {
            Log::error('Failed to mark student card as damaged.', [
                'card_id' => $card->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('student-cards.show', $card->id)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Deactivate a card.
     */
    public function deactivate(StudentCard $card)
    {
        try {
            $this->studentCardService->deactivateCard($card);

            return redirect()
                ->route('student-cards.show', $card->id)
                ->with(
                    'success',
                    'Student card deactivated successfully.'
                );
        } catch (\Throwable $e) {
            Log::error('Failed to deactivate student card.', [
                'card_id' => $card->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('student-cards.show', $card->id)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Display available cards and active students.
     */
    public function available(Request $request)
    {
        $cards = $this->studentCardService->getAvailableCards(
            $request->input('keyword')
        );

        $students = $this->studentCardService->getAvailablePageStudents();

        return view(
            'student-cards.available',
            compact('cards', 'students')
        );
    }

    /**
     * Display student card history.
     */
    public function history(Student $student)
    {
        try {
            $cards = $this->studentCardService->getCardHistory($student);

            if ($cards->isEmpty()) {
                return redirect()
                    ->route('students.show', $student->id)
                    ->with(
                        'info',
                        'This student has no card history.'
                    );
            }

            return view(
                'student-cards.history',
                compact('student', 'cards')
            );
        } catch (\Throwable $e) {
            Log::error('Failed to retrieve student card history.', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('students.show', $student->id)
                ->with(
                    'error',
                    'Unable to retrieve student card history.'
                );
        }
    }

    /**
     * Search a card by card number or QR code.
     */
    public function search(Request $request)
    {
        $request->validate([
            'keyword' => ['required', 'string', 'min:1'],
        ]);

        try {
            $keyword = trim($request->input('keyword'));

            $card = $this->studentCardService->findByNumberOrQr($keyword);

            if (!$card) {
                return redirect()
                    ->route('student-cards.index')
                    ->with('warning', 'Student card not found.');
            }

            return redirect()->route(
                'student-cards.show',
                $card->id
            );
        } catch (\Throwable $e) {
            Log::error('Failed to search student card.', [
                'keyword' => $request->input('keyword'),
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('student-cards.index')
                ->withInput()
                ->with('error', 'Unable to search student card.');
        }
    }

    /**
     * Check whether a student has a current card.
     */
    public function checkCurrentCard(Request $request)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
        ]);

        $student = $this->studentCardService
            ->findStudentWithCurrentCard((int) $validated['student_id']);

        if (!$student) {
            return response()->json([
                'has_current_card' => false,
            ]);
        }

        $currentCard = $student->currentCard;

        return response()->json([
            'has_current_card' => $currentCard !== null,
            'student_disabled' => $student->student_disable,
            'card_number' => $currentCard?->card_number,
            'card_id' => $currentCard?->id,
        ]);
    }

    /**
     * Display ID card preview.
     */
    public function preview(Request $request)
    {
        try {
            $perPage = (int) $request->input('per_page', 12);

            $cards = $this->studentCardService->idCardDetails(
                max(1, min($perPage, 100))
            );

            return view('student-cards.preview', compact('cards'));
        } catch (\Throwable $e) {
            Log::error('Failed to load ID Card Preview.', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return redirect()
                ->route('student-cards.index')
                ->with('error', 'Unable to load ID Card Preview.');
        }
    }


    public function branchSequences(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
        ]);

        $branchId = (int) $validated['branch_id'];

        // Ensure the user can access this branch.
        $branchAccessService = app(
            \App\Services\Branch\BranchAccessService::class
        );

        abort_unless(
            $branchAccessService->canAccessBranch($branchId),
            403
        );

        $sequences = $this->studentCardService
            ->getBranchSequences($branchId);

        return response()->json([
            'success' => true,
            'last_card_sequence' => $sequences['last_card_sequence'],
            'last_qr_sequence' => $sequences['last_qr_sequence'],
            'branch_name' => $sequences['branch_name'],
        ]);
    }
}

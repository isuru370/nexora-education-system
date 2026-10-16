<?php

namespace App\Http\Controllers\Api\StudentCard;

use App\Http\Controllers\Controller;
use App\Services\Branch\BranchAccessService;
use App\Services\Web\StudentCard\StudentCardService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Mobile app: scan a permanent student card and replace (re-assign) a card.
 * (Replaces the old API\StudentCardRegisterController searchQrcode / reAssign.)
 */
class StudentCardController extends Controller
{
    public function __construct(
        private StudentCardService $studentCardService,
        private BranchAccessService $branchAccessService
    ) {}

    /**
     * POST /qr-code  { "card_qr_code": "..." }
     *
     * status = true  -> card is available (can register a new student)
     * status = false -> card not found, or already assigned (student + cards returned)
     */
    public function searchQrcode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'card_qr_code' => ['required', 'string', 'max:150'],
        ]);

        try {
            $result = $this->studentCardService->searchCard($validated['card_qr_code']);

            // An available card can only be registered in its own branch.
            // Stop here if the user cannot work in that branch.
            $cardBranchId = $result['card']['branch_id'] ?? null;

            if (($result['status'] ?? false) === true
                && $cardBranchId !== null
                && !$this->branchAccessService->canAccessBranch((int) $cardBranchId)
            ) {
                $branchName = $result['card']['branch'] ?? 'another branch';

                return response()->json([
                    'status' => false,
                    'message' => "This card belongs to {$branchName}. You cannot register it.",
                ], 403);
            }

            return response()->json($result);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Unable to read the student card.',
            ], 500);
        }
    }

    /**
     * POST /re-assign  { "old_qr_code", "new_qr_code", "reason", "remarks" }
     */
    public function reAssign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'old_qr_code' => ['required', 'string', 'max:150'],
            'new_qr_code' => ['required', 'string', 'max:150', 'different:old_qr_code'],
            'reason' => ['required', 'in:lost,damaged,worn_out,other'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $card = $this->studentCardService->replaceCard(
                $validated['old_qr_code'],
                $validated['new_qr_code'],
                $validated['reason'],
                $validated['remarks'] ?? null
            );

            return response()->json([
                'status' => true,
                'message' => 'Student card reassigned successfully.',
                'data' => [
                    'card_id' => $card->id,
                    'card_number' => $card->card_number,
                    'qr_code' => $card->qr_code,
                    'student_id' => $card->student_id,
                    'status' => $card->status,
                ],
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Card not found.',
            ], 404);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage() ?: 'Unable to reassign the student card.',
            ], 400);
        }
    }
}

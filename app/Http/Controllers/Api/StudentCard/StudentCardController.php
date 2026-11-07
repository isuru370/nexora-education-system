<?php

namespace App\Http\Controllers\Api\StudentCard;

use App\Http\Controllers\Controller;
use App\Services\Branch\BranchAccessService;
use App\Services\Web\Admission\CardReplacementFeeService;
use App\Services\Web\StudentCard\StudentCardService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
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
        private BranchAccessService $branchAccessService,
        private CardReplacementFeeService $cardFees
    ) {}

    /**
     * GET /card-replacement-fees - the fee items for re-assigning a card
     * (Admissions items ticked "Use for card replacement").
     */
    public function replacementFees(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => $this->cardFees->items()->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'amount' => (float) $item->amount,
            ])->values(),
        ]);
    }

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
            // Card fee: an Admissions item ticked "Use for card replacement"
            // (Rs. 0 = free). Optional for app versions before 1.1.3.
            'admission_id' => ['nullable', 'integer'],
            'payment_method' => ['nullable', 'in:cash,card,bank_transfer,online'],
        ]);

        try {
            [$card, $payment] = DB::transaction(function () use ($validated) {
                $card = $this->studentCardService->replaceCard(
                    $validated['old_qr_code'],
                    $validated['new_qr_code'],
                    $validated['reason'],
                    $validated['remarks'] ?? null
                );

                $payment = null;
                if (!empty($validated['admission_id']) && $card->student) {
                    $payment = $this->cardFees->charge(
                        $card->student,
                        (int) $validated['admission_id'],
                        $validated['payment_method'] ?? 'cash',
                        sprintf(
                            'ID card replacement (%s): %s -> %s',
                            str_replace('_', ' ', $validated['reason']),
                            $validated['old_qr_code'],
                            $validated['new_qr_code']
                        ),
                        auth()->id()
                    );
                }

                return [$card, $payment];
            });

            return response()->json([
                'status' => true,
                'message' => 'Student card reassigned successfully.',
                'data' => [
                    'card_id' => $card->id,
                    'card_number' => $card->card_number,
                    'qr_code' => $card->qr_code,
                    'student_id' => $card->student_id,
                    'status' => $card->status,
                    'payment' => $payment ? [
                        'id' => $payment->id,
                        'receipt_number' => $payment->receipt_number,
                        'item' => optional($payment->admission)->name,
                        'amount' => (float) $payment->amount,
                        'payment_method' => $payment->payment_method,
                        'paid_at' => optional($payment->paid_at)->toDateTimeString(),
                        'student_custom_id' => optional($card->student)->custom_id,
                        'student_name' => optional($card->student)->initial_name,
                        'collected_by' => optional(auth()->user())->name,
                    ] : null,
                ],
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Card not found.',
            ], 404);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'status' => false,
                'message' => \App\Support\ErrorDetail::safe($e) ?: 'Unable to reassign the student card.',
            ], 400);
        }
    }
}

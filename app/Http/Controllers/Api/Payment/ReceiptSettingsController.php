<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Support\ReceiptRules;
use Illuminate\Http\JsonResponse;

/**
 * GET /api/receipt-settings: what the staff app prints on the class payment
 * receipt (web: Settings > Receipt).
 */
class ReceiptSettingsController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Receipt settings loaded.',
            'data' => ReceiptRules::all(),
        ]);
    }
}

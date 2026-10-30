<?php

namespace App\Http\Controllers\Api\AppSetup;

use App\Http\Controllers\Controller;
use App\Support\AppSetup;
use App\Support\InstituteInfo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Staff app, first start: check the setup key from the scanned QR.
 * No login (the user logs in after this); limited to 10 tries a minute.
 */
class AppSetupVerifyController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if (!AppSetup::verify($request->input('key'))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid QR code. Ask your institute admin for the current QR.',
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Institute verified.',
            'data' => InstituteInfo::toArray() + [
                'api_base_url' => AppSetup::baseUrl(),
            ],
        ]);
    }
}

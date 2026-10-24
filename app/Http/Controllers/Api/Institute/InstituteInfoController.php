<?php

namespace App\Http\Controllers\Api\Institute;

use App\Http\Controllers\Controller;
use App\Support\InstituteInfo;
use Illuminate\Http\JsonResponse;

class InstituteInfoController extends Controller
{
    /**
     * GET /api/institute (no login) - name, tagline and logo for the staff
     * app's splash and login screens.
     */
    public function show(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Institute loaded successfully.',
            'data' => InstituteInfo::toArray(),
        ]);
    }
}

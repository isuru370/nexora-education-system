<?php

namespace App\Http\Controllers\Api\Feature;

use App\Http\Controllers\Controller;
use App\Support\CardMode;
use App\Support\Features;
use Illuminate\Http\JsonResponse;

class FeatureController extends Controller
{
    /**
     * GET /api/features - which optional features this institute has, so the
     * app can show or hide their menus. Example: {"data": {"courses": true}}
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Features loaded successfully.',
            'data' => (object) Features::all(),
            // How this institute gives ID cards: permanent / temporary
            'card_mode' => CardMode::get(),
        ]);
    }
}

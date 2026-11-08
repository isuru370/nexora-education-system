<?php

namespace App\Http\Controllers\Api\Feedback;

use App\Http\Controllers\Controller;
use App\Jobs\SendAppFeedbackEmail;
use App\Models\AppFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * POST /api/feedback - "Send Feedback" in the staff app (any staff user).
 * Saved for the Super Admin's App Feedback page and emailed to Nexora.
 */
class AppFeedbackController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(AppFeedback::TYPES))],
            'message' => ['required', 'string', 'min:5', 'max:3000'],
            'screenshot' => ['nullable', 'image', 'max:6144'],
            'app_version' => ['nullable', 'string', 'max:40'],
            'device' => ['nullable', 'string', 'max:120'],
            'platform' => ['nullable', 'string', 'max:60'],
        ]);

        $feedback = AppFeedback::create([
            'user_id' => auth()->id(),
            'type' => $data['type'],
            'message' => trim($data['message']),
            'screenshot_path' => $request->hasFile('screenshot')
                ? $request->file('screenshot')->store('feedback', 'public')
                : null,
            'app_version' => $data['app_version'] ?? null,
            'device' => $data['device'] ?? null,
            'platform' => $data['platform'] ?? null,
            'status' => 'new',
        ]);

        SendAppFeedbackEmail::dispatch($feedback->id);

        return response()->json([
            'success' => true,
            'message' => 'Thank you! Your feedback was sent.',
            'data' => ['id' => $feedback->id],
        ], 201);
    }
}

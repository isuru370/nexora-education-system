<?php

namespace App\Http\Controllers\Web\AppFeedback;

use App\Http\Controllers\Controller;
use App\Models\AppFeedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Super Admin: feedback sent from the staff app */
class AppFeedbackController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $type = $request->query('type');

        $feedback = AppFeedback::query()
            ->with('user.userType')
            ->when(array_key_exists((string) $status, AppFeedback::STATUSES), fn ($q) => $q->where('status', $status))
            ->when(array_key_exists((string) $type, AppFeedback::TYPES), fn ($q) => $q->where('type', $type))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $counts = AppFeedback::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('app-feedback.index', compact('feedback', 'counts', 'status', 'type'));
    }

    public function update(Request $request, AppFeedback $appFeedback): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(AppFeedback::STATUSES))],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $appFeedback->update($data);

        return back()->with('success', 'Feedback updated.');
    }
}

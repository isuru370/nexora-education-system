<?php

namespace App\Http\Controllers\Web\TeacherApp;

use App\Http\Controllers\Controller;
use App\Models\TeacherScheduleRequest;
use App\Services\Api\Teacher\TeacherScheduleService;
use App\Services\Branch\BranchAccessService;
use App\Support\TeacherAppRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Settings > Teacher App (Admin, Super Admin): what teachers can do in the
 * staff app, and the class day changes waiting for approval.
 */
class TeacherAppController extends Controller
{
    public function __construct(
        private TeacherScheduleService $schedules,
        private BranchAccessService $branches,
    ) {}

    public function index(): View
    {
        return view('setting.teacher-app.index', [
            'rules' => TeacherAppRules::all(),
            'pendingCount' => $this->requestQuery()->where('status', 'pending')->count(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'attendance' => ['nullable', 'boolean'],
            'schedule' => ['nullable', 'boolean'],
            'schedule_auto' => ['nullable', 'boolean'],
            'payments' => ['nullable', 'boolean'],
        ]);

        TeacherAppRules::save(array_map(fn ($key) => (bool) ($validated[$key] ?? false), array_combine(
            array_keys(TeacherAppRules::KEYS),
            array_keys(TeacherAppRules::KEYS)
        )));

        return redirect()->route('admin.setting.teacher-app')->with('success', 'Teacher app settings saved.');
    }

    public function requests(Request $request): View
    {
        $status = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected', 'failed', 'all'])],
        ])['status'] ?? 'pending';

        $requests = $this->requestQuery()
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->with([
                'teacher:id,full_name,custom_id',
                'branch:id,name',
                'studentClass:id,class_name',
                'classSchedule.hall:id,hall_name',
                'classSchedule.classCategoryFee.category:id,category_name',
                'course:id,name',
                'courseSession.hall:id,hall_name',
                'reviewer:id,name',
            ])
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $hallIds = $requests->getCollection()->pluck('payload.class_hall_id')->filter()->unique();
        $halls = \App\Models\ClassHall::query()->whereIn('id', $hallIds)->pluck('hall_name', 'id');

        return view('setting.teacher-app.requests', [
            'requests' => $requests,
            'status' => $status,
            'halls' => $halls,
            'autoApply' => TeacherAppRules::allows('schedule_auto'),
        ]);
    }

    public function approve(int $id): RedirectResponse
    {
        $request = $this->requestQuery()->findOrFail($id);

        try {
            $result = $this->schedules->approve($request, auth()->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return $result->status === 'approved'
            ? back()->with('success', 'Approved. The class day is changed and parents are told.')
            : back()->withErrors(['request' => 'Could not be applied: ' . $result->error]);
    }

    public function reject(Request $httpRequest, int $id): RedirectResponse
    {
        $note = $httpRequest->validate(['review_note' => ['nullable', 'string', 'max:255']])['review_note'] ?? null;

        try {
            $this->schedules->reject($this->requestQuery()->findOrFail($id), auth()->user(), $note);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', 'Request rejected.');
    }

    /** Requests of the branches this admin can see. */
    protected function requestQuery()
    {
        return $this->branches->applyBranchAccess(TeacherScheduleRequest::query(), 'branch_id');
    }
}

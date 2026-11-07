<?php

namespace App\Http\Controllers\Web\Student;

use App\Http\Controllers\Controller;
use App\Services\Branch\BranchAccessService;
use App\Services\Web\Student\DisabledStudentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Disabled Students: one place to see every disabled student and enable
 * them again (all staff, own branches). See DisabledStudentService.
 */
class DisabledStudentController extends Controller
{
    public function __construct(
        protected DisabledStudentService $service,
        protected BranchAccessService $branchAccess
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'branch_id', 'reason']);

        return view('disabled-students.index', [
            'students' => $this->service->paginate($filters),
            'counts' => $this->service->counts(),
            'filters' => $filters,
            'branches' => $this->branchAccess->getAccessibleBranches(),
            'reasons' => DisabledStudentService::REASONS,
        ]);
    }

    /**
     * Enable one student ("only") or the ticked ones ("ids[]").
     */
    public function enable(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'only' => ['nullable', 'integer'],
            'ids' => ['nullable', 'array', 'max:500'],
            'ids.*' => ['integer'],
            'restore_classes' => ['nullable', 'boolean'],
        ]);

        $ids = !empty($data['only']) ? [(int) $data['only']] : array_map('intval', $data['ids'] ?? []);

        if (!$ids) {
            return back()->with('error', 'Tick the students to enable first.');
        }

        [$enabled, $classes] = $this->service->enable($ids, $request->boolean('restore_classes'), auth()->id());

        if ($enabled === 0) {
            return back()->with('error', 'No disabled student found to enable (already enabled, or not in your branch).');
        }

        $message = $enabled === 1 ? '1 student enabled.' : "{$enabled} students enabled.";
        if ($request->boolean('restore_classes')) {
            $message .= $classes === 1 ? ' Put back in 1 class.' : " Put back in {$classes} classes.";
        }

        return back()->with('success', $message);
    }
}

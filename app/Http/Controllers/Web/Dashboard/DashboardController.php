<?php

namespace App\Http\Controllers\Web\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Web\Dashboard\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function index(Request $request): View
    {
        $role = strtoupper(auth()->user()->userType->code);

        return match ($role) {
            'SUPER_ADMIN', 'ADMIN' => $this->adminDashboard(),
            'BRANCH_ADMIN' => $this->branchAdminDashboard($request),
            'USER' => view('dashboard.user'),
            'TEACHER' => view('dashboard.teacher'),
            default => abort(403, 'Dashboard access denied.'),
        };
    }

    protected function adminDashboard(): View
    {
        return view(
            'dashboard.index',
            $this->dashboardService->getDashboardData()
        );
    }

    /**
     * The admin dashboard, with every number limited to the Branch Admin's
     * branches (branch_users). More than one branch -> a branch picker
     * ("All my branches" or one branch).
     */
    protected function branchAdminDashboard(Request $request): View
    {
        $myBranches = auth()->user()->branches()
            ->orderBy('branches.name')
            ->get(['branches.id', 'branches.name']);

        if ($myBranches->isEmpty()) {
            return view('dashboard.no-branch');
        }

        $selected = (int) $request->query('branch_id');
        $selected = $myBranches->contains('id', $selected) ? $selected : null;

        $branchIds = $selected ? [$selected] : $myBranches->pluck('id')->all();

        return view('dashboard.index', $this->dashboardService->forBranches($branchIds)->getDashboardData() + [
            'branchView' => true,
            'myBranches' => $myBranches,
            'selectedBranchId' => $selected,
            'branchTitle' => $selected
                ? $myBranches->firstWhere('id', $selected)->name
                : ($myBranches->count() === 1 ? $myBranches->first()->name : 'All my branches'),
        ]);
    }
}

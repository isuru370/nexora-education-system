<?php

namespace App\Http\Controllers\Web\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Web\Dashboard\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function index(): View
    {
        $role = strtoupper(auth()->user()->userType->code);

        return match ($role) {
            'SUPER_ADMIN', 'ADMIN' => $this->adminDashboard(),
            'BRANCH_ADMIN' => view('dashboard.branch-admin'),
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
}
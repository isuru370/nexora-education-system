<?php

namespace App\Http\Controllers\Web\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Branch\StoreBranchRequest;
use App\Http\Requests\Web\Branch\UpdateBranchRequest;
use App\Models\Branch;
use App\Services\Branch\BranchAccessService;
use App\Services\Branch\BranchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function __construct(
        protected BranchService $branchService,
        protected BranchAccessService $branchAccess
    ) {}

    /**
     * Display a listing of branches.
     */
    public function index(): View
    {
        $branches = $this->branchService->getAll(false);

        /*
        |--------------------------------------------------------------------------
        | Stats — full dataset, not just the visible list
        |--------------------------------------------------------------------------
        | Computed here so the view stays clean and pagination-safe.
        */
        $totalBranches  = Branch::count();
        $activeBranches = Branch::where('is_active', true)->count();

        $stats = [
            'total'             => $totalBranches,
            'active'            => $activeBranches,
            'inactive'          => Branch::where('is_active', false)->count(),
            'active_percentage' => $totalBranches > 0
                ? (int) round(($activeBranches / $totalBranches) * 100)
                : 0,
        ];

        return view('branches.index', compact('branches', 'stats'));
    }

    /**
     * Show the form for creating a new branch.
     */
    public function create(): View
    {
        return view('branches.create');
    }

    /**
     * Store a newly created branch.
     */
    public function store(StoreBranchRequest $request): RedirectResponse
    {
        $this->branchService->create(
            $request->validated()
        );

        return redirect()
            ->route('branches.index')
            ->with('success', 'Branch created successfully.');
    }

    /**
     * Display the specified branch.
     */
    public function show(int $branch): View
    {
        $branch = $this->branchService->find($branch);

        return view('branches.show', compact('branch'));
    }

    /**
     * Show the form for editing the specified branch.
     */
    public function edit(int $branch): View
    {
        $branch = $this->branchService->find($branch);

        return view('branches.edit', compact('branch'));
    }

    /**
     * Update the specified branch.
     */
    public function update(
        UpdateBranchRequest $request,
        int $branch
    ): RedirectResponse {
        $this->branchService->update(
            $branch,
            $request->validated()
        );

        return redirect()
            ->route('branches.index')
            ->with('success', 'Branch updated successfully.');
    }

    /**
     * Activate the specified branch.
     */
    public function activate(int $branch): RedirectResponse
    {
        $this->branchService->activate($branch);

        return redirect()
            ->route('branches.index')
            ->with('success', 'Branch activated successfully.');
    }

    /**
     * Deactivate the specified branch.
     */
    public function deactivate(int $branch): RedirectResponse
    {
        $this->branchService->deactivate($branch);

        return redirect()
            ->route('branches.index')
            ->with('success', 'Branch deactivated successfully.');
    }

    /**
     * Remove the specified branch.
     */
    public function destroy(int $branch): RedirectResponse
    {
        $this->branchService->delete($branch);

        return redirect()
            ->route('branches.index')
            ->with('success', 'Branch deleted successfully.');
    }

    /**
     * Restore a deleted branch.
     */
    public function restore(int $branch): RedirectResponse
    {
        $this->branchService->restore($branch);

        return redirect()
            ->route('branches.index')
            ->with('success', 'Branch restored successfully.');
    }
}
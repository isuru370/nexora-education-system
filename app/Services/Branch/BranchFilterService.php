<?php

namespace App\Services\Branch;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class BranchFilterService
{
    public function __construct(
        protected BranchAccessService $branchAccess
    ) {}

    /**
     * Apply branch filter to a query.
     */
    public function apply(
        Builder $query,
        ?int $branchId = null
    ): Builder {
        $branchIds = $this->branchAccess->getBranchIds();

        if ($branchIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        if ($branchId !== null) {
            $this->ensureBranchAccess($branchId);

            return $query->where('branch_id', $branchId);
        }

        return $query->whereIn('branch_id', $branchIds);
    }

    /**
     * Apply branch filter with institute-wide records.
     */
    public function applyWithInstituteWide(
        Builder $query,
        ?int $branchId = null
    ): Builder {
        $branchIds = $this->branchAccess->getBranchIds();

        if ($branchIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        if ($branchId !== null) {
            $this->ensureBranchAccess($branchId);

            return $query->where(function (Builder $query) use ($branchId) {
                $query
                    ->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            });
        }

        return $query->where(function (Builder $query) use ($branchIds) {
            $query
                ->whereIn('branch_id', $branchIds)
                ->orWhereNull('branch_id');
        });
    }

    /**
     * Get accessible branches.
     */
    public function accessibleBranches(): Collection
    {
        return $this->branchAccess->getAccessibleBranches();
    }

    /**
     * Ensure the authenticated user can access the branch.
     */
    protected function ensureBranchAccess(int $branchId): void
    {
        if (!$this->branchAccess->canAccessBranch($branchId)) {
            throw new AccessDeniedHttpException(
                'Branch not accessible.'
            );
        }
    }
}
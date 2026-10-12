<?php

namespace App\Services\Branch;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BranchAccessService
{
    /**
     * Get branch IDs accessible by the authenticated user.
     */
    public function getBranchIds(): Collection
    {
        $user = auth()->user();

        if (!$user instanceof User || !$user->userType) {
            return collect();
        }

        $role = strtoupper($user->userType->code);

        return match ($role) {
            'SUPER_ADMIN', 'ADMIN' => Branch::query()
                ->where('is_active', true)
                ->pluck('id'),

            'BRANCH_ADMIN', 'USER' => $user->branches()
                ->where('branches.is_active', true)
                ->pluck('branches.id'),

            'TEACHER' => $this->getTeacherBranchIds($user),

            default => collect(),
        };
    }

    /**
     * Get accessible branches.
     */
    public function getAccessibleBranches(): Collection
    {
        return Branch::query()
            ->whereIn('id', $this->getBranchIds())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Check whether the authenticated user
     * can access the given branch.
     */
    public function canAccessBranch(int $branchId): bool
    {
        return $this->getBranchIds()->contains($branchId);
    }

    /**
     * Get teacher branch IDs.
     */
    protected function getTeacherBranchIds(User $user): Collection
    {
        $teacher = $user->teacher;

        if (!$teacher) {
            return collect();
        }

        return $teacher->branches()
            ->where('branches.is_active', true)
            ->pluck('branches.id');
    }

    /**
     * Apply branch access restriction to a query.
     */
    public function applyBranchAccess(
        Builder $query,
        string $column = 'branch_id'
    ): Builder {
        $branchIds = $this->getBranchIds();

        if ($branchIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($column, $branchIds);
    }

    /**
     * Get the authenticated user's accessible branch IDs
     * as a plain array.
     */
    public function getBranchIdArray(): array
    {
        return $this->getBranchIds()
            ->values()
            ->all();
    }
}
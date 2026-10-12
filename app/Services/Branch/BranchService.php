<?php

namespace App\Services\Branch;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BranchService
{
    /**
     * Get all branches.
     */
    public function getAll(bool $activeOnly = true): Collection
    {
        return Branch::query()
            ->when(
                $activeOnly,
                fn($query) => $query->where('is_active', true)
            )
            ->orderBy('name')
            ->get();
    }

    /**
     * Find a branch by ID.
     */
    public function find(int $branchId): Branch
    {
        return Branch::query()->findOrFail($branchId);
    }

    /**
     * Find an active branch by ID.
     */
    public function findActive(int $branchId): Branch
    {
        return Branch::query()
            ->where('is_active', true)
            ->findOrFail($branchId);
    }

    /**
     * Create a new branch.
     */
    public function create(array $data): Branch
    {
        return DB::transaction(function () use ($data) {

            $data['code'] = $this->generateBranchCode();

            return Branch::query()->create($data);
        });
    }

    /**
     * Generate the next branch code.
     *
     * Example:
     * BR001
     * BR002
     * BR003
     */
    protected function generateBranchCode(): string
    {
        $lastCode = Branch::withTrashed()
            ->lockForUpdate()
            ->where('code', 'like', 'BR%')
            ->orderByDesc('id')
            ->value('code');

        $nextNumber = 1;

        if ($lastCode && preg_match('/^BR(\d+)$/', $lastCode, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        }

        return 'BR' . str_pad(
            $nextNumber,
            3,
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * Update an existing branch.
     */
    public function update(int $branchId, array $data): Branch
    {
        return DB::transaction(function () use ($branchId, $data) {
            $branch = Branch::query()
                ->lockForUpdate()
                ->findOrFail($branchId);

            $branch->update($data);

            return $branch->fresh();
        });
    }

    /**
     * Activate a branch.
     */
    public function activate(int $branchId): Branch
    {
        return DB::transaction(function () use ($branchId) {
            $branch = Branch::query()
                ->lockForUpdate()
                ->findOrFail($branchId);

            $branch->update([
                'is_active' => true,
            ]);

            return $branch->fresh();
        });
    }

    /**
     * Deactivate a branch.
     */
    public function deactivate(int $branchId): Branch
    {
        return DB::transaction(function () use ($branchId) {
            $branch = Branch::query()
                ->lockForUpdate()
                ->findOrFail($branchId);

            $branch->update([
                'is_active' => false,
            ]);

            return $branch->fresh();
        });
    }

    /**
     * Delete a branch.
     */
    public function delete(int $branchId): bool
    {
        return DB::transaction(function () use ($branchId) {
            $branch = Branch::query()
                ->lockForUpdate()
                ->findOrFail($branchId);

            return (bool) $branch->delete();
        });
    }

    /**
     * Restore a soft-deleted branch.
     */
    public function restore(int $branchId): Branch
    {
        return DB::transaction(function () use ($branchId) {
            $branch = Branch::withTrashed()
                ->lockForUpdate()
                ->findOrFail($branchId);

            $branch->restore();

            return $branch->fresh();
        });
    }

    /**
     * Check whether a branch exists.
     */
    public function exists(int $branchId): bool
    {
        return Branch::query()
            ->whereKey($branchId)
            ->exists();
    }

    /**
     * Check whether a branch is active.
     */
    public function isActive(int $branchId): bool
    {
        return Branch::query()
            ->whereKey($branchId)
            ->where('is_active', true)
            ->exists();
    }
}

<?php

namespace App\Services;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BranchService
{
    /**
     * Get all branches.
     */
    public function getAll(): Collection
    {
        return Branch::query()
            ->orderBy('name')
            ->get();
    }

    /**
     * Get active branches.
     */
    public function getActive(): Collection
    {
        return Branch::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Generate unique branch code.
     */
    private function generateBranchCode(): string
    {
        $lastBranch = Branch::query()
            ->orderByDesc('id')
            ->first();

        $nextNumber = $lastBranch
            ? ((int) str_replace('BR-', '', $lastBranch->code)) + 1
            : 1;

        return 'BR-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Create branch.
     */
    public function create(array $data): Branch
    {
        return DB::transaction(function () use ($data) {

            // Remove manually supplied code
            unset($data['code']);

            // Generate unique branch code
            $data['code'] = $this->generateBranchCode();

            return Branch::create($data);
        });
    }

    /**
     * Update branch.
     */
    public function update(Branch $branch, array $data): Branch
    {
        return DB::transaction(function () use ($branch, $data) {

            // Branch code cannot be changed
            unset($data['code']);

            $branch->update($data);

            return $branch->fresh();
        });
    }

    /**
     * Toggle branch active status.
     */
    public function toggleActive(Branch $branch): Branch
    {
        $branch->update([
            'is_active' => !$branch->is_active,
        ]);

        return $branch->fresh();
    }

    /**
     * Delete branch.
     */
    public function delete(Branch $branch): bool
    {
        return DB::transaction(function () use ($branch) {

            return $branch->delete();
        });
    }
}
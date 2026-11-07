<?php

namespace App\Http\Controllers\Concerns;

use App\Services\Branch\BranchAccessService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Records with a branch_id that Admins see for the whole institute and a
 * Branch Admin only for their own branches (Extra Income, Expenses).
 *
 * Old rows saved without a branch (branch_id = null) belong to the whole
 * institute: only Super Admin / Admin see them.
 */
trait BranchScoped
{
    protected function branchAccess(): BranchAccessService
    {
        return app(BranchAccessService::class);
    }

    /** Super Admin / Admin: every branch + institute-wide rows */
    protected function seesWholeInstitute(): bool
    {
        $role = strtoupper((string) optional(optional(auth()->user())->userType)->code);

        return in_array($role, ['SUPER_ADMIN', 'ADMIN'], true);
    }

    /** Limit a query to the user's branches */
    protected function scopeToBranches($query, string $column = 'branch_id')
    {
        if (!$this->seesWholeInstitute()) {
            $query->whereIn($column, $this->branchAccess()->getBranchIds());
        }

        return $query;
    }

    /** 403 when the record is not in the user's branches */
    protected function authorizeBranch(Model $record): void
    {
        if ($this->seesWholeInstitute()) {
            return;
        }

        abort_unless(
            $record->branch_id && $this->branchAccess()->canAccessBranch((int) $record->branch_id),
            403,
            'This record belongs to another branch.'
        );
    }

    /** Branches for the form's Branch box */
    protected function formBranches(): Collection
    {
        return $this->branchAccess()->getAccessibleBranches();
    }

    /**
     * branch_id from the form: Admins may leave it empty (whole institute);
     * a Branch Admin must choose one of their branches (only one -> that one).
     */
    protected function branchIdFromRequest(Request $request): ?int
    {
        $id = $request->filled('branch_id') ? (int) $request->input('branch_id') : null;

        if ($id === null && !$this->seesWholeInstitute()) {
            $mine = $this->branchAccess()->getBranchIds();
            if ($mine->count() === 1) {
                return (int) $mine->first();
            }
        }

        if ($id === null) {
            if ($this->seesWholeInstitute()) {
                return null;
            }

            throw ValidationException::withMessages(['branch_id' => 'Choose the branch.']);
        }

        if (!$this->branchAccess()->canAccessBranch($id)) {
            throw ValidationException::withMessages(['branch_id' => 'You cannot use this branch.']);
        }

        return $id;
    }
}

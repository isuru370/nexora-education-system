<?php

namespace App\Services\Web\Organizer;

use App\Exports\Web\Organizer\OrganizerExport;
use App\Models\ClassPaymentConfig;
use App\Models\Organizer;
use App\Services\Branch\BranchAccessService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class OrganizerService
{
    public function __construct(
        private BranchAccessService $branchAccessService
    ) {}

    /**
     * Get accessible branch IDs as an array.
     * BranchAccessService is not modified.
     */
    protected function accessibleBranchIds(): array
    {
        return $this->branchAccessService
            ->getBranchIds()
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Base query restricted to organizers assigned
     * to branches accessible by the current user.
     */
    protected function baseQuery(): Builder
    {
        $branchIds = $this->accessibleBranchIds();

        return Organizer::query()
            ->whereHas('branches', function ($query) use ($branchIds) {
                $query->whereIn('branches.id', $branchIds);
            })
            ->with('branches');
    }

    /**
     * Validate selected branch IDs against user access.
     */
    protected function validateBranchAccess(array $branchIds): array
    {
        $branchIds = array_values(array_unique(array_map(
            'intval',
            $branchIds
        )));

        $accessibleIds = $this->accessibleBranchIds();

        if (
            empty($branchIds) ||
            count(array_diff($branchIds, $accessibleIds)) > 0
        ) {
            throw ValidationException::withMessages([
                'branch_ids' => 'Select only branches you are allowed to access.',
            ]);
        }

        return $branchIds;
    }

    /**
     * Ensure the organizer belongs to at least one
     * branch accessible by the current user.
     */
    protected function ensureOrganizerAccess(Organizer $organizer): void
    {
        $allowed = $organizer->branches()
            ->whereIn(
                'branches.id',
                $this->accessibleBranchIds()
            )
            ->exists();

        if (!$allowed) {
            abort(403, 'You do not have access to this organizer.');
        }
    }

    /**
     * Get organizers with search and filters.
     */

    public function getIndexData(Request $request): array
    {
        // Apply search, status and branch filters to the listing.
        $query = $this->applyFilters(
            $this->baseQuery(),
            $request
        );

        $organizers = $query
            ->latest('organizers.id')
            ->paginate(10)
            ->appends($request->query());

        // Statistics are restricted to branches accessible by the user.
        $statsQuery = $this->baseQuery();

        // If a branch is selected, calculate statistics for that branch.
        if ($request->filled('branch_id')) {
            $branchId = (int) $request->branch_id;

            if (!in_array(
                $branchId,
                $this->accessibleBranchIds(),
                true
            )) {
                abort(403, 'You do not have access to this branch.');
            }

            $statsQuery->whereHas('branches', function ($query) use ($branchId) {
                $query->where('branches.id', $branchId);
            });
        }

        $stats = [
            'total' => $organizers->total(),

            'active' => (clone $statsQuery)
                ->where('organizers.is_active', 1)
                ->count(),

            'inactive' => (clone $statsQuery)
                ->where('organizers.is_active', 0)
                ->count(),

            'registered_codes' => (clone $statsQuery)
                ->whereNotNull('organizers.code')
                ->count(),
        ];

        return [
            'organizers' => $organizers,

            'branches' => $this->branchAccessService
                ->getAccessibleBranches(),

            'stats' => $stats,
        ];
    }

    /**
     * Get data for create/edit forms.
     */
    public function getFormData(?Organizer $organizer = null): array
    {
        if ($organizer) {
            $this->ensureOrganizerAccess($organizer);
            $organizer->load('branches');
        }

        return [
            'organizer' => $organizer,
            'branches' => $this->branchAccessService
                ->getAccessibleBranches(),
        ];
    }

    /**
     * Generate the next organizer code.
     * Call this inside a database transaction.
     */
    protected function generateCode(): string
    {
        $last = Organizer::withTrashed()
            ->lockForUpdate()
            ->orderByDesc('id')
            ->first();

        $number = $last
            ? ((int) substr($last->code, 3)) + 1
            : 1;

        return 'ORG' . str_pad(
            $number,
            3,
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * Create organizer and assign selected branches.
     */
    public function store(array $data): Organizer
    {
        $branchIds = $this->validateBranchAccess(
            $data['branch_ids'] ?? []
        );

        return DB::transaction(function () use ($data, $branchIds) {
            $organizer = Organizer::create([
                'code' => $this->generateCode(),
                'name' => $data['name'],
                'mobile' => $data['mobile'] ?? null,
                'email' => $data['email'] ?? null,
                'nic' => $data['nic'] ?? null,
                'note' => $data['note'] ?? null,
                'is_active' => $data['is_active'] ?? false,
                'created_by' => Auth::id(),
            ]);

            $organizer->branches()->sync($branchIds);

            return $organizer->load('branches');
        });
    }

    /**
     * Get organizer details.
     */
    public function getDetails(Organizer $organizer): Organizer
    {
        $this->ensureOrganizerAccess($organizer);

        return $organizer->load([
            'branches',
            'createdBy',
            'paymentConfigs.studentClass',
        ]);
    }

    /**
     * Update organizer.
     *
     * Preserve assignments to branches that the current
     * user cannot access. Only accessible assignments
     * can be changed by this user.
     */
    public function update(
        Organizer $organizer,
        array $data
    ): Organizer {
        $this->ensureOrganizerAccess($organizer);

        $branchIds = $this->validateBranchAccess(
            $data['branch_ids'] ?? []
        );

        return DB::transaction(function () use (
            $organizer,
            $data,
            $branchIds
        ) {
            $accessibleIds = $this->accessibleBranchIds();

            // Preserve branch assignments outside the user's access.
            $existingBranchIds = $organizer->branches()
                ->pluck('branches.id')
                ->map(fn($id) => (int) $id)
                ->all();

            $preservedBranchIds = array_diff(
                $existingBranchIds,
                $accessibleIds
            );

            // Replace accessible assignments but preserve the others.
            $finalBranchIds = array_values(array_unique(array_merge(
                $preservedBranchIds,
                $branchIds
            )));

            $organizer->update([
                'name' => $data['name'],
                'mobile' => $data['mobile'] ?? null,
                'email' => $data['email'] ?? null,
                'nic' => $data['nic'] ?? null,
                'note' => $data['note'] ?? null,
                'is_active' => $data['is_active'] ?? false,
            ]);

            $organizer->branches()->sync($finalBranchIds);

            return $organizer->fresh()->load('branches');
        });
    }

    /**
     * Delete organizer only when no active class depends on it.
     */
    public function delete(Organizer $organizer): void
    {
        $this->ensureOrganizerAccess($organizer);

        $hasActiveClasses = ClassPaymentConfig::where(
            'organizer_id',
            $organizer->id
        )
            ->where('is_active', true)
            ->whereHas('studentClass', function ($query) {
                $query->where('is_active', true);
            })
            ->exists();

        if ($hasActiveClasses) {
            throw ValidationException::withMessages([
                'organizer' =>
                'Cannot delete organizer while active classes are assigned.',
            ]);
        }

        DB::transaction(function () use ($organizer) {
            ClassPaymentConfig::where(
                'organizer_id',
                $organizer->id
            )->delete();

            $organizer->branches()->detach();

            $organizer->delete();
        });
    }

    /**
     * Toggle active status and synchronize payment configurations.
     */
    public function toggleActive(Organizer $organizer): bool
    {
        $this->ensureOrganizerAccess($organizer);

        $newStatus = !$organizer->is_active;

        if (!$newStatus) {
            $hasActiveClasses = ClassPaymentConfig::where(
                'organizer_id',
                $organizer->id
            )
                ->where('is_active', true)
                ->whereHas('studentClass', function ($query) {
                    $query->where('is_active', true);
                })
                ->exists();

            if ($hasActiveClasses) {
                throw ValidationException::withMessages([
                    'organizer' =>
                    'Cannot deactivate organizer while active classes are assigned.',
                ]);
            }
        }

        return DB::transaction(function () use (
            $organizer,
            $newStatus
        ) {
            $organizer->update([
                'is_active' => $newStatus,
            ]);

            ClassPaymentConfig::where(
                'organizer_id',
                $organizer->id
            )->update([
                'is_active' => $newStatus,
            ]);

            return $newStatus;
        });
    }

    /**
     * Export filtered organizers to Excel.
     */
    public function exportExcel(Request $request)
    {
        $query = $this->filteredQuery($request);

        return Excel::download(
            new OrganizerExport($query),
            'organizers_' . now()->format('Y-m-d_H-i-s') . '.xlsx'
        );
    }

    /**
     * Export filtered organizers to PDF.
     */
    public function exportPdf(Request $request)
    {
        $organizers = $this->filteredQuery($request)
            ->with('branches')
            ->latest('organizers.id')
            ->get();

        return Pdf::loadView(
            'pdf.organizer.index',
            compact('organizers')
        )
            ->setPaper('a4', 'landscape')
            ->download(
                'organizers_' . now()->format('Y-m-d_H-i-s') . '.pdf'
            );
    }

    /**
     * Apply the same search and filters to listing and exports.
     */
    protected function filteredQuery(Request $request): Builder
    {
        return $this->applyFilters(
            $this->baseQuery(),
            $request
        );
    }

    /**
     * Apply search, status and branch filters.
     */
    protected function applyFilters(
        Builder $query,
        Request $request
    ): Builder {
        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('organizers.name', 'like', "%{$search}%")
                    ->orWhere('organizers.mobile', 'like', "%{$search}%")
                    ->orWhere('organizers.email', 'like', "%{$search}%")
                    ->orWhere('organizers.nic', 'like', "%{$search}%")
                    ->orWhere('organizers.code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where(
                'organizers.is_active',
                $request->is_active === 'true'
            );
        }

        if ($request->filled('branch_id')) {
            $branchId = (int) $request->branch_id;

            if (!in_array(
                $branchId,
                $this->accessibleBranchIds(),
                true
            )) {
                abort(403, 'You do not have access to this branch.');
            }

            $query->whereHas('branches', function ($q) use ($branchId) {
                $q->where('branches.id', $branchId);
            });
        }

        return $query;
    }
}

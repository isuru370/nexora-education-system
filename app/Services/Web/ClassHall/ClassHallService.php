<?php

namespace App\Services\Web\ClassHall;

use App\Models\ClassHall;
use App\Services\Branch\BranchAccessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class ClassHallService
{
    protected BranchAccessService $branchAccessService;

    public function __construct(BranchAccessService $branchAccessService)
    {
        $this->branchAccessService = $branchAccessService;
    }

    protected function accessibleBranchIds(): array
    {
        return $this->branchAccessService
            ->getBranchIds()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    protected function baseQuery(): Builder
    {
        return $this->branchAccessService->applyBranchAccess(
            ClassHall::query(),
            'branch_id'
        );
    }

    public function getIndexData(Request $request): array
    {
        $query = $this->baseQuery()->with('branch');

        if ($request->filled('search')) {
            $search = trim($request->input('search'));

            $query->where(function ($q) use ($search) {
                $q->where('hall_name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('hall_type', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where(
                'is_active',
                $request->input('is_active') === 'true'
            );
        }

        return [
            'halls' => $query->latest('id')
                ->paginate(10)
                ->appends($request->query()),
        ];
    }

    public function getCreateData(): array
    {
        return [
            'branches' => $this->branchAccessService->getAccessibleBranches(),
        ];
    }

    public function getDetails(ClassHall $classHall): ClassHall
    {
        $this->ensureHallAccess($classHall);

        return $classHall->load([
            'branch',
            'schedules',
            'classSchedulePatterns',
            'exams',
        ]);
    }

    public function store(array $data): ClassHall
    {
        $this->ensureBranchAccess((int) $data['branch_id']);

        return DB::transaction(function () use ($data) {
            return ClassHall::create([
                'branch_id' => (int) $data['branch_id'],
                'code' => strtoupper(trim($data['code'])),
                'hall_name' => trim($data['hall_name']),
                'hall_type' => isset($data['hall_type'])
                    ? trim($data['hall_type'])
                    : null,
                'hall_price' => $data['hall_price'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);
        });
    }

    public function update(ClassHall $classHall, array $data): ClassHall
    {
        $this->ensureHallAccess($classHall);
        $this->ensureBranchAccess((int) $data['branch_id']);

        return DB::transaction(function () use ($classHall, $data) {
            $classHall->update([
                'branch_id' => (int) $data['branch_id'],
                'code' => strtoupper(trim($data['code'])),
                'hall_name' => trim($data['hall_name']),
                'hall_type' => isset($data['hall_type'])
                    ? trim($data['hall_type'])
                    : null,
                'hall_price' => $data['hall_price'] ?? null,
                'is_active' => $data['is_active'] ?? false,
            ]);

            return $classHall->fresh('branch');
        });
    }

    public function delete(ClassHall $classHall): void
    {
        $this->ensureHallAccess($classHall);

        if (
            $classHall->schedules()->exists()
            || $classHall->classSchedulePatterns()->exists()
            || $classHall->exams()->exists()
        ) {
            throw ValidationException::withMessages([
                'class_hall' => 'Cannot delete this hall because it is used by schedules, schedule patterns, or exams.',
            ]);
        }

        try {
            $classHall->delete();
        } catch (Throwable $e) {
            Log::error('Class hall delete failed', [
                'hall_id' => $classHall->id,
                'error' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'class_hall' => 'Class hall could not be deleted.',
            ]);
        }
    }

    public function toggleActive(ClassHall $classHall): bool
    {
        $this->ensureHallAccess($classHall);

        if ($classHall->is_active) {
            $hasSchedules = $classHall->schedules()->exists()
                || $classHall->classSchedulePatterns()->exists();

            if ($hasSchedules) {
                throw ValidationException::withMessages([
                    'class_hall' => 'Cannot deactivate this hall because it is used in schedules or schedule patterns.',
                ]);
            }
        }

        $classHall->update([
            'is_active' => ! $classHall->is_active,
        ]);

        return (bool) $classHall->is_active;
    }

    protected function ensureBranchAccess(int $branchId): void
    {
        if (! $this->branchAccessService->canAccessBranch($branchId)) {
            abort(403, 'You do not have access to this branch.');
        }
    }

    protected function ensureHallAccess(ClassHall $classHall): void
    {
        $this->ensureBranchAccess((int) $classHall->branch_id);
    }
}
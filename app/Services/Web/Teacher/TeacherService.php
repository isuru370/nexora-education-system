<?php

namespace App\Services\Web\Teacher;

use App\Models\BankBranch;
use App\Models\Branch;
use App\Models\ClassPaymentConfig;
use App\Models\StudentClass;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Branch\BranchAccessService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class TeacherService
{
    public function __construct(
        protected BranchAccessService $branchAccessService
    ) {}

    /**
     * Get paginated teachers accessible to current user.
     */
    public function getTeachers(array $filters = []): LengthAwarePaginator
    {
        try {
            $branchIds = $this->branchAccessService->getBranchIdArray();

            $query = Teacher::query()
                ->with([
                    'bankBranch.bank',
                    'branches',
                ])
                ->whereHas('branches', function ($query) use ($branchIds) {
                    $query->whereIn('branches.id', $branchIds);
                })
                ->latest();

            if (!empty($filters['search'])) {
                $search = trim($filters['search']);

                $query->where(function ($query) use ($search) {
                    $query->where('custom_id', 'like', "%{$search}%")
                        ->orWhere('full_name', 'like', "%{$search}%")
                        ->orWhere('initials', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%")
                        ->orWhere('nic', 'like', "%{$search}%");
                });
            }

            if (
                array_key_exists('is_active', $filters)
                && $filters['is_active'] !== null
                && $filters['is_active'] !== ''
            ) {
                $isActive = filter_var(
                    $filters['is_active'],
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                );

                if ($isActive !== null) {
                    $query->where('is_active', $isActive);
                }
            }

            if (!empty($filters['bank_branch_id'])) {
                $query->where(
                    'bank_branch_id',
                    $filters['bank_branch_id']
                );
            }

            if (!empty($filters['branch_id'])) {
                $branchId = (int) $filters['branch_id'];

                $this->ensureBranchAccess($branchId);

                $query->whereHas('branches', function ($query) use ($branchId) {
                    $query->where('branches.id', $branchId);
                });
            }

            return $query
                ->paginate(10)
                ->appends($filters);
        } catch (Throwable $e) {
            $this->logException(
                'Teacher list loading failed.',
                $e,
                [
                    'filters' => $filters,
                ]
            );

            throw $e;
        }
    }

    public function getStats(): array
    {
        $branchIds = $this->branchAccessService->getBranchIdArray();

        $baseQuery = Teacher::query()
            ->whereHas('branches', function ($query) use ($branchIds) {
                $query->whereIn('branches.id', $branchIds);
            });

        $branches = Branch::query()
            ->whereIn('id', $branchIds)
            ->where('is_active', true)
            ->withCount([
                'teachers' => function ($query) {
                    $query->where('is_active', true);
                }
            ])
            ->orderBy('name')
            ->get();

        return [
            'total' => (clone $baseQuery)->count(),

            'active' => (clone $baseQuery)
                ->where('is_active', true)
                ->count(),

            'inactive' => (clone $baseQuery)
                ->where('is_active', false)
                ->count(),

            'bank_linked' => (clone $baseQuery)
                ->whereNotNull('bank_branch_id')
                ->count(),

            'branches' => $branches,
        ];
    }

    /**
     * Get a teacher for the edit page.
     */
    public function findForEdit(int $teacherId): Teacher
    {
        try {
            $teacher = Teacher::query()
                ->with('branches')
                ->findOrFail($teacherId);

            $this->ensureTeacherAccessible($teacher);

            return $teacher;
        } catch (Throwable $e) {
            $this->logException(
                'Teacher edit data loading failed.',
                $e,
                [
                    'teacher_id' => $teacherId,
                ]
            );

            throw $e;
        }
    }

    /**
     * Get a teacher for the show page.
     */
    public function findForShow(int $teacherId): Teacher
    {
        try {
            $teacher = Teacher::query()
                ->with([
                    'branches',
                    'bankBranch.bank',
                    'classes.subject',
                    'classes.grade',
                ])
                ->findOrFail($teacherId);

            $this->ensureTeacherAccessible($teacher);

            return $teacher;
        } catch (Throwable $e) {
            $this->logException(
                'Teacher details loading failed.',
                $e,
                [
                    'teacher_id' => $teacherId,
                ]
            );

            throw $e;
        }
    }

    /**
     * Get bank branches for teacher create/edit forms.
     */
    public function getBankBranches(): Collection
    {
        try {
            return BankBranch::query()
                ->with('bank')
                ->orderBy('branch_name')
                ->get();
        } catch (Throwable $e) {
            $this->logException(
                'Teacher bank branches loading failed.',
                $e
            );

            throw $e;
        }
    }

    /**
     * Create a teacher and assign branches.
     */
    public function create(
        array $data,
        array $branchIds
    ): Teacher {
        try {
            return DB::transaction(function () use (
                $data,
                $branchIds
            ) {
                $branchIds = $this->validateBranchIds($branchIds);

                $data['custom_id'] = $this->generateCustomId();
                $data['is_active'] = $data['is_active'] ?? true;

                $teacher = Teacher::query()->create($data);

                $teacher->branches()->sync($branchIds);

                return $teacher->fresh([
                    'branches',
                    'bankBranch.bank',
                ]);
            });
        } catch (Throwable $e) {
            $this->logException(
                'Teacher creation failed.',
                $e,
                [
                    'branch_ids' => $branchIds,
                    'teacher_data' => $this->safeTeacherData($data),
                ]
            );

            throw $e;
        }
    }

    /**
     * Update teacher and branch assignments.
     */
    public function update(
        Teacher $teacher,
        array $data,
        array $branchIds
    ): Teacher {
        try {
            return DB::transaction(function () use (
                $teacher,
                $data,
                $branchIds
            ) {
                $this->ensureTeacherAccessible($teacher);

                $branchIds = $this->validateBranchIds($branchIds);

                $teacher = Teacher::query()
                    ->lockForUpdate()
                    ->findOrFail($teacher->id);

                $data['is_active'] = $data['is_active'] ?? true;

                $teacher->update($data);

                $teacher->branches()->sync($branchIds);

                return $teacher->fresh([
                    'branches',
                    'bankBranch.bank',
                ]);
            });
        } catch (Throwable $e) {
            $this->logException(
                'Teacher update failed.',
                $e,
                [
                    'teacher_id' => $teacher->id ?? null,
                    'branch_ids' => $branchIds,
                    'teacher_data' => $this->safeTeacherData($data),
                ]
            );

            throw $e;
        }
    }

    /**
     * Toggle teacher active status.
     *
     * Also updates related classes and payment configurations.
     */
    public function toggleActive(Teacher $teacher): Teacher
    {
        try {
            return DB::transaction(function () use ($teacher) {

                $this->ensureTeacherAccessible($teacher);

                $teacher = Teacher::query()
                    ->lockForUpdate()
                    ->findOrFail($teacher->id);

                $newStatus = !$teacher->is_active;

                $teacher->update([
                    'is_active' => $newStatus,
                ]);

                StudentClass::query()
                    ->where('teacher_id', $teacher->id)
                    ->update([
                        'is_active' => $newStatus,
                    ]);

                ClassPaymentConfig::query()
                    ->where('teacher_id', $teacher->id)
                    ->update([
                        'is_active' => $newStatus,
                    ]);

                return $teacher->fresh();
            });
        } catch (Throwable $e) {
            $this->logException(
                'Teacher active status update failed.',
                $e,
                [
                    'teacher_id' => $teacher->id ?? null,
                ]
            );

            throw $e;
        }
    }

    /**
     * Delete teacher and related records.
     */
    public function delete(Teacher $teacher): void
    {
        try {
            DB::transaction(function () use ($teacher) {

                $this->ensureTeacherAccessible($teacher);

                $teacher = Teacher::query()
                    ->lockForUpdate()
                    ->findOrFail($teacher->id);

                StudentClass::query()
                    ->where('teacher_id', $teacher->id)
                    ->update([
                        'is_active' => false,
                    ]);

                ClassPaymentConfig::query()
                    ->where('teacher_id', $teacher->id)
                    ->update([
                        'is_active' => false,
                    ]);

                StudentClass::query()
                    ->where('teacher_id', $teacher->id)
                    ->delete();

                ClassPaymentConfig::query()
                    ->where('teacher_id', $teacher->id)
                    ->delete();

                $teacher->delete();
            });
        } catch (Throwable $e) {
            $this->logException(
                'Teacher deletion failed.',
                $e,
                [
                    'teacher_id' => $teacher->id ?? null,
                ]
            );

            throw $e;
        }
    }

    /**
     * Get teachers for Excel/PDF export.
     */
    public function getTeachersForExport(
        array $filters = []
    ): Collection {
        try {
            $branchIds = $this->branchAccessService->getBranchIdArray();

            $query = Teacher::query()
                ->with([
                    'bankBranch.bank',
                    'branches',
                ])
                ->whereHas('branches', function ($query) use ($branchIds) {
                    $query->whereIn('branches.id', $branchIds);
                })
                ->orderBy('id');

            if (!empty($filters['branch_id'])) {
                $branchId = (int) $filters['branch_id'];

                $this->ensureBranchAccess($branchId);

                $query->whereHas('branches', function ($query) use ($branchId) {
                    $query->where('branches.id', $branchId);
                });
            }

            return $query->get();
        } catch (Throwable $e) {
            $this->logException(
                'Teacher export data loading failed.',
                $e,
                [
                    'filters' => $filters,
                ]
            );

            throw $e;
        }
    }

    /**
     * Get teacher login details.
     */
    public function getLoginDetails(int $teacherId): Teacher
    {
        try {
            $teacher = Teacher::query()
                ->with('user')
                ->findOrFail($teacherId);

            $this->ensureTeacherAccessible($teacher);

            return $teacher;
        } catch (Throwable $e) {
            $this->logException(
                'Teacher login details loading failed.',
                $e,
                [
                    'teacher_id' => $teacherId,
                ]
            );

            throw $e;
        }
    }

    /**
     * Create a login account for a teacher.
     */
    public function createLogin(
        Teacher $teacher,
        string $password
    ): User {
        try {
            return DB::transaction(function () use (
                $teacher,
                $password
            ) {

                $this->ensureTeacherAccessible($teacher);

                $teacher = Teacher::query()
                    ->lockForUpdate()
                    ->findOrFail($teacher->id);

                if ($teacher->user_id) {
                    throw new \RuntimeException(
                        'Teacher login account already exists.'
                    );
                }

                if (!$teacher->email) {
                    throw new \RuntimeException(
                        'Teacher email address is required.'
                    );
                }

                $existingUser = User::query()
                    ->where('email', $teacher->email)
                    ->lockForUpdate()
                    ->first();

                if ($existingUser) {
                    throw new \RuntimeException(
                        'A user account already exists with this email address.'
                    );
                }

                $user = User::query()->create([
                    'name' => $teacher->full_name,
                    'email' => $teacher->email,
                    'password' => $password,
                    'is_active' => $teacher->is_active,
                    'user_type_id' => $this->getTeacherUserTypeId(),
                ]);

                $teacher->update([
                    'user_id' => $user->id,
                ]);

                /*
                 * Teacher can access all branches assigned
                 * to the teacher.
                 */
                $branchIds = $teacher->branches()
                    ->pluck('branches.id')
                    ->all();

                if (!empty($branchIds)) {
                    $user->branches()->sync($branchIds);
                }

                return $user;
            });
        } catch (Throwable $e) {
            $this->logException(
                'Teacher login account creation failed.',
                $e,
                [
                    'teacher_id' => $teacher->id ?? null,
                ]
            );

            throw $e;
        }
    }

    /**
     * Reset teacher password.
     */
    public function resetPassword(
        Teacher $teacher,
        string $password
    ): void {
        try {
            DB::transaction(function () use (
                $teacher,
                $password
            ) {

                $this->ensureTeacherAccessible($teacher);

                $teacher = Teacher::query()
                    ->with('user')
                    ->lockForUpdate()
                    ->findOrFail($teacher->id);

                if (!$teacher->user) {
                    throw new \RuntimeException(
                        'Teacher login account does not exist.'
                    );
                }

                $teacher->user->update([
                    'password' => $password,
                ]);
            });
        } catch (Throwable $e) {
            $this->logException(
                'Teacher password reset failed.',
                $e,
                [
                    'teacher_id' => $teacher->id ?? null,
                ]
            );

            throw $e;
        }
    }

    /**
     * Generate the next teacher custom ID.
     *
     * Must be called inside a transaction.
     */
    protected function generateCustomId(): string
    {
        $lastTeacher = Teacher::withTrashed()
            ->lockForUpdate()
            ->orderByDesc('id')
            ->first();

        if (!$lastTeacher) {
            return 'SAT001';
        }

        if (
            !preg_match(
                '/^SAT(\d+)$/',
                $lastTeacher->custom_id,
                $matches
            )
        ) {
            return 'SAT001';
        }

        $nextNumber = (int) $matches[1] + 1;

        return 'SAT' . str_pad(
            $nextNumber,
            3,
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * Validate selected branches against user access.
     */
    protected function validateBranchIds(array $branchIds): array
    {
        $branchIds = collect($branchIds)
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($branchIds)) {
            throw new \InvalidArgumentException(
                'At least one branch must be selected.'
            );
        }

        foreach ($branchIds as $branchId) {
            $this->ensureBranchAccess($branchId);
        }

        return $branchIds;
    }

    /**
     * Ensure current user can access a branch.
     */
    protected function ensureBranchAccess(int $branchId): void
    {
        if (!$this->branchAccessService->canAccessBranch($branchId)) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException(
                'Branch not accessible.'
            );
        }
    }

    /**
     * Ensure current user can access a teacher.
     */
    protected function ensureTeacherAccessible(
        Teacher $teacher
    ): void {
        $branchIds = $this->branchAccessService
            ->getBranchIdArray();

        if (empty($branchIds)) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException(
                'No accessible branches found.'
            );
        }

        $hasAccess = $teacher->branches()
            ->whereIn('branches.id', $branchIds)
            ->exists();

        if (!$hasAccess) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException(
                'Teacher not accessible.'
            );
        }
    }

    /**
     * Get the user type ID used for teachers.
     */
    protected function getTeacherUserTypeId(): int
    {
        $userTypeId = DB::table('user_types')
            ->where('code', 'TEACHER')
            ->value('id');

        if (!$userTypeId) {
            throw new \RuntimeException(
                'Teacher user type not found.'
            );
        }

        return (int) $userTypeId;
    }

    /**
     * Remove sensitive fields before logging.
     */
    protected function safeTeacherData(array $data): array
    {
        unset(
            $data['password'],
            $data['password_confirmation']
        );

        return $data;
    }

    /**
     * Centralized service exception logging.
     */
    protected function logException(
        string $message,
        Throwable $e,
        array $context = []
    ): void {
        Log::error($message, array_merge([
            'user_id' => auth()->id(),
            'exception' => get_class($e),
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], $context));
    }
}

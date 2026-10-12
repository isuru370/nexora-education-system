<?php

namespace App\Services\Web\ClassCategoryFee;

use App\Models\ClassCategory;
use App\Models\ClassCategoryFee;
use App\Models\StudentClass;
use App\Services\Branch\BranchAccessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ClassCategoryFeeService
{
    protected BranchAccessService $branchAccessService;

    public function __construct(BranchAccessService $branchAccessService)
    {
        $this->branchAccessService = $branchAccessService;
    }

    /*
    |--------------------------------------------------------------------------
    | Branch Access
    |--------------------------------------------------------------------------
    */

    protected function accessibleBranchIds(): array
    {
        return $this->branchAccessService
            ->getBranchIds()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    protected function ensureBranchAccess(int $branchId): void
    {
        if (! $this->branchAccessService->canAccessBranch($branchId)) {
            abort(403, 'You do not have access to this branch.');
        }
    }

    protected function findAccessibleClass($id): StudentClass
    {
        $studentClass = StudentClass::query()
            ->whereIn('branch_id', $this->accessibleBranchIds())
            ->findOrFail($id);

        $this->ensureBranchAccess((int) $studentClass->branch_id);

        return $studentClass;
    }

    protected function baseQuery(): Builder
    {
        return ClassCategoryFee::query()
            ->whereHas('studentClass', function ($query) {
                $query->whereIn(
                    'branch_id',
                    $this->accessibleBranchIds()
                );
            });
    }

    protected function findAccessibleFee($id): ClassCategoryFee
    {
        return $this->baseQuery()->findOrFail($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    public function getAll(array $filters = [])
    {
        $query = $this->baseQuery()->with([
            'studentClass.grade',
            'studentClass.subject',
            'studentClass.teacher',
            'studentClass.branch',
            'category',
            'activeFeeOptions',
        ]);

        if (! empty($filters['student_class_id'])) {
            $studentClass = $this->findAccessibleClass(
                $filters['student_class_id']
            );

            $query->where('student_class_id', $studentClass->id);
        }

        if (! empty($filters['class_category_id'])) {
            $query->where(
                'class_category_id',
                $filters['class_category_id']
            );
        }

        if (array_key_exists('is_active', $filters)) {
            $query->where('is_active', $filters['is_active']);
        }

        $perPage = max(
            1,
            min((int) ($filters['per_page'] ?? 10), 100)
        );

        return $query
            ->latest('id')
            ->paginate($perPage)
            ->appends($filters);
    }

    /*
    |--------------------------------------------------------------------------
    | Find
    |--------------------------------------------------------------------------
    */

    public function find($id): ClassCategoryFee
    {
        return $this->findAccessibleFee($id)->load([
            'studentClass.grade',
            'studentClass.subject',
            'studentClass.teacher',
            'studentClass.branch',
            'category',
            'feeOptions',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Form Data
    |--------------------------------------------------------------------------
    */

    public function getIndexFormData(): array
    {
        $branchIds = $this->accessibleBranchIds();

        return [
            'classes' => StudentClass::query()
                ->whereIn('branch_id', $branchIds)
                ->where('is_active', true)
                ->with(['grade', 'subject', 'teacher'])
                ->orderBy('class_name')
                ->get(),

            'categories' => ClassCategory::query()
                ->where('is_active', true)
                ->orderBy('category_name')
                ->get(),
        ];
    }

    public function getCreateFormData($selectedClassId = null): array
    {
        if ($selectedClassId) {
            $selectedClass = $this->findAccessibleClass(
                $selectedClassId
            );

            if (! $selectedClass->is_active) {
                throw ValidationException::withMessages([
                    'student_class_id' => [
                        'Inactive class එකකට category fee add කරන්න බැහැ.',
                    ],
                ]);
            }
        }

        $formData = $this->getIndexFormData();

        $formData['selectedClassId'] = $selectedClassId;

        return $formData;
    }

    public function getEditFormData($id): array
    {
        $classCategoryFee = $this->find($id);

        if (
            ! $classCategoryFee->studentClass ||
            ! $classCategoryFee->studentClass->is_active
        ) {
            throw ValidationException::withMessages([
                'student_class_id' => [
                    'Inactive class fee edit කරන්න බැහැ.',
                ],
            ]);
        }

        $formData = $this->getIndexFormData();

        /*
         * If the existing class/category was deactivated after this
         * fee was created, include it so the current selection remains
         * visible in the edit form.
         */
        if (
            ! $formData['classes']->contains(
                'id',
                $classCategoryFee->student_class_id
            )
        ) {
            $formData['classes']->push(
                $classCategoryFee->studentClass
            );
        }

        if (
            ! $formData['categories']->contains(
                'id',
                $classCategoryFee->class_category_id
            )
        ) {
            $formData['categories']->push(
                $classCategoryFee->category
            );
        }

        $formData['classCategoryFee'] = $classCategoryFee;

        return $formData;
    }

    /*
    |--------------------------------------------------------------------------
    | Validation Helpers
    |--------------------------------------------------------------------------
    */

    protected function validateClassAndCategory(
        array $data
    ): array {
        $studentClass = $this->findAccessibleClass(
            $data['student_class_id']
        );

        if (! $studentClass->is_active) {
            throw ValidationException::withMessages([
                'student_class_id' => [
                    'Inactive class එකකට category fee assign කරන්න බැහැ.',
                ],
            ]);
        }

        $category = ClassCategory::query()
            ->findOrFail($data['class_category_id']);

        if (! $category->is_active) {
            throw ValidationException::withMessages([
                'class_category_id' => [
                    'Inactive category එකක් select කරන්න බැහැ.',
                ],
            ]);
        }

        return [$studentClass, $category];
    }

    protected function ensureNoDuplicate(
        int $studentClassId,
        int $categoryId,
        ?int $ignoreId = null
    ): void {
        $query = ClassCategoryFee::query()
            ->where('student_class_id', $studentClassId)
            ->where('class_category_id', $categoryId);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'class_category_id' => [
                    'This category is already assigned to this class.',
                ],
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Ongoing Class Status
    |--------------------------------------------------------------------------
    */

    protected function syncClassOngoing(int $studentClassId): void
    {
        $studentClass = StudentClass::query()
            ->whereIn('branch_id', $this->accessibleBranchIds())
            ->find($studentClassId);

        /*
         * Do not silently update a class outside accessible branches.
         * Callers should only pass classes previously access-checked.
         */
        if (! $studentClass) {
            return;
        }

        $hasActiveCategoryFees = ClassCategoryFee::query()
            ->where('student_class_id', $studentClassId)
            ->where('is_active', true)
            ->exists();

        $studentClass->update([
            'is_ongoing' => $hasActiveCategoryFees,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function create(array $data): ClassCategoryFee
    {
        return DB::transaction(function () use ($data) {
            [$studentClass] = $this->validateClassAndCategory($data);

            $this->ensureNoDuplicate(
                (int) $studentClass->id,
                (int) $data['class_category_id']
            );

            $classCategoryFee = ClassCategoryFee::create([
                'student_class_id' => $studentClass->id,
                'class_category_id' => $data['class_category_id'],
                'is_active' => array_key_exists('is_active', $data)
                    ? (bool) $data['is_active']
                    : true,
                'note' => $data['note'] ?? null,
            ]);

            $this->syncClassOngoing((int) $studentClass->id);

            return $classCategoryFee->fresh([
                'studentClass',
                'category',
                'feeOptions',
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update($id, array $data): ClassCategoryFee
    {
        return DB::transaction(function () use ($id, $data) {
            $classCategoryFee = $this->findAccessibleFee($id);

            $oldStudentClassId = (int) $classCategoryFee->student_class_id;

            [$newStudentClass] = $this->validateClassAndCategory($data);

            $this->ensureNoDuplicate(
                (int) $newStudentClass->id,
                (int) $data['class_category_id'],
                (int) $classCategoryFee->id
            );

            $classCategoryFee->update([
                'student_class_id' => $newStudentClass->id,
                'class_category_id' => $data['class_category_id'],
                'is_active' => array_key_exists('is_active', $data)
                    ? (bool) $data['is_active']
                    : (bool) $classCategoryFee->is_active,
                'note' => $data['note'] ?? null,
            ]);

            /*
             * Recalculate both classes when the class assignment changes.
             */
            $this->syncClassOngoing($oldStudentClassId);

            if ($oldStudentClassId !== (int) $newStudentClass->id) {
                $this->syncClassOngoing((int) $newStudentClass->id);
            } else {
                $this->syncClassOngoing($oldStudentClassId);
            }

            return $classCategoryFee->fresh([
                'studentClass',
                'category',
                'feeOptions',
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Enrollment Dependency Checks
    |--------------------------------------------------------------------------
    */

    protected function hasEnrolledFeeOptions(
        ClassCategoryFee $classCategoryFee
    ): bool {
        return $classCategoryFee->feeOptions()
            ->whereHas('enrollments')
            ->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    public function delete($id): bool
    {
        return DB::transaction(function () use ($id) {
            $classCategoryFee = $this->findAccessibleFee($id);

            $studentClassId = (int) $classCategoryFee->student_class_id;

            if ($this->hasEnrolledFeeOptions($classCategoryFee)) {
                throw ValidationException::withMessages([
                    'class_category_fee' => [
                        'This category fee cannot be deleted because its fee options are already used by student enrollments.',
                    ],
                ]);
            }

            try {
                $deleted = (bool) $classCategoryFee->delete();
            } catch (QueryException $e) {
                Log::warning('Class category fee delete blocked by database constraint', [
                    'class_category_fee_id' => $classCategoryFee->id,
                    'error' => $e->getMessage(),
                ]);

                throw ValidationException::withMessages([
                    'class_category_fee' => [
                        'This category fee cannot be deleted because related records still depend on it.',
                    ],
                ]);
            }

            $this->syncClassOngoing($studentClassId);

            return $deleted;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Toggle Active
    |--------------------------------------------------------------------------
    */

    public function toggleActive($id): ClassCategoryFee
    {
        return DB::transaction(function () use ($id) {
            $classCategoryFee = $this->findAccessibleFee($id);

            if ($classCategoryFee->is_active) {
                /*
                 * Existing enrollments must continue to reference a
                 * usable fee. Do not deactivate a fee already in use.
                 */
                if ($this->hasEnrolledFeeOptions($classCategoryFee)) {
                    throw ValidationException::withMessages([
                        'class_category_fee' => [
                            'Cannot deactivate this category fee because its fee options are already used by student enrollments.',
                        ],
                    ]);
                }
            } else {
                /*
                 * Reactivation requires an active class and category.
                 */
                $studentClass = $this->findAccessibleClass(
                    $classCategoryFee->student_class_id
                );

                if (! $studentClass->is_active) {
                    throw ValidationException::withMessages([
                        'class_category_fee' => [
                            'Cannot activate a category fee assigned to an inactive class.',
                        ],
                    ]);
                }

                $category = ClassCategory::findOrFail(
                    $classCategoryFee->class_category_id
                );

                if (! $category->is_active) {
                    throw ValidationException::withMessages([
                        'class_category_fee' => [
                            'Cannot activate a fee belonging to an inactive category.',
                        ],
                    ]);
                }
            }

            $classCategoryFee->update([
                'is_active' => ! $classCategoryFee->is_active,
            ]);

            $this->syncClassOngoing(
                (int) $classCategoryFee->student_class_id
            );

            return $classCategoryFee->fresh([
                'studentClass',
                'category',
                'feeOptions',
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Get Active Category Fees by Class
    |--------------------------------------------------------------------------
    */

    public function getByClass($studentClassId)
    {
        $studentClass = $this->findAccessibleClass($studentClassId);

        if (! $studentClass->is_active) {
            return collect();
        }

        return ClassCategoryFee::query()
            ->with([
                'category',
                'activeFeeOptions',
            ])
            ->where('student_class_id', $studentClass->id)
            ->where('is_active', true)
            ->whereHas('category', function ($query) {
                $query->where('is_active', true);
            })
            ->orderBy('id')
            ->get();
    }
}

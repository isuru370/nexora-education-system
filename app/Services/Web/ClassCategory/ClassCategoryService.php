<?php

namespace App\Services\Web\ClassCategory;

use App\Models\ClassCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;

class ClassCategoryService
{
    /**
     * List categories with search, filters and pagination.
     */
    public function getIndexData(Request $request): array
    {
        $query = ClassCategory::query();

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('category_name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where(
                'is_active',
                $request->is_active === 'true'
            );
        }

        if ($request->filled('is_schedulable')) {
            $query->where(
                'is_schedulable',
                $request->is_schedulable === 'true'
            );
        }

        return [
            'categories' => $query
                ->latest()
                ->paginate(10)
                ->appends($request->query()),
        ];
    }

    /**
     * Create a new category.
     */
    public function store(array $data): ClassCategory
    {
        return ClassCategory::create([
            'category_name' => trim($data['category_name']),
            'code' => strtoupper(trim($data['code'])),
            'is_schedulable' => $data['is_schedulable'] ?? true,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Update an existing category.
     */
    public function update(
        ClassCategory $classCategory,
        array $data
    ): ClassCategory {
        $classCategory->update([
            'category_name' => trim($data['category_name']),
            'code' => strtoupper(trim($data['code'])),
            'is_schedulable' => $data['is_schedulable'] ?? false,
            'is_active' => $data['is_active'] ?? false,
        ]);

        return $classCategory->refresh();
    }

    /**
     * Delete category only if it is not used by class category fees.
     */
    public function delete(ClassCategory $classCategory): void
    {
        try {
            if ($classCategory->classCategoryFees()->exists()) {
                throw ValidationException::withMessages([
                    'category' =>
                        'Cannot delete category. It is used in class category fees.',
                ]);
            }

            $classCategory->delete();
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Class category delete failed', [
                'category_id' => $classCategory->id,
                'error' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'category' => 'Class category delete failed.',
            ]);
        }
    }

    /**
     * Activate or deactivate a category.
     */
    public function toggleActive(ClassCategory $classCategory): bool
    {
        if ($classCategory->is_active) {
            $hasActiveFees = $classCategory->classCategoryFees()
                ->where('is_active', true)
                ->exists();

            if ($hasActiveFees) {
                throw ValidationException::withMessages([
                    'category' =>
                        'Cannot deactivate category. It is used in class category fees.',
                ]);
            }
        }

        $newStatus = !$classCategory->is_active;

        DB::transaction(function () use ($classCategory, $newStatus) {
            $classCategory->update([
                'is_active' => $newStatus,
            ]);
        });

        return $newStatus;
    }
}

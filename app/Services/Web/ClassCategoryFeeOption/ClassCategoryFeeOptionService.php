<?php

namespace App\Services\Web\ClassCategoryFeeOption;

use App\Models\ClassCategoryFee;
use App\Models\ClassCategoryFeeOption;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClassCategoryFeeOptionService
{
    /**
     * Get all fee options for a class category fee.
     *
     * @param int $classCategoryFeeId
     * @param bool $activeOnly
     * @return \Illuminate\Support\Collection
     */
    public function getOptionsByCategoryFee(
        $classCategoryFeeId,
        $activeOnly = false
    ) {
        $query = ClassCategoryFeeOption::query()
            ->where('class_category_fee_id', $classCategoryFeeId)
            ->orderByDesc('is_default')
            ->orderBy('id');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->get();
    }


    /**
     * Get a single fee option.
     *
     * @param int $id
     * @return ClassCategoryFeeOption
     */
    public function find($id)
    {
        return ClassCategoryFeeOption::with([
            'classCategoryFee.category',
            'classCategoryFee.studentClass',
        ])->findOrFail($id);
    }


    /**
     * Get active fee options for a class category fee.
     *
     * @param int $classCategoryFeeId
     * @return \Illuminate\Support\Collection
     */
    public function getActiveOptions($classCategoryFeeId)
    {
        return $this->getOptionsByCategoryFee(
            $classCategoryFeeId,
            true
        );
    }


    /**
     * Create a new fee option.
     *
     * @param array $data
     * @return ClassCategoryFeeOption
     */
    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {

            $categoryFee = $this->getValidCategoryFee(
                $data['class_category_fee_id']
            );

            $isActive = array_key_exists('is_active', $data)
                ? (bool) $data['is_active']
                : true;

            $hasOptions = ClassCategoryFeeOption::query()
                ->where('class_category_fee_id', $categoryFee->id)
                ->exists();

            /*
             * First active option automatically becomes default.
             */
            $isDefault = false;

            if ($isActive) {

                if (!$hasOptions) {
                    $isDefault = true;
                } elseif (
                    !empty($data['is_default'])
                ) {
                    $isDefault = true;
                }
            }

            /*
             * Only one default option is allowed.
             */
            if ($isDefault) {
                $this->removeDefaultFromOthers(
                    $categoryFee->id
                );
            }

            return ClassCategoryFeeOption::create([
                'class_category_fee_id' => $categoryFee->id,
                'label' => $data['label'],
                'fee' => $data['fee'],
                'is_default' => $isDefault,
                'is_active' => $isActive,
                'note' => isset($data['note'])
                    ? $data['note']
                    : null,
            ]);
        });
    }


    /**
     * Update an existing fee option.
     *
     * @param int $id
     * @param array $data
     * @return ClassCategoryFeeOption
     */
    public function update($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {

            $option = ClassCategoryFeeOption::findOrFail($id);

            /*
             * Fee option parent cannot be changed.
             *
             * Once an option exists, keep it attached
             * to the original ClassCategoryFee.
             */
            if (
                isset($data['class_category_fee_id']) &&
                (int) $data['class_category_fee_id'] !==
                (int) $option->class_category_fee_id
            ) {
                throw ValidationException::withMessages([
                    'class_category_fee_id' => [
                        'A fee option cannot be moved to another class category fee.'
                    ],
                ]);
            }

            $categoryFee = $this->getValidCategoryFee(
                $option->class_category_fee_id
            );

            $isActive = array_key_exists('is_active', $data)
                ? (bool) $data['is_active']
                : (bool) $option->is_active;

            $isDefault = array_key_exists('is_default', $data)
                ? (bool) $data['is_default']
                : (bool) $option->is_default;


            /*
             * Inactive option cannot be default.
             */
            if (!$isActive) {
                $isDefault = false;
            }


            /*
             * If this option is currently default and is being
             * changed to inactive/non-default, another active
             * default must be assigned.
             */
            if (
                $option->is_default &&
                !$isDefault
            ) {
                $this->removeDefaultFromOthers(
                    $categoryFee->id,
                    $option->id
                );

                $newDefault = $this->getFirstActiveOption(
                    $categoryFee->id,
                    $option->id
                );

                if (!$newDefault) {
                    throw ValidationException::withMessages([
                        'is_default' => [
                            'At least one active default fee option is required.'
                        ],
                    ]);
                }

                $newDefault->update([
                    'is_default' => true,
                ]);
            }


            /*
             * If this option is selected as default,
             * remove default from all other options.
             */
            if ($isDefault) {
                $this->removeDefaultFromOthers(
                    $categoryFee->id,
                    $option->id
                );
            }


            $updateData = [
                'label' => $data['label'],
                'fee' => $data['fee'],
                'is_default' => $isDefault,
                'is_active' => $isActive,
                'note' => isset($data['note'])
                    ? $data['note']
                    : null,
            ];

            $option->update($updateData);

            return $option->fresh();
        });
    }


    /**
     * Delete a fee option.
     *
     * Used fee options cannot be deleted.
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        return DB::transaction(function () use ($id) {

            $option = ClassCategoryFeeOption::findOrFail($id);

            /*
             * Existing enrollments depend on this option.
             */
            if ($option->enrollments()->exists()) {
                throw ValidationException::withMessages([
                    'message' => [
                        'This fee option cannot be deleted because it is already used by student enrollments.'
                    ],
                ]);
            }

            $wasDefault = (bool) $option->is_default;

            $categoryFeeId = $option->class_category_fee_id;

            $option->delete();


            /*
             * If the deleted option was default,
             * assign another active option as default.
             */
            if ($wasDefault) {

                $newDefault = $this->getFirstActiveOption(
                    $categoryFeeId
                );

                if ($newDefault) {
                    $newDefault->update([
                        'is_default' => true,
                    ]);
                }
            }

            return true;
        });
    }


    /**
     * Restore a soft-deleted fee option.
     *
     * @param int $id
     * @return ClassCategoryFeeOption
     */
    public function restore($id)
    {
        return DB::transaction(function () use ($id) {

            $option = ClassCategoryFeeOption::withTrashed()
                ->findOrFail($id);

            if (!$option->trashed()) {
                return $option;
            }

            $this->getValidCategoryFee(
                $option->class_category_fee_id
            );

            $option->restore();

            /*
             * Restored option should not automatically
             * become default if another default already exists.
             */
            $hasDefault = ClassCategoryFeeOption::query()
                ->where(
                    'class_category_fee_id',
                    $option->class_category_fee_id
                )
                ->where('id', '!=', $option->id)
                ->where('is_active', true)
                ->where('is_default', true)
                ->exists();

            if (!$hasDefault && $option->is_active) {
                $option->update([
                    'is_default' => true,
                ]);
            }

            return $option->fresh();
        });
    }


    /**
     * Set a fee option as default.
     *
     * @param int $id
     * @return ClassCategoryFeeOption
     */
    public function setDefault($id)
    {
        return DB::transaction(function () use ($id) {

            $option = ClassCategoryFeeOption::findOrFail($id);

            /*
             * Validate parent class category fee.
             */
            $this->getValidCategoryFee(
                $option->class_category_fee_id
            );

            /*
             * Inactive option cannot be default.
             */
            if (!$option->is_active) {
                throw ValidationException::withMessages([
                    'message' => [
                        'Inactive fee option cannot be set as default.'
                    ],
                ]);
            }

            $this->removeDefaultFromOthers(
                $option->class_category_fee_id,
                $option->id
            );

            $option->update([
                'is_default' => true,
            ]);

            return $option->fresh();
        });
    }


    /**
     * Activate a fee option.
     *
     * @param int $id
     * @return ClassCategoryFeeOption
     */
    public function activate($id)
    {
        return DB::transaction(function () use ($id) {

            $option = ClassCategoryFeeOption::findOrFail($id);

            $this->getValidCategoryFee(
                $option->class_category_fee_id
            );

            $option->update([
                'is_active' => true,
            ]);

            /*
             * If there is no active default,
             * make this option the default.
             */
            $hasDefault = ClassCategoryFeeOption::query()
                ->where(
                    'class_category_fee_id',
                    $option->class_category_fee_id
                )
                ->where('id', '!=', $option->id)
                ->where('is_active', true)
                ->where('is_default', true)
                ->exists();

            if (!$hasDefault) {

                $this->removeDefaultFromOthers(
                    $option->class_category_fee_id,
                    $option->id
                );

                $option->update([
                    'is_default' => true,
                ]);
            }

            return $option->fresh();
        });
    }


    /**
     * Deactivate a fee option.
     *
     * Existing enrollments are not affected.
     *
     * @param int $id
     * @return ClassCategoryFeeOption
     */
    public function deactivate($id)
    {
        return DB::transaction(function () use ($id) {

            $option = ClassCategoryFeeOption::findOrFail($id);

            $wasDefault = (bool) $option->is_default;

            $categoryFeeId = $option->class_category_fee_id;

            /*
             * If this is the only active option,
             * do not allow deactivation.
             */
            $anotherActiveOptionExists = ClassCategoryFeeOption::query()
                ->where('class_category_fee_id', $categoryFeeId)
                ->where('id', '!=', $option->id)
                ->where('is_active', true)
                ->exists();

            if (!$anotherActiveOptionExists) {
                throw ValidationException::withMessages([
                    'message' => [
                        'At least one active fee option is required.'
                    ],
                ]);
            }

            $option->update([
                'is_active' => false,
                'is_default' => false,
            ]);


            /*
             * If this was the default option,
             * assign another active option as default.
             */
            if ($wasDefault) {

                $newDefault = $this->getFirstActiveOption(
                    $categoryFeeId
                );

                if ($newDefault) {
                    $newDefault->update([
                        'is_default' => true,
                    ]);
                }
            }

            return $option->fresh();
        });
    }


    /**
     * Get active default option.
     *
     * @param int $classCategoryFeeId
     * @return ClassCategoryFeeOption|null
     */
    public function getDefaultOption($classCategoryFeeId)
    {
        return ClassCategoryFeeOption::query()
            ->where(
                'class_category_fee_id',
                $classCategoryFeeId
            )
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();
    }


    /**
     * Get default option or first active option.
     *
     * @param int $classCategoryFeeId
     * @return ClassCategoryFeeOption|null
     */
    public function getDefaultOrFirstActiveOption(
        $classCategoryFeeId
    ) {
        return ClassCategoryFeeOption::query()
            ->where(
                'class_category_fee_id',
                $classCategoryFeeId
            )
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }


    /**
     * Validate parent ClassCategoryFee.
     *
     * @param int $id
     * @return ClassCategoryFee
     */
    protected function getValidCategoryFee($id)
    {
        $categoryFee = ClassCategoryFee::query()
            ->with([
                'category',
                'studentClass',
            ])
            ->where('id', $id)
            ->where('is_active', true)
            ->first();

        if (!$categoryFee) {
            throw ValidationException::withMessages([
                'class_category_fee_id' => [
                    'Selected class category fee is invalid or inactive.'
                ],
            ]);
        }

        /*
         * Parent class must exist.
         */
        if (!$categoryFee->studentClass) {
            throw ValidationException::withMessages([
                'class_category_fee_id' => [
                    'Student class not found.'
                ],
            ]);
        }

        /*
         * Parent class must be active.
         */
        if (!$categoryFee->studentClass->is_active) {
            throw ValidationException::withMessages([
                'class_category_fee_id' => [
                    'Cannot use a fee option from an inactive class.'
                ],
            ]);
        }

        /*
         * Category must exist and be active.
         */
        if (!$categoryFee->category) {
            throw ValidationException::withMessages([
                'class_category_fee_id' => [
                    'Class category not found.'
                ],
            ]);
        }

        if (!$categoryFee->category->is_active) {
            throw ValidationException::withMessages([
                'class_category_fee_id' => [
                    'Cannot use a fee option from an inactive category.'
                ],
            ]);
        }

        return $categoryFee;
    }


    /**
     * Get first active option.
     *
     * @param int $classCategoryFeeId
     * @param int|null $exceptId
     * @return ClassCategoryFeeOption|null
     */
    protected function getFirstActiveOption(
        $classCategoryFeeId,
        $exceptId = null
    ) {
        $query = ClassCategoryFeeOption::query()
            ->where(
                'class_category_fee_id',
                $classCategoryFeeId
            )
            ->where('is_active', true);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        return $query
            ->orderBy('id')
            ->first();
    }


    /**
     * Remove default status from other options.
     *
     * @param int $classCategoryFeeId
     * @param int|null $exceptId
     * @return void
     */
    protected function removeDefaultFromOthers(
        $classCategoryFeeId,
        $exceptId = null
    ) {
        $query = ClassCategoryFeeOption::query()
            ->where(
                'class_category_fee_id',
                $classCategoryFeeId
            );

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        $query->update([
            'is_default' => false,
        ]);
    }
}
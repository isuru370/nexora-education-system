<?php

namespace App\Http\Controllers\Web\ClassCategoryFee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ClassCategoryFee\StoreClassCategoryFeeRequest;
use App\Http\Requests\Web\ClassCategoryFee\UpdateClassCategoryFeeRequest;
use App\Models\StudentClass;
use App\Services\Web\ClassCategoryFee\ClassCategoryFeeService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Throwable;

class ClassCategoryFeeController extends Controller
{
   

    public function __construct(
        private ClassCategoryFeeService $classCategoryFeeService
    ) {
        
    }

    /**
     * Display category fees.
     */
    public function index(Request $request)
    {
        $filters = [
            'student_class_id' => $request->input('student_class_id'),
            'class_category_id' => $request->input('class_category_id'),
            'per_page' => $request->input('per_page', 10),
        ];

        if ($request->filled('is_active')) {
            $filters['is_active'] = $request->boolean('is_active');
        }

        $fees = $this->classCategoryFeeService->getAll($filters);

        $formData = $this->classCategoryFeeService->getIndexFormData();

        return view(
            'class_category_fees.index',
            array_merge(['fees' => $fees], $formData)
        );
    }

    /**
     * Show create form.
     */
    public function create(Request $request)
    {
        try {
            $data = $this->classCategoryFeeService->getCreateFormData(
                $request->input('student_class_id')
            );

            return view('class_category_fees.create', $data);
        } catch (ValidationException $e) {
            return redirect()
                ->route('student-classes.index')
                ->with('error', collect($e->errors())->flatten()->first());
        }
    }

    /**
     * Store category fee.
     */
    public function store(StoreClassCategoryFeeRequest $request)
    {
        try {
            $this->classCategoryFeeService->create(
                $request->validated()
            );

            return redirect()
                ->route('class-category-fees.index')
                ->with(
                    'success',
                    'Class category fee created successfully.'
                );
        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput();
        }
    }

    /**
     * Display category fee details.
     */
    public function show($id)
    {
        $classCategoryFee = $this->classCategoryFeeService->find($id);

        return view(
            'class_category_fees.show',
            compact('classCategoryFee')
        );
    }

    /**
     * Show edit form.
     */
    public function edit($id)
    {
        try {
            $data = $this->classCategoryFeeService->getEditFormData($id);

            return view('class_category_fees.edit', $data);
        } catch (ValidationException $e) {
            return redirect()
                ->route('class-category-fees.index')
                ->with('error', collect($e->errors())->flatten()->first());
        }
    }

    /**
     * Update category fee.
     */
    public function update(
        UpdateClassCategoryFeeRequest $request,
        $id
    ) {
        try {
            $this->classCategoryFeeService->update(
                $id,
                $request->validated()
            );

            return redirect()
                ->route('class-category-fees.index')
                ->with(
                    'success',
                    'Class category fee updated successfully.'
                );
        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput();
        }
    }

    /**
     * Delete category fee.
     */
    public function destroy($id)
    {
        try {
            $this->classCategoryFeeService->delete($id);

            return redirect()
                ->route('class-category-fees.index')
                ->with(
                    'success',
                    'Class category fee deleted successfully.'
                );
        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (Throwable $e) {
            Log::error('Class category fee delete failed', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return back()->with(
                'error',
                'Class category fee delete failed.'
            );
        }
    }

    /**
     * Toggle active status.
     */
    public function toggleActive($id)
    {
        try {
            $this->classCategoryFeeService->toggleActive($id);

            return back()->with(
                'success',
                'Class category fee status updated.'
            );
        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (Throwable $e) {
            Log::error('Class category fee status update failed', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return back()->with(
                'error',
                'Unable to update class category fee status.'
            );
        }
    }

    /**
     * Get active category fees for a class (AJAX).
     */
    public function byClass(StudentClass $studentClass)
    {
        $fees = $this->classCategoryFeeService->getByClass(
            $studentClass->id
        );

        return response()->json(
            $fees->map(function ($fee) {
                return [
                    'id' => $fee->id,
                    'category_id' => $fee->class_category_id,
                    'category_name' => $fee->category
                        ? $fee->category->category_name
                        : '-',
                    'fee_options' => $fee->activeFeeOptions
                        ->map(function ($option) {
                            return [
                                'id' => $option->id,
                                'label' => $option->label,
                                'fee' => (float) $option->fee,
                                'is_default' => (bool) $option->is_default,
                                'note' => $option->note,
                            ];
                        })
                        ->values(),
                ];
            })->values()
        );
    }
}
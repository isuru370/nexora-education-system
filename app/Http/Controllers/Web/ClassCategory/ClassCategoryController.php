<?php

namespace App\Http\Controllers\Web\ClassCategory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ClassCategory\StoreClassCategoryRequest;
use App\Http\Requests\Web\ClassCategory\UpdateClassCategoryRequest;
use App\Models\ClassCategory;
use App\Services\Web\ClassCategory\ClassCategoryService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ClassCategoryController extends Controller
{
    protected ClassCategoryService $classCategoryService;

    public function __construct(
        ClassCategoryService $classCategoryService
    ) {
        $this->classCategoryService = $classCategoryService;
    }

    public function index(Request $request)
    {
        $data = $this->classCategoryService
            ->getIndexData($request);

        return view('class_categories.index', $data);
    }

    public function create()
    {
        return view('class_categories.create');
    }

    public function store(StoreClassCategoryRequest $request)
    {
        $this->classCategoryService->store(
            $request->validated()
        );

        return redirect()
            ->route('class-categories.index')
            ->with('success', 'Class category created successfully.');
    }

    public function show(ClassCategory $classCategory)
    {
        return view(
            'class_categories.show',
            compact('classCategory')
        );
    }

    public function edit(ClassCategory $classCategory)
    {
        return view(
            'class_categories.edit',
            compact('classCategory')
        );
    }

    public function update(
        UpdateClassCategoryRequest $request,
        ClassCategory $classCategory
    ) {
        $this->classCategoryService->update(
            $classCategory,
            $request->validated()
        );

        return redirect()
            ->route('class-categories.index')
            ->with('success', 'Class category updated successfully.');
    }

    public function destroy(ClassCategory $classCategory)
    {
        try {
            $this->classCategoryService->delete($classCategory);

            return redirect()
                ->route('class-categories.index')
                ->with('success', 'Class category deleted successfully.');
        } catch (ValidationException $e) {
            return back()->with(
                'error',
                collect($e->errors())->flatten()->first()
            );
        }
    }

    public function toggleActive(ClassCategory $classCategory)
    {
        try {
            $this->classCategoryService->toggleActive($classCategory);

            return back()->with(
                'success',
                'Category status updated successfully.'
            );
        } catch (ValidationException $e) {
            return back()->with(
                'error',
                collect($e->errors())->flatten()->first()
            );
        }
    }
}

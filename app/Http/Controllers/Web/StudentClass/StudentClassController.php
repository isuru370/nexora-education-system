<?php

namespace App\Http\Controllers\Web\StudentClass;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StudentClass\StoreStudentClassRequest;
use App\Http\Requests\Web\StudentClass\UpdateStudentClassRequest;
use App\Models\StudentClass;
use App\Services\Web\StudentClass\StudentClassService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentClassController extends Controller
{
    protected StudentClassService $studentClassService;

    public function __construct(StudentClassService $studentClassService)
    {
        $this->studentClassService = $studentClassService;
    }

    /**
     * Display classes with filters and pagination.
     */
    public function index(Request $request): View
    {
        $data = $this->studentClassService->getIndexData($request);

        return view('student_classes.index', $data);
    }

    /**
     * Show the create form.
     */
    public function create(): View
    {
        return view(
            'student_classes.create',
            $this->studentClassService->getFormData()
        );
    }

    /**
     * Store a new class.
     */
    public function store(
        StoreStudentClassRequest $request
    ): RedirectResponse {
        $this->studentClassService->store($request->validated());

        return redirect()
            ->route('student-classes.index')
            ->with('success', 'Class created successfully.');
    }

    /**
     * Display a class and its details.
     */
    public function show(StudentClass $studentClass): View
    {
        return view(
            'student_classes.show',
            $this->studentClassService->getDetails($studentClass)
        );
    }

    /**
     * Show the edit form.
     */
    public function edit(StudentClass $studentClass): View
    {
        return view(
            'student_classes.edit',
            $this->studentClassService->getFormData($studentClass)
        );
    }

    /**
     * Update an existing class.
     */
    public function update(
        UpdateStudentClassRequest $request,
        StudentClass $studentClass
    ): RedirectResponse {
        $this->studentClassService->update(
            $studentClass,
            $request->validated()
        );

        return redirect()
            ->route('student-classes.index')
            ->with('success', 'Class updated successfully.');
    }

    /**
     * Delete an inactive, non-ongoing class.
     */
    public function destroy(StudentClass $studentClass): RedirectResponse
    {
        try {
            $this->studentClassService->delete($studentClass);

            return redirect()
                ->route('student-classes.index')
                ->with('success', 'Class deleted successfully.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return back()->with(
                'error',
                collect($exception->errors())->flatten()->first()
                    ?? 'Unable to delete this class.'
            );
        }
    }

    /**
     * Toggle class active status.
     */
    public function toggleActive(
        StudentClass $studentClass
    ): RedirectResponse {
        try {
            $this->studentClassService->toggleActive($studentClass);

            return back()->with(
                'success',
                'Class status updated successfully.'
            );
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return back()->with(
                'error',
                collect($exception->errors())->flatten()->first()
                    ?? 'Unable to update class status.'
            );
        }
    }

    /**
     * Toggle class ongoing status.
     */
    public function toggleOngoing(
        StudentClass $studentClass
    ): RedirectResponse {
        $this->studentClassService->toggleOngoing($studentClass);

        return back()->with(
            'success',
            'Ongoing status updated successfully.'
        );
    }

    /**
     * Export accessible classes to Excel.
     */
    public function exportExcel()
    {
        return $this->studentClassService->exportExcel();
    }

    /**
     * Export accessible classes to PDF.
     */
    public function exportPdf()
    {
        return $this->studentClassService->exportPdf();
    }

    /**
     * Search active and ongoing classes.
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
        ]);

        $classes = $this->studentClassService->search(
            trim($validated['q'] ?? '')
        );

        return response()->json($classes);
    }
}

<?php

namespace App\Http\Controllers\Web\StudentClassEnrollment;

use App\Exports\Web\StudentClassEnrollment\ClassEnrollmentStudent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StudentClassEnrollment\StoreStudentClassEnrollmentRequest;
use App\Http\Requests\Web\StudentClassEnrollment\UpdateStudentClassEnrollmentRequest;
use App\Models\ClassCategory;
use App\Models\ClassCategoryFee;
use App\Models\StudentClass;
use App\Models\StudentClassEnrollment;
use App\Services\Branch\BranchAccessService;
use App\Services\Web\StudentClassEnrollment\StudentClassEnrollmentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class StudentClassEnrollmentController extends Controller
{
    public function __construct(
        protected StudentClassEnrollmentService $studentClassEnrollmentService,
        protected BranchAccessService $branchAccessService,
    ) {}

    /**
     * Enrollment index.
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);

        if (!in_array($perPage, [10, 20, 50, 100])) {
            $perPage = 10;
        }

        $search = trim($request->input('search', ''));

        /** @var Collection<int, int> $accessibleBranchIds */
        $accessibleBranchIds = $this->branchAccessService->getBranchIds();

        $classesQuery = StudentClass::query()
            ->with([
                'grade',
                'subject',
                'teacher',
                'categoryFees.category',
            ])
            ->whereIn('branch_id', $accessibleBranchIds)
            ->whereHas('enrollments', function ($q) use ($accessibleBranchIds) {
                $q->whereIn('branch_id', $accessibleBranchIds);
            })
            ->withCount([
                'enrollments as total_students_count' => function ($q) use ($accessibleBranchIds) {
                    $q->whereIn('branch_id', $accessibleBranchIds);
                },
                'enrollments as active_students_count' => function ($q) use ($accessibleBranchIds) {
                    $q->whereIn('branch_id', $accessibleBranchIds)
                        ->where('is_active', true);
                },
                'enrollments as inactive_students_count' => function ($q) use ($accessibleBranchIds) {
                    $q->whereIn('branch_id', $accessibleBranchIds)
                        ->where('is_active', false);
                },
            ]);

        if ($search !== '') {
            $classesQuery->where(function ($query) use ($search) {
                $query->where('class_name', 'like', "%{$search}%")
                    ->orWhere('class_type', 'like', "%{$search}%")
                    ->orWhere('medium', 'like', "%{$search}%")
                    ->orWhereHas('grade', function ($q) use ($search) {
                        $q->where('grade_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('subject', function ($q) use ($search) {
                        $q->where('subject_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('teacher', function ($q) use ($search) {
                        $q->where('initials', 'like', "%{$search}%");
                    });
            });
        }

        $classes = $classesQuery
            ->orderBy('class_name')
            ->paginate($perPage);

        $classes->appends($request->all());

        $classIds = collect($classes->items())->pluck('id');

        /*
         * Category statistics — branch-scoped.
         */
        $categoryStats = StudentClassEnrollment::query()
            ->join(
                'class_category_fees',
                'student_class_enrollments.class_category_fee_id',
                '=',
                'class_category_fees.id'
            )
            ->selectRaw('
                student_class_enrollments.student_class_id,
                student_class_enrollments.class_category_fee_id,

                COUNT(*) as total_count,

                SUM(CASE WHEN student_class_enrollments.is_active = 1 THEN 1 ELSE 0 END) as active_count,

                SUM(CASE WHEN student_class_enrollments.is_active = 0 THEN 1 ELSE 0 END) as inactive_count,

                SUM(CASE WHEN student_class_enrollments.is_free_card = 1 THEN 1 ELSE 0 END) as free_card_count
            ')
            ->whereIn('student_class_enrollments.student_class_id', $classIds)
            ->whereIn('student_class_enrollments.branch_id', $accessibleBranchIds)
            ->groupBy(
                'student_class_enrollments.student_class_id',
                'student_class_enrollments.class_category_fee_id'
            )
            ->get()
            ->groupBy('student_class_id');

        return view(
            'student-class-enrollments.index',
            compact('classes', 'categoryStats')
        );
    }

    /**
     * Show students in a specific class category.
     */
    public function categoryStudents(
        Request $request,
        StudentClass $studentClass,
        ClassCategory $classCategory
    ) {
        $perPage = (int) $request->input('per_page', 20);

        if (!in_array($perPage, [10, 20, 50, 100])) {
            $perPage = 20;
        }

        $search = trim($request->input('search', ''));

        /** @var Collection<int, int> $accessibleBranchIds */
        $accessibleBranchIds = $this->branchAccessService->getBranchIds();

        $feeIds = ClassCategoryFee::query()
            ->where('student_class_id', $studentClass->id)
            ->where('class_category_id', $classCategory->id)
            ->pluck('id');

        $query = StudentClassEnrollment::query()
            ->with([
                'branch',
                'student',
                'studentClass.grade',
                'studentClass.subject',
                'studentClass.teacher',
                'classCategoryFee.category',
                'classCategoryFeeOption',
            ])
            ->where('student_class_id', $studentClass->id)
            ->whereIn('class_category_fee_id', $feeIds)
            ->whereIn('branch_id', $accessibleBranchIds);

        if ($search !== '') {
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('custom_id', 'like', "%{$search}%")
                    ->orWhere('temporary_qr_code', 'like', "%{$search}%")
                    ->orWhere('initial_name', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        $enrollments = $query
            ->orderByDesc('is_active')
            ->latest()
            ->paginate($perPage);

        $enrollments->appends($request->all());

        return view(
            'student-class-enrollments.category-students',
            compact('studentClass', 'classCategory', 'enrollments')
        );
    }

    /**
     * Toggle enrollment active status.
     */
    public function toggleActive(
        StudentClassEnrollment $studentClassEnrollment
    ) {
        try {
            $this->studentClassEnrollmentService
                ->toggleStatus($studentClassEnrollment);

            return back()->with(
                'success',
                'Student enrollment status updated successfully.'
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return back()->with('error', \App\Support\ErrorDetail::safe($e));
        }
    }

    /**
     * Create enrollment page.
     */
    public function create()
    {
        return view(
            'student-class-enrollments.create',
            [
                'enrollment' => new StudentClassEnrollment(),
            ]
        );
    }

    /**
     * Store enrollment.
     */
    public function store(
        StoreStudentClassEnrollmentRequest $request
    ) {
        try {
            $this->studentClassEnrollmentService->create(
                $request->validated()
            );

            return redirect()
                ->route('student-class-enrollments.index')
                ->with('success', 'Student enrolled successfully.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return back()
                ->withInput()
                ->with('error', \App\Support\ErrorDetail::safe($e));
        }
    }

    /**
     * Show enrollment.
     */
    public function show(
        StudentClassEnrollment $studentClassEnrollment
    ) {
        $studentClassEnrollment->load([
            'branch',
            'student',
            'studentClass',
            'classCategoryFee.category',
            'classCategoryFeeOption',
            'payments',
        ]);

        return view(
            'student-class-enrollments.show',
            compact('studentClassEnrollment')
        );
    }

    /**
     * Edit enrollment.
     */
    public function edit(
        StudentClassEnrollment $studentClassEnrollment
    ) {
        $studentClassEnrollment->load([
            'branch',
            'student',
            'studentClass.grade',
            'studentClass.subject',
            'studentClass.teacher',
            'classCategoryFee.category',
            'classCategoryFeeOption',
        ]);

        $feeOptions = $this->studentClassEnrollmentService
            ->getFeeOptions(
                (int) $studentClassEnrollment->student_class_id,
                (int) $studentClassEnrollment->class_category_fee_id
            );

        return view(
            'student-class-enrollments.edit',
            [
                'enrollment' => $studentClassEnrollment,
                'feeOptions' => $feeOptions,
            ]
        );
    }

    /**
     * Update enrollment.
     */
    public function update(
        UpdateStudentClassEnrollmentRequest $request,
        StudentClassEnrollment $studentClassEnrollment
    ) {
        try {
            $this->studentClassEnrollmentService->update(
                $studentClassEnrollment,
                $request->validated()
            );

            return redirect()
                ->route(
                    'student-class-enrollments.show',
                    $studentClassEnrollment
                )
                ->with('success', 'Enrollment updated successfully.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return back()
                ->withInput()
                ->with('error', \App\Support\ErrorDetail::safe($e));
        }
    }

    /**
     * Delete enrollment.
     */
    public function destroy(
        StudentClassEnrollment $studentClassEnrollment
    ) {
        try {
            $this->studentClassEnrollmentService
                ->delete($studentClassEnrollment);

            return redirect()
                ->route('student-class-enrollments.index')
                ->with('success', 'Enrollment deleted successfully.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return back()->with('error', \App\Support\ErrorDetail::safe($e));
        }
    }

    /**
     * Student leaves the class.
     */
    public function leave(
        Request $request,
        StudentClassEnrollment $studentClassEnrollment
    ) {
        $request->validate([
            'left_at' => ['nullable', 'date'],
        ]);

        try {
            $this->studentClassEnrollmentService->update(
                $studentClassEnrollment,
                [
                    'is_active' => false,
                    'left_at' => $request->input(
                        'left_at',
                        now()->toDateString()
                    ),
                ]
            );

            return back()->with(
                'success',
                'Student left from class successfully.'
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return back()->with('error', \App\Support\ErrorDetail::safe($e));
        }
    }

    /**
     * Restore enrollment.
     */
    public function restore(int $id)
    {
        try {
            $this->studentClassEnrollmentService->restore($id);

            $enrollment = StudentClassEnrollment::findOrFail($id);

            return redirect()
                ->route(
                    'student-class-enrollments.show',
                    $enrollment
                )
                ->with('success', 'Enrollment restored successfully.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return back()->with('error', \App\Support\ErrorDetail::safe($e));
        }
    }

    /**
     * PDF export.
     */
    public function categoryStudentsPdf(
        Request $request,
        StudentClass $studentClass,
        ClassCategory $classCategory
    ) {
        $studentClass->load([
            'grade',
            'subject',
            'teacher',
        ]);

        /** @var Collection<int, int> $accessibleBranchIds */
        $accessibleBranchIds = $this->branchAccessService->getBranchIds();

        $feeIds = ClassCategoryFee::query()
            ->where('student_class_id', $studentClass->id)
            ->where('class_category_id', $classCategory->id)
            ->pluck('id');

        $query = StudentClassEnrollment::query()
            ->with([
                'branch',
                'student',
                'classCategoryFee.category',
                'classCategoryFeeOption',
            ])
            ->where('student_class_id', $studentClass->id)
            ->whereIn('class_category_fee_id', $feeIds)
            ->whereIn('branch_id', $accessibleBranchIds);

        if ($request->filled('search')) {
            $search = trim($request->input('search'));

            $query->whereHas('student', function ($q) use ($search) {
                $q->where('custom_id', 'like', "%{$search}%")
                    ->orWhere('temporary_qr_code', 'like', "%{$search}%")
                    ->orWhere('initial_name', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        $enrollments = $query
            ->orderByDesc('is_active')
            ->latest()
            ->get();

        $pdf = Pdf::loadView(
            'pdf.student-class-enrollments.index',
            [
                'studentClass' => $studentClass,
                'classCategory' => $classCategory,
                'enrollments' => $enrollments,
            ]
        )->setPaper('a4', 'landscape');

        $fileName =
            str_replace(' ', '-', strtolower($studentClass->class_name))
            . '-'
            . str_replace(' ', '-', strtolower($classCategory->category_name))
            . '-students.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Excel export.
     */
    public function categoryStudentsExcel(
        Request $request,
        StudentClass $studentClass,
        ClassCategory $classCategory
    ) {
        $studentClass->load([
            'grade',
            'subject',
            'teacher',
        ]);

        /** @var Collection<int, int> $accessibleBranchIds */
        $accessibleBranchIds = $this->branchAccessService->getBranchIds();

        $feeIds = ClassCategoryFee::query()
            ->where('student_class_id', $studentClass->id)
            ->where('class_category_id', $classCategory->id)
            ->pluck('id');

        $query = StudentClassEnrollment::query()
            ->with([
                'branch',
                'student',
                'classCategoryFee.category',
                'classCategoryFeeOption',
            ])
            ->where('student_class_id', $studentClass->id)
            ->whereIn('class_category_fee_id', $feeIds)
            ->whereIn('branch_id', $accessibleBranchIds);

        if ($request->filled('search')) {
            $search = trim($request->input('search'));

            $query->whereHas('student', function ($q) use ($search) {
                $q->where('custom_id', 'like', "%{$search}%")
                    ->orWhere('temporary_qr_code', 'like', "%{$search}%")
                    ->orWhere('initial_name', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        $enrollments = $query
            ->orderByDesc('is_active')
            ->latest()
            ->get();

        $fileName =
            str_replace(' ', '-', strtolower($studentClass->class_name))
            . '-'
            . str_replace(' ', '-', strtolower($classCategory->category_name))
            . '-students.xlsx';

        return Excel::download(
            new ClassEnrollmentStudent(
                $enrollments,
                $studentClass,
                $classCategory
            ),
            $fileName
        );
    }

    /**
     * Category-wise payment students.
     */
    public function classCategoryWisePaymentStudent(
        int $class,
        int $classCategoryFee,
        int $year,
        int $month
    ) {
        try {
            $perPage = (int) request()->get('per_page', 50);

            $data = $this->studentClassEnrollmentService
                ->classCategoryWisePaymentStudent(
                    $class,
                    $classCategoryFee,
                    $year,
                    $month,
                    $perPage
                );

            return view(
                'student-class-enrollments.category-wise-payment',
                [
                    'students' => $data['students'],
                    'pagination' => $data['pagination'],
                    'class' => $class,
                    'classCategoryFee' => $classCategoryFee,
                    'year' => $year,
                    'month' => $month,
                    'perPage' => $perPage,
                ]
            );
        } catch (Throwable $e) {
            logger()->error(
                'Class Category Wise Payment Student Error',
                [
                    'message' => $e->getMessage(),
                    'class' => $class,
                    'class_category_fee' => $classCategoryFee,
                    'year' => $year,
                    'month' => $month,
                ]
            );

            return back()->with(
                'error',
                'Something went wrong while fetching data.'
            );
        }
    }
}
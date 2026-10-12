<?php

namespace App\Services\Web\StudentClass;

use App\Exports\Web\StudentClass\StudentClassesExport;
use App\Models\ClassCategoryFee;
use App\Models\Grade;
use App\Models\Organizer;
use App\Models\StudentClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Services\Branch\BranchAccessService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class StudentClassService
{


    public function __construct(
        private BranchAccessService $branchAccessService,
        private ClassPaymentConfigService $classPaymentConfigService,
    ) {}

    /**
     * Get the base query restricted to accessible branches.
     */
    protected function baseQuery(): Builder
    {
        $query = StudentClass::query();

        $this->branchAccessService->applyBranchAccess(
            $query,
            'student_classes.branch_id'
        );

        return $query;
    }

    /**
     * Ensure the user can access the requested branch.
     */
    protected function ensureBranchAccess(int $branchId): void
    {
        if (!$this->branchAccessService->canAccessBranch($branchId)) {
            abort(403, 'You do not have access to this branch.');
        }
    }

    /**
     * Ensure the class belongs to an accessible branch.
     */
    protected function ensureClassAccess(StudentClass $studentClass): void
    {
        $this->ensureBranchAccess((int) $studentClass->branch_id);
    }

    /**
     * Get classes with search and filters.
     */
    public function getIndexData(Request $request): array
    {
        $query = $this->baseQuery()->with([
            'branch',
            'teacher',
            'subject',
            'grade',
            'paymentConfig.organizer',
        ]);

        $search = trim((string) $request->input('search', ''));

        if ($search !== '') {
            $query->where(function (Builder $q) use ($search) {
                $q->where('student_classes.class_name', 'like', "%{$search}%")
                    ->orWhere('student_classes.medium', 'like', "%{$search}%")
                    ->orWhereHas('teacher', function (Builder $teacher) use ($search) {
                        $teacher->where('full_name', 'like', "%{$search}%")
                            ->orWhere('custom_id', 'like', "%{$search}%");
                    })
                    ->orWhereHas('subject', function (Builder $subject) use ($search) {
                        $subject->where('subject_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('grade', function (Builder $grade) use ($search) {
                        $grade->where('grade_name', 'like', "%{$search}%");
                    });
            });
        }

        foreach (['branch_id', 'teacher_id', 'grade_id', 'subject_id', 'class_type'] as $filter) {
            if ($request->filled($filter)) {
                $query->where('student_classes.' . $filter, $request->input($filter));
            }
        }

        foreach (['is_active', 'is_ongoing'] as $filter) {
            if ($request->filled($filter)) {
                $query->where(
                    'student_classes.' . $filter,
                    $request->input($filter) === 'true'
                );
            }
        }

        return [
            'classes' => $query->latest('student_classes.id')
                ->paginate(10)
                ->appends($request->query()),

            'branches' => $this->branchAccessService->getAccessibleBranches(),

            'search' => $search,
        ];
    }

    /**
     * Data needed by create/edit forms.
     */
    public function getFormData(?StudentClass $studentClass = null): array
    {
        if ($studentClass) {
            $this->ensureClassAccess($studentClass);
            $studentClass->load('paymentConfig');
        }

        return [
            'studentClass' => $studentClass,
            'paymentConfig' => $studentClass ? $studentClass->paymentConfig : null,

            'branches' => $this->branchAccessService->getAccessibleBranches(),

            'teachers' => Teacher::where('is_active', true)
                ->orderBy('full_name')
                ->get(),

            'grades' => Grade::where('is_active', true)
                ->orderBy('grade_name')
                ->get(),

            'subjects' => Subject::where('is_active', true)
                ->orderBy('subject_name')
                ->get(),

            'organizers' => Organizer::where('is_active', true)
                ->orderBy('name')
                ->get(),
        ];
    }

    /**
     * Create a class and its payment configuration.
     *
     * $data must contain validated form data, including branch_id.
     */
    public function store(array $data): StudentClass
    {
        $branchId = (int) $data['branch_id'];

        $this->ensureBranchAccess($branchId);

        return DB::transaction(function () use ($data, $branchId) {

            $gradeId = $this->resolveGradeId($data);
            $subjectId = $this->resolveSubjectId($data);

            $studentClass = StudentClass::create([
                'branch_id' => $branchId,
                'class_name' => $data['class_name'],
                'class_type' => $data['class_type'],
                'medium' => $data['medium'],
                'teacher_id' => $data['teacher_id'],
                'subject_id' => $subjectId,
                'grade_id' => $gradeId,
                'is_active' => $data['is_active'] ?? false,
                'is_ongoing' => $data['is_ongoing'] ?? false,
            ]);

            $this->classPaymentConfigService->createForClass(
                $studentClass,
                $data
            );

            return $studentClass;
        });
    }

    /**
     * Get one class and its details.
     */
    public function getDetails(StudentClass $studentClass): array
{
    $this->ensureClassAccess($studentClass);

    $studentClass->load([
        'branch',
        'teacher',
        'subject',
        'grade',
        'paymentConfig.organizer',
        'paymentConfig.createdBy',
        'paymentConfig.updatedBy',
    ]);

    $categoryFees = ClassCategoryFee::with([
        'category',
        'feeOptions',          
        'activeFeeOptions',    
    ])
        ->where('student_class_id', $studentClass->id)
        ->whereNull('deleted_at')
        ->orderByDesc('is_active')
        ->latest()
        ->get();

    return compact('studentClass', 'categoryFees');
}

    /**
     * Update a class and its payment configuration.
     */
    public function update(
    StudentClass $studentClass,
    array $data
): StudentClass {
    $this->ensureClassAccess($studentClass);

    $branchId = (int) ($data['branch_id'] ?? $studentClass->branch_id);

    $this->ensureBranchAccess($branchId);

    // Prevent moving a class to another branch.
    if ($branchId !== (int) $studentClass->branch_id) {
        throw ValidationException::withMessages([
            'branch_id' => 'Changing the branch of an existing class is not allowed.',
        ]);
    }

    return DB::transaction(function () use ($studentClass, $data) {
        $gradeId = $this->resolveGradeId($data);
        $subjectId = $this->resolveSubjectId($data);

        // Update student class.
        $studentClass->update([
            'class_name' => $data['class_name'],
            'class_type' => $data['class_type'],
            'medium' => $data['medium'],
            'teacher_id' => $data['teacher_id'],
            'subject_id' => $subjectId,
            'grade_id' => $gradeId,
            'is_active' => $data['is_active'] ?? false,
            'is_ongoing' => $data['is_ongoing'] ?? false,
        ]);

        // Update payment configuration through its service.
        $this->classPaymentConfigService->updateForClass(
            $studentClass,
            $data
        );

        return $studentClass->fresh();
    });
}

    /**
     * Delete an inactive, non-ongoing class.
     */
    public function delete(StudentClass $studentClass): void
    {
        $this->ensureClassAccess($studentClass);

        if ($studentClass->is_ongoing) {
            throw ValidationException::withMessages([
                'student_class' => 'Ongoing classes cannot be deleted.',
            ]);
        }

        if ($studentClass->is_active) {
            throw ValidationException::withMessages([
                'student_class' => 'Active classes cannot be deleted.',
            ]);
        }

        DB::transaction(function () use ($studentClass) {
            $studentClass->paymentConfig()->delete();
            $studentClass->delete();
        });
    }

    /**
     * Toggle class active status.
     */
    public function toggleActive(StudentClass $studentClass): StudentClass
    {
        $this->ensureClassAccess($studentClass);

        if ($studentClass->is_active && $studentClass->is_ongoing) {
            throw ValidationException::withMessages([
                'student_class' => 'You cannot deactivate an ongoing class.',
            ]);
        }

        return DB::transaction(function () use ($studentClass) {
            $newStatus = !$studentClass->is_active;

            $studentClass->update(['is_active' => $newStatus]);

            if ($studentClass->paymentConfig) {
                $studentClass->paymentConfig->update([
                    'is_active' => $newStatus,
                ]);
            }

            return $studentClass->fresh();
        });
    }

    /**
     * Toggle ongoing status.
     */
    public function toggleOngoing(StudentClass $studentClass): StudentClass
    {
        $this->ensureClassAccess($studentClass);

        $studentClass->update([
            'is_ongoing' => !$studentClass->is_ongoing,
        ]);

        return $studentClass->fresh();
    }

    /**
     * Export accessible classes to Excel.
     */
    public function exportExcel()
    {
        $query = $this->baseQuery();

        return Excel::download(
            new StudentClassesExport($query),
            'student-classes.xlsx'
        );
    }

    /**
     * Generate PDF for accessible classes.
     */
    public function exportPdf()
    {
        $classes = $this->baseQuery()
            ->with([
                'branch',
                'teacher',
                'subject',
                'grade',
                'paymentConfig.organizer',
            ])
            ->latest('student_classes.id')
            ->get();

        return Pdf::loadView('pdf.student-class.index', compact('classes'))
            ->setPaper('a4', 'landscape')
            ->download('student-classes.pdf');
    }

    /**
     * Resolve existing or newly entered grade.
     */
    protected function resolveGradeId(array $data): int
    {
        if (!empty($data['new_grade'])) {
            return (int) Grade::firstOrCreate(
                ['grade_name' => trim($data['new_grade'])],
                ['is_active' => true]
            )->id;
        }

        return (int) $data['grade_id'];
    }

    /**
     * Resolve existing or newly entered subject.
     */
    protected function resolveSubjectId(array $data): int
    {
        if (!empty($data['new_subject'])) {
            return (int) Subject::firstOrCreate(
                ['subject_name' => trim($data['new_subject'])],
                ['is_active' => true]
            )->id;
        }

        return (int) $data['subject_id'];
    }

    /**
     * Search active, ongoing classes.
     */
    public function search(string $search = '')
    {
        $query = $this->baseQuery()
            ->with(['grade', 'subject', 'teacher'])
            ->where('student_classes.is_active', true)
            ->where('student_classes.is_ongoing', true);

        if ($search !== '') {
            $query->where(function (Builder $sub) use ($search) {
                $sub->where('student_classes.class_name', 'like', "%{$search}%")
                    ->orWhere('student_classes.class_type', 'like', "%{$search}%")
                    ->orWhere('student_classes.medium', 'like', "%{$search}%")
                    ->orWhereHas('grade', function (Builder $q) use ($search) {
                        $q->where('grade_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('subject', function (Builder $q) use ($search) {
                        $q->where('subject_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('teacher', function (Builder $q) use ($search) {
                        $q->where('initials', 'like', "%{$search}%");
                    });
            });
        }

        return $query->orderBy('student_classes.class_name')
            ->limit(20)
            ->get()
            ->map(function (StudentClass $class) {
                return [
                    'id' => $class->id,
                    'text' => $class->class_name
                        . ' | Grade: ' . ($class->grade->grade_name ?? '-')
                        . ' | Subject: ' . ($class->subject->subject_name ?? '-')
                        . ' | Teacher: ' . ($class->teacher->initials ?? '-'),
                ];
            });
    }
}

<?php

namespace App\Services\Web\StudentClass;

use App\Exports\Web\StudentClass\StudentClassesExport;
use App\Models\ClassCategoryFee;
use App\Models\Grade;
use App\Models\Organizer;
use App\Models\StudentClass;
use App\Models\StudentClassEnrollment;
use App\Models\ClassCategoryFeeOption;
use App\Models\ClassCategory;
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

        $classes = $query->latest('student_classes.id')
            ->paginate(10)
            ->appends($request->query());

        return [
            'classes' => $classes,

            // Classes that have at least one active fee option
            'classesWithFees' => ClassCategoryFeeOption::query()
                ->join('class_category_fees', 'class_category_fees.id', '=', 'class_category_fee_options.class_category_fee_id')
                ->whereIn('class_category_fees.student_class_id', $classes->pluck('id'))
                ->whereNull('class_category_fees.deleted_at')
                ->where('class_category_fees.is_active', true)
                ->whereNull('class_category_fee_options.deleted_at')
                ->where('class_category_fee_options.is_active', true)
                ->distinct()
                ->pluck('class_category_fees.student_class_id')
                ->map(fn ($id) => (int) $id)
                ->all(),

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
            'categories' => ClassCategory::query()
                ->where('is_active', true)
                ->orderBy('category_name')
                ->get(['id', 'category_name', 'code']),

            'feeBlocks' => $this->feeBlocks($studentClass),

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

            $this->syncFees($studentClass, $data['fees'] ?? []);

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

            if (array_key_exists('fees', $data)) {
                $this->syncFees($studentClass, $data['fees']);
            }

            return $studentClass->fresh();
        });
    }

    /**
     * The class's categories and fee options for the form (with how many
     * students are on each option).
     */
    protected function feeBlocks(?StudentClass $studentClass): array
    {
        if (!$studentClass) {
            return [];
        }

        $students = StudentClassEnrollment::query()
            ->where('student_class_id', $studentClass->id)
            ->where('is_active', true)
            ->selectRaw('class_category_fee_option_id, COUNT(*) as total')
            ->groupBy('class_category_fee_option_id')
            ->pluck('total', 'class_category_fee_option_id');

        return ClassCategoryFee::query()
            ->where('student_class_id', $studentClass->id)
            ->with(['feeOptions' => fn ($q) => $q->orderBy('id')])
            ->orderBy('id')
            ->get()
            ->map(function (ClassCategoryFee $fee) use ($students) {
                $options = $fee->feeOptions->values();
                $default = $options->search(fn ($o) => $o->is_default);

                return [
                    'id' => $fee->id,
                    'category_id' => $fee->class_category_id,
                    'is_active' => (bool) $fee->is_active,
                    'default' => $default === false ? 0 : $default,
                    'options' => $options->map(fn ($o) => [
                        'id' => $o->id,
                        'label' => $o->label,
                        'fee' => (float) $o->fee,
                        'is_active' => (bool) $o->is_active,
                        'students' => (int) ($students[$o->id] ?? 0),
                    ])->all(),
                ];
            })
            ->all();
    }

    /**
     * Save the form's "Categories & Fees". Nothing is deleted: categories
     * and options not in the form stay as they are; options are turned off
     * with is_active.
     *
     * @param  array<int, array>  $blocks
     */
    protected function syncFees(StudentClass $studentClass, array $blocks): void
    {
        foreach (array_values($blocks) as $block) {
            $categoryFee = ClassCategoryFee::withTrashed()
                ->where('student_class_id', $studentClass->id)
                ->where('class_category_id', (int) $block['category_id'])
                ->first();

            $blockActive = (bool) ($block['is_active'] ?? true);

            if (!$categoryFee) {
                $categoryFee = ClassCategoryFee::create([
                    'student_class_id' => $studentClass->id,
                    'class_category_id' => (int) $block['category_id'],
                    'is_active' => $blockActive,
                ]);
            } else {
                if ($categoryFee->trashed()) {
                    $categoryFee->restore();
                }
                $categoryFee->update(['is_active' => $blockActive]);
            }

            $saved = [];

            foreach (array_values((array) ($block['options'] ?? [])) as $index => $row) {
                $values = [
                    'label' => trim((string) $row['label']),
                    'fee' => round((float) $row['fee'], 2),
                    'is_active' => (bool) ($row['is_active'] ?? true),
                ];

                $option = !empty($row['id'])
                    ? ClassCategoryFeeOption::query()
                        ->where('class_category_fee_id', $categoryFee->id)
                        ->find((int) $row['id'])
                    : null;

                if (!empty($row['id']) && !$option) {
                    throw ValidationException::withMessages([
                        'fees' => 'A fee option does not belong to this class. Please reload the page.',
                    ]);
                }

                if ($option) {
                    $option->update($values);
                } else {
                    $option = ClassCategoryFeeOption::create($values + [
                        'class_category_fee_id' => $categoryFee->id,
                        'is_default' => false,
                    ]);
                }

                $saved[$index] = $option;
            }

            // One default per category: the chosen one if active, else the first active
            $chosen = $saved[(int) ($block['default'] ?? 0)] ?? null;
            if (!$chosen || !$chosen->is_active) {
                $chosen = ClassCategoryFeeOption::query()
                    ->where('class_category_fee_id', $categoryFee->id)
                    ->where('is_active', true)
                    ->orderByDesc('is_default')
                    ->orderBy('id')
                    ->first();
            }

            ClassCategoryFeeOption::query()
                ->where('class_category_fee_id', $categoryFee->id)
                ->update(['is_default' => false]);

            if ($chosen) {
                ClassCategoryFeeOption::query()->whereKey($chosen->id)->update(['is_default' => true]);
            }
        }
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

        // Only active classes can be marked as ongoing
        if (!$studentClass->is_active) {
            throw ValidationException::withMessages([
                'is_ongoing' => 'Only active classes can be marked as ongoing.',
            ]);
        }

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

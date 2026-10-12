<?php

namespace App\Services\Web\Student;

use App\Models\Admission;
use App\Models\FcmToken;
use App\Models\Student;
use App\Services\Branch\BranchAccessService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class StudentService
{
    public function __construct(
        private BranchAccessService $branchAccessService,
        private StudentPhotoService $studentPhotoService,
        private StudentAdmissionService $studentAdmissionService
    ) {}

    /**
     * Get paginated students.
     */
    public function getStudents(array $filters = []): LengthAwarePaginator
    {
        $query = Student::query()
            ->with('grade')
            ->whereIn(
                'branch_id',
                $this->branchAccessService->getBranchIdArray()
            )
            ->latest();

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);

            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere(
                        'temporary_qr_code',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'custom_id',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        if (!empty($filters['grade_id'])) {
            $query->where(
                'grade_id',
                (int) $filters['grade_id']
            );
        }

        return $query
            ->paginate(10)
            ->appends($filters);
    }

    /**
     * Find student for show page.
     */
    public function findForShow(int $studentId): Student
    {
        $student = Student::query()
            ->with([
                'grade',
                'branch',
                'currentCard',
                'currentEnrollment',
            ])
            ->findOrFail($studentId);

        $this->ensureStudentAccess($student);

        return $student;
    }

    /**
     * Find student for edit page.
     */
    public function findForEdit(int $studentId): Student
    {
        $student = Student::query()
            ->with([
                'grade',
                'branch',
            ])
            ->findOrFail($studentId);

        $this->ensureStudentAccess($student);

        return $student;
    }

    /**
     * Get grades for student forms.
     */
    public function getGrades(): Collection
    {
        return \App\Models\Grade::query()
            ->orderBy('grade_name')
            ->get();
    }

    public function getStatistics(): array
    {
        $branchIds = $this->branchAccessService->getBranchIdArray();

        $statistics = Student::query()
            ->whereIn('branch_id', $branchIds)
            ->selectRaw('COUNT(*) AS total_students')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END), 0) AS active_students'
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN admission = 1 THEN 1 ELSE 0 END), 0) AS admission_paid'
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN student_disable = 1 THEN 1 ELSE 0 END), 0) AS disabled_students'
            )
            ->first();

        $branchWiseCounts = \App\Models\Branch::query()
            ->whereIn('id', $branchIds)
            ->where('is_active', true)
            ->withCount('students')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'code',
            ]);

        return [
            'total_students' => (int) $statistics->total_students,
            'active_students' => (int) $statistics->active_students,
            'admission_paid' => (int) $statistics->admission_paid,
            'disabled_students' => (int) $statistics->disabled_students,
            'branch_wise_counts' => $branchWiseCounts,
        ];
    }

    /**
     * Get admissions for student edit form.
     *
     * Includes the student's latest paid admission even
     * if that admission is no longer active.
     */
    public function getAdmissionsForEdit(Student $student): array
    {
        $this->ensureStudentAccess($student);

        $latestAdmissionPayment = $student->admissionPayments()
            ->where('status', 'paid')
            ->latest()
            ->first();

        $admissions = Admission::withTrashed()
            ->where(function ($query) use ($latestAdmissionPayment) {
                $query->active();

                if ($latestAdmissionPayment?->admission_id) {
                    $query->orWhere(
                        'id',
                        $latestAdmissionPayment->admission_id
                    );
                }
            })
            ->orderBy('name')
            ->get();

        return [
            'admissions' => $admissions,
            'latestAdmissionPayment' => $latestAdmissionPayment,
        ];
    }

    /**
     * Update student.
     *
     * custom_id is NEVER updated here.
     *
     * Permanent card QR -> custom_id is handled only
     * by StudentCardService.
     */
    public function update(
        Student $student,
        array $data,
        $image = null
    ): Student {
        return DB::transaction(function () use (
            $student,
            $data,
            $image
        ) {

            /*
             * Lock student row to prevent concurrent updates.
             */
            $student = Student::query()
                ->lockForUpdate()
                ->findOrFail($student->id);

            /*
             * Validate current branch access.
             */
            $this->ensureStudentAccess($student);

            /*
             * Branch can be changed during normal update,
             * but only to a branch accessible by the current user.
             */
            if (isset($data['branch_id'])) {
                $newBranchId = (int) $data['branch_id'];

                if (
                    !$this->branchAccessService
                        ->canAccessBranch($newBranchId)
                ) {
                    abort(
                        403,
                        'You do not have access to the selected branch.'
                    );
                }
            }

            /*
             * Never allow normal student update to change
             * permanent/custom ID.
             */
            unset(
                $data['custom_id'],
                $data['temporary_qr_code'],
                $data['temporary_qr_code_expire_date'],
                $data['admission'],
                $data['admission_id']
            );

            /*
             * Handle new student image.
             */
            if ($image !== null) {
                $data['img_url'] = $this->studentPhotoService
                    ->handleStudentImage(
                        $image,
                        $data['gender'] ?? $student->gender
                    );

                $data['last_image_update_at'] = now();
            }

            /*
             * Update student details.
             */
            $student->update($data);

            /*
             * Admission status/payment is synchronized separately.
             *
             * The admission payment branch is based on the
             * student's main branch.
             */
            $hasAdmission = !empty(request()->input('admission'));

            $admissionId = request()->filled('admission_id')
                ? (int) request()->input('admission_id')
                : null;

            $this->studentAdmissionService
                ->syncAdmissionPayment(
                    $student->fresh(),
                    $hasAdmission,
                    $admissionId
                );

            return $student->fresh([
                'grade',
                'branch',
            ]);
        });
    }

    /**
     * Toggle student active status.
     */
    public function toggleActive(Student $student): Student
    {
        return DB::transaction(function () use ($student) {

            $student = Student::query()
                ->lockForUpdate()
                ->findOrFail($student->id);

            $this->ensureStudentAccess($student);

            $isActive = !$student->is_active;

            $student->update([
                'is_active' => $isActive,
            ]);

            /*
             * When student is deactivated,
             * deactivate all FCM tokens.
             */
            if (!$isActive) {
                FcmToken::query()
                    ->where('student_id', $student->id)
                    ->where('is_active', true)
                    ->update([
                        'is_active' => false,
                    ]);
            }

            return $student->fresh();
        });
    }

    /**
     * Soft delete student.
     */
    public function delete(Student $student): void
    {
        DB::transaction(function () use ($student) {

            $student = Student::query()
                ->lockForUpdate()
                ->findOrFail($student->id);

            $this->ensureStudentAccess($student);

            /*
             * Deactivate all FCM tokens first.
             */
            FcmToken::query()
                ->where('student_id', $student->id)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                ]);

            /*
             * Soft delete student.
             */
            $student->delete();
        });
    }

    /**
     * Search active students.
     */
    public function search(string $search): Collection
    {
        $search = trim($search);

        return Student::query()
            ->whereIn(
                'branch_id',
                $this->branchAccessService->getBranchIdArray()
            )
            ->where('is_active', true)
            ->when(
                $search !== '',
                function ($query) use ($search) {
                    $query->where(function ($sub) use ($search) {
                        $sub->where(
                            'custom_id',
                            'like',
                            "%{$search}%"
                        )
                            ->orWhere(
                                'temporary_qr_code',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'full_name',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'initial_name',
                                'like',
                                "%{$search}%"
                            );
                    });
                }
            )
            ->orderBy('full_name')
            ->limit(20)
            ->get();
    }

    /**
     * Ensure current user can access student's branch.
     */
    protected function ensureStudentAccess(Student $student): void
    {
        if (
            !$this->branchAccessService
                ->canAccessBranch(
                    (int) $student->branch_id
                )
        ) {
            abort(
                403,
                'You do not have access to this student.'
            );
        }
    }
}

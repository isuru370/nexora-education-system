<?php

namespace App\Services\Web\Exam;

use App\Models\Exam;
use App\Models\StudentClass;
use App\Models\ClassCategory;
use App\Models\ClassCategoryFee;
use App\Models\ClassHall;
use App\Models\StudentClassEnrollment;
use App\Models\StudentResult;
use App\Services\Branch\BranchAccessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ExamService
{
    /** Pass mark (percentage) used for passed / failed. */
    public const PASS_PERCENTAGE = 35;

    public function __construct(
        protected BranchAccessService $branchAccess
    ) {
    }

    /**
     * Exams the logged-in user may see (only their branches).
     */
    public function scopedExams(): Builder
    {
        return $this->branchAccess->applyBranchAccess(Exam::query());
    }

    /**
     * Check class / category / hall for a new or edited exam and
     * return the data with branch_id taken from the class.
     *
     * @throws ValidationException
     */
    public function prepareExamData(array $data): array
    {
        $class = StudentClass::query()
            ->select('id', 'branch_id', 'is_active')
            ->find($data['student_class_id'] ?? 0);

        if (!$class || !$this->branchAccess->canAccessBranch((int) $class->branch_id)) {
            throw ValidationException::withMessages([
                'student_class_id' => 'You cannot add exams for this class.',
            ]);
        }

        $categoryOk = ClassCategoryFee::query()
            ->where('student_class_id', $class->id)
            ->where('class_category_id', $data['class_category_id'] ?? 0)
            ->exists();

        if (!$categoryOk) {
            throw ValidationException::withMessages([
                'class_category_id' => 'This category is not part of the selected class.',
            ]);
        }

        if (!empty($data['class_hall_id'])) {
            $hallBranch = ClassHall::query()
                ->whereKey($data['class_hall_id'])
                ->value('branch_id');

            if ($hallBranch !== null && (int) $hallBranch !== (int) $class->branch_id) {
                throw ValidationException::withMessages([
                    'class_hall_id' => 'Pick a hall in the same branch as the class.',
                ]);
            }
        }

        $data['branch_id'] = (int) $class->branch_id;

        return $data;
    }
    /**
     * Get all exams with filters and pagination
     */
    public function getAllExams(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->scopedExams()
            ->with(['studentClass.grade', 'category', 'hall', 'cancelledBy']);

        // Apply search filter
        if (!empty($filters['search'])) {
            $query->where('title', 'like', '%' . $filters['search'] . '%');
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply class filter
        if (!empty($filters['student_class_id'])) {
            $query->where('student_class_id', $filters['student_class_id']);
        }

        // Apply date range filters
        if (!empty($filters['exam_date_from'])) {
            $query->whereDate('exam_date', '>=', $filters['exam_date_from']);
        }

        if (!empty($filters['exam_date_to'])) {
            $query->whereDate('exam_date', '<=', $filters['exam_date_to']);
        }

        // Newest first by default; 'order' => 'asc' for nearest first (upcoming)
        $direction = ($filters['order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query->orderBy('exam_date', $direction)
            ->orderBy('start_time', $direction);

        return $query->paginate($perPage);
    }

    /**
     * Get single exam by ID with relationships
     */
    public function getExamById(int $id): ?Exam
    {
        return $this->scopedExams()->with([
            'studentClass',
            'studentClass.grade',
            'studentClass.subject',
            'category',
            'hall',
            'cancelledBy',
            'results.student'
        ])->find($id);
    }

    /**
     * Create a new exam
     */
    public function createExam(array $data): Exam
    {
        // Set default status if not provided
        if (!isset($data['status'])) {
            $data['status'] = 'scheduled';
        }

        return Exam::create($data);
    }

    /**
     * Update an existing exam
     */
    public function updateExam(Exam $exam, array $data): bool
    {
        // Cancelled from the edit form: record who / when, like the Cancel button
        if (($data['status'] ?? null) === 'cancelled' && $exam->status !== 'cancelled') {
            $data['cancelled_by'] = auth()->id();
            $data['cancelled_at'] = now();
        }

        return $exam->update($data);
    }

    /**
     * Cancel an exam with reason
     */
    public function cancelExam(Exam $exam, int $userId, string $reason): bool
    {
        return $exam->cancel($userId, $reason);
    }

    /**
     * Delete an exam (soft delete)
     */
    public function deleteExam(Exam $exam): bool
    {
        return $exam->delete();
    }

    /**
     * Check if a hall is available for a given time slot
     */
    public function checkHallAvailability(
        int $hallId,
        string $date,
        string $startTime,
        string $endTime,
        ?int $excludeExamId = null
    ): bool {
        $query = Exam::query()->where('class_hall_id', $hallId)
            ->whereDate('exam_date', $date)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where(function ($sub) use ($startTime, $endTime) {
                    $sub->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime);
                });
            });

        if ($excludeExamId) {
            $query->where('id', '!=', $excludeExamId);
        }

        return !$query->exists();
    }

    /**
     * Get categories by class (only those with active fees)
     */
    public function getCategoriesByClass(int $classId): Collection
    {
        $classBranch = StudentClass::query()->whereKey($classId)->value('branch_id');

        if ($classBranch === null || !$this->branchAccess->canAccessBranch((int) $classBranch)) {
            return new Collection();
        }

        $classCategoryFees = ClassCategoryFee::with([
                'category',
                'activeFeeOptions:id,class_category_fee_id,label,fee,is_default,is_active',
            ])
            ->where('student_class_id', $classId)
            ->where('is_active', true)
            ->get();

        // Extract unique categories
        $categories = $classCategoryFees->map(function ($fee) {
            $category = $fee->category;

            if (!$category) {
                return null;
            }

            // Keep only active fee options for categories returned to the controller.
            $category->setRelation(
                'feeOptions',
                $fee->activeFeeOptions
            );

            return $category;
        })->filter(function ($category) {
            return $category !== null && $category->is_active;
        })->unique('id')->values();

        return $categories;
    }

    /**
     * Get all active classes with relationships
     */
    public function getClasses(): Collection
    {
        return StudentClass::with(['grade', 'subject', 'teacher', 'branch:id,name'])
            ->whereIn('branch_id', $this->branchAccess->getBranchIds())
            ->where('is_active', true)
            ->orderBy('class_name')
            ->get();
    }

    /**
     * Get all active halls
     */
    public function getHalls(): Collection
    {
        return ClassHall::where('is_active', true)
            ->whereIn('branch_id', $this->branchAccess->getBranchIds())
            ->orderBy('hall_name')
            ->get();
    }

    /**
     * Get all active categories
     */
    public function getAllCategories(): Collection
    {
        return ClassCategory::where('is_active', true)
            ->where('is_schedulable', true)
            ->orderBy('category_name')
            ->get();
    }

    /**
     * Get categories (alias for getAllCategories)
     */
    public function getCategories(): Collection
    {
        return $this->getAllCategories();
    }

    /**
     * Get exam statistics for dashboard
     */
    public function getExamStats(): array
    {
        return [
            'total' => $this->scopedExams()->count(),
            'scheduled' => $this->scopedExams()->where('status', 'scheduled')->count(),
            'ongoing' => $this->scopedExams()->where('status', 'ongoing')->count(),
            'completed' => $this->scopedExams()->where('status', 'completed')->count(),
            'cancelled' => $this->scopedExams()->where('status', 'cancelled')->count(),
            'upcoming' => $this->scopedExams()->where('status', 'scheduled')
                ->whereDate('exam_date', '>=', now()->toDateString())
                ->count(),
        ];
    }

    /**
     * Update exam status
     */
    public function updateExamStatus(Exam $exam, string $status): bool
    {
        $validStatuses = ['scheduled', 'ongoing', 'completed', 'cancelled'];

        if (!in_array($status, $validStatuses)) {
            Log::warning('Invalid exam status attempted: ' . $status);
            return false;
        }

        return $exam->update(['status' => $status]);
    }

    /**
     * Get exams by class
     */
    public function getExamsByClass(int $classId): Collection
    {
        return $this->scopedExams()->with(['category', 'hall'])
            ->where('student_class_id', $classId)
            ->orderBy('exam_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get();
    }

    /**
     * Get upcoming exams
     */
    public function getUpcomingExams(int $limit = 5): Collection
    {
        return $this->scopedExams()->with(['studentClass', 'category'])
            ->where('status', 'scheduled')
            ->whereDate('exam_date', '>=', now()->toDateString())
            ->orderBy('exam_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get today's exams
     */
    public function getTodayExams(): Collection
    {
        return $this->scopedExams()->with(['studentClass', 'category', 'hall'])
            ->whereDate('exam_date', now()->toDateString())
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_time', 'asc')
            ->get();
    }

    /**
     * Get exams by date range
     */
    public function getExamsByDateRange(string $from, string $to): Collection
    {
        return $this->scopedExams()->with(['studentClass', 'category', 'hall'])
            ->whereDate('exam_date', '>=', $from)
            ->whereDate('exam_date', '<=', $to)
            ->where('status', '!=', 'cancelled')
            ->orderBy('exam_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(): array
    {
        return [
            'total_today' => $this->getTodayExams()->count(),
            'total_upcoming' => $this->scopedExams()->where('status', 'scheduled')
                ->whereDate('exam_date', '>=', now()->toDateString())
                ->count(),
            'total_completed' => $this->scopedExams()->where('status', 'completed')->count(),
            'total_cancelled' => $this->scopedExams()->where('status', 'cancelled')->count(),
            'total_scheduled' => $this->scopedExams()->where('status', 'scheduled')->count(),
            'total_ongoing' => $this->scopedExams()->where('status', 'ongoing')->count(),
        ];
    }

    /**
     * Get exams by status
     */
    public function getExamsByStatus(string $status): Collection
    {
        return $this->scopedExams()->with(['studentClass', 'category', 'hall'])
            ->where('status', $status)
            ->orderBy('exam_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get();
    }

    /**
     * Count exams by status
     */
    public function countExamsByStatus(string $status): int
    {
        return $this->scopedExams()->where('status', $status)->count();
    }

    /**
     * Get exams scheduled for a specific date
     */
    public function getExamsByDate(string $date): Collection
    {
        return $this->scopedExams()->with(['studentClass', 'category', 'hall'])
            ->whereDate('exam_date', $date)
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_time', 'asc')
            ->get();
    }

    /**
     * Check if exam exists with conflicts
     */
    public function hasConflicts(Exam $exam, array $data): bool
    {
        // Check hall availability
        $hallId = $data['class_hall_id'] ?? $exam->class_hall_id;
        $date = $data['exam_date'] ?? $exam->exam_date->format('Y-m-d');
        $startTime = $data['start_time'] ?? $exam->start_time;
        $endTime = $data['end_time'] ?? $exam->end_time;

        return !$this->checkHallAvailability($hallId, $date, $startTime, $endTime, $exam->id);
    }

    /**
     * Get upcoming exams count
     */
    public function getUpcomingExamsCount(): int
    {
        return $this->scopedExams()->where('status', 'scheduled')
            ->whereDate('exam_date', '>=', now()->toDateString())
            ->count();
    }

    /**
     * Get exams with results
     */
    public function getExamsWithResults(): Collection
    {
        return $this->scopedExams()->with(['results', 'studentClass'])
            ->whereHas('results')
            ->orderBy('exam_date', 'desc')
            ->get();
    }

    /**
     * Get exams without results
     */
    public function getExamsWithoutResults(): Collection
    {
        return $this->scopedExams()->with(['studentClass'])
            ->whereDoesntHave('results')
            ->where('status', 'completed')
            ->orderBy('exam_date', 'desc')
            ->get();
    }

    /**
     * Get exam by title (for search)
     */
    public function getExamByTitle(string $title): ?Exam
    {
        return $this->scopedExams()->with(['studentClass', 'category', 'hall'])
            ->where('title', 'like', '%' . $title . '%')
            ->first();
    }

    /**
     * Get recent exams
     */
    public function getRecentExams(int $limit = 10): Collection
    {
        return $this->scopedExams()->with(['studentClass', 'category'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Bulk update exam status
     */
    public function bulkUpdateStatus(array $examIds, string $status): int
    {
        $validStatuses = ['scheduled', 'ongoing', 'completed', 'cancelled'];

        if (!in_array($status, $validStatuses)) {
            return 0;
        }

        return $this->scopedExams()->whereIn('id', $examIds)
            ->where('status', '!=', 'cancelled')
            ->update(['status' => $status]);
    }

    /**
     * Get exam schedule for a specific class
     */
    public function getClassExamSchedule(int $classId, ?string $date = null): Collection
    {
        $query = $this->scopedExams()->with(['category', 'hall'])
            ->where('student_class_id', $classId)
            ->where('status', '!=', 'cancelled');

        if ($date) {
            $query->whereDate('exam_date', $date);
        }

        return $query->orderBy('exam_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();
    }

    /**
     * Check if hall is booked for any exam
     */
    public function isHallBooked(int $hallId, string $date): bool
    {
        return $this->scopedExams()->where('class_hall_id', $hallId)
            ->whereDate('exam_date', $date)
            ->where('status', '!=', 'cancelled')
            ->exists();
    }

    /**
     * Get students for exam mark entry
     */
    public function getExamStudents(int $examId)
    {
        $exam = $this->scopedExams()->findOrFail($examId);

        return StudentClassEnrollment::query()
            ->where('student_class_id', $exam->student_class_id)
            ->where('is_active', true)
            ->whereHas('classCategoryFee', function ($q) use ($exam) {
                $q->where(
                    'class_category_id',
                    $exam->class_category_id
                );
            })
            ->with([
                'student' => function ($q) {
                    $q->select(
                        'id',
                        'custom_id',
                        'temporary_qr_code',
                        'permanent_qr_active',
                        'initial_name',
                        'img_url'
                    );
                },

                'student.results' => function ($q) use ($exam) {
                    $q->where(
                        'exam_id',
                        $exam->id
                    );
                }
            ])
            ->get()
            ->filter(fn ($enrollment) => $enrollment->student !== null)
            ->sortBy(fn ($enrollment) => $enrollment->student->initial_name)
            ->values();
    }

    /**
     * Save marks for the exam's students.
     *
     * $marks = [student_id => ['marks' => '72.5', 'is_absent' => '1', 'remark' => '...']]
     * - only students enrolled in the exam's class + category are saved
     * - blank marks (and not absent) = not entered: skipped, or cleared if saved before
     *
     * @return array{saved:int, absent:int, cleared:int, skipped:int}
     */
    public function saveMarks(Exam $exam, array $marks, float $maxMarks, ?int $userId): array
    {
        $allowed = $this->getExamStudents($exam->id)
            ->pluck('student_id')
            ->map(fn ($id) => (int) $id)
            ->flip();

        $stats = ['saved' => 0, 'absent' => 0, 'cleared' => 0, 'skipped' => 0];

        foreach ($marks as $studentId => $row) {
            $studentId = (int) $studentId;

            if (!$allowed->has($studentId)) {
                $stats['skipped']++;
                continue;
            }

            $row = is_array($row) ? $row : [];
            $isAbsent = filter_var($row['is_absent'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $raw = isset($row['marks']) ? trim((string) $row['marks']) : '';
            $remark = isset($row['remark']) ? trim((string) $row['remark']) : null;

            $key = ['student_id' => $studentId, 'exam_id' => $exam->id];

            if (!$isAbsent && $raw === '') {
                $existing = StudentResult::query()->where($key)->first();

                if ($existing) {
                    $existing->update([
                        'user_id' => $userId,
                        'marks' => null,
                        'percentage' => null,
                        'grade' => null,
                        'rank' => null,
                        'status' => 'pending',
                        'is_absent' => false,
                        'is_updated' => true,
                        'remark' => $remark ?: null,
                    ]);
                    $stats['cleared']++;
                }

                continue;
            }

            if ($isAbsent) {
                $values = [
                    'marks' => null,
                    'percentage' => null,
                    'grade' => null,
                    'status' => 'absent',
                    'is_absent' => true,
                ];
                $stats['absent']++;
            } else {
                $mark = round((float) $raw, 2);
                $percentage = $maxMarks > 0 ? round(($mark / $maxMarks) * 100, 2) : 0;

                $values = [
                    'marks' => $mark,
                    'percentage' => $percentage,
                    'grade' => $this->calculateGrade($percentage),
                    'status' => $percentage >= self::PASS_PERCENTAGE ? 'passed' : 'failed',
                    'is_absent' => false,
                ];
                $stats['saved']++;
            }

            StudentResult::updateOrCreate($key, $values + [
                'branch_id' => $exam->branch_id,
                'user_id' => $userId,
                'max_marks' => $maxMarks,
                'is_updated' => true,
                'remark' => $remark ?: null,
            ]);
        }

        $this->recalculateRanks($exam);

        return $stats;
    }

    /**
     * Rank by marks (highest = 1). Same marks share a rank (1, 1, 3).
     * Absent / not entered students get no rank.
     */
    public function recalculateRanks(Exam $exam): int
    {
        StudentResult::query()
            ->where('exam_id', $exam->id)
            ->where(fn ($q) => $q->where('is_absent', true)->orWhereNull('marks'))
            ->update(['rank' => null]);

        $results = StudentResult::query()
            ->where('exam_id', $exam->id)
            ->where('is_absent', false)
            ->whereNotNull('marks')
            ->orderByDesc('marks')
            ->get(['id', 'marks']);

        $rank = 0;
        $previous = null;

        foreach ($results as $index => $result) {
            if ($previous === null || (float) $result->marks !== $previous) {
                $rank = $index + 1;
                $previous = (float) $result->marks;
            }

            StudentResult::query()->whereKey($result->id)->update(['rank' => $rank]);
        }

        return $results->count();
    }

    /**
     * Summary numbers for the results page.
     */
    public function resultSummary($results): array
    {
        $marked = $results->filter(fn ($r) => !$r->is_absent && $r->marks !== null);

        return [
            'total' => $results->count(),
            'marked' => $marked->count(),
            'absent' => $results->where('is_absent', true)->count(),
            'passed' => $results->where('status', 'passed')->count(),
            'failed' => $results->where('status', 'failed')->count(),
            'average' => $marked->count() ? round($marked->avg(fn ($r) => (float) $r->marks), 2) : null,
            'highest' => $marked->count() ? (float) $marked->max(fn ($r) => (float) $r->marks) : null,
            'lowest' => $marked->count() ? (float) $marked->min(fn ($r) => (float) $r->marks) : null,
            'pass_rate' => $marked->count()
                ? round($results->where('status', 'passed')->count() / $marked->count() * 100)
                : null,
            'grades' => collect(['A', 'B', 'C', 'S', 'F'])
                ->mapWithKeys(fn ($g) => [$g => $marked->filter(fn ($r) => $r->getRawOriginal('grade') === $g)->count()])
                ->all(),
        ];
    }

    /**
     * Calculate grade from percentage
     */
    public function calculateGrade(float $percentage): string
    {
        if ($percentage >= 75) {
            return 'A';
        }

        if ($percentage >= 65) {
            return 'B';
        }

        if ($percentage >= 50) {
            return 'C';
        }

        if ($percentage >= 35) {
            return 'S';
        }

        return 'F';
    }
}

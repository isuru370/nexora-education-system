<?php

namespace App\Http\Controllers\Web\Exam;

use App\Exports\Web\Exam\ExamResultsExport;
use App\Exports\Web\Exam\ExamsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Exam\CancelExamRequest;
use App\Http\Requests\Web\Exam\StoreExamRequest;
use App\Http\Requests\Web\Exam\UpdateExamRequest;
use App\Models\Exam;
use App\Models\StudentClass;
use App\Services\Branch\BranchAccessService;
use App\Services\Web\Exam\ExamService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class ExamController extends Controller
{
    public function __construct(
        protected ExamService $examService,
        protected BranchAccessService $branchAccess
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Exam in one of the user's branches, or 404.
     */
    protected function findExam(int $id): Exam
    {
        $exam = $this->examService->getExamById($id);

        abort_if(!$exam, 404, 'Exam not found');

        return $exam;
    }

    /**
     * "rank" is a reserved word in MySQL 8, so it must be quoted.
     */
    protected function rankNullsLast(): string
    {
        return 'CASE WHEN ' . DB::getQueryGrammar()->wrap('rank') . ' IS NULL THEN 1 ELSE 0 END';
    }

    /**
     * Active classes (with fee categories) in the user's branches, for the form.
     */
    protected function formClasses()
    {
        return StudentClass::with([
            'grade:id,grade_name',
            'teacher:id,full_name',
            'branch:id,name',
            'categoryFees.category:id,category_name',
            'categoryFees.activeFeeOptions:id,class_category_fee_id,label,fee,is_default,is_active',
        ])
            ->whereIn('branch_id', $this->branchAccess->getBranchIds())
            ->where('is_active', true)
            ->orderBy('class_name')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | List / CRUD
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): View
    {
        $filters = $request->only([
            'search',
            'status',
            'student_class_id',
            'exam_date_from',
            'exam_date_to',
        ]);

        $exams = $this->examService->getAllExams($filters, 15)->withQueryString();
        $stats = $this->examService->getExamStats();
        $classes = $this->examService->getClasses();
        $upcomingExams = $this->examService->getUpcomingExams(5);

        return view('exams.index', compact('exams', 'stats', 'classes', 'upcomingExams', 'filters'));
    }

    public function create(): View
    {
        $classes = $this->formClasses();
        $halls = $this->examService->getHalls();
        $categories = collect();

        return view('exams.create', compact('classes', 'categories', 'halls'));
    }

    public function store(StoreExamRequest $request): RedirectResponse
    {
        $data = $this->examService->prepareExamData($request->validated());

        if (!$this->examService->checkHallAvailability(
            (int) $data['class_hall_id'],
            $data['exam_date'],
            $data['start_time'],
            $data['end_time']
        )) {
            return back()->withInput()->with(
                'error',
                'The selected hall is already booked for this time.'
            );
        }

        try {
            $exam = DB::transaction(fn () => $this->examService->createExam($data));
        } catch (\Throwable $e) {
            Log::error('Error creating exam', [
                'message' => $e->getMessage(),
                'data' => $request->except(['_token']),
            ]);

            return back()->withInput()->with('error', 'Failed to create exam. Please try again.');
        }

        return redirect()
            ->route('admin.exams.show', $exam->id)
            ->with('success', "Exam '{$exam->title}' has been created.");
    }

    public function show(int $id): View
    {
        $exam = $this->findExam($id);

        $students = $this->examService->getExamStudents($exam->id);
        $summary = $this->examService->resultSummary($exam->results);

        return view('exams.show', compact('exam', 'students', 'summary'));
    }

    public function edit(int $id): View
    {
        $exam = $this->findExam($id);
        $classes = $this->formClasses();
        $halls = $this->examService->getHalls();

        return view('exams.edit', compact('exam', 'classes', 'halls'));
    }

    public function update(UpdateExamRequest $request, int $id): RedirectResponse
    {
        $exam = $this->findExam($id);

        if ($exam->status === 'completed') {
            return redirect()
                ->route('admin.exams.show', $exam->id)
                ->with('error', 'Completed exams cannot be modified.');
        }

        $data = $this->examService->prepareExamData($request->validated());

        if (!$this->examService->checkHallAvailability(
            (int) $data['class_hall_id'],
            $data['exam_date'],
            $data['start_time'],
            $data['end_time'],
            $exam->id
        )) {
            return back()->withInput()->with(
                'error',
                'The selected hall is already booked for this time.'
            );
        }

        try {
            DB::transaction(fn () => $this->examService->updateExam($exam, $data));
        } catch (\Throwable $e) {
            Log::error('Error updating exam', [
                'exam_id' => $id,
                'message' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Failed to update exam. Please try again.');
        }

        return redirect()
            ->route('admin.exams.show', $exam->id)
            ->with('success', "Exam '{$exam->title}' has been updated.");
    }

    public function cancel(CancelExamRequest $request, int $id): RedirectResponse
    {
        $exam = $this->findExam($id);

        if ($exam->status === 'cancelled') {
            return back()->with('error', 'This exam is already cancelled.');
        }

        if ($exam->status === 'completed') {
            return back()->with('error', 'Completed exams cannot be cancelled.');
        }

        $this->examService->cancelExam($exam, (int) auth()->id(), (string) $request->cancel_reason);

        return redirect()
            ->route('admin.exams.show', $exam->id)
            ->with('success', "Exam '{$exam->title}' has been cancelled.");
    }

    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:scheduled,ongoing,completed,cancelled'],
        ]);

        $exam = $this->findExam($id);

        if ($request->status === 'cancelled') {
            if ($exam->status !== 'cancelled') {
                $this->examService->cancelExam($exam, (int) auth()->id(), (string) $request->input('cancel_reason', ''));
            }
        } else {
            $this->examService->updateExamStatus($exam, $request->status);
        }

        return back()->with('success', 'Exam status changed to ' . ucfirst($request->status) . '.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $exam = $this->findExam($id);

        if ($exam->results()->exists()) {
            return back()->with('error', 'Cannot delete an exam that already has results.');
        }

        $title = $exam->title;
        $this->examService->deleteExam($exam);

        return redirect()
            ->route('admin.exams.index')
            ->with('success', "Exam '{$title}' has been deleted.");
    }

    public function upcoming(Request $request): View
    {
        $filters = [
            'search' => $request->search,
            'student_class_id' => $request->student_class_id,
            'status' => 'scheduled',
            'exam_date_from' => today()->toDateString(),
            'order' => 'asc',
        ];

        $exams = $this->examService->getAllExams($filters, 15)->withQueryString();
        $stats = $this->examService->getExamStats();
        $classes = $this->examService->getClasses();
        $upcomingExams = $this->examService->getUpcomingExams(10);

        return view('exams.upcoming', compact('exams', 'stats', 'classes', 'upcomingExams', 'filters'));
    }

    /*
    |--------------------------------------------------------------------------
    | AJAX
    |--------------------------------------------------------------------------
    */

    public function counts(): JsonResponse
    {
        $stats = $this->examService->getExamStats();

        return response()->json([
            'success' => true,
            'counts' => [
                'scheduled' => $stats['scheduled'],
                'ongoing' => $stats['ongoing'],
                'completed' => $stats['completed'],
                'cancelled' => $stats['cancelled'],
                'upcoming' => $stats['upcoming'],
                'total' => $stats['total'],
            ],
        ]);
    }

    public function getCategoriesByClass(Request $request): JsonResponse
    {
        $request->validate([
            'class_id' => ['required', 'exists:student_classes,id'],
        ]);

        $categories = $this->examService->getCategoriesByClass((int) $request->class_id);

        return response()->json([
            'success' => true,
            'categories' => $categories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->category_name,
                'code' => $category->code,
                'fee_options' => $category->feeOptions
                    ? $category->feeOptions
                        ->where('is_active', true)
                        ->map(fn ($option) => [
                            'id' => $option->id,
                            'label' => $option->label,
                            'fee' => (float) $option->fee,
                            'is_default' => (bool) $option->is_default,
                        ])
                        ->values()
                        ->all()
                    : [],
            ])->values(),
        ]);
    }

    public function checkHallAvailability(Request $request): JsonResponse
    {
        $request->validate([
            'hall_id' => ['required', 'exists:class_halls,id'],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'exclude_exam_id' => ['nullable', 'exists:exams,id'],
        ]);

        $isAvailable = $this->examService->checkHallAvailability(
            (int) $request->hall_id,
            $request->date,
            $request->start_time,
            $request->end_time,
            $request->exclude_exam_id ? (int) $request->exclude_exam_id : null
        );

        return response()->json([
            'available' => $isAvailable,
            'message' => $isAvailable ? 'Hall is available' : 'Hall is already booked for this time',
        ]);
    }

    public function searchClasses(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('q', ''));
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 20;

        $query = StudentClass::query()
            ->with(['grade:id,grade_name', 'teacher:id,full_name'])
            ->select('id', 'class_name', 'grade_id', 'teacher_id')
            ->whereIn('branch_id', $this->branchAccess->getBranchIds())
            ->where('is_active', true);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('class_name', 'LIKE', "%{$search}%")
                    ->orWhereHas('grade', fn ($g) => $g->where('grade_name', 'LIKE', "%{$search}%"))
                    ->orWhereHas('teacher', fn ($t) => $t->where('full_name', 'LIKE', "%{$search}%"));
            });
        }

        $total = (clone $query)->count();

        $classes = $query->orderBy('class_name')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return response()->json([
            'results' => $classes->map(function ($class) {
                $label = $class->class_name;

                if ($class->grade) {
                    $label .= " ({$class->grade->grade_name})";
                }

                if ($class->teacher) {
                    $label .= " - {$class->teacher->full_name}";
                }

                return ['id' => $class->id, 'text' => $label];
            }),
            'pagination' => ['more' => ($page * $perPage) < $total],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Marks / results
    |--------------------------------------------------------------------------
    */

    public function markEntry(int $id): View|RedirectResponse
    {
        $exam = $this->findExam($id);

        if ($exam->status === 'cancelled') {
            return redirect()
                ->route('admin.exams.show', $exam->id)
                ->with('error', 'Marks cannot be entered for a cancelled exam.');
        }

        $students = $this->examService->getExamStudents($exam->id);

        $maxMarks = (float) ($exam->results->first()?->max_marks ?? 100);

        return view('exams.mark-entry', compact('exam', 'students', 'maxMarks'));
    }

    public function saveMarks(Request $request, int $id): RedirectResponse
    {
        $exam = $this->findExam($id);

        if ($exam->status === 'cancelled') {
            return back()->with('error', 'Marks cannot be entered for a cancelled exam.');
        }

        $request->validate([
            'max_marks' => ['required', 'numeric', 'min:1', 'max:1000'],
            'marks' => ['required', 'array'],
            'marks.*.marks' => ['nullable', 'numeric', 'min:0'],
            'marks.*.remark' => ['nullable', 'string', 'max:255'],
        ], [
            'marks.*.marks.numeric' => 'Marks must be a number.',
            'marks.*.marks.min' => 'Marks cannot be below 0.',
        ]);

        $maxMarks = (float) $request->max_marks;

        $tooHigh = collect($request->marks)
            ->filter(fn ($row) => is_array($row)
                && empty($row['is_absent'])
                && isset($row['marks']) && $row['marks'] !== ''
                && (float) $row['marks'] > $maxMarks)
            ->keys();

        if ($tooHigh->isNotEmpty()) {
            throw ValidationException::withMessages(
                $tooHigh->mapWithKeys(fn ($studentId) => [
                    "marks.{$studentId}.marks" => "Marks cannot be more than {$maxMarks}.",
                ])->all()
            );
        }

        try {
            $stats = DB::transaction(fn () => $this->examService->saveMarks(
                $exam,
                $request->marks,
                $maxMarks,
                auth()->id()
            ));
        } catch (\Throwable $e) {
            Log::error('Error saving marks', [
                'exam_id' => $id,
                'message' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Failed to save marks. Please try again.');
        }

        $message = "Marks saved: {$stats['saved']} entered, {$stats['absent']} absent";

        if ($stats['cleared']) {
            $message .= ", {$stats['cleared']} cleared";
        }

        return redirect()
            ->route('admin.exams.results', $exam->id)
            ->with('success', $message . '.');
    }

    public function results(int $id): View
    {
        $exam = $this->findExam($id);

        $results = $exam->results()
            ->with(['student:id,custom_id,temporary_qr_code,permanent_qr_active,initial_name,img_url', 'enteredBy:id,name'])
            ->orderByRaw($this->rankNullsLast())
            ->orderBy('rank')
            ->orderByDesc('marks')
            ->get();

        $summary = $this->examService->resultSummary($results);

        return view('exams.results', compact('exam', 'results', 'summary'));
    }

    /**
     * Re-rank and mark the exam as completed (results published).
     */
    public function recalculateRanks(int $id): RedirectResponse
    {
        $exam = $this->findExam($id);

        if ($exam->status === 'cancelled') {
            return back()->with('error', 'This exam is cancelled.');
        }

        try {
            $ranked = DB::transaction(function () use ($exam) {
                $count = $this->examService->recalculateRanks($exam);
                $this->examService->updateExamStatus($exam, 'completed');

                return $count;
            });
        } catch (\Throwable $e) {
            Log::error('Error recalculating ranks', [
                'exam_id' => $id,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to recalculate ranks.');
        }

        return back()->with('success', "Ranks updated for {$ranked} students. Exam marked as completed.");
    }

    /*
    |--------------------------------------------------------------------------
    | Exports
    |--------------------------------------------------------------------------
    */

    public function exportExcel()
    {
        $exams = $this->examService->scopedExams()
            ->with(['studentClass', 'category', 'hall', 'results'])
            ->orderByDesc('exam_date')
            ->get();

        return Excel::download(new ExamsExport($exams), 'exam-report.xlsx');
    }

    public function exportPdf()
    {
        $exams = $this->examService->scopedExams()
            ->with(['studentClass', 'category', 'hall', 'results'])
            ->orderByDesc('exam_date')
            ->get();

        return Pdf::loadView('pdf.exam.index', compact('exams'))
            ->download('exam-report.pdf');
    }

    public function exportResultsExcel(int $exam)
    {
        $exam = $this->findExam($exam);

        return Excel::download(
            new ExamResultsExport($exam),
            'exam-results-' . $exam->id . '.xlsx'
        );
    }

    public function exportResultsPdf(int $exam)
    {
        $exam = $this->findExam($exam);

        $exam->load(['studentClass.grade', 'category']);

        $results = $exam->results()
            ->with(['student', 'enteredBy'])
            ->orderByRaw($this->rankNullsLast())
            ->orderBy('rank')
            ->orderByDesc('marks')
            ->get();

        return Pdf::loadView('pdf.exam.results', compact('exam', 'results'))
            ->download('exam-results-' . $exam->id . '.pdf');
    }
}

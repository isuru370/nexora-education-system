<?php

namespace App\Http\Controllers\Web\ClassSchedule;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ClassSchedule\BulkEditClassScheduleRequest;
use App\Http\Requests\Web\ClassSchedule\CancelClassScheduleRequest;
use App\Http\Requests\Web\ClassSchedule\CategorySchedulesRequest;
use App\Http\Requests\Web\ClassSchedule\StoreClassScheduleRequest;
use App\Http\Requests\Web\ClassSchedule\UpdateBulkScheduleRequest;
use App\Http\Requests\Web\ClassSchedule\UpdateClassScheduleRequest;
use App\Models\Branch;
use App\Models\ClassSchedule;
use App\Models\ClassSchedulePattern;
use App\Models\StudentClass;
use App\Services\Web\ClassSchedule\ClassScheduleQueryService;
use App\Services\Web\ClassSchedule\ClassScheduleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ClassScheduleController extends Controller

{

    public function __construct(
        protected ClassScheduleService $classScheduleService,
        protected ClassScheduleQueryService $classScheduleQueryService,
    ) {}


    public function index(Request $request)
    {


        $perPage = max(
            1,
            min((int) $request->input('per_page', 10), 100)
        );

        $search = trim((string) $request->input('search', ''));

        $accessibleBranchIds = $this->classScheduleService
            ->accessibleBranchIds();

        $branchId = $request->filled('branch_id')
            ? (int) $request->input('branch_id')
            : null;

        $studentClassId = $request->filled('student_class_id')
            ? (int) $request->input('student_class_id')
            : null;

        // Only branches accessible to this user
        $branches = Branch::query()
            ->whereIn('id', $accessibleBranchIds)
            ->orderBy('name')   // ✅ branch_name → name
            ->get();

        // Only classes belonging to accessible branches
        $filterClasses = StudentClass::query()
            ->whereIn('branch_id', $accessibleBranchIds)
            ->where('is_active', true)
            ->where('is_ongoing', true)
            ->when($branchId !== null, function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->orderBy('class_name')
            ->get(['id', 'branch_id', 'class_name']);

        // If a submitted class is not in the accessible/selected branch,
        // do not allow it to influence the results.
        if ($studentClassId !== null) {
            $validClass = $filterClasses->contains('id', $studentClassId);

            if (!$validClass) {
                $studentClassId = null;
            }
        }

        $classes = $this->classScheduleQueryService->getIndexClasses(
            $search,
            $perPage,
            $branchId,
            $studentClassId
        );


        return view('class_schedules.index', compact(
            'classes',
            'search',
            'branches',
            'filterClasses'
        ));
    }


    public function create(Request $request)

    {

        $validated = $request->validate([

            'student_class_id' => ['required', 'integer'],

            'class_category_fee_id' => ['required', 'integer'],

            'type' => ['nullable', 'in:single,recurring'],

        ]);

        $studentClassId = (int) $validated['student_class_id'];

        $categoryFeeId = (int) $validated['class_category_fee_id'];

        $scheduleType = $validated['type'] ?? 'recurring';

        try {

            $data = $this->classScheduleService->getCreateData(

                $studentClassId,

                $categoryFeeId

            );

            return view('class_schedules.create', array_merge($data, [

                'selectedClassId' => $studentClassId,

                'selectedCategoryFeeId' => $categoryFeeId,

                'scheduleType' => $scheduleType,

            ]));
        } catch (Throwable $e) {

            Log::warning('Unable to open class schedule create page.', [

                'student_class_id' => $studentClassId,

                'class_category_fee_id' => $categoryFeeId,

                'error' => $e->getMessage(),

            ]);

            return redirect()

                ->route('class-schedules.index')

                ->with('error', 'Please select a valid class and category fee.');
        }
    }

    public function store(StoreClassScheduleRequest $request)
    {
        $validated = $request->validated();

        try {
            $this->classScheduleService->createSchedule($validated);

            return redirect()
                ->route('class-schedules.index', [
                    'class_id' => $validated['student_class_id'],
                    'class_category_fee_id' => $validated['class_category_fee_id'],
                ])
                ->with('success', 'Class schedule created successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // ✅ Validation errors → re-throw කරන්න
            // Laravel automatically form එකට errors සමඟ redirect කරයි
            throw $e;
        } catch (Throwable $e) {
            Log::error('Class schedule creation failed.', [
                'student_class_id' => $validated['student_class_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', \App\Support\ErrorDetail::safe($e));
        }
    }

    public function show(ClassSchedule $classSchedule)

    {

        try {

            $data = $this->classScheduleService->getShowData($classSchedule);

            return view('class_schedules.show', $data);
        } catch (Throwable $e) {

            Log::warning('Unable to show class schedule.', [

                'schedule_id' => $classSchedule->id,

                'error' => $e->getMessage(),

            ]);

            abort(404);
        }
    }

    public function categorySchedules(CategorySchedulesRequest $request)

    {

        $validated = $request->validated();

        $search = trim((string) ($validated['search'] ?? ''));

        // ClassScheduleService::getCategorySchedules() accepts only these two IDs.
        // Keep the service unchanged and call its existing signature.
        $data = $this->classScheduleService->getCategorySchedules(
            (int) $validated['student_class_id'],
            (int) $validated['class_category_fee_id']
        );

        return view('class_schedules.category_show', array_merge($data, [

            'search' => $search,

        ]));
    }

    public function edit(ClassSchedule $classSchedule)

    {

        try {

            if ($this->classScheduleService->isLocked($classSchedule)) {

                return back()->with('error', 'This schedule is locked and cannot be edited.');
            }

            $data = $this->classScheduleService->getEditData($classSchedule);

            return view('class_schedules.edit', $data);
        } catch (Throwable $e) {

            Log::warning('Unable to edit class schedule.', [

                'schedule_id' => $classSchedule->id,

                'error' => $e->getMessage(),

            ]);

            abort(404);
        }
    }

    public function update(
    UpdateClassScheduleRequest $request,
    ClassSchedule $classSchedule
) {
    $validated = $request->validated();

    try {
        $this->classScheduleService->updateSchedule($classSchedule, $validated);

        return redirect()
            ->route('class-schedules.categorySchedules', [
                'student_class_id' => $validated['student_class_id'],
                'class_category_fee_id' => $validated['class_category_fee_id'],
            ])
            ->with('success', 'Class schedule updated successfully.');

    } catch (\Illuminate\Validation\ValidationException $e) {
        // ✅ Validation errors → re-throw කරන්න
        // Laravel automatically form එකට errors සමඟ redirect කරයි
        throw $e;

    } catch (Throwable $e) {
        Log::error('Class schedule update failed.', [
            'schedule_id' => $classSchedule->id,
            'error' => $e->getMessage(),
        ]);

        return back()
            ->withInput()
            ->with('error', \App\Support\ErrorDetail::safe($e));
    }
}

    public function destroy(ClassSchedule $classSchedule)

    {

        try {

            $this->classScheduleService->deleteSchedule($classSchedule);

            return redirect()

                ->route('class-schedules.index')

                ->with('success', 'Class schedule deleted successfully.');
        } catch (Throwable $e) {

            Log::error('Class schedule deletion failed.', [

                'schedule_id' => $classSchedule->id,

                'error' => $e->getMessage(),

            ]);

            return back()->with('error', \App\Support\ErrorDetail::safe($e));
        }
    }

    public function toggleActive(ClassSchedule $classSchedule)

    {

        try {

            $this->classScheduleService->toggleScheduleActive($classSchedule);

            return back()->with('success', 'Schedule status updated.');
        } catch (Throwable $e) {

            Log::warning('Unable to toggle schedule active state.', [

                'schedule_id' => $classSchedule->id,

                'error' => $e->getMessage(),

            ]);

            return back()->with('error', \App\Support\ErrorDetail::safe($e));
        }
    }

    public function cancel(

        CancelClassScheduleRequest $request,

        ClassSchedule $classSchedule

    ) {

        $validated = $request->validated();

        try {

            $this->classScheduleService->cancelSchedule(

                $classSchedule,

                $validated['cancel_reason']

            );

            return back()->with('success', 'Schedule cancelled successfully.');
        } catch (Throwable $e) {

            Log::warning('Unable to cancel class schedule.', [

                'schedule_id' => $classSchedule->id,

                'error' => $e->getMessage(),

            ]);

            return back()->withInput()->with('error', \App\Support\ErrorDetail::safe($e));
        }
    }

    public function todayClasses(Request $request)

    {

        $search = trim((string) $request->input('search', ''));

        $data = $this->classScheduleService->getTodayClasses($search);

        return view('today-classes.index', $data);
    }

    public function statusUpdate(ClassSchedule $classSchedule)

    {

        try {

            $this->classScheduleService->markScheduleCompleted($classSchedule);

            return back()->with(

                'success',

                'Schedule marked as completed successfully.'

            );
        } catch (Throwable $e) {

            Log::warning('Unable to mark schedule completed.', [

                'schedule_id' => $classSchedule->id,

                'error' => $e->getMessage(),

            ]);

            return back()->with('error', \App\Support\ErrorDetail::safe($e));
        }
    }

    public function bulkEdit(BulkEditClassScheduleRequest $request)

    {

        $validated = $request->validated();

        try {

            $data = $this->classScheduleService->getBulkEditData(

                (int) $validated['student_class_id'],

                (int) $validated['class_category_fee_id']

            );

            return view('class_schedules.bulk-edit', $data);
        } catch (Throwable $e) {

            Log::warning('Unable to open bulk schedule page.', [

                'student_class_id' => $validated['student_class_id'],

                'class_category_fee_id' => $validated['class_category_fee_id'],

                'error' => $e->getMessage(),

            ]);

            return back()->with('error', 'Unable to load bulk schedule page.');
        }
    }

    public function editBulkSchedule(ClassSchedulePattern $pattern)

    {

        try {

            $data = $this->classScheduleService->getEditBulkData($pattern);

            return view('class_schedules.edit-bulk', $data);
        } catch (Throwable $e) {

            Log::warning('Unable to edit bulk schedule pattern.', [

                'pattern_id' => $pattern->id,

                'error' => $e->getMessage(),

            ]);

            abort(404);
        }
    }

    public function updateBulkSchedule(

        UpdateBulkScheduleRequest $request,

        ClassSchedulePattern $pattern

    ) {

        $validated = $request->validated();

        try {

            $this->classScheduleService->updateBulkSchedule(

                $pattern,

                $validated

            );

            return redirect()

                ->route('class-schedules.bulkEdit', [

                    'student_class_id' => $pattern->student_class_id,

                    'class_category_fee_id' => $pattern->class_category_fee_id,

                ])

                ->with('success', 'Bulk schedule updated successfully.');
        } catch (Throwable $e) {

            Log::error('Bulk schedule update failed.', [

                'pattern_id' => $pattern->id,

                'error' => $e->getMessage(),

            ]);

            return back()

                ->withInput()

                ->with('error', 'Failed to update schedule.');
        }
    }
}

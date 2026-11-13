<?php

namespace App\Services\Api\Teacher;

use App\Models\ClassCategoryFee;
use App\Models\ClassHall;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\StudentClass;
use App\Models\Teacher;
use App\Models\TeacherScheduleRequest;
use App\Models\User;
use App\Services\Course\CourseSessionService;
use App\Services\Web\ClassSchedule\ClassScheduleService;
use App\Support\TeacherAppRules;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * A teacher changes their own class days from the staff app: change a day
 * (date / time / hall), add an extra day or cancel a day. Classes and
 * courses. With "schedule_auto" on it is done at once (parents are told);
 * otherwise it waits for an admin (Admin > Teacher Requests).
 */
class TeacherScheduleService
{
    public const DAYS_AHEAD = 60;

    public function __construct(
        private ClassScheduleService $classSchedules,
        private CourseSessionService $courseSessions,
    ) {}

    /** Upcoming class days (today + 60 days) and this teacher's requests. */
    public function upcoming(Teacher $teacher): array
    {
        $from = now()->toDateString();
        $to = now()->addDays(self::DAYS_AHEAD)->toDateString();

        $pending = TeacherScheduleRequest::query()
            ->where('teacher_id', $teacher->id)
            ->where('status', 'pending')
            ->get();

        $pendingFor = fn (string $kind, int $id) => $pending->first(fn ($r) => $r->kind === $kind
            && ($kind === 'class' ? $r->class_schedule_id : $r->course_session_id) === $id) !== null;

        $classes = ClassSchedule::query()
            ->whereHas('studentClass', fn ($q) => $q->where('teacher_id', $teacher->id))
            ->whereBetween('class_date', [$from, $to . ' 23:59:59'])
            ->with(['studentClass:id,class_name,branch_id', 'classCategoryFee.category:id,category_name', 'hall:id,hall_name'])
            ->orderBy('class_date')
            ->orderBy('start_time')
            ->get()
            ->map(fn (ClassSchedule $s) => [
                'type' => 'class',
                'id' => $s->id,
                'class_id' => $s->student_class_id,
                'title' => $s->studentClass->class_name ?? 'Class',
                'category' => $s->classCategoryFee->category->category_name ?? null,
                'class_category_fee_id' => $s->class_category_fee_id,
                'date' => $s->class_date?->toDateString(),
                'start_time' => substr((string) $s->start_time, 0, 5),
                'end_time' => substr((string) $s->end_time, 0, 5),
                'hall_id' => $s->class_hall_id,
                'hall' => $s->hall->hall_name ?? null,
                'status' => $s->status,
                'is_active' => (bool) $s->is_active,
                // Same rules as the web: change from tomorrow, cancel until it starts
                'can_change' => $s->status === 'scheduled' && $s->class_date && $s->class_date->gt(today()),
                'can_cancel' => $s->status === 'scheduled' && $s->isCancellable(),
                'pending_request' => $pendingFor('class', $s->id),
                'cancel_reason' => $s->cancel_reason,
            ]);

        $courses = collect();

        if (feature('courses')) {
            $courses = CourseSession::query()
                ->whereHas('course.courseTeachers', fn ($q) => $q->where('teacher_id', $teacher->id))
                ->whereBetween('session_date', [$from, $to . ' 23:59:59'])
                ->with(['course:id,name', 'hall:id,hall_name'])
                ->orderBy('session_date')
                ->orderBy('start_time')
                ->get()
                ->map(fn (CourseSession $s) => [
                    'type' => 'course',
                    'id' => $s->id,
                    'class_id' => $s->course_id,
                    'title' => $s->course->name ?? 'Course',
                    'category' => 'Course',
                    'class_category_fee_id' => null,
                    'date' => $s->session_date->toDateString(),
                    'start_time' => substr((string) $s->start_time, 0, 5),
                    'end_time' => substr((string) $s->end_time, 0, 5),
                    'hall_id' => $s->class_hall_id,
                    'hall' => $s->hall->hall_name ?? null,
                    'status' => $s->status,
                    'is_active' => true,
                    'can_change' => $s->canEdit(),
                    'can_cancel' => $s->canCancel(),
                    'pending_request' => $pendingFor('course', $s->id),
                    'cancel_reason' => $s->cancel_reason,
                ]);
        }

        return [
            'auto_apply' => TeacherAppRules::allows('schedule_auto'),
            'schedules' => $classes->concat($courses)
                ->sortBy(fn ($r) => $r['date'] . ' ' . $r['start_time'])
                ->values()
                ->all(),
            'requests' => $this->requests($teacher),
        ];
    }

    /** Classes / courses with categories and halls, for the add / change form. */
    public function options(Teacher $teacher): array
    {
        $classes = StudentClass::query()
            ->where('teacher_id', $teacher->id)
            ->where('is_active', true)
            ->where('is_ongoing', true)
            ->orderBy('class_name')
            ->get(['id', 'class_name', 'branch_id']);

        $fees = ClassCategoryFee::query()
            ->whereIn('student_class_id', $classes->pluck('id'))
            ->where('is_active', true)
            ->with('category:id,category_name')
            ->get();

        $courses = feature('courses')
            ? Course::query()
                ->whereHas('courseTeachers', fn ($q) => $q->where('teacher_id', $teacher->id))
                ->where('is_active', true)
                ->whereDate('end_date', '>=', now()->toDateString())
                ->orderBy('name')
                ->get(['id', 'name', 'branch_id'])
            : collect();

        $branchIds = $classes->pluck('branch_id')->merge($courses->pluck('branch_id'))->unique()->values();

        $halls = ClassHall::query()
            ->whereIn('branch_id', $branchIds)
            ->where('is_active', true)
            ->orderBy('hall_name')
            ->get(['id', 'hall_name', 'branch_id']);

        $hallsOf = fn ($branchId) => $halls->where('branch_id', $branchId)
            ->map(fn ($h) => ['id' => $h->id, 'name' => $h->hall_name])
            ->values();

        return [
            'classes' => $classes->map(fn (StudentClass $c) => [
                'id' => $c->id,
                'name' => $c->class_name,
                'categories' => $fees->where('student_class_id', $c->id)
                    ->map(fn ($f) => ['class_category_fee_id' => $f->id, 'name' => $f->category->category_name ?? 'Category'])
                    ->values(),
                'halls' => $hallsOf($c->branch_id),
            ])->values(),
            'courses' => $courses->map(fn (Course $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'halls' => $hallsOf($c->branch_id),
            ])->values(),
        ];
    }

    /**
     * New request from the app. Applied at once when "schedule_auto" is on.
     *
     * @param  array{kind: string, action: string, id?: int, class_category_fee_id?: int,
     *               date?: string, start_time?: string, end_time?: string,
     *               class_hall_id?: ?int, reason?: ?string, note?: ?string}  $data
     */
    public function submit(Teacher $teacher, User $user, array $data): TeacherScheduleRequest
    {
        $request = $this->build($teacher, $user, $data);

        if (!TeacherAppRules::allows('schedule_auto')) {
            $request->save();

            return $request;
        }

        // Applied now: a failure goes back to the teacher and nothing is saved.
        // (The schedule services use their own transactions and tell the
        // parents after saving.)
        $this->run($request);

        $request->status = 'approved';
        $request->reviewed_at = now();
        $request->save();

        return $request->fresh();
    }

    /** Admin approves: the change is made and parents are told. */
    public function approve(TeacherScheduleRequest $request, User $admin): TeacherScheduleRequest
    {
        $this->assertPending($request);

        try {
            $this->run($request);
            $request->update(['status' => 'approved', 'reviewed_by' => $admin->id, 'reviewed_at' => now(), 'error' => null]);
        } catch (ValidationException $e) {
            $this->fail($request, $admin, collect($e->errors())->flatten()->first());
        } catch (HttpException $e) {
            $this->fail($request, $admin, $e->getMessage() ?: 'This class day cannot be changed.');
        } catch (\RuntimeException $e) {
            $this->fail($request, $admin, $e->getMessage());
        }

        return $request->fresh();
    }

    public function reject(TeacherScheduleRequest $request, User $admin, ?string $note): TeacherScheduleRequest
    {
        $this->assertPending($request);

        $request->update([
            'status' => 'rejected',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'review_note' => $note ?: null,
        ]);

        return $request;
    }

    /** Teacher takes back a request that is still waiting. */
    public function withdraw(Teacher $teacher, int $id): void
    {
        $request = TeacherScheduleRequest::query()
            ->where('teacher_id', $teacher->id)
            ->where('status', 'pending')
            ->findOrFail($id);

        $request->delete();
    }

    /** This teacher's requests from the last 30 days. */
    public function requests(Teacher $teacher): array
    {
        return TeacherScheduleRequest::query()
            ->where('teacher_id', $teacher->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->with(['studentClass:id,class_name', 'course:id,name'])
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (TeacherScheduleRequest $r) => $this->requestRow($r))
            ->all();
    }

    public function requestRow(TeacherScheduleRequest $r): array
    {
        return [
            'id' => $r->id,
            'kind' => $r->kind,
            'action' => $r->action,
            'title' => $r->title(),
            'schedule_id' => $r->kind === 'class' ? $r->class_schedule_id : $r->course_session_id,
            'details' => $r->payload,
            'status' => $r->status,
            'error' => $r->error,
            'review_note' => $r->review_note,
            'created_at' => $r->created_at?->toISOString(),
            'reviewed_at' => $r->reviewed_at?->toISOString(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Internal
    |--------------------------------------------------------------------------
    */

    /** Checks the teacher owns the class / day and makes the (unsaved) request. */
    protected function build(Teacher $teacher, User $user, array $data): TeacherScheduleRequest
    {
        $kind = $data['kind'];
        $action = $data['action'];

        $payload = array_filter([
            'date' => $data['date'] ?? null,
            'start_time' => isset($data['start_time']) ? substr($data['start_time'], 0, 5) : null,
            'end_time' => isset($data['end_time']) ? substr($data['end_time'], 0, 5) : null,
            'class_hall_id' => $data['class_hall_id'] ?? null,
            'class_category_fee_id' => $data['class_category_fee_id'] ?? null,
            'reason' => $data['reason'] ?? null,
            'note' => $data['note'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        $request = new TeacherScheduleRequest([
            'teacher_id' => $teacher->id,
            'user_id' => $user->id,
            'kind' => $kind,
            'action' => $action,
            'status' => 'pending',
        ]);

        if ($kind === 'course') {
            abort_unless(feature('courses'), 404);

            if ($action === 'create') {
                $course = Course::query()
                    ->whereKey($data['id'] ?? 0)
                    ->whereHas('courseTeachers', fn ($q) => $q->where('teacher_id', $teacher->id))
                    ->firstOrFail();
            } else {
                $session = CourseSession::query()
                    ->whereKey($data['id'] ?? 0)
                    ->whereHas('course.courseTeachers', fn ($q) => $q->where('teacher_id', $teacher->id))
                    ->firstOrFail();

                $this->assertCourseDayOpen($session, $action);
                $course = $session->course;
                $request->course_session_id = $session->id;

                // Keep what is not being changed
                $payload += [
                    'date' => $session->session_date->toDateString(),
                    'start_time' => substr((string) $session->start_time, 0, 5),
                    'end_time' => substr((string) $session->end_time, 0, 5),
                ];

                if (!array_key_exists('class_hall_id', $payload) && $session->class_hall_id) {
                    $payload['class_hall_id'] = $session->class_hall_id;
                }
            }

            $request->course_id = $course->id;
            $request->branch_id = $course->branch_id;
        } else {
            if ($action === 'create') {
                $class = StudentClass::query()
                    ->whereKey($data['id'] ?? 0)
                    ->where('teacher_id', $teacher->id)
                    ->firstOrFail();

                if (empty($payload['class_category_fee_id'])) {
                    throw ValidationException::withMessages(['class_category_fee_id' => 'Choose the category.']);
                }
            } else {
                $schedule = ClassSchedule::query()
                    ->whereKey($data['id'] ?? 0)
                    ->whereHas('studentClass', fn ($q) => $q->where('teacher_id', $teacher->id))
                    ->firstOrFail();

                $this->assertClassDayOpen($schedule, $action);
                $class = $schedule->studentClass;
                $request->class_schedule_id = $schedule->id;

                $payload += [
                    'date' => $schedule->class_date?->toDateString(),
                    'start_time' => substr((string) $schedule->start_time, 0, 5),
                    'end_time' => substr((string) $schedule->end_time, 0, 5),
                    'class_category_fee_id' => $schedule->class_category_fee_id,
                ];

                if (!array_key_exists('class_hall_id', $payload) && $schedule->class_hall_id) {
                    $payload['class_hall_id'] = $schedule->class_hall_id;
                }
            }

            if (!empty($payload['class_category_fee_id'])) {
                $belongs = ClassCategoryFee::query()
                    ->whereKey($payload['class_category_fee_id'])
                    ->where('student_class_id', $class->id)
                    ->exists();

                if (!$belongs) {
                    throw ValidationException::withMessages(['class_category_fee_id' => 'This category is not in the class.']);
                }
            }

            $request->student_class_id = $class->id;
            $request->branch_id = $class->branch_id;
        }

        if ($action !== 'cancel') {
            $this->assertTimes($payload);
        }

        if ($action === 'cancel' && empty($payload['reason'])) {
            throw ValidationException::withMessages(['reason' => 'Give a reason for cancelling.']);
        }

        if (!empty($payload['class_hall_id'])) {
            $hallOk = ClassHall::query()->whereKey($payload['class_hall_id'])->where('branch_id', $request->branch_id)->exists();

            if (!$hallOk) {
                throw ValidationException::withMessages(['class_hall_id' => 'Choose a hall of this branch.']);
            }
        }

        $open = TeacherScheduleRequest::query()
            ->where('status', 'pending')
            ->where('kind', $kind)
            ->when($request->class_schedule_id, fn ($q) => $q->where('class_schedule_id', $request->class_schedule_id))
            ->when($request->course_session_id, fn ($q) => $q->where('course_session_id', $request->course_session_id))
            ->when($action === 'create', fn ($q) => $q->where('action', 'create')
                ->where($kind === 'course' ? 'course_id' : 'student_class_id', $kind === 'course' ? $request->course_id : $request->student_class_id)
                ->where('payload->date', $payload['date'] ?? null))
            ->exists();

        if ($open) {
            throw ValidationException::withMessages(['schedule' => 'A request for this class day is already waiting for approval.']);
        }

        $request->payload = $payload;

        return $request;
    }

    /** Makes the change (the services tell the parents). */
    protected function run(TeacherScheduleRequest $request): void
    {
        $p = $request->payload ?? [];

        if ($request->kind === 'course') {
            $data = [
                'session_date' => $p['date'] ?? null,
                'start_time' => $p['start_time'] ?? null,
                'end_time' => $p['end_time'] ?? null,
                'class_hall_id' => $p['class_hall_id'] ?? null,
            ];

            match ($request->action) {
                'create' => $this->courseSessions->add(Course::query()->findOrFail($request->course_id), $data),
                'update' => $this->courseSessions->edit(CourseSession::query()->findOrFail($request->course_session_id), $data),
                'cancel' => $this->courseSessions->cancel(
                    CourseSession::query()->findOrFail($request->course_session_id),
                    $p['reason'] ?? null,
                    $request->user_id
                ),
            };

            return;
        }

        $data = [
            'student_class_id' => $request->student_class_id,
            'class_category_fee_id' => $p['class_category_fee_id'] ?? null,
            'class_hall_id' => $p['class_hall_id'] ?? null,
            'class_date' => $p['date'] ?? null,
            'start_time' => $p['start_time'] ?? null,
            'end_time' => $p['end_time'] ?? null,
            'note' => $p['note'] ?? null,
        ];

        switch ($request->action) {
            case 'create':
                $this->classSchedules->createSchedule($data);
                break;

            case 'update':
                $schedule = ClassSchedule::query()->findOrFail($request->class_schedule_id);
                $this->classSchedules->updateSchedule($schedule, $data + ['is_active' => $schedule->is_active]);
                break;

            case 'cancel':
                $schedule = ClassSchedule::query()->findOrFail($request->class_schedule_id);
                $this->classSchedules->cancelSchedule($schedule, $p['reason'] ?? 'Cancelled by the teacher');

                // The teacher cancelled it, not the admin who approved
                if ($request->user_id && Auth::id() !== $request->user_id) {
                    $schedule->forceFill(['cancelled_by' => $request->user_id])->saveQuietly();
                }
                break;
        }
    }

    protected function fail(TeacherScheduleRequest $request, User $admin, ?string $error): void
    {
        Log::info('Teacher schedule request could not be applied', ['id' => $request->id, 'error' => $error]);

        $request->update([
            'status' => 'failed',
            'error' => $error ?: 'Could not be applied.',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);
    }

    protected function assertPending(TeacherScheduleRequest $request): void
    {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'This request is already ' . $request->status . '.']);
        }
    }

    /** Same rules as the web: cancel until it starts, change only from tomorrow. */
    protected function assertClassDayOpen(ClassSchedule $schedule, string $action): void
    {
        if ($schedule->status !== 'scheduled' || !$schedule->isCancellable()) {
            throw ValidationException::withMessages(['schedule' => 'Only a class day that has not started can be changed or cancelled.']);
        }

        if ($action === 'update' && !$schedule->class_date->gt(today())) {
            throw ValidationException::withMessages(['schedule' => 'Today\'s class cannot be changed. Cancel it or add an extra day.']);
        }
    }

    protected function assertCourseDayOpen(CourseSession $session, string $action): void
    {
        $ok = $action === 'cancel' ? $session->canCancel() : $session->canEdit();

        if (!$ok) {
            throw ValidationException::withMessages(['schedule' => 'Only a class day that has not started can be changed or cancelled.']);
        }
    }

    protected function assertTimes(array $p): void
    {
        if (empty($p['date']) || empty($p['start_time']) || empty($p['end_time'])) {
            throw ValidationException::withMessages(['date' => 'Give the date, start time and end time.']);
        }

        if ($p['end_time'] <= $p['start_time']) {
            throw ValidationException::withMessages(['end_time' => 'The end time must be after the start time.']);
        }

        if (Carbon::parse($p['date'] . ' ' . $p['start_time'])->lte(now())) {
            throw ValidationException::withMessages(['date' => 'Choose a date and time that has not passed.']);
        }
    }
}

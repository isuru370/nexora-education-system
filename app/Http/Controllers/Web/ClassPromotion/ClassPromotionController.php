<?php

namespace App\Http\Controllers\Web\ClassPromotion;

use App\Http\Controllers\Controller;
use App\Models\ClassPromotion;
use App\Services\Web\ClassPromotion\PromoteClassService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * "Promote Class" page (admin only): pick a class, fill the new class
 * (name, grade, teacher, start date, students, schedule), history, undo.
 */
class ClassPromotionController extends Controller
{
    private const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    public function __construct(private PromoteClassService $service)
    {
    }

    public function index(): View
    {
        $this->service->applyDue();

        return view('class-promotions.index', [
            'classes' => $this->service->classes(),
            'history' => ClassPromotion::with(['promotedBy:id,name', 'undoneBy:id,name'])->latest('id')->paginate(20),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $request->validate([
            'class_id' => ['required', 'integer'],
            'start_date' => ['nullable', 'date'],
        ]);

        try {
            $data = $this->service->formData((int) $request->query('class_id'), $request->query('start_date'));
        } catch (ValidationException $e) {
            return redirect()->route('class-promotions.index')->with('error', collect($e->errors())->flatten()->first());
        }

        return view('class-promotions.create', $data + ['days' => self::DAYS]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'from_class_id' => ['required', 'integer', 'exists:student_classes,id'],
            'class_name' => ['required', 'string', 'max:150'],
            'grade_id' => ['required', 'integer', 'exists:grades,id'],
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'start_date' => ['required', 'date'],
            'schedule_until' => ['required', 'date', 'after_or_equal:start_date'],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer'],
            'schedules' => ['nullable', 'array'],
            'schedules.*.include' => ['nullable'],
            'schedules.*.class_day' => ['required_with:schedules.*.include', 'nullable', 'in:' . implode(',', self::DAYS)],
            'schedules.*.start_time' => ['required_with:schedules.*.include', 'nullable', 'date_format:H:i'],
            'schedules.*.end_time' => ['required_with:schedules.*.include', 'nullable', 'date_format:H:i'],
            'schedules.*.class_hall_id' => ['nullable', 'integer', 'exists:class_halls,id'],
            'schedules.*.class_category_fee_id' => ['required_with:schedules.*.include', 'nullable', 'integer'],
        ], [
            'student_ids.required' => 'Tick at least one student to move to the new class.',
        ]);

        // only ticked schedule rows
        $data['schedules'] = collect($data['schedules'] ?? [])->filter(fn ($r) => ! empty($r['include']))->values()->all();

        foreach ($data['schedules'] as $i => $row) {
            if ($row['end_time'] <= $row['start_time']) {
                throw ValidationException::withMessages(["schedules.$i.end_time" => 'Schedule: the end time must be after the start time.']);
            }
        }

        try {
            $promotion = $this->service->promote((int) $data['from_class_id'], $data, auth()->id());
        } catch (ValidationException $e) {
            return back()->withInput()->with('error', collect($e->errors())->flatten()->first());
        }

        $message = $promotion->status === ClassPromotion::DONE
            ? 'Promoted: the new class has started and the old class is closed.'
            : 'Promoted: the new class starts on ' . $promotion->start_date->format('Y-m-d')
                . '. The old class goes on until then and closes by itself.';

        return redirect()->route('class-promotions.show', $promotion)->with('success', $message);
    }

    public function show(ClassPromotion $promotion): View
    {
        $promotion->load(['promotedBy:id,name', 'undoneBy:id,name']);

        return view('class-promotions.show', [
            'promotion' => $promotion,
            'details' => $this->service->details($promotion),
        ]);
    }

    public function undo(ClassPromotion $promotion): RedirectResponse
    {
        try {
            $this->service->undo($promotion, auth()->id());
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()->route('class-promotions.show', $promotion)
            ->with('success', 'Undone: the new class was removed and "' . $promotion->from_class_name . '" is as before.');
    }
}

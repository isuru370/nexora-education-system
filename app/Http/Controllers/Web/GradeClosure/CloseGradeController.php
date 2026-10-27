<?php

namespace App\Http\Controllers\Web\GradeClosure;

use App\Http\Controllers\Controller;
use App\Models\GradeClosure;
use App\Services\Web\GradeClosure\CloseGradeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * "Close Batch" page (admin only): pick grades, preview, close, history, undo.
 */
class CloseGradeController extends Controller
{
    public function __construct(private CloseGradeService $service)
    {
    }

    /** Grades + history; with ?grade_ids[]= also the preview. */
    public function index(Request $request): View
    {
        $this->service->importOldFiles();

        $selected = array_map('intval', (array) $request->query('grade_ids', []));
        $preview = null;
        $previewError = null;

        if ($selected) {
            try {
                $preview = $this->service->preview($selected);
            } catch (ValidationException $e) {
                $previewError = collect($e->errors())->flatten()->first();
            }
        }

        return view('close-grade.index', [
            'grades' => $this->service->grades(),
            'selected' => $selected,
            'preview' => $preview,
            'previewError' => $previewError,
            'history' => GradeClosure::with(['closedBy:id,name', 'undoneBy:id,name'])->latest('id')->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'grade_ids' => ['required', 'array', 'min:1'],
            'grade_ids.*' => ['integer', 'exists:grades,id'],
        ]);

        try {
            $closure = $this->service->close($data['grade_ids'], auth()->id(), 'web');
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()
            ->route('close-grade.show', $closure)
            ->with('success', sprintf(
                'Closed %s: %d classes, %d students deactivated.',
                implode(', ', $closure->grade_names),
                $closure->total('classes'),
                $closure->total('students')
            ));
    }

    public function show(GradeClosure $closure): View
    {
        $closure->load(['closedBy:id,name', 'undoneBy:id,name']);

        return view('close-grade.show', [
            'closure' => $closure,
            'details' => $this->service->details($closure),
        ]);
    }

    public function undo(GradeClosure $closure): RedirectResponse
    {
        try {
            $this->service->undo($closure, auth()->id());
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()
            ->route('close-grade.show', $closure)
            ->with('success', 'Undone: ' . implode(', ', $closure->grade_names) . ' classes and students are active again.');
    }
}

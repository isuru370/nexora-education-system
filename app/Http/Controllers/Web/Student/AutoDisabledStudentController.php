<?php

namespace App\Http\Controllers\Web\Student;

use App\Http\Controllers\Controller;
use App\Services\Branch\BranchAccessService;
use App\Services\Web\Student\StudentAutoDisableService;
use App\Support\ErrorDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Students made inactive by the nightly job (no attendance for 2 months):
 * list, PDF and Re-activate. See App\Services\Web\Student\StudentAutoDisableService.
 */
class AutoDisabledStudentController extends Controller
{
    public function __construct(
        protected StudentAutoDisableService $service,
        protected BranchAccessService $branchAccess
    ) {}

    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        [$from, $to] = $this->service->range($filters);

        return view('auto-disabled-students.index', [
            'records' => $this->service->paginate($filters),
            'filters' => $filters,
            'from' => $from,
            'to' => $to,
            'lastRun' => $this->service->lastRun(),
            'branches' => $this->branchAccess->getAccessibleBranches(),
        ]);
    }

    public function pdf(Request $request): Response
    {
        $filters = $this->filters($request);
        [$from, $to] = $this->service->range($filters);

        return Pdf::loadView('auto-disabled-students.pdf', [
            'records' => $this->service->all($filters),
            'from' => $from,
            'to' => $to,
            'filters' => $filters,
        ])->setPaper('a4', 'landscape')
            ->download('auto-disabled-students-' . $from->format('Y-m-d') . ($from->isSameDay($to) ? '' : '-to-' . $to->format('Y-m-d')) . '.pdf');
    }

    public function reactivate(int $record): RedirectResponse
    {
        try {
            $result = $this->service->reactivate($record);
        } catch (DomainException $e) {
            return back()->with('error', ErrorDetail::safe($e));
        }

        return back()->with('success', $result['student']->custom_id . ' ' . $result['student']->initial_name
            . ' is active again and back in ' . $result['classes'] . ' class(es).');
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:inactive,reactivated'],
            'branch_id' => ['nullable', 'integer'],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Web\Welfare;

use App\Http\Controllers\Controller;
use App\Services\Web\Welfare\WelfareService;
use App\Support\ErrorDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Teacher welfare (feature "welfare"): monthly welfare from teacher
 * salaries and the branch welfare fund. See App\Services\Web\Welfare\WelfareService.
 */
class WelfareController extends Controller
{
    public function __construct(protected WelfareService $welfare) {}

    public function index(Request $request)
    {
        [$branchId, $year, $month] = $this->filters($request);

        return view('welfare.index', $this->welfare->month($branchId, $year, $month) + [
            'branches' => $this->welfare->branchOptions(),
            'branchId' => $branchId,
            'year' => $year,
            'month' => $month,
            'balance' => $this->welfare->balance($branchId),
        ]);
    }

    public function saveAmounts(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'integer'],
            'amounts' => ['required', 'array'],
            'amounts.*' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        ]);

        return $this->run($request, function () use ($validated) {
            $this->welfare->saveAmounts((int) $validated['branch_id'], $validated['amounts']);

            return 'Monthly welfare amounts saved.';
        });
    }

    public function deduct(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'integer'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'teachers' => ['required', 'array', 'min:1'],
            'teachers.*' => ['integer'],
            'amounts' => ['required', 'array'],
            'amounts.*' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        ], ['teachers.required' => 'Tick at least one teacher.']);

        $selected = [];
        foreach ($validated['teachers'] as $teacherId) {
            $selected[(int) $teacherId] = $validated['amounts'][$teacherId] ?? 0;
        }

        try {
            $result = $this->welfare->deduct(
                (int) $validated['branch_id'], (int) $validated['year'], (int) $validated['month'], $selected
            );
        } catch (DomainException $e) {
            return back()->with('error', ErrorDetail::safe($e));
        }

        $redirect = back();
        if ($result['done']) {
            $redirect = $redirect->with('success', 'Welfare taken: ' . implode(', ', $result['done']));
        }
        if ($result['skipped']) {
            $redirect = $redirect->with('error', 'Not taken: ' . implode('; ', $result['skipped']));
        }

        return $redirect;
    }

    public function cancelDeduction(Request $request, int $deduction): RedirectResponse
    {
        return $this->run($request, function () use ($deduction) {
            $this->welfare->cancelDeduction($deduction);

            return 'Welfare cancelled. It is added back to the salary.';
        });
    }

    public function fund(Request $request)
    {
        [$branchId, $year, $month] = $this->filters($request);

        return view('welfare.fund', $this->welfare->fund($branchId, $year, $month) + [
            'branches' => $this->welfare->branchOptions(),
            'branchId' => $branchId,
            'year' => $year,
            'month' => $month,
        ]);
    }

    public function fundPdf(Request $request): Response
    {
        [$branchId, $year, $month] = $this->filters($request);
        $data = $this->welfare->fund($branchId, $year, $month);

        return Pdf::loadView('welfare.fund-pdf', $data)
            ->setPaper('a4')
            ->download(sprintf('welfare-fund-%04d-%02d.pdf', $year, $month));
    }

    public function storeExpense(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'integer'],
            'expense_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:200'],
            'paid_to' => ['nullable', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:10000000'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        return $this->run($request, function () use ($validated) {
            $this->welfare->addExpense((int) $validated['branch_id'], $validated);

            return 'Welfare fund expense saved.';
        });
    }

    public function cancelExpense(Request $request, int $expense): RedirectResponse
    {
        return $this->run($request, function () use ($expense) {
            $this->welfare->cancelExpense($expense);

            return 'Expense cancelled. The money is back in the fund.';
        });
    }

    /** @return array{0: int, 1: int, 2: int} */
    private function filters(Request $request): array
    {
        $request->validate([
            'branch_id' => ['nullable', 'integer'],
            'year' => ['nullable', 'integer', 'min:2020', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        try {
            $branchId = $this->welfare->resolveBranchId($request->filled('branch_id') ? (int) $request->input('branch_id') : null);
        } catch (DomainException $e) {
            abort(403, $e->getMessage());
        }

        return [$branchId, (int) $request->input('year', now()->year), (int) $request->input('month', now()->month)];
    }

    private function run(Request $request, callable $action): RedirectResponse
    {
        try {
            return back()->with('success', $action());
        } catch (DomainException $e) {
            return back()->withInput()->with('error', ErrorDetail::safe($e));
        }
    }
}

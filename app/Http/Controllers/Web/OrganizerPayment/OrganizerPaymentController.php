<?php

namespace App\Http\Controllers\Web\OrganizerPayment;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Organizer;
use App\Models\OrganizerPayment;
use App\Services\Web\Organizer\OrganizerSalaryService;
use DomainException;
use Illuminate\Http\Request;

/**
 * Organizer salary, per branch + month (OrganizerSalaryService has the rules).
 */
class OrganizerPaymentController extends Controller
{
    public function __construct(
        protected OrganizerSalaryService $salaries
    ) {}

    /** year / month / branch from the request (this month, first branch by default) */
    private function period(Request $request): array
    {
        $year = (int) ($request->year ?? now()->year);
        $month = (int) ($request->month ?? now()->month);

        if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
            [$year, $month] = [now()->year, now()->month];
        }

        $branchId = $this->salaries->resolveBranchId($request->filled('branch_id') ? (int) $request->branch_id : null);

        return [$year, $month, $branchId];
    }

    public function index(Request $request)
    {
        try {
            [$year, $month, $branchId] = $this->period($request);
        } catch (DomainException $e) {
            abort(403, $e->getMessage());
        }

        $organizerRows = $this->salaries->monthRows($branchId, $year, $month);

        return view('organizer-payments.index', [
            'year' => $year,
            'month' => $month,
            'branchId' => $branchId,
            'branches' => $this->salaries->branchOptions(),
            'organizerRows' => $organizerRows,
            'summary' => [
                'total_income' => round($organizerRows->sum('total_income'), 2),
                'advance_amount' => round($organizerRows->sum('advance_amount'), 2),
                'deduction_amount' => round($organizerRows->sum('deduction_amount'), 2),
                'other_amount' => round($organizerRows->sum('other_amount'), 2),
                'salary_paid' => round($organizerRows->sum('salary_paid'), 2),
                'net_total' => round($organizerRows->sum('net_total'), 2),
            ],
        ]);
    }

    public function pay(Request $request, Organizer $organizer)
    {
        try {
            [$year, $month, $branchId] = $this->period($request);
        } catch (DomainException $e) {
            abort(403, $e->getMessage());
        }

        return view('organizer-payments.pay', $this->pageData($organizer, $branchId, $year, $month));
    }

    public function store(Request $request, Organizer $organizer)
    {
        $validated = $request->validate([
            'payment_type' => ['required', 'in:salary'],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'month' => ['required', 'integer', 'between:1,12'],
            'branch_id' => ['required', 'integer'],
            'expected_net' => ['nullable', 'numeric'],
        ]);

        try {
            $this->salaries->paySalary(
                $organizer->id,
                (int) $validated['branch_id'],
                (int) $validated['year'],
                (int) $validated['month'],
                isset($validated['expected_net']) ? (float) $validated['expected_net'] : null
            );
        } catch (DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.organizer-payments.salary-slip', [
                'organizer' => $organizer->id,
                'year' => $validated['year'],
                'month' => $validated['month'],
                'branch_id' => $validated['branch_id'],
            ])
            ->with('success', 'Organizer salary paid successfully.');
    }

    public function storeAdjustment(Request $request, Organizer $organizer)
    {
        $validated = $request->validate([
            'payment_type' => ['required', 'in:advance,deduction,other'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'branch_id' => ['required', 'integer'],
            'reason' => ['nullable', 'string', 'max:150'],
            'note' => ['nullable', 'string'],
        ]);

        try {
            $this->salaries->storeAdjustment($organizer->id, (int) $validated['branch_id'], $validated);
        } catch (DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()
            ->back()
            ->with('success', ucfirst($validated['payment_type']) . ' saved successfully.');
    }

    public function destroy(OrganizerPayment $organizerPayment)
    {
        try {
            $this->salaries->delete($organizerPayment);
        } catch (DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Organizer payment deleted successfully.');
    }

    public function salarySlip(Request $request, Organizer $organizer)
    {
        try {
            [$year, $month, $branchId] = $this->period($request);
        } catch (DomainException $e) {
            abort(403, $e->getMessage());
        }

        $data = $this->pageData($organizer, $branchId, $year, $month);

        if (!$data['salaryRecord']) {
            return redirect()
                ->route('admin.organizer-payments.pay', ['organizer' => $organizer->id, 'year' => $year, 'month' => $month, 'branch_id' => $branchId])
                ->with('error', 'Salary payment record not found.');
        }

        return view('organizer-payments.salary-slip', $data);
    }

    private function pageData(Organizer $organizer, int $branchId, int $year, int $month): array
    {
        $totals = $this->salaries->forOrganizer($organizer->id, $branchId, $year, $month);

        return [
            'organizer' => $organizer,
            'year' => $year,
            'month' => $month,
            'branchId' => $branchId,
            'branch' => Branch::query()->find($branchId),
            'branches' => $this->salaries->branchOptions(),
            'totalIncome' => $totals['earned'],
            'earlierBalance' => $totals['earlier_balance'],
            'advanceAmount' => $totals['advance'],
            'deductionAmount' => $totals['deduction'],
            'otherAmount' => $totals['other'],
            'salaryPaid' => $totals['salary'] ? round((float) $totals['salary']->amount, 2) : 0.0,
            'netTotal' => $totals['net'],
            'stillOwed' => $totals['still_owed'],
            'salaryRecord' => $totals['salary'],
            'payBlockReason' => $this->salaries->payBlockReason($year, $month, $totals),
            'adjustments' => OrganizerPayment::query()
                ->where('branch_id', $branchId)
                ->where('organizer_id', $organizer->id)
                ->whereIn('payment_type', OrganizerSalaryService::ADJUSTMENT_TYPES)
                ->whereYear('payment_date', $year)
                ->whereMonth('payment_date', $month)
                ->orderBy('payment_date')
                ->get(),
        ];
    }
}

<?php

namespace App\Http\Controllers\Web\TeacherSalary;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\TeacherSalary\PayTeacherSalaryRequest;
use App\Http\Requests\Web\TeacherSalary\StoreTeacherPaymentRequest;
use App\Http\Requests\Web\TeacherSalary\TeacherSalaryFilterRequest;
use App\Services\Web\TeacherSalary\TeacherSalaryPaymentService;
use App\Services\Web\TeacherSalary\TeacherSalaryService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Throwable;

class TeacherSalaryController extends Controller
{
    public function __construct(
        protected TeacherSalaryService $salaryService,
        protected TeacherSalaryPaymentService $paymentService
    ) {}

    /**
     * All teachers of a branch for a month.
     */
    public function index(TeacherSalaryFilterRequest $request)
    {
        $year = $request->year();
        $month = $request->month();

        try {
            $branchId = $this->salaryService->resolveBranchId($request->branchId());
        } catch (DomainException $e) {
            abort(403, $e->getMessage());
        }

        $data = $this->salaryService->monthlyIncome($branchId, $year, $month);

        return view('teacher-salaries.index', [
            'salaryRows' => $data['data'],
            'summary' => $data['summary'],
            'year' => $year,
            'month' => $month,
            'monthName' => $data['month_name'],
            'branch' => $data['branch'],
            'branchId' => $branchId,
            'branches' => $this->salaryService->branchOptions(),
        ]);
    }

    /**
     * One teacher's salary for a branch and month.
     * The filter form can change year / month / branch with query parameters.
     */
    public function show(TeacherSalaryFilterRequest $request, int $teacher, int $year, int $month)
    {
        $year = $request->year($year);
        $month = $this->validMonth($request->month($month));

        try {
            $branchId = $this->salaryService->resolveBranchId($request->branchId(), $teacher);
        } catch (DomainException $e) {
            return $this->toIndex($year, $month, $e->getMessage());
        }

        return view('teacher-salaries.show', [
            'data' => $this->salaryService->getTeacherSalaryDetails($teacher, $branchId, $year, $month),
            'year' => $year,
            'month' => $month,
            'branchId' => $branchId,
            'branches' => $this->salaryService->branchOptions($teacher),
        ]);
    }

    public function teacherSalaryPaid(PayTeacherSalaryRequest $request, int $teacher): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $this->paymentService->paySalary(
                $teacher,
                (int) $validated['branch_id'],
                (int) $validated['year'],
                (int) $validated['month'],
                isset($validated['amount']) ? (float) $validated['amount'] : null,
                $validated['note'] ?? null
            );
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Failed to pay teacher salary.');
        }

        return back()->with([
            'success' => 'Salary paid successfully.',
            'slip_open' => true,
            'slip_teacher' => $teacher,
            'slip_year' => (int) $validated['year'],
            'slip_month' => (int) $validated['month'],
            'slip_branch' => (int) $validated['branch_id'],
        ]);
    }

    public function printSalarySlip(TeacherSalaryFilterRequest $request, int $teacher, int $year, int $month)
    {
        $month = $this->validMonth($month);

        try {
            $branchId = $this->salaryService->resolveBranchId($request->branchId(), $teacher);
        } catch (DomainException $e) {
            return $this->toIndex($year, $month, $e->getMessage());
        }

        return view('teacher-salaries.salary-slip-exact', [
            'data' => $this->salaryService->salarySlip($teacher, $branchId, $year, $month),
        ]);
    }

    public function teacherPaymentStore(StoreTeacherPaymentRequest $request, int $teacher): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $this->paymentService->storePayment($teacher, (int) $validated['branch_id'], $validated);
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Failed to add payment record.');
        }

        return back()->with('success', 'Payment record added successfully.');
    }

    public function paymentDetailsView(TeacherSalaryFilterRequest $request, int $teacher, int $year, int $month)
    {
        $month = $this->validMonth($month);

        try {
            $branchId = $this->salaryService->resolveBranchId($request->branchId(), $teacher);
        } catch (DomainException $e) {
            return $this->toIndex($year, $month, $e->getMessage());
        }

        return view(
            'teacher-salaries.payment-details',
            $this->salaryService->teacherAllPayments($teacher, $branchId, $year, $month)
        );
    }

    public function paymentDelete(int $paymentId): RedirectResponse
    {
        try {
            $this->paymentService->deletePayment($paymentId);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Failed to delete payment record.');
        }

        return back()->with('success', 'Payment record deleted successfully.');
    }

    public function teacherPaymentAndClassSummery(
        TeacherSalaryFilterRequest $request,
        int $teacher,
        int $year,
        int $month
    ) {
        $month = $this->validMonth($month);

        try {
            $branchId = $this->salaryService->resolveBranchId($request->branchId(), $teacher);
        } catch (DomainException $e) {
            return $this->toIndex($year, $month, $e->getMessage());
        }

        return view('teacher-salaries.summary', [
            'data' => $this->salaryService->teacherPaymentAndClassSummary($teacher, $branchId, $year, $month),
            'teacherId' => $teacher,
            'year' => $year,
            'month' => $month,
            'branchId' => $branchId,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function validMonth(int $month): int
    {
        abort_if($month < 1 || $month > 12, 404);

        return $month;
    }

    protected function toIndex(int $year, int $month, string $message): RedirectResponse
    {
        return redirect()
            ->route('teacher-salaries.index', ['year' => $year, 'month' => $month])
            ->with('error', $message);
    }
}
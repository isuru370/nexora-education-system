<?php

namespace App\Http\Controllers\Web\Admission;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admission\StoreAdmissionPaymentRequest;
use App\Http\Requests\Web\Admission\UpdateAdmissionPaymentRequest;
use App\Models\AdmissionPayment;
use App\Services\Web\Admission\AdmissionPaymentService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdmissionPaymentController extends Controller
{
    public function __construct(
        protected AdmissionPaymentService $admissionPaymentService
    ) {}

        public function index(Request $request): View
    {
        $payments = $this->admissionPaymentService->paginate(
            $request->query('search'),
            $request->query('status')
        );

        $summary = $this->admissionPaymentService->summary();

        return view('admission_payments.index', compact('payments', 'summary'));
    }

    public function create(): View
    {
        $options = $this->admissionPaymentService->formOptions();

        return view('admission_payments.create', $options);
    }

    public function store(StoreAdmissionPaymentRequest $request): RedirectResponse
    {
        try {
            $payment = $this->admissionPaymentService->create($request->validated());
        } catch (DomainException $e) {
            return back()->withInput()->with('error', \App\Support\ErrorDetail::safe($e));
        }

        return redirect()
            ->route('admission-payments.show', $payment)
            ->with('success', 'Admission payment created successfully.');
    }

    public function show(AdmissionPayment $admissionPayment): View
    {
        try {
            $admissionPayment = $this->admissionPaymentService->getShowData($admissionPayment);
        } catch (DomainException $e) {
            abort(403, \App\Support\ErrorDetail::safe($e));
        }

        return view('admission_payments.show', compact('admissionPayment'));
    }

    public function edit(AdmissionPayment $admissionPayment): View
    {
        try {
            $this->admissionPaymentService->assertCanView($admissionPayment);
        } catch (DomainException $e) {
            abort(403, \App\Support\ErrorDetail::safe($e));
        }

        $options = $this->admissionPaymentService->formOptions($admissionPayment);

        return view(
            'admission_payments.edit',
            array_merge(['admissionPayment' => $admissionPayment], $options)
        );
    }

    public function update(
        UpdateAdmissionPaymentRequest $request,
        AdmissionPayment $admissionPayment
    ): RedirectResponse {
        try {
            $this->admissionPaymentService->update(
                $admissionPayment,
                $request->validated()
            );
        } catch (DomainException $e) {
            return back()->withInput()->with('error', \App\Support\ErrorDetail::safe($e));
        }

        return redirect()
            ->route('admission-payments.show', $admissionPayment)
            ->with('success', 'Admission payment updated successfully.');
    }

    public function destroy(AdmissionPayment $admissionPayment): RedirectResponse
    {
        try {
            $this->admissionPaymentService->delete($admissionPayment);
        } catch (DomainException $e) {
            return back()->with('error', \App\Support\ErrorDetail::safe($e));
        }

        return redirect()
            ->route('admission-payments.index')
            ->with('success', 'Admission payment deleted successfully.');
    }
}
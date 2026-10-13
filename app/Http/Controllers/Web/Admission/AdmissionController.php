<?php

namespace App\Http\Controllers\Web\Admission;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admission\StoreAdmissionRequest;
use App\Http\Requests\Web\Admission\UpdateAdmissionRequest;
use App\Models\Admission;
use App\Services\Web\Admission\AdmissionService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdmissionController extends Controller
{
    public function __construct(
        protected AdmissionService $admissionService
    ) {}

    public function index(Request $request): View
    {
        $admissions = $this->admissionService->paginate(
            $request->query('search')
        );

        return view('admissions.index', compact('admissions'));
    }

    public function create(): View
    {
        return view('admissions.create');
    }

    public function store(StoreAdmissionRequest $request): RedirectResponse
    {
        $this->admissionService->create($request->validated());

        return redirect()
            ->route('admissions.index')
            ->with('success', 'Admission created successfully.');
    }

    public function show(Admission $admission): View
    {
        $admission = $this->admissionService->getShowData($admission);

        return view('admissions.show', compact('admission'));
    }

    public function edit(Admission $admission): View
    {
        return view('admissions.edit', compact('admission'));
    }

    public function update(
        UpdateAdmissionRequest $request,
        Admission $admission
    ): RedirectResponse {
        $this->admissionService->update($admission, $request->validated());

        return redirect()
            ->route('admissions.index')
            ->with('success', 'Admission updated successfully.');
    }

    public function destroy(Admission $admission): RedirectResponse
    {
        try {
            $this->admissionService->delete($admission);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admissions.index')
            ->with('success', 'Admission deleted successfully.');
    }
}
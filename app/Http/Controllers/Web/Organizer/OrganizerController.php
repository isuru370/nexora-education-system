<?php

namespace App\Http\Controllers\Web\Organizer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Organizer\StoreOrganizerRequest;
use App\Http\Requests\Web\Organizer\UpdateOrganizerRequest;
use App\Models\Organizer;
use App\Services\Web\Organizer\OrganizerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class OrganizerController extends Controller
{
    protected OrganizerService $organizerService;

    public function __construct(OrganizerService $organizerService)
    {
        $this->organizerService = $organizerService;
    }

    public function index(Request $request): View
    {
        return view(
            'organizers.index',
            $this->organizerService->getIndexData($request)
        );
    }

    public function create(): View
    {
        return view(
            'organizers.create',
            $this->organizerService->getFormData()
        );
    }

    public function store(StoreOrganizerRequest $request): RedirectResponse
    {
        $this->organizerService->store($request->validated());

        return redirect()
            ->route('organizers.index')
            ->with('success', 'Organizer created successfully.');
    }

    public function show(Organizer $organizer): View
    {
        return view('organizers.show', [
            'organizer' => $this->organizerService->getDetails($organizer),
        ]);
    }

    public function edit(Organizer $organizer): View
    {
        return view(
            'organizers.edit',
            $this->organizerService->getFormData($organizer)
        );
    }

    public function update(
        UpdateOrganizerRequest $request,
        Organizer $organizer
    ): RedirectResponse {
        $this->organizerService->update(
            $organizer,
            $request->validated()
        );

        return redirect()
            ->route('organizers.index')
            ->with('success', 'Organizer updated successfully.');
    }

    public function destroy(Organizer $organizer): RedirectResponse
    {
        try {
            $this->organizerService->delete($organizer);

            return redirect()
                ->route('organizers.index')
                ->with('success', 'Organizer deleted successfully.');
        } catch (ValidationException $exception) {
            return back()->with(
                'error',
                collect($exception->errors())->flatten()->first()
            );
        }
    }

    public function toggleActive(Organizer $organizer): RedirectResponse
    {
        try {
            $this->organizerService->toggleActive($organizer);

            return back()->with('success', 'Organizer status updated.');
        } catch (ValidationException $exception) {
            return back()->with(
                'error',
                collect($exception->errors())->flatten()->first()
            );
        }
    }

    public function exportExcel(Request $request)
    {
        return $this->organizerService->exportExcel($request);
    }

    public function exportPdf(Request $request)
    {
        return $this->organizerService->exportPdf($request);
    }
}
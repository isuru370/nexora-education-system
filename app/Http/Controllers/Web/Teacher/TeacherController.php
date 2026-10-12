<?php

namespace App\Http\Controllers\Web\Teacher;

use App\Exports\Web\Teacher\TeachersExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Teacher\StoreTeacherRequest;
use App\Http\Requests\Web\Teacher\UpdateTeacherRequest;
use App\Services\Branch\BranchAccessService;
use App\Services\Web\Teacher\TeacherService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class TeacherController extends Controller
{
    public function __construct(
        protected TeacherService $teacherService,
        protected BranchAccessService $branchAccess
    ) {}

    /**
     * Display teacher list.
     */
    public function index(Request $request): View
    {
        $teachers = $this->teacherService->getTeachers(
            $request->only([
                'search',
                'is_active',
                'bank_branch_id',
                'branch_id',
            ])
        );

        $stats = $this->teacherService->getStats();

        return view(
            'teachers.index',
            compact('teachers', 'stats')
        );
    }

    /**
     * Show teacher create page.
     */
    public function create(): View
    {
        $bankBranches = $this->teacherService->getBankBranches();

        $branches = $this->branchAccess->getAccessibleBranches();

        $selectedBranchIds = [];

        return view(
            'teachers.create',
            compact(
                'bankBranches',
                'branches',
                'selectedBranchIds'
            )
        );
    }

    /**
     * Store a new teacher.
     */
    public function store(
        StoreTeacherRequest $request
    ): RedirectResponse {
        $data = $request->validated();

        $branchIds = $data['branch_ids'] ?? [];

        unset($data['branch_ids']);

        $this->teacherService->create(
            $data,
            $branchIds
        );

        return redirect()
            ->route('teachers.index')
            ->with(
                'success',
                'Teacher created successfully.'
            );
    }

    /**
     * Display teacher details.
     */
    public function show(int $teacher): View
    {
        $teacher = $this->teacherService->findForShow($teacher);

        return view(
            'teachers.show',
            compact('teacher')
        );
    }

    /**
     * Show teacher edit page.
     */
    public function edit(int $teacher): View
    {
        $teacher = $this->teacherService->findForEdit($teacher);

        $bankBranches = $this->teacherService->getBankBranches();

        $branches = $this->branchAccess->getAccessibleBranches();

        $selectedBranchIds = $teacher->branches
            ->pluck('id')
            ->toArray();

        return view(
            'teachers.edit',
            compact(
                'teacher',
                'bankBranches',
                'branches',
                'selectedBranchIds'
            )
        );
    }

    /**
     * Update teacher.
     */
    public function update(
        UpdateTeacherRequest $request,
        int $teacher
    ): RedirectResponse {
        $teacherModel = $this->teacherService->findForEdit($teacher);

        $data = $request->validated();

        $branchIds = $data['branch_ids'] ?? [];

        unset($data['branch_ids']);

        $this->teacherService->update(
            $teacherModel,
            $data,
            $branchIds
        );

        return redirect()
            ->route('teachers.index')
            ->with(
                'success',
                'Teacher updated successfully.'
            );
    }

    /**
     * Toggle teacher active status.
     */
    public function toggleActive(
        int $teacher
    ): RedirectResponse {
        $teacherModel = $this->teacherService->findForEdit($teacher);

        $teacherModel = $this->teacherService->toggleActive(
            $teacherModel
        );

        return back()->with(
            'success',
            $teacherModel->is_active
                ? 'Teacher activated successfully.'
                : 'Teacher deactivated successfully.'
        );
    }

    /**
     * Delete teacher.
     */
    public function destroy(
        int $teacher
    ): RedirectResponse {
        $teacherModel = $this->teacherService->findForEdit($teacher);

        $this->teacherService->delete($teacherModel);

        return redirect()
            ->route('teachers.index')
            ->with(
                'success',
                'Teacher deleted successfully.'
            );
    }

    /**
     * Export teachers to Excel.
     */
    public function exportExcel(Request $request)
    {
        $filters = $request->only([
            'branch_id',
        ]);

        $teachers = $this->teacherService->getTeachersForExport(
            $filters
        );

        return Excel::download(
            new TeachersExport($teachers),
            'teachers.xlsx'
        );
    }

    /**
     * Export teachers to PDF.
     */
    public function exportPdf(Request $request)
    {
        $filters = $request->only([
            'branch_id',
        ]);

        $teachers = $this->teacherService->getTeachersForExport(
            $filters
        );

        $pdf = Pdf::loadView(
            'pdf.teacher.index',
            compact('teachers')
        )->setPaper('a4', 'landscape');

        return $pdf->download('teachers.pdf');
    }

    /**
     * Display teacher login details.
     */
    public function loginDetails(int $teacher): View
    {
        $teacher = $this->teacherService->getLoginDetails($teacher);

        return view('teachers.login-details', compact('teacher'));
    }

    /**
     * Create teacher login account.
     */
    public function createLogin(
        Request $request,
        int $teacher
    ): RedirectResponse {
        $request->validate([
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        $teacherModel = $this->teacherService->findForEdit($teacher);

        $this->teacherService->createLogin(
            $teacherModel,
            $request->password
        );

        return back()->with(
            'success',
            'Login account created successfully.'
        );
    }

    /**
     * Reset teacher password.
     */
    public function resetPassword(
        Request $request,
        int $teacher
    ): RedirectResponse {
        $request->validate([
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        $teacherModel = $this->teacherService->findForEdit($teacher);

        $this->teacherService->resetPassword(
            $teacherModel,
            $request->password
        );

        return back()->with(
            'success',
            'Password reset successfully.'
        );
    }
}

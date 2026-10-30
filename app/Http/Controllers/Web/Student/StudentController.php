<?php

namespace App\Http\Controllers\Web\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Student\UpdateStudentRequest;
use App\Services\Web\Student\StudentService;
use App\Exports\Web\Student\StudentsExport;
use App\Models\Branch;
use App\Services\Web\Student\Pdf\StudentPdfService;
use App\Services\Web\Student\Pdf\StudentQrReportPdfService;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Throwable;

class StudentController extends Controller
{
    public function __construct(
        private StudentService $studentService,
        private StudentPdfService $studentPdfService,
        private StudentQrReportPdfService $studentQrReportPdfService
    ) {}

    /**
     * Display students.
     */
    public function index(Request $request)
    {
        $students = $this->studentService->getStudents(
            $request->query()
        );

        $grades = $this->studentService->getGrades();

        $statistics = $this->studentService->getStatistics();

        return view(
            'students.index',
            compact(
                'students',
                'grades',
                'statistics'
            )
        );
    }

    /**
     * Display student details.
     */
    public function show(int $student)
    {
        try {
            $student = $this->studentService
                ->findForShow($student);

            return view(
                'students.show',
                compact('student')
            );
        } catch (Throwable $e) {
            report($e);

            return back()
                ->with(
                    'error',
                    'Failed to load student details.'
                );
        }
    }

    /**
     * Show student edit form.
     */
    public function edit(int $student)
    {
        try {
            $branches = Branch::all();
            $student = $this->studentService
                ->findForEdit($student);

            $grades = $this->studentService
                ->getGrades();

            $admissionData = $this->studentService
                ->getAdmissionsForEdit($student);

            $admissions = $admissionData['admissions'];
            $latestAdmissionPayment =
                $admissionData['latestAdmissionPayment'];

            return view(
                'students.edit',
                compact(
                    'student',
                    'branches',
                    'grades',
                    'admissions',
                    'latestAdmissionPayment'
                )
            );
        } catch (Throwable $e) {
            report($e);

            return back()
                ->with(
                    'error',
                    'Failed to load student.'
                );
        }
    }

    /**
     * Update student.
     *
     * custom_id is NOT changed here.
     *
     * Permanent card QR -> custom_id is handled only
     * by StudentCardService.
     */
    public function update(
        UpdateStudentRequest $request,
        int $student
    ) {
        try {

            $studentModel = $this->studentService
                ->findForEdit($student);

            $this->studentService->update(
                $studentModel,
                $request->validated(),
                $request->file('image')
            );

            return redirect()
                ->route(
                    'students.show',
                    $studentModel
                )
                ->with(
                    'success',
                    'Student updated successfully.'
                );
        } catch (Throwable $e) {

            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    \App\Support\ErrorDetail::safe($e)
                );
        }
    }

    /**
     * Toggle student active status.
     */
    public function toggleActive(int $student)
    {
        try {

            $studentModel = $this->studentService
                ->findForEdit($student);

            $studentModel = $this->studentService
                ->toggleActive($studentModel);

            return back()->with(
                'success',
                $studentModel->is_active
                    ? 'Student activated successfully.'
                    : 'Student deactivated successfully.'
            );
        } catch (Throwable $e) {

            report($e);

            return back()->with(
                'error',
                'Student status update failed.'
            );
        }
    }

    /**
     * Soft delete student.
     */
    public function destroy(int $student)
    {
        try {

            $studentModel = $this->studentService
                ->findForEdit($student);

            $this->studentService
                ->delete($studentModel);

            return redirect()
                ->route('students.index')
                ->with(
                    'success',
                    'Student deleted successfully.'
                );
        } catch (Throwable $e) {

            report($e);

            return back()->with(
                'error',
                'Student delete failed.'
            );
        }
    }

    /**
     * Search active students.
     */
    public function search(Request $request)
    {
        $search = trim(
            $request->input('q', '')
        );

        return $this->studentService
            ->search($search)
            ->map(function ($student) {
                return [
                    'id' => $student->id,
                    'text' =>
                    "{$student->custom_id} - {$student->full_name}",
                ];
            });
    }

    public function exportExcel()
    {
        return Excel::download(
            app(StudentsExport::class),
            'students.xlsx'
        );
    }

    public function exportPdf()
    {
        try {
            return $this->studentPdfService->download();
        } catch (Throwable $e) {
            report($e);

            return back()->with(
                'error',
                'Failed to generate students PDF.'
            );
        }
    }

    public function downloadQrDetailsPdf()
    {
        try {
            return $this->studentQrReportPdfService->download();
        } catch (Throwable $e) {
            report($e);

            return back()->with(
                'error',
                'Failed to generate student QR report.'
            );
        }
    }
}

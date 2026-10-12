<?php

namespace App\Http\Controllers\Web\GenerateStudentId;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\GenerateStudentId\IndexStudentIdCardRequest;
use App\Models\Student;
use App\Services\Web\GenerateStudentId\GenerateStudentIdService;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateStudentIdController extends Controller
{
    public function __construct(
        private GenerateStudentIdService $service
    ) {}

    /**
     * Display the student ID card generation page.
     */
    public function index(IndexStudentIdCardRequest $request)
    {
        try {
            $validated = $request->validated();

            $students = $this->service->getIndexData(
                $validated['search'] ?? null
            );

            return view('generate-student-id.index', compact('students'));

        } catch (Throwable $e) {
            Log::error('Student ID Card Fetch Error', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return back()->with('error', 'Something went wrong.');
        }
    }

    /**
     * Print a student's ID card.
     */
    public function print(Student $student)
    {
        try {
            return view('generate-student-id.print', compact('student'));

        } catch (Throwable $e) {
            Log::error('Failed to load print view.', [
                'student_id' => $student->id,
                'message'    => $e->getMessage(),
            ]);

            return back()->with('error', 'Unable to print ID card.');
        }
    }
}
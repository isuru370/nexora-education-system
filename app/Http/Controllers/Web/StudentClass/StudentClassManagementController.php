<?php

namespace App\Http\Controllers\Web\StudentClass;

use App\Http\Controllers\Controller;
use App\Models\ClassCategoryFee;
use App\Models\ClassCategoryFeeOption;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\StudentClassEnrollment;
use App\Services\Branch\BranchAccessService;
use App\Services\Web\StudentClass\StudentClassManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Throwable;

class StudentClassManagementController extends Controller
{
    public function __construct(
        protected StudentClassManagementService $studentClassManagementService,
        protected BranchAccessService $branchAccessService,
    ) {}


    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    /**
     * Student Class Management main page.
     */
    public function index()
    {
        return view('student-class-management.index');
    }


    /*
    |--------------------------------------------------------------------------
    | SEARCH STUDENT CLASSES
    |--------------------------------------------------------------------------
    */

    /**
     * Search student by:
     * - Full Name / Initial Name
     * - Mobile / WhatsApp / Guardian Mobile
     * - Custom ID (permanent QR)
     * - Temporary QR code
     *
     * Branch-scoped.
     */
    public function searchStudentClasses(Request $request)
    {
        try {
            $search = trim((string) $request->input('search'));

            /*
            |--------------------------------------------------------------------------
            | No Search
            |--------------------------------------------------------------------------
            */
            if ($request->isMethod('get') && $search === '') {
                return redirect()
                    ->route('student-class-management.index')
                    ->with(
                        'info',
                        'Please enter Student Name, Mobile Number, Custom ID or QR code'
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | Validation
            |--------------------------------------------------------------------------
            */
            $validator = Validator::make(
                ['search' => $search],
                ['search' => 'required|string|max:255']
            );

            if ($validator->fails()) {
                return redirect()
                    ->route('student-class-management.index')
                    ->withErrors($validator)
                    ->withInput();
            }

            /*
            |--------------------------------------------------------------------------
            | Branch Scope
            |--------------------------------------------------------------------------
            */
            /** @var Collection<int, int> $accessibleBranchIds */
            $accessibleBranchIds = $this->branchAccessService->getBranchIds();

            /*
            |--------------------------------------------------------------------------
            | Search Students
            |--------------------------------------------------------------------------
            */
            $students = Student::query()
                ->where(function ($query) use ($search) {
                    $query
                        ->where('full_name', 'LIKE', "%{$search}%")
                        ->orWhere('initial_name', 'LIKE', "%{$search}%")
                        ->orWhere('custom_id', 'LIKE', "%{$search}%")
                        ->orWhere('temporary_qr_code', 'LIKE', "%{$search}%")
                        ->orWhere('mobile', 'LIKE', "%{$search}%")
                        ->orWhere('whatsapp_mobile', 'LIKE', "%{$search}%")
                        ->orWhere('guardian_mobile', 'LIKE', "%{$search}%");
                })
                ->whereHas('enrollments', function ($q) use ($accessibleBranchIds) {
                    $q->whereIn('branch_id', $accessibleBranchIds);
                })
                ->with([
                    'enrollments' => function ($query) use ($accessibleBranchIds) {
                        $query
                            ->whereIn('branch_id', $accessibleBranchIds)
                            ->with([
                                'branch',
                                'studentClass.grade',
                                'studentClass.teacher',
                                'classCategoryFee.category',
                                'classCategoryFeeOption',
                            ]);
                    },
                ])
                ->orderBy('initial_name')
                ->limit(50)
                ->get();

            /*
            |--------------------------------------------------------------------------
            | No Student Found
            |--------------------------------------------------------------------------
            */
            if ($students->isEmpty()) {
                return redirect()
                    ->route('student-class-management.index')
                    ->with('error', 'No student found for: ' . $search)
                    ->withInput();
            }

            /*
            |--------------------------------------------------------------------------
            | One Student Found
            |--------------------------------------------------------------------------
            */
            if ($students->count() === 1) {
                $student = $students->first();

                $classes = $this->mapEnrollmentClasses($student->enrollments);

                return view(
                    'student-class-management.show',
                    compact('student', 'classes')
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Multiple Students Found
            |--------------------------------------------------------------------------
            */
            return view(
                'student-class-management.search-results',
                compact('students', 'search')
            );
        } catch (Throwable $e) {
            return redirect()
                ->route('student-class-management.index')
                ->with('error', 'An error occurred: ' . \App\Support\ErrorDetail::safe($e))
                ->withInput();
        }
    }


    /*
    |--------------------------------------------------------------------------
    | QR SCAN
    |--------------------------------------------------------------------------
    */

    /**
     * Handle QR code scan — student lookup.
     *
     * QR code එකේ URL එක:
     *   /admin/student-class-management/scan/{qrCode}
     *
     * QR scan කරාම මේ route එකට redirect වෙනවා.
     * Student හොයාගෙන, ඒ student ගේ classes පෙන්නනවා.
     */
    public function scanQr(string $qrCode)
    {
        try {
            /** @var Collection<int, int> $accessibleBranchIds */
            $accessibleBranchIds = $this->branchAccessService->getBranchIds();

            /*
            |--------------------------------------------------------------------------
            | Find Student by QR (branch-scoped)
            |--------------------------------------------------------------------------
            */
            $student = Student::query()
                ->where(function ($query) use ($qrCode) {
                    $query
                        ->where('custom_id', $qrCode)
                        ->orWhere('temporary_qr_code', $qrCode);
                })
                ->whereHas('enrollments', function ($q) use ($accessibleBranchIds) {
                    $q->whereIn('branch_id', $accessibleBranchIds);
                })
                ->with([
                    'enrollments' => function ($query) use ($accessibleBranchIds) {
                        $query
                            ->whereIn('branch_id', $accessibleBranchIds)
                            ->with([
                                'branch',
                                'studentClass.grade',
                                'studentClass.teacher',
                                'classCategoryFee.category',
                                'classCategoryFeeOption',
                            ]);
                    },
                ])
                ->first();

            if (!$student) {
                return redirect()
                    ->route('student-class-management.index')
                    ->with('error', 'Student not found for QR: ' . $qrCode);
            }

            /*
            |--------------------------------------------------------------------------
            | Redirect to student classes
            |--------------------------------------------------------------------------
            */
            $classes = $this->mapEnrollmentClasses($student->enrollments);

            return view(
                'student-class-management.show',
                compact('student', 'classes')
            );
        } catch (Throwable $e) {
            return redirect()
                ->route('student-class-management.index')
                ->with('error', 'QR scan error: ' . \App\Support\ErrorDetail::safe($e));
        }
    }


    /*
    |--------------------------------------------------------------------------
    | PAYMENT
    |--------------------------------------------------------------------------
    */

    /**
     * Get payment details for a specific
     * Student + Class + Enrollment.
     */
    public function payment(
        int $studentId,
        int $studentClassId,
        int $enrollmentId
    ) {
        try {
            $data = $this->studentClassManagementService
                ->getPaymentDetails(
                    $studentId,
                    $studentClassId,
                    $enrollmentId
                );

            return view(
                'student-class-management.payment',
                compact('data')
            );
        } catch (Throwable $e) {
            return redirect()
                ->back()
                ->with('error', 'Unable to load payment details: ' . \App\Support\ErrorDetail::safe($e));
        }
    }


    /*
    |--------------------------------------------------------------------------
    | ATTENDANCE
    |--------------------------------------------------------------------------
    */

    /**
     * Get attendance details for a specific
     * Student + Class + Enrollment.
     */
    public function attendance(
        int $studentId,
        int $studentClassId,
        int $enrollmentId
    ) {
        try {
            $data = $this->studentClassManagementService
                ->getAttendanceDetails(
                    $studentId,
                    $studentClassId,
                    $enrollmentId
                );

            return view(
                'student-class-management.attendance',
                compact('data')
            );
        } catch (Throwable $e) {
            return redirect()
                ->back()
                ->with('error', 'Unable to load attendance details: ' . \App\Support\ErrorDetail::safe($e));
        }
    }


    /*
    |--------------------------------------------------------------------------
    | TUTE
    |--------------------------------------------------------------------------
    */

    /**
     * Get tute details for a specific
     * Student + Class + Enrollment + Category Fee.
     */
    public function tute(
        int $studentId,
        int $studentClassId,
        int $enrollmentId
    ) {
        try {
            $data = $this->studentClassManagementService
                ->getTuteDetails(
                    $studentId,
                    $studentClassId,
                    $enrollmentId
                );

            return view(
                'student-class-management.tute',
                compact('data')
            );
        } catch (Throwable $e) {
            return redirect()
                ->back()
                ->with('error', 'Unable to load tute details: ' . \App\Support\ErrorDetail::safe($e));
        }
    }


    /*
    |--------------------------------------------------------------------------
    | TOGGLE SINGLE CLASS STATUS
    |--------------------------------------------------------------------------
    */

    /**
     * Toggle a single enrollment's active status (branch-scoped).
     */
    public function toggleClassStatus(
        Request $request,
        int $enrollmentId
    ) {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'is_active' => 'required|boolean',
                    'left_at' => 'nullable|date',
                    'note' => 'nullable|string|max:500',
                ]
            );

            if ($validator->fails()) {
                return redirect()
                    ->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            /** @var Collection<int, int> $accessibleBranchIds */
            $accessibleBranchIds = $this->branchAccessService->getBranchIds();

            $enrollment = StudentClassEnrollment::with(['student', 'studentClass'])
                ->whereIn('branch_id', $accessibleBranchIds)
                ->find($enrollmentId);

            if (!$enrollment) {
                return redirect()
                    ->back()
                    ->with('error', 'Class enrollment not found');
            }

            $studentId = $enrollment->student_id;

            $enrollment->is_active = $request->boolean('is_active');

            if (!$request->boolean('is_active')) {
                $enrollment->left_at = $request->left_at ?? now();
            } else {
                $enrollment->left_at = null;
            }

            if ($request->has('note')) {
                $enrollment->note = $request->note;
            }

            $enrollment->save();

            $message = $request->boolean('is_active')
                ? 'Class activated successfully'
                : 'Class deactivated successfully';

            return redirect()
                ->route('student-class-management.show', $studentId)
                ->with('success', $message);
        } catch (Throwable $e) {
            return redirect()
                ->back()
                ->with('error', 'An error occurred while updating class status: ' . \App\Support\ErrorDetail::safe($e));
        }
    }


    /*
    |--------------------------------------------------------------------------
    | DEACTIVATE / ACTIVATE
    |--------------------------------------------------------------------------
    */

    /**
     * Deactivate a single enrollment (branch-scoped).
     */
    public function deactivateClass(int $enrollmentId)
    {
        try {
            /** @var Collection<int, int> $accessibleBranchIds */
            $accessibleBranchIds = $this->branchAccessService->getBranchIds();

            $enrollment = StudentClassEnrollment::whereIn('branch_id', $accessibleBranchIds)
                ->find($enrollmentId);

            if (!$enrollment) {
                return redirect()
                    ->back()
                    ->with('error', 'Enrollment not found');
            }

            $enrollment->is_active = false;
            $enrollment->left_at = now();
            $enrollment->save();

            return redirect()
                ->route('student-class-management.show', $enrollment->student_id)
                ->with('success', 'Class deactivated successfully');
        } catch (Throwable $e) {
            return redirect()
                ->back()
                ->with('error', 'An error occurred: ' . \App\Support\ErrorDetail::safe($e));
        }
    }


    /**
     * Activate a single enrollment (branch-scoped).
     */
    public function activateClass(int $enrollmentId)
    {
        try {
            /** @var Collection<int, int> $accessibleBranchIds */
            $accessibleBranchIds = $this->branchAccessService->getBranchIds();

            $enrollment = StudentClassEnrollment::whereIn('branch_id', $accessibleBranchIds)
                ->find($enrollmentId);

            if (!$enrollment) {
                return redirect()
                    ->back()
                    ->with('error', 'Enrollment not found');
            }

            $enrollment->is_active = true;
            $enrollment->left_at = null;
            $enrollment->save();

            return redirect()
                ->route('student-class-management.show', $enrollment->student_id)
                ->with('success', 'Class activated successfully');
        } catch (Throwable $e) {
            return redirect()
                ->back()
                ->with('error', 'An error occurred: ' . \App\Support\ErrorDetail::safe($e));
        }
    }


    /*
    |--------------------------------------------------------------------------
    | TOGGLE ALL CLASSES STATUS
    |--------------------------------------------------------------------------
    */

    /**
     * Toggle all classes for a student (branch-scoped).
     */
    public function toggleAllClassesStatus(
        Request $request,
        int $studentId
    ) {
        try {
            $validator = Validator::make(
                $request->all(),
                ['is_active' => 'required|boolean']
            );

            if ($validator->fails()) {
                return redirect()
                    ->route('student-class-management.show', $studentId)
                    ->withErrors($validator);
            }

            /** @var Collection<int, int> $accessibleBranchIds */
            $accessibleBranchIds = $this->branchAccessService->getBranchIds();

            $student = Student::find($studentId);

            if (!$student) {
                return redirect()
                    ->route('student-class-management.show', $studentId)
                    ->with('error', 'Student not found');
            }

            $isActive = $request->boolean('is_active');

            $updated = StudentClassEnrollment::where('student_id', $studentId)
                ->whereIn('branch_id', $accessibleBranchIds)
                ->update([
                    'is_active' => $isActive,
                    'left_at' => $isActive ? null : now(),
                ]);

            $message = $isActive
                ? 'All classes activated successfully'
                : 'All classes deactivated successfully';

            return redirect()
                ->route('student-class-management.show', $studentId)
                ->with('success', $message . ' (' . $updated . ' classes updated)');
        } catch (Throwable $e) {
            return redirect()
                ->route('student-class-management.show', $studentId)
                ->with('error', 'An error occurred: ' . \App\Support\ErrorDetail::safe($e));
        }
    }


    /*
    |--------------------------------------------------------------------------
    | ASSIGN CLASS
    |--------------------------------------------------------------------------
    */

    /**
     * Show assign class form (branch-scoped).
     */
    public function showAssignClassForm($studentId = null)
    {
        /** @var Collection<int, int> $accessibleBranchIds */
        $accessibleBranchIds = $this->branchAccessService->getBranchIds();

        $student = null;

        if ($studentId) {
            $student = Student::find($studentId);
        }

        $classes = StudentClass::query()
            ->where('is_active', true)
            ->whereIn('branch_id', $accessibleBranchIds)   // ✅ Branch scope
            ->with([
                'branch',
                'teacher',
                'subject',
                'grade',
                'categoryFees' => function ($query) {
                    $query
                        ->where('is_active', true)
                        ->with([
                            'category',
                            'activeFeeOptions' => function ($optionQuery) {
                                $optionQuery
                                    ->where('is_active', true)
                                    ->orderByDesc('is_default')
                                    ->orderBy('id');
                            },
                        ]);
                },
            ])
            ->orderBy('class_name')
            ->get();

        $students = Student::query()
            ->where('is_active', true)
            ->orderBy('initial_name')
            ->get();

        return view(
            'student-class-management.assign',
            compact('student', 'classes', 'students')
        );
    }


    /**
     * Assign a new class to a student (branch-aware).
     *
     * Branch එක class එකෙන් ගන්නවා — user input එකෙන් නෙවෙයි.
     */
    public function assignClassToStudent(Request $request)
    {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'student_id' => ['required', 'exists:students,id'],
                    'student_class_id' => ['required', 'exists:student_classes,id'],
                    'class_category_fee_id' => ['required', 'exists:class_category_fees,id'],
                    'class_category_fee_option_id' => ['required', 'exists:class_category_fee_options,id'],
                    'is_active' => ['boolean'],
                    'is_free_card' => ['boolean'],
                    'enrolled_at' => ['nullable', 'date'],
                    'note' => ['nullable', 'string', 'max:500'],
                ]
            );

            if ($validator->fails()) {
                return redirect()
                    ->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            /** @var Collection<int, int> $accessibleBranchIds */
            $accessibleBranchIds = $this->branchAccessService->getBranchIds();

            /*
            |--------------------------------------------------------------------------
            | Find Student
            |--------------------------------------------------------------------------
            */
            $student = Student::find($request->student_id);

            if (!$student) {
                return redirect()->back()
                    ->with('error', 'Student not found.')
                    ->withInput();
            }

            if (!$student->is_active) {
                return redirect()->back()
                    ->with('error', 'This student is inactive.')
                    ->withInput();
            }

            /*
            |--------------------------------------------------------------------------
            | Find Student Class (branch-scoped)
            |--------------------------------------------------------------------------
            */
            $studentClass = StudentClass::query()
                ->where('id', $request->student_class_id)
                ->whereIn('branch_id', $accessibleBranchIds)   // ✅ Branch scope
                ->first();

            if (!$studentClass) {
                return redirect()->back()
                    ->with('error', 'Student class not found or you do not have access.')
                    ->withInput();
            }

            if (!$studentClass->is_active) {
                return redirect()->back()
                    ->with('error', 'This class is inactive.')
                    ->withInput();
            }

            /** ✅ Branch ID — class එකෙන් ගන්නවා (user input එකෙන් නෙවෙයි) */
            $branchId = (int) $studentClass->branch_id;

            /*
            |--------------------------------------------------------------------------
            | Validate Category Fee
            |--------------------------------------------------------------------------
            */
            $classCategoryFee = ClassCategoryFee::query()
                ->where('id', $request->class_category_fee_id)
                ->where('student_class_id', $request->student_class_id)
                ->where('is_active', true)
                ->first();

            if (!$classCategoryFee) {
                return redirect()->back()
                    ->with('error', 'Invalid or inactive class category fee for this class.')
                    ->withInput();
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Fee Option
            |--------------------------------------------------------------------------
            */
            $feeOption = ClassCategoryFeeOption::query()
                ->where('id', $request->class_category_fee_option_id)
                ->where('class_category_fee_id', $classCategoryFee->id)
                ->where('is_active', true)
                ->first();

            if (!$feeOption) {
                return redirect()->back()
                    ->with('error', 'Invalid or inactive fee option for this category.')
                    ->withInput();
            }

            /*
            |--------------------------------------------------------------------------
            | Check Existing Enrollment
            |--------------------------------------------------------------------------
            */
            $existing = StudentClassEnrollment::query()
                ->where('student_id', $request->student_id)
                ->where('student_class_id', $request->student_class_id)
                ->where('class_category_fee_id', $request->class_category_fee_id)
                ->where('branch_id', $branchId)
                ->where('is_active', true)
                ->first();

            if ($existing) {
                /*
                | Same fee option → already enrolled
                */
                if (
                    (int) $existing->class_category_fee_option_id ===
                    (int) $request->class_category_fee_option_id
                ) {
                    return redirect()->back()
                        ->with(
                            'error',
                            'This student is already enrolled in this category with the selected fee option.'
                        )
                        ->withInput();
                }

                /*
                | Different fee option → update
                */
                $existing->update([
                    'class_category_fee_option_id' => $request->class_category_fee_option_id,
                    'is_active' => $request->boolean('is_active', true),
                    'is_free_card' => $request->boolean('is_free_card', false),
                    'enrolled_at' => $request->enrolled_at
                        ? $request->enrolled_at
                        : $existing->enrolled_at,
                    'note' => $request->note,
                ]);

                return redirect()
                    ->route('student-class-management.show', $request->student_id)
                    ->with('success', 'Student class fee option updated successfully.');
            }

            /*
            |--------------------------------------------------------------------------
            | Create Enrollment (with branch_id)
            |--------------------------------------------------------------------------
            */
            StudentClassEnrollment::create([
                'branch_id' => $branchId,   // ✅ Class එකෙන් ගන්න branch_id
                'student_id' => $request->student_id,
                'student_class_id' => $request->student_class_id,
                'class_category_fee_id' => $request->class_category_fee_id,
                'class_category_fee_option_id' => $request->class_category_fee_option_id,
                'is_active' => $request->boolean('is_active', true),
                'is_free_card' => $request->boolean('is_free_card', false),
                'enrolled_at' => $request->enrolled_at
                    ? $request->enrolled_at
                    : now()->toDateString(),
                'note' => $request->note,
            ]);

            return redirect()
                ->route('student-class-management.show', $request->student_id)
                ->with('success', 'Class assigned to student successfully.');
        } catch (Throwable $e) {
            return redirect()->back()
                ->with('error', 'An error occurred: ' . \App\Support\ErrorDetail::safe($e))
                ->withInput();
        }
    }


    /*
    |--------------------------------------------------------------------------
    | SHOW STUDENT CLASSES
    |--------------------------------------------------------------------------
    */

    /**
     * Display student classes (branch-scoped).
     */
    public function showStudentClasses(int $studentId)
    {
        try {
            /** @var Collection<int, int> $accessibleBranchIds */
            $accessibleBranchIds = $this->branchAccessService->getBranchIds();

            $student = Student::query()
                ->with([
                    'enrollments' => function ($query) use ($accessibleBranchIds) {
                        $query
                            ->whereIn('branch_id', $accessibleBranchIds)
                            ->with([
                                'branch',
                                'studentClass.grade',
                                'studentClass.teacher',
                                'classCategoryFee.category',
                                'classCategoryFeeOption',
                            ]);
                    },
                ])
                ->find($studentId);

            if (!$student) {
                return redirect()
                    ->route('student-class-management.index')
                    ->with('error', 'Student not found');
            }

            $classes = $this->mapEnrollmentClasses($student->enrollments);

            return view(
                'student-class-management.show',
                compact('student', 'classes')
            );
        } catch (Throwable $e) {
            return redirect()
                ->route('student-class-management.index')
                ->with('error', 'Unable to load student classes: ' . \App\Support\ErrorDetail::safe($e));
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CATEGORY FEES (AJAX)
    |--------------------------------------------------------------------------
    */

    /**
     * Get category fees for a class (AJAX, branch-scoped).
     */
    public function getCategoryFees(int $classId)
    {
        try {
            /** @var Collection<int, int> $accessibleBranchIds */
            $accessibleBranchIds = $this->branchAccessService->getBranchIds();

            /** ✅ Class එකට access තියෙනවද බලන්න */
            $studentClass = StudentClass::query()
                ->where('id', $classId)
                ->whereIn('branch_id', $accessibleBranchIds)
                ->first();

            if (!$studentClass) {
                return response()->json([
                    'success' => false,
                    'message' => 'Class not found or you do not have access.',
                    'data' => [],
                ], 404);
            }

            $fees = ClassCategoryFee::query()
                ->where('student_class_id', $classId)
                ->where('is_active', true)
                ->with([
                    'category',
                    'activeFeeOptions' => function ($query) {
                        $query
                            ->where('is_active', true)
                            ->orderByDesc('is_default')
                            ->orderBy('id');
                    },
                ])
                ->get()
                ->map(function ($fee) {
                    return [
                        'id' => $fee->id,
                        'category_id' => $fee->class_category_id,
                        'category_name' => $fee->category
                            ? $fee->category->category_name
                            : 'N/A',
                        'fee_options' => $fee->activeFeeOptions
                            ->map(function ($option) {
                                return [
                                    'id' => $option->id,
                                    'label' => $option->label,
                                    'fee' => (float) $option->fee,
                                    'is_default' => (bool) $option->is_default,
                                    'is_active' => (bool) $option->is_active,
                                    'note' => $option->note,
                                ];
                            })
                            ->values()
                            ->toArray(),
                    ];
                })
                ->values()
                ->toArray();

            return response()->json([
                'success' => true,
                'data' => $fees,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading category fees',
                'error' => \App\Support\ErrorDetail::safe($e),
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | HELPER — MAP ENROLLMENT CLASSES
    |--------------------------------------------------------------------------
    */

    /**
     * Map enrollments to array format for views.
     *
     * @param Collection<int, StudentClassEnrollment> $enrollments
     * @return Collection<int, array<string, mixed>>
     */
    protected function mapEnrollmentClasses(Collection $enrollments): Collection
    {
        return $enrollments->map(function ($enrollment) {
            $studentClass = $enrollment->studentClass;
            $grade = $studentClass?->grade;
            $teacher = $studentClass?->teacher;
            $categoryFee = $enrollment->classCategoryFee;
            $category = $categoryFee?->category;
            $feeOption = $enrollment->classCategoryFeeOption;

            /*
            | Selected Fee
            */
            $selectedFee = 0;

            if (!$enrollment->is_free_card && $feeOption) {
                $selectedFee = (float) $feeOption->fee;
            }

            return [
                'enrollment_id' => $enrollment->id,
                'branch_id' => $enrollment->branch_id,               // ✅
                'branch_name' => $enrollment->branch?->name ?? 'N/A', // ✅
                'class_id' => $enrollment->student_class_id,
                'class_category_fee_id' => $enrollment->class_category_fee_id,
                'class_category_fee_option_id' => $enrollment->class_category_fee_option_id,

                /* Class */
                'class_name' => $studentClass?->class_name ?? 'N/A',
                'class_type' => $studentClass?->class_type ?? 'N/A',
                'medium' => $studentClass?->medium ?? 'N/A',

                /* Grade */
                'grade_name' => $grade?->grade_name ?? 'N/A',

                /* Teacher */
                'teacher_initials' => $teacher?->initials ?? 'N/A',

                /* Category */
                'category_name' => $category?->category_name ?? 'N/A',

                /* Fee Option */
                'fee_option' => $feeOption ? [
                    'id' => $feeOption->id,
                    'label' => $feeOption->label,
                    'fee' => (float) $feeOption->fee,
                    'is_default' => (bool) $feeOption->is_default,
                    'is_active' => (bool) $feeOption->is_active,
                    'note' => $feeOption->note,
                ] : null,

                /* Fee */
                'fee' => $selectedFee,
                'final_fee' => $selectedFee,

                /* Status */
                'is_active' => (bool) $enrollment->is_active,
                'is_free_card' => (bool) $enrollment->is_free_card,
                'payment_status' => $enrollment->payment_status,

                /* Dates */
                'enrolled_at' => $enrollment->enrolled_at?->format('Y-m-d'),
                'left_at' => $enrollment->left_at?->format('Y-m-d'),

                /* Note */
                'note' => $enrollment->note,
            ];
        });
    }
}

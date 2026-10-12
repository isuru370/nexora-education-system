<?php

namespace App\Http\Controllers\Admin;

use App\Exports\Teacher\TeacherSalaryReportExport;
use App\Exports\MonthlyReport\TeacherWithStudentPaymentDateReportExport;
use App\Exports\MonthlyReport\TeacherWithStudentPaymentReportExport;
use App\Http\Controllers\Controller;
use App\Models\PaymentSplitSnapshot;
use App\Models\StudentClass;
use App\Models\Teacher;
use App\Models\TeacherPayment;
use App\Models\TeacherSalary;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class MonthlyReportController extends Controller
{
    public function index(Request $request)
    {
        $teachers = Teacher::select(
            'id',
            'custom_id',
            'initials'
        )->get();

        return view('admin.monthly-report.index', compact('teachers'));
    }

    private function teacherSalaryReportData(int $year, int $month)
    {
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate   = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $teachers = Teacher::get();

        return $teachers->map(function ($teacher) use ($year, $month, $startDate, $endDate) {

            $grossIncome = PaymentSplitSnapshot::where('teacher_id', $teacher->id)
                ->whereBetween('payment_date', [$startDate, $endDate])
                ->sum('teacher_amount');

            $advanceDeduction = TeacherPayment::where('teacher_id', $teacher->id)
                ->whereBetween('payment_date', [$startDate, $endDate])
                ->sum('amount');

            $salary = TeacherSalary::where('teacher_id', $teacher->id)
                ->where('salary_year', $year)
                ->where('salary_month', $month)
                ->first();

            return [
                'teacher_id'        => $teacher->id,
                'custom_id'         => $teacher->custom_id,
                'initials'          => $teacher->initials,
                'gross_income'      => (float) $grossIncome,
                'advance_deduction' => (float) $advanceDeduction,
                'salary_paid_status' => optional($salary)->status ?: 'unpaid',
                'salary'            => $salary,
            ];
        })->toArray();
    }

    public function TeacherSalaryReportExcel(Request $request)
    {
        try {
            $year  = (int) $request->get('year', now()->year);
            $month = (int) $request->get('month', now()->month);

            $report = $this->teacherSalaryReportData($year, $month);

            return Excel::download(
                new TeacherSalaryReportExport($report, $year, $month),
                "teacher-salary-report-{$year}-{$month}.xlsx"
            );
        } catch (\Throwable $e) {
            Log::error('Teacher Salary Excel generation failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate teacher salary excel.',
            ], 500);
        }
    }

    public function TeacherSalaryReportPdf(Request $request)
    {
        try {
            $year  = (int) $request->get('year', now()->year);
            $month = (int) $request->get('month', now()->month);

            $report = $this->teacherSalaryReportData($year, $month);

            $pdf = Pdf::loadView('admin.pdf.teacher.teacher_salary_report', [
                'report' => $report,
                'year'   => $year,
                'month'  => $month,
            ])->setPaper('a4', 'landscape');

            return $pdf->download("teacher-salary-report-{$year}-{$month}.pdf");
        } catch (\Throwable $e) {
            Log::error('Teacher Salary PDF generation failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    public function TeacherWithStudentPaymentReport(Request $request)
    {
        try {
            $validated = $request->validate([
                'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
                'year' => ['required', 'integer', 'min:2000', 'max:2100'],
                'month' => ['required', 'integer', 'between:1,12'],
            ]);

            $payload = $this->buildTeacherWithStudentPaymentReportData(
                (int) $validated['teacher_id'],
                (int) $validated['year'],
                (int) $validated['month']
            );

            return response()->json([
                'success' => true,
                'data' => $payload,
            ]);
        } catch (\Throwable $e) {
            Log::error('Teacher with student payment report generation failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    public function TeacherWithStudentPaymentReportExcel(Request $request)
    {
        try {
            $validated = $request->validate([
                'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
                'year' => ['required', 'integer', 'min:2000', 'max:2100'],
                'month' => ['required', 'integer', 'between:1,12'],
            ]);

            $teacherId = (int) $validated['teacher_id'];
            $year = (int) $validated['year'];
            $month = (int) $validated['month'];

            $payload = $this->buildTeacherWithStudentPaymentReportData($teacherId, $year, $month);

            return Excel::download(
                new TeacherWithStudentPaymentReportExport($payload, $year, $month),
                "teacher-with-student-payment-report-{$teacherId}-{$year}-{$month}.xlsx"
            );
        } catch (\Throwable $e) {
            Log::error('Teacher with student payment excel report generation failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    public function TeacherWithStudentPaymentReportPdf(Request $request)
    {
        try {
            $validated = $request->validate([
                'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
                'year' => ['required', 'integer', 'min:2000', 'max:2100'],
                'month' => ['required', 'integer', 'between:1,12'],
            ]);

            $teacherId = (int) $validated['teacher_id'];
            $year = (int) $validated['year'];
            $month = (int) $validated['month'];

            $payload = $this->buildTeacherWithStudentPaymentReportData($teacherId, $year, $month);

            $pdf = Pdf::loadView('admin.pdf.teacher.teacher_with_student_payment_report', [
                'report' => $payload,
                'year' => $year,
                'month' => $month,
            ])->setPaper('a4', 'landscape');

            return $pdf->download("teacher-with-student-payment-report-{$teacherId}-{$year}-{$month}.pdf");
        } catch (\Throwable $e) {
            Log::error('Teacher with student payment pdf report generation failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    private function buildTeacherWithStudentPaymentReportData(int $teacherId, int $year, int $month): array
    {
        $teacher = Teacher::select('id', 'custom_id', 'initials')
            ->findOrFail($teacherId);

        $classes = StudentClass::select('id', 'class_name', 'grade_id', 'teacher_id')
            ->where('teacher_id', $teacherId)
            ->with([
                'grade:id,grade_name',
                'categoryFees' => function ($query) {
                    $query->select('id', 'student_class_id', 'class_category_id')
                        ->with([
                            'category:id,category_name',
                        ])
                        ->orderBy('id');
                },
                'enrollments' => function ($query) use ($year, $month) {
                    $query->select(
                        'id',
                        'student_id',
                        'student_class_id',
                        'class_category_fee_id',
                        'class_category_fee_option_id',
                        'is_active',
                        'is_free_card'
                    )
                        ->where('is_active', 1)
                        ->with([
                            'student:id,permanent_qr_active,custom_id,temporary_qr_code,initial_name,guardian_mobile',
                            'payments' => function ($paymentQuery) use ($year, $month) {
                                $paymentQuery->select(
                                    'id',
                                    'student_id',
                                    'student_class_enrollment_id',
                                    'amount',
                                    'status',
                                    'payment_month'
                                )
                                    ->where('status', 'completed')
                                    ->whereYear('payment_month', $year)
                                    ->whereMonth('payment_month', $month);
                            },
                            'classCategoryFee:id,student_class_id,class_category_id',
                            'classCategoryFee.category:id,category_name',
                            'classCategoryFeeOption:id,class_category_fee_id,label,fee',
                        ]);
                },
            ])
            ->orderBy('class_name')
            ->get();

        $reportClasses = [];
        $overallTotalStudents = 0;
        $overallPaidStudents = 0;
        $overallUnpaidStudents = 0;
        $overallFreeCardStudents = 0;

        foreach ($classes as $class) {
            $classCategories = [];

            foreach ($class->categoryFees as $fee) {
                $categoryEnrollments = $class->enrollments
                    ->where('class_category_fee_id', $fee->id)
                    ->values();

                $paidStudents = [];
                $unpaidStudents = [];
                $freeCardStudents = [];

                foreach ($categoryEnrollments as $enrollment) {
                    $student = $enrollment->student;

                    $paidAmount = (float) $enrollment->payments->sum('amount');
                    $finalFee = (float) $enrollment->final_fee;

                    if ($enrollment->is_free_card) {
                        $status = 'freecard';
                    } elseif ($finalFee <= 0) {
                        $status = 'paid';
                    } elseif ($paidAmount >= $finalFee) {
                        $status = 'paid';
                    } else {
                        $status = 'unpaid';
                    }

                    $studentData = [
                        'student_id' => optional($student)->id,
                        'student_code' => (optional($student)->permanent_qr_active == 1)
                            ? optional($student)->custom_id
                            : optional($student)->temporary_qr_code,
                        'initial_name' => optional($student)->initial_name,
                        'guardian_mobile' => optional($student)->guardian_mobile,
                        'is_free_card' => (bool) $enrollment->is_free_card,
                        'fee_option' => $enrollment->classCategoryFeeOption
                            ? [
                                'id' => $enrollment->classCategoryFeeOption->id,
                                'label' => $enrollment->classCategoryFeeOption->label,
                                'fee' => (float) $enrollment->classCategoryFeeOption->fee,
                            ]
                            : null,
                        'final_fee' => $finalFee,
                        'paid_amount' => $paidAmount,
                        'balance' => max($finalFee - $paidAmount, 0),
                        'status' => $status,
                    ];

                    if ($status === 'freecard') {
                        $freeCardStudents[] = $studentData;
                        $overallFreeCardStudents++;
                    } elseif ($status === 'paid') {
                        $paidStudents[] = $studentData;
                        $overallPaidStudents++;
                    } else {
                        $unpaidStudents[] = $studentData;
                        $overallUnpaidStudents++;
                    }

                    $overallTotalStudents++;
                }

                $classCategories[] = [
                    'category_fee_id' => $fee->id,

                    'category_id' => $fee->class_category_id,

                    'category_name' => optional($fee->category)->category_name,

                    'fee_options' => $categoryEnrollments
                        ->map(function ($enrollment) {
                            return $enrollment->classCategoryFeeOption
                                ? [
                                    'id' => $enrollment->classCategoryFeeOption->id,
                                    'label' => $enrollment->classCategoryFeeOption->label,
                                    'fee' => (float) $enrollment->classCategoryFeeOption->fee,
                                ]
                                : null;
                        })
                        ->filter()
                        ->unique('id')
                        ->values()
                        ->toArray(),

                    'total_students' => $categoryEnrollments->count(),

                    'paid_count' => count($paidStudents),

                    'unpaid_count' => count($unpaidStudents),

                    'freecard_count' => count($freeCardStudents),

                    'students' => [
                        'paid' => $paidStudents,
                        'unpaid' => $unpaidStudents,
                        'freecard' => $freeCardStudents,
                    ],
                ];
            }

            $reportClasses[] = [
                'class_id' => $class->id,
                'class_name' => $class->class_name,
                'grade_name' => optional($class->grade)->grade_name,
                'categories' => $classCategories,
            ];
        }

        return [
            'teacher' => [
                'id' => $teacher->id,
                'custom_id' => $teacher->custom_id,
                'initials' => $teacher->initials,
            ],
            'filter' => [
                'year' => $year,
                'month' => $month,
            ],
            'summary' => [
                'total_classes' => $classes->count(),
                'total_students' => $overallTotalStudents,
                'paid_students' => $overallPaidStudents,
                'unpaid_students' => $overallUnpaidStudents,
                'freecard_students' => $overallFreeCardStudents,
            ],
            'classes' => $reportClasses,
        ];
    }



    //payment paid at 

    public function TeacherWithStudentPaymentDateReport(Request $request)
    {
        try {
            $validated = $request->validate([
                'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
                'year' => ['required', 'integer', 'min:2000', 'max:2100'],
                'month' => ['required', 'integer', 'between:1,12'],
            ]);

            $payload = $this->buildTeacherWithStudentPaymentDateReportData(
                (int) $validated['teacher_id'],
                (int) $validated['year'],
                (int) $validated['month']
            );

            return response()->json([
                'success' => true,
                'data' => $payload,
            ]);
        } catch (\Throwable $e) {
            Log::error('Teacher with student payment report generation failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    public function TeacherWithStudentPaymentDateReportExcel(Request $request)
    {
        try {
            $validated = $request->validate([
                'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
                'year' => ['required', 'integer', 'min:2000', 'max:2100'],
                'month' => ['required', 'integer', 'between:1,12'],
            ]);

            $teacherId = (int) $validated['teacher_id'];
            $year = (int) $validated['year'];
            $month = (int) $validated['month'];

            $payload = $this->buildTeacherWithStudentPaymentDateReportData($teacherId, $year, $month);

            return Excel::download(
                new TeacherWithStudentPaymentDateReportExport($payload, $year, $month),
                "teacher-with-student-payment-date-report-{$teacherId}-{$year}-{$month}.xlsx"
            );
        } catch (\Throwable $e) {
            Log::error('Teacher with student payment excel report generation failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    public function TeacherWithStudentPaymentDateReportPdf(Request $request)
    {
        try {
            $validated = $request->validate([
                'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
                'year' => ['required', 'integer', 'min:2000', 'max:2100'],
                'month' => ['required', 'integer', 'between:1,12'],
            ]);

            $teacherId = (int) $validated['teacher_id'];
            $year = (int) $validated['year'];
            $month = (int) $validated['month'];

            $payload = $this->buildTeacherWithStudentPaymentDateReportData($teacherId, $year, $month);

            $pdf = Pdf::loadView('admin.pdf.teacher.teacher_with_student_payment_report', [
                'report' => $payload,
                'year' => $year,
                'month' => $month,
            ])->setPaper('a4', 'landscape');

            return $pdf->download("teacher-with-student-payment-date-report-{$teacherId}-{$year}-{$month}.pdf");
        } catch (\Throwable $e) {
            Log::error('Teacher with student payment date pdf report generation failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }


    private function buildTeacherWithStudentPaymentDateReportData(int $teacherId, int $year, int $month): array
    {
        $teacher = Teacher::select('id', 'custom_id', 'initials')
            ->findOrFail($teacherId);

        $classes = StudentClass::select('id', 'class_name', 'grade_id', 'teacher_id')
            ->where('teacher_id', $teacherId)
            ->with([
                'grade:id,grade_name',

                'categoryFees' => function ($query) {
                    $query->select(
                        'id',
                        'student_class_id',
                        'class_category_id'
                    )->with([
                        'category:id,category_name',
                    ])->orderBy('id');
                },

                'enrollments' => function ($query) use ($year, $month) {
                    $query->select(
                        'id',
                        'student_id',
                        'student_class_id',
                        'class_category_fee_id',
                        'class_category_fee_option_id',
                        'is_active',
                        'is_free_card'
                    )
                        ->where('is_active', 1)
                        ->with([
                            'student:id,permanent_qr_active,custom_id,temporary_qr_code,initial_name,guardian_mobile',

                            'payments' => function ($paymentQuery) use ($year, $month) {
                                $paymentQuery->select(
                                    'id',
                                    'student_id',
                                    'student_class_enrollment_id',
                                    'amount',
                                    'status',
                                    'paid_at'
                                )
                                    ->where('status', 'completed')
                                    ->whereYear('paid_at', $year)
                                    ->whereMonth('paid_at', $month);
                            },

                            'classCategoryFee:id,student_class_id,class_category_id',
                            'classCategoryFee.category:id,category_name',
                            'classCategoryFeeOption:id,class_category_fee_id,label,fee',
                        ]);
                },
            ])
            ->orderBy('class_name')
            ->get();

        $reportClasses = [];

        $overallTotalStudents = 0;
        $overallPaidStudents = 0;
        $overallUnpaidStudents = 0;
        $overallFreeCardStudents = 0;

        foreach ($classes as $class) {

            $classCategories = [];

            foreach ($class->categoryFees as $fee) {

                $categoryEnrollments = $class->enrollments
                    ->where('class_category_fee_id', $fee->id)
                    ->values();

                $paidStudents = [];
                $unpaidStudents = [];
                $freeCardStudents = [];

                foreach ($categoryEnrollments as $enrollment) {

                    $student = $enrollment->student;

                    $paidAmount = (float) $enrollment->payments->sum('amount');

                    $finalFee = (float) $enrollment->final_fee;

                    /*
                 * FREE CARD
                 */
                    if ($enrollment->is_free_card) {

                        $status = 'freecard';

                        /*
                 * PAID
                 */
                    } elseif ($finalFee <= 0) {

                        $status = 'paid';
                    } elseif ($paidAmount >= $finalFee) {

                        $status = 'paid';

                        /*
                 * UNPAID
                 */
                    } else {

                        $status = 'unpaid';
                    }

                    $studentData = [
                        'student_id' => optional($student)->id,

                        'student_code' => (
                            optional($student)->permanent_qr_active == 1
                        )
                            ? optional($student)->custom_id
                            : optional($student)->temporary_qr_code,

                        'initial_name' => optional($student)->initial_name,

                        'guardian_mobile' => optional($student)->guardian_mobile,

                        'is_free_card' => (bool) $enrollment->is_free_card,

                        'fee_option' => $enrollment->classCategoryFeeOption
                            ? [
                                'id' => $enrollment->classCategoryFeeOption->id,
                                'label' => $enrollment->classCategoryFeeOption->label,
                                'fee' => (float) $enrollment->classCategoryFeeOption->fee,
                            ]
                            : null,

                        'final_fee' => $finalFee,

                        'paid_amount' => $paidAmount,

                        'balance' => max($finalFee - $paidAmount, 0),

                        'status' => $status,
                    ];

                    /*
                 * Separate student groups
                 */
                    if ($status === 'freecard') {

                        $freeCardStudents[] = $studentData;

                        $overallFreeCardStudents++;
                    } elseif ($status === 'paid') {

                        $paidStudents[] = $studentData;

                        $overallPaidStudents++;
                    } else {

                        $unpaidStudents[] = $studentData;

                        $overallUnpaidStudents++;
                    }

                    $overallTotalStudents++;
                }

                $classCategories[] = [
                    'category_fee_id' => $fee->id,

                    'category_id' => $fee->class_category_id,

                    'category_name' => optional($fee->category)->category_name,

                    /*
                 * Available Fee Options
                 */
                    'fee_options' => $categoryEnrollments
                        ->map(function ($enrollment) {
                            return $enrollment->classCategoryFeeOption
                                ? [
                                    'id' => $enrollment->classCategoryFeeOption->id,
                                    'label' => $enrollment->classCategoryFeeOption->label,
                                    'fee' => (float) $enrollment->classCategoryFeeOption->fee,
                                ]
                                : null;
                        })
                        ->filter()
                        ->unique('id')
                        ->values()
                        ->toArray(),

                    'total_students' => $categoryEnrollments->count(),

                    'paid_count' => count($paidStudents),

                    'unpaid_count' => count($unpaidStudents),

                    'freecard_count' => count($freeCardStudents),

                    /*
                 * Students
                 */
                    'students' => [
                        'paid' => $paidStudents,

                        'unpaid' => $unpaidStudents,

                        'freecard' => $freeCardStudents,
                    ],
                ];
            }

            $reportClasses[] = [
                'class_id' => $class->id,

                'class_name' => $class->class_name,

                'grade_name' => optional($class->grade)->grade_name,

                'categories' => $classCategories,
            ];
        }

        return [
            'teacher' => [
                'id' => $teacher->id,
                'custom_id' => $teacher->custom_id,
                'initials' => $teacher->initials,
            ],

            'filter' => [
                'year' => $year,
                'month' => $month,
            ],

            'summary' => [
                'total_classes' => $classes->count(),

                'total_students' => $overallTotalStudents,

                'paid_students' => $overallPaidStudents,

                'unpaid_students' => $overallUnpaidStudents,

                'freecard_students' => $overallFreeCardStudents,
            ],

            'classes' => $reportClasses,
        ];
    }
}

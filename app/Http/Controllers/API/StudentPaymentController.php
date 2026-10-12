<?php

namespace App\Http\Controllers\API;

use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Jobs\SendNotificationJob;
use App\Jobs\SendPaymentSms;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\StudentClassEnrollment;
use App\Services\Notification\PaymentNotificationService;
use App\Services\ReceiptNumberService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class StudentPaymentController extends Controller
{
    protected PaymentNotificationService $paymentNotification;

    public function __construct(PaymentNotificationService $paymentNotification)
    {
        $this->paymentNotification = $paymentNotification;
    }
    public function todayReceipt(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'per_page' => ['nullable', 'integer', 'in:5,10,15,20,50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $search = isset($validated['search']) ? $validated['search'] : null;
        $date = isset($validated['date']) ? $validated['date'] : today()->toDateString();
        $perPage = isset($validated['per_page']) ? (int) $validated['per_page'] : 10;

        $baseQuery = StudentClassEnrollment::with([
            'student',
            'studentClass.teacher',
            'studentClass.grade',

            // Category
            'classCategoryFee.category',

            // Selected Fee Option
            'classCategoryFeeOption',

            // Today's payments
            'payments' => function ($query) use ($date) {
                $query->whereDate('created_at', $date)
                    ->latest();
            },
        ])
            ->whereHas('payments', function ($query) use ($date) {
                $query->whereDate('created_at', $date);
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($subQuery) use ($search) {

                    $subQuery->whereHas('student', function ($studentQuery) use ($search) {
                        $studentQuery->where('initial_name', 'like', "%{$search}%")
                            ->orWhere('custom_id', 'like', "%{$search}%")
                            ->orWhere('temporary_qr_code', 'like', "%{$search}%")
                            ->orWhere('mobile', 'like', "%{$search}%")
                            ->orWhere('guardian_mobile', 'like', "%{$search}%");
                    })

                        ->orWhereHas('studentClass', function ($classQuery) use ($search) {
                            $classQuery->where('class_name', 'like', "%{$search}%")
                                ->orWhereHas('teacher', function ($teacherQuery) use ($search) {
                                    $teacherQuery->where('initials', 'like', "%{$search}%")
                                        ->orWhere('custom_id', 'like', "%{$search}%");
                                })
                                ->orWhereHas('grade', function ($gradeQuery) use ($search) {
                                    $gradeQuery->where('grade_name', 'like', "%{$search}%");
                                });
                        })

                        ->orWhereHas('classCategoryFee.category', function ($categoryQuery) use ($search) {
                            $categoryQuery->where('category_name', 'like', "%{$search}%");
                        })

                        // Search by Fee Option label
                        ->orWhereHas('classCategoryFeeOption', function ($feeOptionQuery) use ($search) {
                            $feeOptionQuery->where('label', 'like', "%{$search}%");
                        });
                });
            })
            ->latest();

        /*
    |--------------------------------------------------------------------------
    | SUMMARY
    |--------------------------------------------------------------------------
    */

        $summaryRows = (clone $baseQuery)->get();

        $summary = [
            'enrollments' => $summaryRows->count(),

            'payment_count' => $summaryRows->sum(function ($enrollment) {
                return $enrollment->payments->count();
            }),

            'total_amount' => $summaryRows->sum(function ($enrollment) {
                return $enrollment->payments->sum('amount');
            }),
        ];

        /*
    |--------------------------------------------------------------------------
    | PAGINATION
    |--------------------------------------------------------------------------
    */

        $paginated = (clone $baseQuery)
            ->paginate($perPage)
            ->appends($request->query());

        /*
    |--------------------------------------------------------------------------
    | RESPONSE DATA
    |--------------------------------------------------------------------------
    */

        $data = collect($paginated->items())
            ->map(function ($enrollment) {

                $student = $enrollment->student;
                $studentClass = $enrollment->studentClass;
                $teacher = $studentClass ? $studentClass->teacher : null;
                $grade = $studentClass ? $studentClass->grade : null;

                $categoryFee = $enrollment->classCategoryFee;
                $category = $categoryFee ? $categoryFee->category : null;

                $feeOption = $enrollment->classCategoryFeeOption;

                $todayPayments = $enrollment->payments;

                return [
                    'enrollment_id' => $enrollment->id,

                    /*
                |--------------------------------------------------------------------------
                | Student
                |--------------------------------------------------------------------------
                */

                    'student_id' => $student ? $student->id : null,
                    'initial_name' => $student ? $student->initial_name : null,
                    'mobile' => $student ? $student->mobile : null,
                    'guardian_mobile' => $student ? $student->guardian_mobile : null,

                    'qr_code' => $student
                        ? (
                            $student->permanent_qr_active == 1
                            ? $student->custom_id
                            : $student->temporary_qr_code
                        )
                        : null,

                    /*
                |--------------------------------------------------------------------------
                | Class
                |--------------------------------------------------------------------------
                */

                    'student_class_id' => $studentClass ? $studentClass->id : null,
                    'class_name' => $studentClass ? $studentClass->class_name : null,

                    'teacher_custom_id' => $teacher ? $teacher->custom_id : null,
                    'teacher_name' => $teacher ? $teacher->initials : null,

                    'grade_name' => $grade ? $grade->grade_name : null,

                    /*
                |--------------------------------------------------------------------------
                | Category
                |--------------------------------------------------------------------------
                */

                    'category_name' => $category
                        ? $category->category_name
                        : null,

                    /*
                |--------------------------------------------------------------------------
                | Selected Fee Option
                |--------------------------------------------------------------------------
                */

                    'class_category_fee_id' => $categoryFee
                        ? $categoryFee->id
                        : null,

                    'class_category_fee_option_id' => $feeOption
                        ? $feeOption->id
                        : null,

                    'fee_option' => $feeOption
                        ? [
                            'id' => $feeOption->id,
                            'label' => $feeOption->label,
                            'fee' => (float) $feeOption->fee,
                        ]
                        : null,

                    /*
                |--------------------------------------------------------------------------
                | Payment
                |--------------------------------------------------------------------------
                */

                    'is_free_card' => (bool) $enrollment->is_free_card,

                    'final_fee' => (float) $enrollment->final_fee,

                    'payment_status' => $enrollment->payment_status,

                    'balance' => (float) $enrollment->balance,

                    'today_payment_count' => $todayPayments->count(),

                    'today_payment_total' => (float) $todayPayments->sum('amount'),

                    /*
                |--------------------------------------------------------------------------
                | Today's Payments
                |--------------------------------------------------------------------------
                */

                    'today_payments' => $todayPayments
                        ->map(function ($payment) {
                            return [
                                'id' => $payment->id,
                                'amount' => (float) $payment->amount,
                                'paid_at' => $payment->paid_at,
                                'payment_month' => $payment->payment_month,
                                'payment_method' => $payment->payment_method,
                                'status' => $payment->status,
                                'receipt_number' => $payment->receipt_number,
                                'note' => $payment->note,
                            ];
                        })
                        ->values(),
                ];
            })
            ->values();

        /*
    |--------------------------------------------------------------------------
    | RESPONSE
    |--------------------------------------------------------------------------
    */

        return response()->json([
            'success' => true,

            'data' => $data,

            'summary' => $summary,

            'filters' => [
                'date' => $date,
                'search' => $search,
                'per_page' => $perPage,
            ],

            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ],
        ]);
    }
    public function destroy(int $paymentId): JsonResponse
    {
        try {
            $payment = Payment::with('splitSnapshot')
                ->findOrFail($paymentId);

            // Allow delete only within 14 days
            if ($payment->created_at->lt(now()->subDays(14))) {
                return response()->json([
                    'success' => false,
                    'message' => 'This payment can only be deleted within 14 days.',
                ], 403);
            }

            DB::beginTransaction();

            try {
                // Delete payment split snapshot first
                if ($payment->splitSnapshot) {
                    $payment->splitSnapshot->forceDelete();
                }

                // Delete payment permanently
                $payment->forceDelete();

                DB::commit();

                // Laravel log
                Log::info('Payment deleted successfully.', [
                    'payment_id' => $paymentId,
                    'deleted_by' => auth()->id(),
                    'deleted_at' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment deleted successfully.',
                ]);
            } catch (\Exception $exception) {
                DB::rollBack();

                throw $exception;
            }
        } catch (\Exception $exception) {

            Log::error('Payment delete failed.', [
                'payment_id' => $paymentId,
                'user_id' => auth()->id(),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while deleting payment.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    public function todayPayments(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $search = $validated['search'] ?? null;
        $date = $validated['date'] ?? today()->toDateString();

        /*
    |--------------------------------------------------------------------------
    | Base Query - Payment
    |--------------------------------------------------------------------------
    |
    | One payment = one record.
    |
    */

        $baseQuery = Payment::query()
            ->with([
                'student',

                'enrollment.studentClass.teacher',
                'enrollment.studentClass.grade',

                'enrollment.classCategoryFee.category',

                'enrollment.classCategoryFeeOption',
            ])
            ->whereDate('paid_at', $date)
            ->where('status', 'completed');

        /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

        $baseQuery
            ->when($search, function ($q) use ($search) {

                $q->where(function ($subQuery) use ($search) {

                    /*
                |--------------------------------------------------------------------------
                | Student Search
                |--------------------------------------------------------------------------
                */

                    $subQuery->whereHas(
                        'student',
                        function ($studentQuery) use ($search) {

                            $studentQuery
                                ->where(
                                    'initial_name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'custom_id',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'temporary_qr_code',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'mobile',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'guardian_mobile',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );

                    /*
                |--------------------------------------------------------------------------
                | Class Search
                |--------------------------------------------------------------------------
                */

                    $subQuery->orWhereHas(
                        'enrollment.studentClass',
                        function ($classQuery) use ($search) {

                            $classQuery
                                ->where(
                                    'class_name',
                                    'like',
                                    "%{$search}%"
                                )

                                /*
                            |--------------------------------------------------------------------------
                            | Teacher Search
                            |--------------------------------------------------------------------------
                            */

                                ->orWhereHas(
                                    'teacher',
                                    function ($teacherQuery) use ($search) {

                                        $teacherQuery
                                            ->where(
                                                'initials',
                                                'like',
                                                "%{$search}%"
                                            )
                                            ->orWhere(
                                                'custom_id',
                                                'like',
                                                "%{$search}%"
                                            );
                                    }
                                )

                                /*
                            |--------------------------------------------------------------------------
                            | Grade Search
                            |--------------------------------------------------------------------------
                            */

                                ->orWhereHas(
                                    'grade',
                                    function ($gradeQuery) use ($search) {

                                        $gradeQuery->where(
                                            'grade_name',
                                            'like',
                                            "%{$search}%"
                                        );
                                    }
                                );
                        }
                    );

                    /*
                |--------------------------------------------------------------------------
                | Category Search
                |--------------------------------------------------------------------------
                */

                    $subQuery->orWhereHas(
                        'enrollment.classCategoryFee.category',
                        function ($categoryQuery) use ($search) {

                            $categoryQuery->where(
                                'category_name',
                                'like',
                                "%{$search}%"
                            );
                        }
                    );

                    /*
                |--------------------------------------------------------------------------
                | Fee Option Search
                |--------------------------------------------------------------------------
                */

                    $subQuery->orWhereHas(
                        'enrollment.classCategoryFeeOption',
                        function ($feeOptionQuery) use ($search) {

                            $feeOptionQuery->where(
                                'label',
                                'like',
                                "%{$search}%"
                            );
                        }
                    );

                    /*
                |--------------------------------------------------------------------------
                | Receipt Search
                |--------------------------------------------------------------------------
                */

                    $subQuery->orWhere(
                        'receipt_number',
                        'like',
                        "%{$search}%"
                    );
                });
            })
            ->latest('paid_at');

        /*
    |--------------------------------------------------------------------------
    | Summary
    |--------------------------------------------------------------------------
    */

        $summaryRows = (clone $baseQuery)->get();

        $summary = [
            'payment_count' => $summaryRows->count(),

            'total_amount' => $summaryRows->sum('amount'),

            'total_discount' => $summaryRows->sum('discount_amount'),
        ];

        /*
    |--------------------------------------------------------------------------
    | Get Payments
    |--------------------------------------------------------------------------
    */

        $payments = (clone $baseQuery)->get();

        /*
    |--------------------------------------------------------------------------
    | Transform
    |--------------------------------------------------------------------------
    */

        $data = $payments
            ->map(function ($payment) {

                $student = $payment->student;

                /*
            |--------------------------------------------------------------------------
            | Payment -> Enrollment
            |--------------------------------------------------------------------------
            |
            | Payment model relationship is:
            |
            | enrollment()
            |
            */

                $enrollment = $payment->enrollment;

                $studentClass = $enrollment
                    ? $enrollment->studentClass
                    : null;

                /*
            |--------------------------------------------------------------------------
            | Category
            |--------------------------------------------------------------------------
            */

                $category = null;

                if ($enrollment && $enrollment->classCategoryFee) {
                    $category = $enrollment->classCategoryFee->category;
                }

                /*
            |--------------------------------------------------------------------------
            | Fee Option
            |--------------------------------------------------------------------------
            */

                $feeOption = $enrollment
                    ? $enrollment->classCategoryFeeOption
                    : null;

                /*
            |--------------------------------------------------------------------------
            | Teacher
            |--------------------------------------------------------------------------
            */

                $teacher = $studentClass
                    ? $studentClass->teacher
                    : null;

                /*
            |--------------------------------------------------------------------------
            | Grade
            |--------------------------------------------------------------------------
            */

                $grade = $studentClass
                    ? $studentClass->grade
                    : null;

                return [

                    /*
                |--------------------------------------------------------------------------
                | Payment Details
                |--------------------------------------------------------------------------
                */

                    'payment_id' => $payment->id,

                    'receipt_number' => $payment->receipt_number,

                    'amount' => (float) $payment->amount,

                    'discount_amount' => (float) $payment->discount_amount,

                    'paid_at' => $payment->paid_at,

                    'payment_month' => $payment->payment_month,

                    'payment_method' => $payment->payment_method,

                    'status' => $payment->status,

                    'reference_number' => $payment->reference_number,

                    'mark_method' => $payment->mark_method,

                    'note' => $payment->note,

                    /*
                |--------------------------------------------------------------------------
                | Student Details
                |--------------------------------------------------------------------------
                */

                    'student' => [

                        'id' => $student
                            ? $student->id
                            : null,

                        'custom_id' => $student
                            ? $student->custom_id
                            : null,

                        'initial_name' => $student
                            ? $student->initial_name
                            : null,

                        'mobile' => $student
                            ? $student->mobile
                            : null,

                        'guardian_mobile' => $student
                            ? $student->guardian_mobile
                            : null,

                        'img_url' => $student
                            ? $student->img_url
                            : null,

                        'qr_code' => $student
                            ? (
                                $student->permanent_qr_active == 1
                                ? $student->custom_id
                                : $student->temporary_qr_code
                            )
                            : null,
                    ],

                    /*
                |--------------------------------------------------------------------------
                | Class Details
                |--------------------------------------------------------------------------
                */

                    'class' => [

                        'id' => $studentClass
                            ? $studentClass->id
                            : null,

                        'class_name' => $studentClass
                            ? $studentClass->class_name
                            : null,
                    ],

                    /*
                |--------------------------------------------------------------------------
                | Category
                |--------------------------------------------------------------------------
                */

                    'category' => [

                        'id' => $category
                            ? $category->id
                            : null,

                        'name' => $category
                            ? $category->category_name
                            : null,
                    ],

                    /*
                |--------------------------------------------------------------------------
                | Grade
                |--------------------------------------------------------------------------
                */

                    'grade' => [

                        'id' => $grade
                            ? $grade->id
                            : null,

                        'name' => $grade
                            ? $grade->grade_name
                            : null,
                    ],

                    /*
                |--------------------------------------------------------------------------
                | Teacher
                |--------------------------------------------------------------------------
                */

                    'teacher' => [

                        'id' => $teacher
                            ? $teacher->id
                            : null,

                        'custom_id' => $teacher
                            ? $teacher->custom_id
                            : null,

                        'name' => $teacher
                            ? $teacher->initials
                            : null,
                    ],

                    /*
                |--------------------------------------------------------------------------
                | Fee Option
                |--------------------------------------------------------------------------
                */

                    'fee_option' => [

                        'id' => $feeOption
                            ? $feeOption->id
                            : null,

                        'name' => $feeOption
                            ? $feeOption->label
                            : null,

                        'fee' => $feeOption
                            ? (float) $feeOption->fee
                            : 0,
                    ],

                    /*
                |--------------------------------------------------------------------------
                | Enrollment Details
                |--------------------------------------------------------------------------
                */

                    'enrollment' => [

                        'id' => $enrollment
                            ? $enrollment->id
                            : null,

                        'is_free_card' => $enrollment
                            ? (bool) $enrollment->is_free_card
                            : false,

                        'final_fee' => $enrollment
                            ? (float) $enrollment->final_fee
                            : 0,

                        'balance' => $enrollment
                            ? (float) $enrollment->balance
                            : 0,

                        'payment_status' => $enrollment
                            ? $enrollment->payment_status
                            : null,
                    ],
                ];
            })
            ->values();

        /*
    |--------------------------------------------------------------------------
    | Response
    |--------------------------------------------------------------------------
    */

        return response()->json([

            'success' => true,

            'data' => $data,

            'summary' => [

                'payment_count' => (int) $summary['payment_count'],

                'total_amount' => (float) $summary['total_amount'],

                'total_discount' => (float) $summary['total_discount'],
            ],

            'filters' => [

                'date' => $date,

                'search' => $search,
            ],

            'meta' => [

                'total' => $data->count(),
            ],
        ]);
    }


    public function fetchStudentAllPayment(int $studentId, int $enrolledId)
    {
        try {
            $payments = Payment::query()
                ->select([
                    'id',
                    'student_id',
                    'student_class_enrollment_id',
                    'mark_method',
                    'amount',
                    'note',
                    'discount_amount',
                    'paid_at',
                    'payment_month',
                    'receipt_number',
                    'status',
                ])
                ->where('student_id', $studentId)
                ->where(
                    'student_class_enrollment_id',
                    $enrolledId
                )
                ->where('status', 'completed')
                ->orderByDesc('paid_at')
                ->get();

            $monthWiseSummary = $payments
                ->groupBy(function ($payment) {
                    return $payment->payment_month
                        ? Carbon::parse(
                            $payment->payment_month
                        )->format('Y-m')
                        : 'unknown';
                })
                ->map(function ($items, $monthKey) {
                    return [
                        'month' => $monthKey,

                        'month_name' => $monthKey !== 'unknown'
                            ? Carbon::createFromFormat(
                                'Y-m',
                                $monthKey
                            )->format('F Y')
                            : 'Unknown',

                        'count' => $items->count(),

                        'total_amount' => (float) $items->sum(
                            'amount'
                        ),

                        'total_discount_amount' => (float) $items->sum(
                            'discount_amount'
                        ),

                        'payments' => $items
                            ->map(function ($payment) {
                                return [
                                    'id' => $payment->id,

                                    'mark_method' => $payment->mark_method,

                                    'amount' => (float) $payment->amount,

                                    'discount_amount' => (float) $payment->discount_amount,

                                    'note' => $payment->note,

                                    'paid_at' => optional(
                                        $payment->paid_at
                                    )->toDateTimeString(),

                                    'payment_month' => optional(
                                        $payment->payment_month
                                    )->toDateString(),

                                    'receipt_number' => $payment->receipt_number,

                                    'status' => $payment->status,
                                ];
                            })
                            ->values(),
                    ];
                })
                ->sortByDesc('month')
                ->values();

            return response()->json([
                'success' => true,

                'count' => $payments->count(),

                'data' => $monthWiseSummary,
            ]);
        } catch (Throwable $e) {
            Log::error(
                'Fetch Student All Payment Error',
                [
                    'student_id' => $studentId,
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                    'trace' => $e->getTraceAsString(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while fetching student payments.',
            ], 500);
        }
    }
}

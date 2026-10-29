<?php

namespace App\Http\Controllers\Api\Admission;

use App\Services\Notification\AdmissionPaymentNotificationService;
use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\AdmissionPayment;
use App\Models\Student;
use App\Services\Web\Student\StudentAdmissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AdmissionPaymentController extends Controller
{
    public function fetchAdmission(Request $request)
    {
        try {

            $admissions = Admission::active()
                ->select('id', 'name', 'amount')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $admissions,
            ]);
        } catch (\Throwable $e) {

            Log::error('Failed to fetch admissions.', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch admissions.',
            ], 500);
        }
    }

    public function fetchAdmissionPayment(Request $request)
    {
        try {

            $paidStudents = AdmissionPayment::with([
                'admission:id,name,amount',
                'student:id,custom_id,initial_name,grade_id,guardian_mobile',
                'student.grade:id,grade_name',
                'collectedBy:id,name',
            ])
                ->latest()
                ->get()
                ->map(function ($payment) {

                    return [
                        'payment_id' => $payment->id,
                        'receipt_number' => $payment->receipt_number,

                        'student' => [
                            'id' => $payment->student?->id,
                            'custom_id' => $payment->student?->custom_id,
                            'name' => $payment->student?->initial_name,
                            'grade' => $payment->student?->grade?->grade_name,
                            'guardian_mobile' => $payment->student?->guardian_mobile,
                        ],

                        'admission' => [
                            'id' => $payment->admission?->id,
                            'name' => $payment->admission?->name,
                            'amount' => $payment->admission?->amount,
                        ],

                        'payment' => [
                            'amount' => $payment->amount,
                            'payment_method' => $payment->payment_method,
                            'status' => $payment->status,
                            'paid_at' => $payment->paid_at,
                            'collected_by' => $payment->collectedBy?->name,
                            'note' => $payment->note,
                        ],
                    ];
                });

            $unpaidStudents = Student::with([
                'grade:id,grade_name'
            ])
                ->where('admission', false)
                ->latest()
                ->get()
                ->map(function ($student) {

                    return [
                        'id' => $student->id,
                        'custom_id' => $student->custom_id,
                        'name' => $student->initial_name,
                        'grade' => $student->grade?->grade_name,
                        'guardian_mobile' => $student->guardian_mobile,
                        'temporary_qr_code' => $student->temporary_qr_code,
                        'admission' => false,
                    ];
                });

            return response()->json([
                'success' => true,

                'summary' => [
                    'paid_count' => $paidStudents->count(),
                    'unpaid_count' => $unpaidStudents->count(),
                    'total_count' => $paidStudents->count() + $unpaidStudents->count(),
                ],

                'paid_students' => $paidStudents,

                'unpaid_students' => $unpaidStudents,

            ]);
        } catch (\Throwable $e) {

            Log::error('Failed to fetch admission details.', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch admission details.',
            ], 500);
        }
    }

    /**
     * POST /api/admission-payments/store  { "student_id", "admission_id" }
     *
     * Uses the same StudentAdmissionService as student registration, so the
     * payment gets the student's branch, the cashier, paid_at and a receipt number.
     */
    public function storeAdmissionPayment(
        Request $request,
        StudentAdmissionService $admissionService,
        AdmissionPaymentNotificationService $admissionNotification
    )
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'admission_id' => ['required', 'integer', 'exists:admissions,id'],
        ]);

        $student = Student::query()->findOrFail($validated['student_id']);

        $alreadyPaid = AdmissionPayment::query()
            ->where('student_id', $student->id)
            ->where('status', AdmissionPayment::STATUS_PAID)
            ->exists();

        if ($alreadyPaid) {
            return response()->json([
                'success' => false,
                'message' => 'Admission fee is already paid for this student.',
            ], 422);
        }

        try {
            $payment = $admissionService->createAdmissionPayment($student, (int) $validated['admission_id']);

            // Parent notification (Settings > Notifications)
            $admissionNotification->sendPaid($payment);

            return response()->json([
                'success' => true,
                'message' => 'Admission payment recorded successfully.',
                'student' => $student->fresh('grade'),
                'admission_payment' => [
                    'id' => $payment->id,
                    'receipt_number' => $payment->receipt_number,
                    'amount' => $payment->amount,
                    'status' => $payment->status,
                    'paid_at' => optional($payment->paid_at)->toDateTimeString(),
                    'payment_method' => $payment->payment_method,
                    'admission_name' => optional($payment->admission)->name,
                    'note' => $payment->note,
                ],
            ]);
        } catch (HttpException $e) {
            // e.g. 403 "You do not have access to this branch."
            return response()->json([
                'success' => false,
                'message' => \App\Support\ErrorDetail::safe($e),
            ], $e->getStatusCode());
        } catch (\Throwable $e) {
            Log::error('Failed to store admission payment.', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to record admission payment.',
            ], 500);
        }
    }
}
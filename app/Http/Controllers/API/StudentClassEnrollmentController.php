<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentClassEnrollment;
use App\Services\StudentClassEnrollmentService;
use App\Services\StudentQRService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class StudentClassEnrollmentController extends Controller
{
    protected $studentClassEnrollmentService;

    public function __construct(
        StudentClassEnrollmentService $studentClassEnrollmentService
    ) {
        $this->studentClassEnrollmentService =
            $studentClassEnrollmentService;
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => [
                'required',
                'integer',
                'exists:students,id',
            ],

            'student_class_id' => [
                'required',
                'integer',
                'exists:student_classes,id',
            ],

            'class_category_fee_id' => [
                'required',
                'integer',
                'exists:class_category_fees,id',
            ],

            'class_category_fee_option_id' => [
                'required',
                'integer',
                'exists:class_category_fee_options,id',
            ],

            'is_free_card' => [
                'nullable',
                'boolean',
            ],

            'enrolled_at' => [
                'nullable',
                'date',
            ],

            'note' => [
                'nullable',
                'string',
            ],
        ]);

        try {

            $enrollment =
                $this->studentClassEnrollmentService->create(
                    $validated
                );

            return response()->json([
                'success' => true,
                'message' => 'Student enrolled successfully.',

                'data' => [
                    'enrollment_id' =>
                        $enrollment->id,

                    'student_id' =>
                        $enrollment->student_id,

                    'student_class_id' =>
                        $enrollment->student_class_id,

                    'class_category_fee_id' =>
                        $enrollment->class_category_fee_id,

                    'class_category_fee_option_id' =>
                        $enrollment->class_category_fee_option_id,

                    'is_active' =>
                        (bool) $enrollment->is_active,

                    'is_free_card' =>
                        (bool) $enrollment->is_free_card,

                    'final_fee' =>
                        (float) $enrollment->final_fee,

                    'registered_date' =>
                        $enrollment->enrolled_at
                            ? $enrollment->enrolled_at->format('Y-m-d')
                            : null,
                ],
            ], 201);

        } catch (ValidationException $e) {

            return response()->json([
                'success' => false,
                'message' =>
                    collect($e->errors())
                        ->flatten()
                        ->first()
                    ?? 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'success' => false,
                'message' =>
                    'Something went wrong while enrolling student.',
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        StudentClassEnrollment $enrollment
    ): JsonResponse {

        $validated = $request->validate([
            'class_category_fee_id' => [
                'required',
                'integer',
                'exists:class_category_fees,id',
            ],

            'class_category_fee_option_id' => [
                'required',
                'integer',
                'exists:class_category_fee_options,id',
            ],

            'is_free_card' => [
                'nullable',
                'boolean',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'enrolled_at' => [
                'nullable',
                'date',
            ],

            'left_at' => [
                'nullable',
                'date',
            ],

            'note' => [
                'nullable',
                'string',
            ],
        ]);

        try {

            $enrollment =
                $this->studentClassEnrollmentService->update(
                    $enrollment,
                    $validated
                );

            return response()->json([
                'success' => true,
                'message' =>
                    'Enrollment updated successfully.',

                'data' => [
                    'enrollment_id' =>
                        $enrollment->id,

                    'class_category_fee_id' =>
                        $enrollment->class_category_fee_id,

                    'class_category_fee_option_id' =>
                        $enrollment->class_category_fee_option_id,

                    'is_active' =>
                        (bool) $enrollment->is_active,

                    'is_free_card' =>
                        (bool) $enrollment->is_free_card,

                    'final_fee' =>
                        (float) $enrollment->final_fee,
                ],
            ]);

        } catch (ValidationException $e) {

            return response()->json([
                'success' => false,
                'message' =>
                    collect($e->errors())
                        ->flatten()
                        ->first()
                    ?? 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'success' => false,
                'message' =>
                    'Something went wrong while updating enrollment.',
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Toggle Status
    |--------------------------------------------------------------------------
    */

    public function toggleClassStatusChange(
        int $enrollmentId
    ): JsonResponse {

        try {

            $enrollment =
                StudentClassEnrollment::findOrFail(
                    $enrollmentId
                );

            $enrollment =
                $this->studentClassEnrollmentService
                    ->toggleStatus($enrollment);

            return response()->json([
                'success' => true,

                'message' => $enrollment->is_active
                    ? 'Class activated successfully.'
                    : 'Class deactivated successfully.',

                'data' => [
                    'enrollment_id' =>
                        $enrollment->id,

                    'is_active' =>
                        (bool) $enrollment->is_active,

                    'left_at' =>
                        $enrollment->left_at,
                ],
            ]);

        } catch (ValidationException $e) {

            return response()->json([
                'success' => false,
                'message' =>
                    collect($e->errors())
                        ->flatten()
                        ->first()
                    ?? 'Validation failed.',
            ], 422);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'success' => false,
                'message' =>
                    'Unable to change enrollment status.',
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Student Classes
    |--------------------------------------------------------------------------
    */

    public function fetchStudentClasses(
        int $studentId
    ): JsonResponse {

        try {

            $enrollments =
                $this->studentClassEnrollmentService
                    ->getStudentClasses($studentId);

            $data = $enrollments->map(function ($enrollment) {

                return [
                    'enrollment_id' =>
                        $enrollment->id,

                    'class_id' =>
                        $enrollment->studentClass?->id,

                    'class_name' =>
                        $enrollment->studentClass?->class_name,

                    'grade_name' =>
                        $enrollment->studentClass?->grade?->grade_name,

                    'teacher_name' =>
                        $enrollment->studentClass?->teacher?->full_name,

                    'class_category_fee_id' =>
                        $enrollment->class_category_fee_id,

                    'class_category_fee_option_id' =>
                        $enrollment->class_category_fee_option_id,

                    'category_name' =>
                        $enrollment
                            ->classCategoryFee
                            ?->category
                            ?->category_name,

                    'fee_option' =>
                        $enrollment->classCategoryFeeOption
                            ? [
                                'id' =>
                                    $enrollment
                                        ->classCategoryFeeOption
                                        ->id,

                                'label' =>
                                    $enrollment
                                        ->classCategoryFeeOption
                                        ->label,

                                'fee' =>
                                    (float)
                                    $enrollment
                                        ->classCategoryFeeOption
                                        ->fee,
                            ]
                            : null,

                    'is_active' =>
                        (bool) $enrollment->is_active,

                    'is_free_card' =>
                        (bool) $enrollment->is_free_card,

                    'final_fee' =>
                        (float) $enrollment->final_fee,

                    'paid_amount' =>
                        (float) $enrollment->paid_amount,

                    'balance' =>
                        (float) $enrollment->balance,

                    'payment_status' =>
                        $enrollment->payment_status,

                    'registered_date' =>
                        $enrollment->enrolled_at
                            ? $enrollment->enrolled_at
                                ->format('Y-m-d')
                            : null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);

        } catch (Throwable $e) {

            Log::error(
                'fetchStudentClasses failed',
                [
                    'student_id' => $studentId,
                    'error' => $e->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' =>
                    'Unable to fetch student classes.',
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Deactivate
    |--------------------------------------------------------------------------
    */

    public function deactivateEnrollment(
        int $enrollmentId
    ): JsonResponse {

        try {

            $enrollment =
                StudentClassEnrollment::findOrFail(
                    $enrollmentId
                );

            $this->studentClassEnrollmentService
                ->deactivate($enrollment);

            return response()->json([
                'success' => true,
                'message' =>
                    'Student removed from class successfully.',
            ]);

        } catch (ValidationException $e) {

            return response()->json([
                'success' => false,
                'message' =>
                    collect($e->errors())
                        ->flatten()
                        ->first()
                    ?? 'Validation failed.',
            ], 422);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'success' => false,
                'message' =>
                    'Unable to remove student from class.',
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Read Student By QR
    |--------------------------------------------------------------------------
    */

    public function readStudentClass(
        Request $request
    ) {

        $validated = $request->validate([
            'qr_code' => [
                'required',
                'string',
                'max:150',
            ],
        ]);

        try {

            $qrResult =
                StudentQRService::read(
                    $validated['qr_code']
                );

            if (!($qrResult['success'] ?? false)) {

                return response()->json([
                    'success' => false,
                    'message' =>
                        $qrResult['message']
                        ?? 'Invalid QR code.',
                ], $qrResult['status_code'] ?? 422);
            }

            $studentId =
                $qrResult['student_id'] ?? null;

            if (!$studentId) {

                return response()->json([
                    'success' => false,
                    'message' =>
                        'Student ID not found in QR result.',
                ], 422);
            }

            $student = Student::query()
                ->with('grade')
                ->find($studentId);

            if (!$student) {

                return response()->json([
                    'success' => false,
                    'message' =>
                        'Student not found.',
                ], 404);
            }

            $enrollments =
                $this->studentClassEnrollmentService
                    ->getStudentClasses($studentId);

            $data = $enrollments->map(function ($enrollment) {

                return [
                    'enrollment_id' =>
                        $enrollment->id,

                    'class_id' =>
                        $enrollment->studentClass?->id,

                    'class_name' =>
                        $enrollment->studentClass?->class_name,

                    'grade_name' =>
                        $enrollment->studentClass?->grade?->grade_name,

                    'teacher_name' =>
                        $enrollment->studentClass?->teacher?->full_name,

                    'class_category_fee_id' =>
                        $enrollment->class_category_fee_id,

                    'class_category_fee_option_id' =>
                        $enrollment->class_category_fee_option_id,

                    'category_name' =>
                        $enrollment
                            ->classCategoryFee
                            ?->category
                            ?->category_name,

                    'fee_option' =>
                        $enrollment->classCategoryFeeOption
                            ? [
                                'id' =>
                                    $enrollment
                                        ->classCategoryFeeOption
                                        ->id,

                                'label' =>
                                    $enrollment
                                        ->classCategoryFeeOption
                                        ->label,

                                'fee' =>
                                    (float)
                                    $enrollment
                                        ->classCategoryFeeOption
                                        ->fee,
                            ]
                            : null,

                    'is_active' =>
                        (bool) $enrollment->is_active,

                    'is_free_card' =>
                        (bool) $enrollment->is_free_card,

                    'final_fee' =>
                        (float) $enrollment->final_fee,

                    'paid_amount' =>
                        (float) $enrollment->paid_amount,

                    'balance' =>
                        (float) $enrollment->balance,

                    'payment_status' =>
                        $enrollment->payment_status,

                    'registered_date' =>
                        $enrollment->enrolled_at
                            ? $enrollment->enrolled_at
                                ->format('Y-m-d')
                            : null,
                ];
            });

            return response()->json([
                'success' => true,

                'student' => [
                    'id' => $student->id,

                    'custom_id' =>
                        $student->permanent_qr_active
                            ? $student->custom_id
                            : $student->temporary_qr_code,

                    'temporary_qr_code' =>
                        $student->temporary_qr_code,

                    'initial_name' =>
                        $student->initial_name,

                    'img_url' =>
                        $student->img_url,

                    'grade_id' =>
                        $student->grade?->id,

                    'grade_name' =>
                        $student->grade?->grade_name,
                ],

                'data' => $data,
            ]);

        } catch (Exception $e) {

            Log::error(
                'readStudentClass failed',
                [
                    'qr_code' =>
                        $validated['qr_code'],

                    'error' =>
                        $e->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' =>
                    'Unable to read QR code.',
            ], 500);
        }
    }
}
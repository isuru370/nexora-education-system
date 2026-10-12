<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\StudentClassEnrollment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PaymentCollectionReportService
{
    /**
     * Generate monthly payment collection report.
     *
     * Report structure:
     *
     * Class
     *   └── Grade
     *       └── Category
     *           ├── Fee Options
     *           ├── Students
     *           ├── Expected
     *           ├── Collected
     *           ├── Due
     *           ├── Collection %
     *           ├── Due %
     *           ├── Teacher
     *           ├── Organizer
     *           └── Institute
     */
    public function generate(string $paymentMonth): array
    {
        /*
        |--------------------------------------------------------------------------
        | Normalize selected month
        |--------------------------------------------------------------------------
        */

        $month = Carbon::parse($paymentMonth)->startOfMonth();

        $monthStart = $month->toDateString();
        $monthEnd = $month->copy()->endOfMonth()->toDateString();


        /*
        |--------------------------------------------------------------------------
        | Get enrollments valid for selected month
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | We DO NOT use:
        |
        | ->where('is_active', true)
        |
        | because a student may have been disabled/left after the
        | selected month.
        |
        | Example:
        |
        | enrolled_at = 2026-01-01
        | left_at     = 2026-09-03
        |
        | September report => INCLUDE
        | October report   => EXCLUDE
        |
        */

        $enrollments = StudentClassEnrollment::query()

            // Student must have been enrolled before month ended
            ->whereDate(
                'enrolled_at',
                '<=',
                $monthEnd
            )

            // Enrollment must not have ended before selected month
            ->where(function ($query) use ($monthStart) {

                $query
                    ->whereNull('left_at')
                    ->orWhereDate(
                        'left_at',
                        '>=',
                        $monthStart
                    );
            })

            /*
            |--------------------------------------------------------------------------
            | Required relationships
            |--------------------------------------------------------------------------
            |
            | studentClass.grade
            |     -> Used for grade_name
            |
            | classCategoryFee.category
            |     -> Used for category_name
            |
            | classCategoryFeeOption
            |     -> Used for selected fee option
            |     -> Also required by final_fee accessor
            |
            */

            ->with([
                'studentClass.grade',
                'classCategoryFee',
                'classCategoryFee.category',
                'classCategoryFeeOption',
            ])

            ->get();


        /*
        |--------------------------------------------------------------------------
        | Get completed payments for selected month
        |--------------------------------------------------------------------------
        */

        $payments = Payment::query()

            ->where(
                'payment_month',
                $monthStart
            )

            ->where(
                'status',
                'completed'
            )

            ->with([
                'enrollment.studentClass.grade',
                'enrollment.classCategoryFee',
                'enrollment.classCategoryFee.category',
                'enrollment.classCategoryFeeOption',
                'splitSnapshot',
            ])

            ->get();


        /*
        |--------------------------------------------------------------------------
        | Group payments by enrollment
        |--------------------------------------------------------------------------
        */

        $paymentsByEnrollment = $payments
            ->groupBy(
                'student_class_enrollment_id'
            );


        /*
        |--------------------------------------------------------------------------
        | Group enrollments by Class + Category
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | We keep grouping by Class + Category.
        |
        | A category can have multiple fee options.
        | Therefore fee options are collected separately below.
        |
        */

        $groups = $enrollments
            ->groupBy(function ($enrollment) {

                return
                    $enrollment->student_class_id
                    . '_'
                    . $enrollment->class_category_fee_id;
            });


        /*
        |--------------------------------------------------------------------------
        | Build report rows
        |--------------------------------------------------------------------------
        */

        $rows = $groups
            ->map(function (Collection $group) use (
                $paymentsByEnrollment
            ) {

                $firstEnrollment = $group->first();


                /*
                |--------------------------------------------------------------------------
                | Class
                |--------------------------------------------------------------------------
                */

                $class = $firstEnrollment->studentClass;


                /*
                |--------------------------------------------------------------------------
                | Category
                |--------------------------------------------------------------------------
                */

                $categoryFee =
                    $firstEnrollment->classCategoryFee;

                $category =
                    optional($categoryFee)->category;


                /*
                |--------------------------------------------------------------------------
                | Student count
                |--------------------------------------------------------------------------
                */

                $studentCount =
                    $group->count();


                /*
                |--------------------------------------------------------------------------
                | Fee Options
                |--------------------------------------------------------------------------
                |
                | A single category can have multiple fee options.
                |
                | Example:
                |
                | Theory
                |   - Theory Only     2500
                |   - Special         2000
                |
                */

                $feeOptions = $group
                    ->map(function ($enrollment) {

                        $option =
                            $enrollment->classCategoryFeeOption;

                        if (!$option) {
                            return null;
                        }

                        return [
                            'id' => $option->id,
                            'label' => $option->label,
                            'fee' => (float) $option->fee,
                        ];
                    })
                    ->filter()
                    ->unique('id')
                    ->values()
                    ->all();


                /*
                |--------------------------------------------------------------------------
                | Expected amount
                |--------------------------------------------------------------------------
                |
                | Expected amount is calculated from the enrollment
                | final_fee.
                |
                | final_fee comes from the selected Fee Option.
                |
                | Free Card => 0
                |
                */

                $expected = round(
                    $group->sum(
                        function ($enrollment) {

                            return (float)
                                $enrollment->final_fee;
                        }
                    ),
                    2
                );


                /*
                |--------------------------------------------------------------------------
                | Enrollment IDs
                |--------------------------------------------------------------------------
                */

                $enrollmentIds =
                    $group
                        ->pluck('id')
                        ->all();


                /*
                |--------------------------------------------------------------------------
                | Get payments belonging to this group
                |--------------------------------------------------------------------------
                */

                $groupPayments =
                    collect();

                foreach (
                    $enrollmentIds
                    as $enrollmentId
                ) {

                    $enrollmentPayments =
                        $paymentsByEnrollment->get(
                            $enrollmentId,
                            collect()
                        );

                    $groupPayments =
                        $groupPayments->merge(
                            $enrollmentPayments
                        );
                }


                /*
                |--------------------------------------------------------------------------
                | Collected amount
                |--------------------------------------------------------------------------
                */

                $collected = round(
                    $groupPayments->sum(
                        function ($payment) {

                            return (float)
                                $payment->amount;
                        }
                    ),
                    2
                );


                /*
                |--------------------------------------------------------------------------
                | Due amount
                |--------------------------------------------------------------------------
                */

                $due = round(
                    max(
                        $expected - $collected,
                        0
                    ),
                    2
                );


                /*
                |--------------------------------------------------------------------------
                | Collection percentage
                |--------------------------------------------------------------------------
                */

                $collectionPercentage =
                    $expected > 0
                        ? round(
                            (
                                $collected
                                / $expected
                            ) * 100,
                            2
                        )
                        : 0;


                /*
                |--------------------------------------------------------------------------
                | Due percentage
                |--------------------------------------------------------------------------
                */

                $duePercentage =
                    $expected > 0
                        ? round(
                            (
                                $due
                                / $expected
                            ) * 100,
                            2
                        )
                        : 0;


                /*
                |--------------------------------------------------------------------------
                | Teacher amount
                |--------------------------------------------------------------------------
                |
                | Based on actual collected payments.
                |
                | Historical split comes from
                | PaymentSplitSnapshot.
                |
                */

                $teacherAmount =
                    round(
                        $groupPayments->sum(
                            function ($payment) {

                                return (float)
                                    optional(
                                        $payment->splitSnapshot
                                    )->teacher_amount;
                            }
                        ),
                        2
                    );


                /*
                |--------------------------------------------------------------------------
                | Organizer amount
                |--------------------------------------------------------------------------
                */

                $organizerAmount =
                    round(
                        $groupPayments->sum(
                            function ($payment) {

                                return (float)
                                    optional(
                                        $payment->splitSnapshot
                                    )->organizer_amount;
                            }
                        ),
                        2
                    );


                /*
                |--------------------------------------------------------------------------
                | Institution amount
                |--------------------------------------------------------------------------
                */

                $institutionAmount =
                    round(
                        $groupPayments->sum(
                            function ($payment) {

                                return (float)
                                    optional(
                                        $payment->splitSnapshot
                                    )->institution_amount;
                            }
                        ),
                        2
                    );


                /*
                |--------------------------------------------------------------------------
                | Return row
                |--------------------------------------------------------------------------
                */

                return [

                    /*
                    |--------------------------------------------------------------------------
                    | Class
                    |--------------------------------------------------------------------------
                    */

                    'student_class_id' =>
                        optional($class)->id,

                    'class_name' =>
                        optional($class)->class_name
                        ?: 'Unknown Class',


                    /*
                    |--------------------------------------------------------------------------
                    | Grade
                    |--------------------------------------------------------------------------
                    */

                    'grade_name' =>
                        optional(
                            optional($class)->grade
                        )->grade_name
                        ?: 'Unknown Grade',


                    /*
                    |--------------------------------------------------------------------------
                    | Category
                    |--------------------------------------------------------------------------
                    */

                    'category_fee_id' =>
                        optional($categoryFee)->id,

                    'category_name' =>
                        optional($category)->category_name
                        ?: 'Unknown Category',


                    /*
                    |--------------------------------------------------------------------------
                    | Fee Options
                    |--------------------------------------------------------------------------
                    */

                    'fee_options' =>
                        $feeOptions,


                    /*
                    |--------------------------------------------------------------------------
                    | Student Count
                    |--------------------------------------------------------------------------
                    */

                    'student_count' =>
                        $studentCount,


                    /*
                    |--------------------------------------------------------------------------
                    | Financial Summary
                    |--------------------------------------------------------------------------
                    */

                    'expected' =>
                        $expected,

                    'collected' =>
                        $collected,

                    'due' =>
                        $due,


                    /*
                    |--------------------------------------------------------------------------
                    | Percentages
                    |--------------------------------------------------------------------------
                    */

                    'collection_percentage' =>
                        $collectionPercentage,

                    'due_percentage' =>
                        $duePercentage,


                    /*
                    |--------------------------------------------------------------------------
                    | Distribution
                    |--------------------------------------------------------------------------
                    */

                    'teacher_amount' =>
                        $teacherAmount,

                    'organizer_amount' =>
                        $organizerAmount,

                    'institution_amount' =>
                        $institutionAmount,
                ];
            })

            ->values();


        /*
        |--------------------------------------------------------------------------
        | Institute totals
        |--------------------------------------------------------------------------
        */

        $totalExpected =
            round(
                $rows->sum('expected'),
                2
            );


        $totalCollected =
            round(
                $rows->sum('collected'),
                2
            );


        $totalDue =
            round(
                max(
                    $totalExpected
                    - $totalCollected,
                    0
                ),
                2
            );


        /*
        |--------------------------------------------------------------------------
        | Total Collection %
        |--------------------------------------------------------------------------
        */

        $totalCollectionPercentage =
            $totalExpected > 0
                ? round(
                    (
                        $totalCollected
                        / $totalExpected
                    ) * 100,
                    2
                )
                : 0;


        /*
        |--------------------------------------------------------------------------
        | Total Due %
        |--------------------------------------------------------------------------
        */

        $totalDuePercentage =
            $totalExpected > 0
                ? round(
                    (
                        $totalDue
                        / $totalExpected
                    ) * 100,
                    2
                )
                : 0;


        /*
        |--------------------------------------------------------------------------
        | Total Teacher
        |--------------------------------------------------------------------------
        */

        $totalTeacher =
            round(
                $rows->sum('teacher_amount'),
                2
            );


        /*
        |--------------------------------------------------------------------------
        | Total Organizer
        |--------------------------------------------------------------------------
        */

        $totalOrganizer =
            round(
                $rows->sum('organizer_amount'),
                2
            );


        /*
        |--------------------------------------------------------------------------
        | Total Institution
        |--------------------------------------------------------------------------
        */

        $totalInstitution =
            round(
                $rows->sum('institution_amount'),
                2
            );


        /*
        |--------------------------------------------------------------------------
        | Return complete report
        |--------------------------------------------------------------------------
        */

        return [

            /*
            |--------------------------------------------------------------------------
            | Month
            |--------------------------------------------------------------------------
            */

            'month' =>
                $month->format('F Y'),

            'month_start' =>
                $monthStart,


            /*
            |--------------------------------------------------------------------------
            | Summary
            |--------------------------------------------------------------------------
            */

            'summary' => [

                'student_count' =>
                    $enrollments->count(),

                'expected' =>
                    $totalExpected,

                'collected' =>
                    $totalCollected,

                'due' =>
                    $totalDue,

                'collection_percentage' =>
                    $totalCollectionPercentage,

                'due_percentage' =>
                    $totalDuePercentage,

                'teacher_amount' =>
                    $totalTeacher,

                'organizer_amount' =>
                    $totalOrganizer,

                'institution_amount' =>
                    $totalInstitution,
            ],


            /*
            |--------------------------------------------------------------------------
            | Class + Grade + Category Rows
            |--------------------------------------------------------------------------
            */

            'rows' =>
                $rows,
        ];
    }
}
@extends('layouts.app')

@section('content')

    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0">
                    Student Payment List
                </h4>

                <small class="text-muted">
                    Teacher: {{ $teacher->full_name }}
                    |
                    Class: {{ $studentClass->class_name }}
                    |
                    Grade: {{ optional($studentClass->grade)->grade_name ?? '-' }}
                    |
                    Category: {{ optional($categoryFee->category)->category_name ?? '-' }}
                    |
                    {{ $year }}-{{ str_pad($month, 2, '0', STR_PAD_LEFT) }}
                </small>
            </div>

            <a href="{{ route('admin.teacher-salaries.show', [
                'teacher' => $teacher->id,
                'year' => $year,
                'month' => $month,
            ]) }}" class="btn btn-secondary">

                Back

            </a>
        </div>


        @php

            /*
            |--------------------------------------------------------------------------
            | PAGE TOTALS
            |--------------------------------------------------------------------------
            */

            $totalFinalFee = $enrollments->sum(function ($enrollment) {
                return (float) $enrollment->final_fee;
            });

            $totalPaid = $enrollments->sum(function ($enrollment) {
                return $enrollment->payments->sum('amount');
            });

            $totalPayments = $enrollments->sum(function ($enrollment) {
                return $enrollment->payments->count();
            });

        @endphp


        <!-- SUMMARY -->

        <div class="row mb-4">

            <div class="col-md-4">

                <div class="card shadow-sm border-0">

                    <div class="card-body">

                        <small>Total Students</small>

                        <h4>
                            {{ $enrollments->total() }}
                        </h4>

                    </div>

                </div>

            </div>


            <div class="col-md-4">

                <div class="card shadow-sm border-0">

                    <div class="card-body">

                        <small>Total Payments</small>

                        <h4>
                            {{ $totalPayments }}
                        </h4>

                    </div>

                </div>

            </div>


            <div class="col-md-4">

                <div class="card shadow-sm border-0">

                    <div class="card-body">

                        <small>Paid Total</small>

                        <h4>
                            {{ number_format($totalPaid, 2) }}
                        </h4>

                    </div>

                </div>

            </div>

        </div>


        <!-- PAYMENT TABLE -->

        <div class="card shadow-sm border-0">

            <div class="card-body table-responsive">

                <table class="table table-bordered table-hover align-middle">

                    <thead class="table-light">

                        <tr>

                            <th>
                                Student ID
                            </th>

                            <th>
                                Student Name
                            </th>

                            <th>
                                Fee Option
                            </th>

                            <th class="text-end">
                                Final Fee
                            </th>

                            <th class="text-end">
                                Payment Count
                            </th>

                            <th class="text-end">
                                Paid Total
                            </th>

                            <th>
                                Payments
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse ($enrollments as $enrollment)

                            @php

                                $student = $enrollment->student;

                                $payments = $enrollment->payments;

                                /*
                                |--------------------------------------------------------------------------
                                | SELECTED FEE OPTION
                                |--------------------------------------------------------------------------
                                */

                                $feeOption = $enrollment->classCategoryFeeOption;

                                $feeOptionLabel = $feeOption
                                    ? $feeOption->label
                                    : 'Fee Option Not Found';

                                $feeOptionFee = $feeOption
                                    ? (float) $feeOption->fee
                                    : 0;

                                /*
                                |--------------------------------------------------------------------------
                                | FINAL FEE
                                |--------------------------------------------------------------------------
                                */

                                $finalFee = $enrollment->is_free_card
                                    ? 0
                                    : (float) $enrollment->final_fee;

                            @endphp


                            <tr>

                                <!-- STUDENT ID -->

                                <td>

                                    {{ optional($student)->custom_id ?? optional($student)->id ?? '-' }}

                                </td>


                                <!-- STUDENT NAME -->

                                <td>

                                    <strong>

                                        {{ optional($student)->initial_name
                                            ?? optional($student)->full_name
                                            ?? '-' }}

                                    </strong>

                                </td>


                                <!-- FEE OPTION -->

                                <td>

                                    @if ($enrollment->is_free_card)

                                        <span class="badge bg-success">

                                            Free Card

                                        </span>

                                    @else

                                        <div>

                                            <span class="badge bg-primary">

                                                {{ $feeOptionLabel }}

                                            </span>

                                            <div class="small text-muted mt-1">

                                                Option Fee:
                                                LKR {{ number_format($feeOptionFee, 2) }}

                                            </div>

                                        </div>

                                    @endif

                                </td>


                                <!-- FINAL FEE -->

                                <td class="text-end">

                                    @if ($enrollment->is_free_card)

                                        <span class="text-success fw-bold">

                                            FREE

                                        </span>

                                    @else

                                        <strong>

                                            {{ number_format($finalFee, 2) }}

                                        </strong>

                                    @endif

                                </td>


                                <!-- PAYMENT COUNT -->

                                <td class="text-end">

                                    {{ $payments->count() }}

                                </td>


                                <!-- PAID TOTAL -->

                                <td class="text-end fw-bold text-success">

                                    {{ number_format($payments->sum('amount'), 2) }}

                                </td>


                                <!-- PAYMENT DETAILS -->

                                <td>

                                    @forelse ($payments as $payment)

                                        <div class="border rounded p-2 mb-1 bg-light">

                                            <strong>

                                                {{ $payment->receipt_number ?? 'No Receipt' }}

                                            </strong>

                                            <br>

                                            Amount:

                                            <strong>

                                                {{ number_format($payment->amount, 2) }}

                                            </strong>

                                            <br>

                                            Paid Month:

                                            {{ \Carbon\Carbon::parse($payment->payment_month)->format('F Y') }}

                                            <br>

                                            Paid At:

                                            {{ $payment->paid_at
                                                ? $payment->paid_at->format('Y-m-d H:i:s')
                                                : '-' }}

                                            <br>

                                            Method:

                                            {{ ucfirst($payment->payment_method ?? '-') }}

                                        </div>

                                    @empty

                                        <span class="text-muted">

                                            No payment for selected month

                                        </span>

                                    @endforelse

                                </td>

                            </tr>


                        @empty

                            <tr>

                                <td colspan="7" class="text-center text-muted">

                                    No enrolled students found

                                </td>

                            </tr>

                        @endforelse

                    </tbody>


                    <!-- TOTAL -->

                    <tfoot class="table-light">

                        <tr>

                            <th colspan="3" class="text-end">

                                Page Total

                            </th>


                            <th class="text-end">

                                {{ number_format($totalFinalFee, 2) }}

                            </th>


                            <th class="text-end">

                                {{ $totalPayments }}

                            </th>


                            <th class="text-end">

                                {{ number_format($totalPaid, 2) }}

                            </th>


                            <th></th>

                        </tr>

                    </tfoot>

                </table>


                {{ $enrollments->links() }}


            </div>

        </div>

    </div>

@endsection
@php
    $summary = $summary ?? [
        'class_income' => 0,

        // Fee breakdown
        'class_fee_income' => 0,
        'hall_fee_income' => 0,
        'total_fee_income' => 0,

        // Income distribution
        'admission_income' => 0,
        'teacher_income' => 0,
        'organizer_income' => 0,
        'institution_income' => 0,

        // Other income / expenses
        'extra_income' => 0,
        'total_expenses' => 0,

        // Final
        'gross_income' => 0,
        'net_income' => 0,
    ];
@endphp


{{-- ============================================================
     MAIN STUDENT PAYMENT BREAKDOWN
============================================================ --}}

<div class="row g-3 mb-4">

    {{-- Total Student Payments --}}
    <div class="col-md-3">
        <div class="summary-card summary-blue">
            <div class="card-body text-center">
                <small class="summary-label">Student Payments</small>

                <h3 class="summary-value">
                    LKR {{ number_format($summary['class_income'] ?? 0, 2) }}
                </h3>

                <small class="summary-sub">
                    Actual Payments Collected
                </small>
            </div>
        </div>
    </div>


    {{-- Class Fee --}}
    <div class="col-md-3">
        <div class="summary-card summary-info">
            <div class="card-body text-center">
                <small class="summary-label">Class Fee</small>

                <h3 class="summary-value">
                    LKR {{ number_format($summary['class_fee_income'] ?? 0, 2) }}
                </h3>

                <small class="summary-sub">
                    Teacher / Organizer Split
                </small>
            </div>
        </div>
    </div>


    {{-- Hall Fee --}}
    <div class="col-md-3">
        <div class="summary-card summary-warning">
            <div class="card-body text-center">
                <small class="summary-label">Hall Fee</small>

                <h3 class="summary-value">
                    LKR {{ number_format($summary['hall_fee_income'] ?? 0, 2) }}
                </h3>

                <small class="summary-sub">
                    100% Institute Income
                </small>
            </div>
        </div>
    </div>


    {{-- Total Fee --}}
    <div class="col-md-3">
        <div class="summary-card summary-dark">
            <div class="card-body text-center">
                <small class="summary-label">Total Fee</small>

                <h3 class="summary-value">
                    LKR {{ number_format($summary['total_fee_income'] ?? 0, 2) }}
                </h3>

                <small class="summary-sub">
                    Class Fee + Hall Fee
                </small>
            </div>
        </div>
    </div>

</div>


{{-- ============================================================
     INCOME DISTRIBUTION
============================================================ --}}

<div class="row g-3 mb-4">

    {{-- Teacher Income --}}
    <div class="col-md-3">
        <div class="summary-card summary-green">
            <div class="card-body text-center">
                <small class="summary-label">Teacher Income</small>

                <h3 class="summary-value">
                    LKR {{ number_format($summary['teacher_income'] ?? 0, 2) }}
                </h3>

                <small class="summary-sub">
                    Teacher Share
                </small>
            </div>
        </div>
    </div>


    {{-- Organizer Income --}}
    <div class="col-md-3">
        <div class="summary-card summary-warning">
            <div class="card-body text-center">
                <small class="summary-label">Organizer Income</small>

                <h3 class="summary-value">
                    LKR {{ number_format($summary['organizer_income'] ?? 0, 2) }}
                </h3>

                <small class="summary-sub">
                    Organizer Share
                </small>
            </div>
        </div>
    </div>


    {{-- Institute Income --}}
    <div class="col-md-3">
        <div class="summary-card summary-dark">
            <div class="card-body text-center">
                <small class="summary-label">Institute Income</small>

                <h3 class="summary-value">
                    LKR {{ number_format($summary['institution_income'] ?? 0, 2) }}
                </h3>

                <small class="summary-sub">
                    Class Share + Hall Fee
                </small>
            </div>
        </div>
    </div>


    {{-- Admission Income --}}
    <div class="col-md-3">
        <div class="summary-card summary-info">
            <div class="card-body text-center">
                <small class="summary-label">Admission Income</small>

                <h3 class="summary-value">
                    LKR {{ number_format($summary['admission_income'] ?? 0, 2) }}
                </h3>

                <small class="summary-sub">
                    Admission Payments
                </small>
            </div>
        </div>
    </div>

</div>


{{-- ============================================================
     OTHER INCOME / EXPENSES
============================================================ --}}

<div class="row g-3 mb-4">

    {{-- Extra Income --}}
    <div class="col-md-4">
        <div class="summary-card summary-green">
            <div class="card-body text-center">
                <small class="summary-label">Extra Income</small>

                <h3 class="summary-value">
                    LKR {{ number_format($summary['extra_income'] ?? 0, 2) }}
                </h3>

                <small class="summary-sub">
                    Additional Income
                </small>
            </div>
        </div>
    </div>


    {{-- Gross Income --}}
    <div class="col-md-4">
        <div class="summary-card summary-blue">
            <div class="card-body text-center">
                <small class="summary-label">Gross Income</small>

                <h3 class="summary-value">
                    LKR {{ number_format($summary['gross_income'] ?? 0, 2) }}
                </h3>

                <small class="summary-sub">
                    Institute Income + Admission + Extra
                </small>
            </div>
        </div>
    </div>


    {{-- Expenses --}}
    <div class="col-md-4">
        <div class="summary-card summary-red">
            <div class="card-body text-center">
                <small class="summary-label">Expenses</small>

                <h3 class="summary-value">
                    LKR {{ number_format($summary['total_expenses'] ?? 0, 2) }}
                </h3>

                <small class="summary-sub">
                    Total Institute Expenses
                </small>
            </div>
        </div>
    </div>

</div>


{{-- ============================================================
     NET INCOME
============================================================ --}}

<div class="row g-3 mb-4">

    <div class="col-12">
        <div
            class="summary-card"
            style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);"
        >
            <div class="card-body text-center">

                <small class="summary-label text-white-50">
                    Net Institute Income
                </small>

                <h3 class="summary-value text-white">
                    LKR {{ number_format($summary['net_income'] ?? 0, 2) }}
                </h3>

                <small class="summary-sub text-white-50">
                    Gross Income - Expenses
                </small>

            </div>
        </div>
    </div>

</div>
@php
    $organizerSummaries = $organizer_summaries ?? [];
@endphp

<div class="main-card mb-4">
    <div class="main-card-header">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="bi bi-person-bounding-box me-2"></i>
                Organizer Income Summary
            </h4>

            <p class="mb-0">
                Organizer income distribution for the selected month
            </p>
        </div>

        <div class="header-badge">
            {{ count($organizerSummaries) }} Organizers
        </div>
    </div>

    <div class="table-responsive" style="max-height: 400px;">
        <table class="table custom-table table-bordered table-hover align-middle mb-0 table-sticky">

            <thead>
                <tr>
                    <th>Organizer</th>
                    <th>Payments</th>
                    <th>Student Payments (LKR)</th>
                    <th>Class Fee (LKR)</th>
                    <th>Hall Fee (LKR)</th>
                    <th>Organizer (LKR)</th>
                    <th>Institute (LKR)</th>
                </tr>
            </thead>

            <tbody>

                @forelse($organizerSummaries as $org)

                    <tr>

                        {{-- Organizer --}}
                        <td>
                            <div class="fw-semibold">
                                {{ $org['organizer_name'] ?? 'Unknown' }}
                            </div>

                            @if(!empty($org['organizer_code']))
                                <small class="text-muted">
                                    {{ $org['organizer_code'] }}
                                </small>
                            @endif
                        </td>

                        {{-- Payment Count --}}
                        <td>
                            <span class="badge bg-primary custom-badge">
                                {{ $org['payment_count'] ?? 0 }}
                            </span>
                        </td>

                        {{-- Actual Student Payments --}}
                        <td class="fw-semibold">
                            LKR
                            {{ number_format($org['total_income'] ?? 0, 2) }}
                        </td>

                        {{-- Class Fee --}}
                        <td>
                            LKR
                            {{ number_format($org['class_fee_income'] ?? 0, 2) }}
                        </td>

                        {{-- Hall Fee --}}
                        <td class="text-warning fw-bold">
                            LKR
                            {{ number_format($org['hall_fee_income'] ?? 0, 2) }}
                        </td>

                        {{-- Organizer Income --}}
                        <td class="text-success fw-bold">
                            LKR
                            {{ number_format($org['organizer_income'] ?? 0, 2) }}
                        </td>

                        {{-- Institute Income --}}
                        <td class="text-primary fw-bold">
                            LKR
                            {{ number_format($org['institution_income'] ?? 0, 2) }}
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">

                            <i class="bi bi-inbox fs-4 d-block mb-2"></i>

                            No organizer data found

                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>
    </div>
</div>
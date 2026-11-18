@extends('layouts.app')

@section('title', 'Student Card History')
@section('page-title', 'Student Card History')

@section('content')

<div class="ui-container-fluid">

    {{-- Header --}}
    <div class="tw:flex! tw:justify-between! tw:items-center! tw:mb-6!">

        <div>
            <h4 class="tw:font-bold! tw:mb-1!">Student Card History</h4>
            <p class="tw:text-bs-secondary-color! tw:mb-0!">
                Complete card assignment and replacement history
            </p>
        </div>

        <a href="{{ route('admin.students.show', $student->id) }}"
           class="ui-btn ui-btn-secondary">
            <i class="bi bi-arrow-left"></i>
            Back
        </a>

    </div>

    {{-- Student Information --}}
    <div class="ui-card tw:shadow-bs-sm! tw:[border:0]! tw:mb-6!">

        <div class="ui-card-header header-primary tw:bg-bs-primary! tw:text-bs-white!">
            <h5 class="tw:mb-0!">
                <i class="bi bi-person-badge"></i>
                Student Information
            </h5>
        </div>

        <div class="ui-card-body">

            <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3">

                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[25%] tw:mb-4!">
                    <label class="tw:text-bs-secondary-color!">Student Name</label>
                    <h6 class="tw:font-bold!">
                        {{ $student->full_name }}
                    </h6>
                </div>

                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[25%] tw:mb-4!">
                    <label class="tw:text-bs-secondary-color!">Registration No</label>
                    <h6 class="tw:font-bold!">
                        {{ $student->registration_number }}
                    </h6>
                </div>

                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[25%] tw:mb-4!">
                    <label class="tw:text-bs-secondary-color!">Current QR</label>

                    @if($student->custom_id)

                        <div>
                            <span class="ui-badge tw:bg-bs-success!">
                                {{ $student->custom_id }}
                            </span>
                        </div>

                    @else

                        <span class="ui-badge tw:bg-bs-danger!">
                            No Active Card
                        </span>

                    @endif

                </div>

                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[25%] tw:mb-4!">

                    <label class="tw:text-bs-secondary-color!">Status</label>

                    @if($student->student_disable)

                        <div>
                            <span class="ui-badge tw:bg-bs-danger!">
                                Disabled
                            </span>
                        </div>

                    @else

                        <div>
                            <span class="ui-badge tw:bg-bs-success!">
                                Active
                            </span>
                        </div>

                    @endif

                </div>

            </div>

        </div>

    </div>

    {{-- History Table --}}
    <div class="ui-card tw:shadow-bs-sm! tw:[border:0]!">

        <div class="ui-card-header">

            <h5 class="tw:mb-0!">
                <i class="bi bi-clock-history"></i>
                Card History
            </h5>

        </div>

        <div class="ui-card-body tw:p-0!">

            <div class="ui-table-responsive">

                <table class="ui-table ui-table-hover tw:align-middle! tw:mb-0!">

                    <thead class="ui-table-light">

                    <tr>

                        <th>#</th>
                        <th>Action</th>
                        <th>Card</th>
                        <th>Old Card</th>
                        <th>New Card</th>
                        <th>Reason</th>
                        <th>Performed By</th>
                        <th>Date</th>

                    </tr>

                    </thead>

                    <tbody>

                    @forelse($cards as $history)

                        <tr>

                            <td>
                                {{ $cards->firstItem() + $loop->index }}
                            </td>

                            <td>

                                @switch($history->action)

                                    @case('assigned')
                                        <span class="ui-badge tw:bg-bs-primary!">
                                            Assigned
                                        </span>
                                    @break

                                    @case('replaced')
                                        <span class="ui-badge tw:bg-bs-info!">
                                            Replaced
                                        </span>
                                    @break

                                    @case('lost')
                                        <span class="ui-badge tw:bg-bs-danger!">
                                            Lost
                                        </span>
                                    @break

                                    @case('damaged')
                                        <span class="ui-badge tw:bg-bs-warning! tw:text-bs-dark!">
                                            Damaged
                                        </span>
                                    @break

                                    @case('deactivated')
                                        <span class="ui-badge tw:bg-bs-secondary!">
                                            Deactivated
                                        </span>
                                    @break

                                @endswitch

                            </td>

                            <td>
                                {{ optional($history->card)->card_number ?? '-' }}
                            </td>

                            <td>
                                {{ optional($history->oldCard)->card_number ?? '-' }}
                            </td>

                            <td>
                                {{ optional($history->newCard)->card_number ?? '-' }}
                            </td>

                            <td>
                                {{ $history->reason ?? '-' }}
                            </td>

                            <td>
                                {{ optional($history->performedBy)->name ?? 'System' }}
                            </td>

                            <td>
                                {{ $history->performed_at->format('d M Y h:i A') }}
                            </td>

                        </tr>
                                            @empty

                        <tr>

                            <td colspan="8" class="tw:text-center! tw:py-12!">

                                <i class="bi bi-clock-history tw:text-[length:calc(1.375rem_+_1.5vw)]! tw:xl:text-[length:2.5rem]! tw:text-bs-secondary-color!"></i>

                                <h5 class="tw:mt-4!">
                                    No Card History Found
                                </h5>

                                <p class="tw:text-bs-secondary-color! tw:mb-0!">
                                    This student has no recorded card history.
                                </p>

                            </td>

                        </tr>

                    @endforelse

                    </tbody>

                </table>

            </div>

        </div>

        @if($cards->hasPages())

            <div class="ui-card-footer tw:bg-bs-white!">

                {{ $cards->links() }}

            </div>

        @endif

    </div>

</div>

@endsection


@push('styles')

<style>

.ui-card{
    border-radius:16px;
}

.ui-card-header{
    font-weight:600;
}

.ui-table th{
    font-size:14px;
    font-weight:600;
    white-space:nowrap;
}

.ui-table td{
    vertical-align:middle;
}

.ui-badge{
    font-size:12px;
    padding:7px 10px;
}

.ui-table tbody tr:hover{
    background:#f8f9fa;
}

label{
    font-size:13px;
    font-weight:600;
    color:#6c757d;
    margin-bottom:5px;
}

h6{
    margin-bottom:0;
}

.ui-card-header.header-primary{
    border-radius:16px 16px 0 0;
}

.ui-pagination{
    margin-bottom:0;
}

.ui-pagination .ui-page-link{
    border-radius:8px;
    margin:0 2px;
}

@media(max-width:768px){

    .ui-table{
        font-size:13px;
    }

    .ui-badge{
        font-size:11px;
    }

    h4{
        font-size:20px;
    }

}

</style>

@endpush
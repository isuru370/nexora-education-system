@extends('layouts.app')

@section('title', 'Category Students')
@section('page-title', 'Category Students')

@section('content')

    <div class="ui-container-fluid">

        {{-- =========================================================
        HEADER
    ========================================================== --}}
        <div class="ui-card tw:[border:0]! tw:shadow-bs-sm! tw:rounded-bs-xl! tw:mb-6!">

            <div class="ui-card-header tw:bg-bs-white! tw:[border:0]! tw:py-4!">

                <div class="tw:flex! tw:justify-between! tw:items-center! tw:flex-wrap! tw:gap-4!">

                    <div>

                        <h5 class="tw:mb-1! tw:font-bold!">

                            {{ $studentClass->class_name }}

                            {{-- ✅ Branch badge --}}
                            @if ($studentClass->branch)
                                <span class="ui-badge tw:bg-bs-info! tw:text-bs-dark! tw:ml-2!">
                                    <i class="bi bi-building tw:mr-1!"></i>
                                    {{ $studentClass->branch->name }}
                                </span>
                            @endif

                        </h5>

                        <small class="tw:text-bs-secondary-color!">

                            Grade:
                            {{ optional($studentClass->grade)->grade_name ?? '-' }}

                            <span class="tw:mx-1!">|</span>

                            Subject:
                            {{ optional($studentClass->subject)->subject_name ?? '-' }}

                            <span class="tw:mx-1!">|</span>

                            Teacher:
                            {{ optional($studentClass->teacher)->initials ?? '-' }}

                            <span class="tw:mx-1!">|</span>

                            Category:
                            <strong class="tw:text-bs-primary!">{{ $classCategory->category_name }}</strong>

                        </small>

                    </div>


                    <div class="tw:flex! tw:gap-2! tw:flex-wrap!">

                        {{-- PDF Export --}}
                        <a href="{{ route(
                            'student-class-enrollments.category-students.pdf',
                            [
                                'studentClass' => $studentClass->id,
                                'classCategory' => $classCategory->id,
                            ] + request()->query(),
                        ) }}"
                            class="ui-btn ui-btn-danger ui-btn-sm">

                            <i class="bi bi-file-earmark-pdf tw:mr-1!"></i>
                            PDF

                        </a>


                        {{-- Excel Export --}}
                        <a href="{{ route(
                            'student-class-enrollments.category-students.excel',
                            [
                                'studentClass' => $studentClass->id,
                                'classCategory' => $classCategory->id,
                            ] + request()->query(),
                        ) }}"
                            class="ui-btn ui-btn-success ui-btn-sm">

                            <i class="bi bi-file-earmark-excel tw:mr-1!"></i>
                            Excel

                        </a>


                        {{-- Back --}}
                        <a href="{{ route('student-class-enrollments.index') }}" class="ui-btn ui-btn-outline-secondary ui-btn-sm">

                            <i class="bi bi-arrow-left tw:mr-1!"></i>
                            Back

                        </a>

                    </div>

                </div>

            </div>


            {{-- =====================================================
            BODY
        ====================================================== --}}
            <div class="ui-card-body">

                {{-- =================================================
                ALERTS
            ================================================== --}}
                @if (session('success'))
                    <div class="ui-alert ui-alert-success tw:[border:0]! tw:shadow-bs-sm! tw:rounded-bs-lg!">
                        <i class="bi bi-check-circle tw:mr-1!"></i>
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="ui-alert ui-alert-danger tw:[border:0]! tw:shadow-bs-sm! tw:rounded-bs-lg!">
                        <i class="bi bi-exclamation-circle tw:mr-1!"></i>
                        {{ session('error') }}
                    </div>
                @endif


                {{-- =================================================
                SEARCH
            ================================================== --}}
                <form method="GET"
                    action="{{ route('student-class-enrollments.category-students', [
                        'studentClass' => $studentClass->id,
                        'classCategory' => $classCategory->id,
                    ]) }}"
                    class="tw:flex tw:flex-wrap tw:-mt-2 tw:-mx-1 tw:mb-6!">

                    <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[58.33333333%]">
                        <input type="text" name="search" class="ui-form-control"
                            placeholder="Search Student ID / QR / Initial Name / Full Name / Mobile..."
                            value="{{ request('search') }}">
                    </div>


                    <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[16.66666667%]">
                        <select name="per_page" class="ui-form-select">
                            @foreach ([10, 20, 50, 100] as $count)
                                <option value="{{ $count }}"
                                    {{ (int) request('per_page', 20) === $count ? 'selected' : '' }}>
                                    {{ $count }}
                                </option>
                            @endforeach
                        </select>
                    </div>


                    <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[8.33333333%]">
                        <button class="ui-btn ui-btn-primary tw:w-full!" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>


                    <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[16.66666667%]">
                        <a href="{{ route('student-class-enrollments.category-students', [
                            'studentClass' => $studentClass->id,
                            'classCategory' => $classCategory->id,
                        ]) }}"
                            class="ui-btn ui-btn-outline-secondary tw:w-full!">
                            Reset
                        </a>
                    </div>

                </form>


                {{-- =================================================
                SUMMARY BADGES
            ================================================== --}}
                <div class="tw:flex! tw:gap-2! tw:mb-4! tw:flex-wrap!">

                    <span class="ui-badge tw:bg-bs-primary!">
                        Total: {{ $enrollments->total() }}
                    </span>

                    <span class="ui-badge tw:bg-bs-success!">
                        Active: {{ $enrollments->where('is_active', true)->count() }}
                    </span>

                    <span class="ui-badge tw:bg-bs-secondary!">
                        Inactive: {{ $enrollments->where('is_active', false)->count() }}
                    </span>

                    <span class="ui-badge tw:bg-bs-warning! tw:text-bs-dark!">
                        Free Cards: {{ $enrollments->where('is_free_card', true)->count() }}
                    </span>

                </div>


                {{-- =================================================
                STUDENTS TABLE
            ================================================== --}}
                <div class="ui-table-responsive">

                    <table class="ui-table ui-table-hover tw:align-middle!">

                        <thead class="ui-table-light">
                            <tr>
                                <th>#</th>
                                <th>Student</th>
                                <th>Branch</th>
                                <th>Contact</th>
                                <th>Fee Option</th>
                                <th class="tw:text-right!">Fee</th>
                                <th>Status</th>
                                <th class="tw:text-right!">Actions</th>
                            </tr>
                        </thead>


                        <tbody>

                            @forelse($enrollments as $key => $enrollment)

                                @php
                                    $student = $enrollment->student;

                                    /*
                                |------------------------------------------------------------------
                                | QR Details
                                |------------------------------------------------------------------
                                */
                                    $studentCode = '-';
                                    $qrType = '-';
                                    $qrBadge = 'tw:bg-bs-secondary!';

                                    if (
                                        $student &&
                                        $student->permanent_qr_active &&
                                        !empty($student->custom_id) &&
                                        $student->custom_id != '0'
                                    ) {
                                        $studentCode = $student->custom_id;
                                        $qrType = 'Permanent QR';
                                        $qrBadge = 'tw:bg-bs-success!';
                                    } elseif ($student && $student->temporary_qr_code) {
                                        $studentCode = $student->temporary_qr_code;
                                        $qrType = 'Temporary QR';
                                        $qrBadge = 'tw:bg-bs-warning! tw:text-bs-dark!';
                                    }

                                    /*
                                |------------------------------------------------------------------
                                | Fee Option
                                |------------------------------------------------------------------
                                */
                                    $feeOption = $enrollment->classCategoryFeeOption;

                                    /*
                                |------------------------------------------------------------------
                                | Final Fee
                                |------------------------------------------------------------------
                                */
                                    $selectedFee = $enrollment->final_fee;
                                @endphp


                                <tr>

                                    {{-- # --}}
                                    <td class="tw:text-bs-secondary-color!">
                                        {{ $enrollments->firstItem() + $key }}
                                    </td>


                                    {{-- STUDENT --}}
                                    <td>
                                        <div class="tw:font-bold!">
                                            {{ $student->full_name ?? '-' }}
                                        </div>

                                        <small class="tw:text-bs-secondary-color! tw:block!">
                                            {{ $student->initial_name ?? '-' }}
                                        </small>

                                        <small class="tw:text-bs-secondary-color!">
                                            ID: {{ $student->custom_id ?? '-' }}
                                        </small>
                                    </td>


                                    {{-- BRANCH --}}
                                    <td>
                                        @if ($enrollment->branch)
                                            <span class="ui-badge tw:bg-bs-info! tw:text-bs-dark!">
                                                <i class="bi bi-building tw:mr-1!"></i>
                                                {{ $enrollment->branch->name }}
                                            </span>
                                        @else
                                            <span class="tw:text-bs-secondary-color!">-</span>
                                        @endif
                                    </td>


                                    {{-- CONTACT --}}
                                    <td>
                                        @if ($student && $student->mobile)
                                            <a href="tel:{{ $student->mobile }}">
                                                {{ $student->mobile }}
                                            </a>
                                        @else
                                            <span class="tw:text-bs-secondary-color!">-</span>
                                        @endif
                                    </td>


                                    {{-- FEE OPTION --}}
                                    <td>
                                        @if ($enrollment->is_free_card)
                                            <span class="ui-badge tw:bg-bs-dark!">
                                                <i class="bi bi-gift tw:mr-1!"></i>
                                                Free Card
                                            </span>
                                        @elseif($feeOption)
                                            <span class="ui-badge tw:bg-bs-primary!">
                                                {{ $feeOption->label }}
                                            </span>

                                            @if ($feeOption->is_default)
                                                <br>
                                                <small class="tw:text-bs-secondary-color!">
                                                    <i class="bi bi-star-fill tw:text-bs-warning!"></i>
                                                    Default Option
                                                </small>
                                            @endif
                                        @else
                                            <span class="tw:text-bs-secondary-color!">-</span>
                                        @endif
                                    </td>


                                    {{-- FEE --}}
                                    <td class="tw:text-right!">
                                        @if ($enrollment->is_free_card)
                                            <span class="tw:font-bold! tw:text-bs-success!">
                                                Free
                                            </span>
                                        @elseif($feeOption)
                                            <span class="tw:font-bold! tw:text-bs-primary!">
                                                Rs. {{ number_format((float) $selectedFee, 2) }}
                                            </span>

                                            @if ((float) $feeOption->fee !== (float) $selectedFee)
                                                <br>
                                                <small class="tw:text-bs-secondary-color!">
                                                    Option: Rs. {{ number_format((float) $feeOption->fee, 2) }}
                                                </small>
                                            @endif
                                        @else
                                            <span class="tw:text-bs-secondary-color!">-</span>
                                        @endif
                                    </td>


                                    {{-- STATUS --}}
                                    <td>
                                        @if ($enrollment->is_active)
                                            <span class="ui-badge tw:bg-bs-success!">
                                                <i class="bi bi-check-circle tw:mr-1!"></i>
                                                Active
                                            </span>
                                        @else
                                            <span class="ui-badge tw:bg-bs-secondary!">
                                                <i class="bi bi-x-circle tw:mr-1!"></i>
                                                Inactive
                                            </span>

                                            @if ($enrollment->left_at)
                                                <br>
                                                <small class="tw:text-bs-secondary-color!">
                                                    Left: {{ $enrollment->left_at->format('Y-m-d') }}
                                                </small>
                                            @endif
                                        @endif
                                    </td>


                                    {{-- ACTIONS --}}
                                    <td class="tw:text-right!">
                                        <div class="tw:flex! tw:justify-end! tw:gap-1! tw:flex-wrap!">

                                            {{-- View --}}
                                            <a href="{{ route('student-class-enrollments.show', $enrollment) }}"
                                                class="ui-btn ui-btn-sm ui-btn-outline-primary" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </a>


                                            {{-- Edit --}}
                                            <a href="{{ route('student-class-enrollments.edit', $enrollment) }}"
                                                class="ui-btn ui-btn-sm ui-btn-outline-warning" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>


                                            {{-- Toggle --}}
                                            <form method="POST"
                                                action="{{ route('student-class-enrollments.toggle-active', $enrollment) }}"
                                                onsubmit="return confirm('Change enrollment status?');">
                                                @csrf
                                                @method('PATCH')

                                                @if ($enrollment->is_active)
                                                    <button type="submit" class="ui-btn ui-btn-sm ui-btn-outline-secondary"
                                                        title="Deactivate">
                                                        <i class="bi bi-pause-fill"></i>
                                                    </button>
                                                @else
                                                    <button type="submit" class="ui-btn ui-btn-sm ui-btn-outline-success"
                                                        title="Activate">
                                                        <i class="bi bi-play-fill"></i>
                                                    </button>
                                                @endif
                                            </form>

                                        </div>
                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="9" class="tw:text-center! tw:text-bs-secondary-color! tw:py-12!">
                                        <i class="bi bi-people tw:text-[length:calc(1.375rem_+_1.5vw)]! tw:xl:text-[length:2.5rem]! tw:block! tw:mb-2! tw:text-bs-secondary-color!"></i>

                                        <div class="tw:font-semibold!">
                                            No students found
                                        </div>

                                        <small>
                                            Try a different search term.
                                        </small>
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>


                {{-- =================================================
                PAGINATION
            ================================================== --}}
                <div class="tw:mt-4!">
                    {{ $enrollments->appends(request()->query())->links() }}
                </div>

            </div>

        </div>

    </div>

@endsection

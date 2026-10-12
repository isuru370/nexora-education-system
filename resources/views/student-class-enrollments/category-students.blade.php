@extends('layouts.app')

@section('title', 'Category Students')
@section('page-title', 'Category Students')

@section('content')

    <div class="container-fluid">

        {{-- =========================================================
        HEADER
    ========================================================== --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4">

            <div class="card-header bg-white border-0 py-3">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                    <div>

                        <h5 class="mb-1 fw-bold">

                            {{ $studentClass->class_name }}

                            {{-- ✅ Branch badge --}}
                            @if ($studentClass->branch)
                                <span class="badge bg-info text-dark ms-2">
                                    <i class="bi bi-building me-1"></i>
                                    {{ $studentClass->branch->name }}
                                </span>
                            @endif

                        </h5>

                        <small class="text-muted">

                            Grade:
                            {{ optional($studentClass->grade)->grade_name ?? '-' }}

                            <span class="mx-1">|</span>

                            Subject:
                            {{ optional($studentClass->subject)->subject_name ?? '-' }}

                            <span class="mx-1">|</span>

                            Teacher:
                            {{ optional($studentClass->teacher)->initials ?? '-' }}

                            <span class="mx-1">|</span>

                            Category:
                            <strong class="text-primary">{{ $classCategory->category_name }}</strong>

                        </small>

                    </div>


                    <div class="d-flex gap-2 flex-wrap">

                        {{-- PDF Export --}}
                        <a href="{{ route(
                            'student-class-enrollments.category-students.pdf',
                            [
                                'studentClass' => $studentClass->id,
                                'classCategory' => $classCategory->id,
                            ] + request()->query(),
                        ) }}"
                            class="btn btn-danger btn-sm">

                            <i class="bi bi-file-earmark-pdf me-1"></i>
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
                            class="btn btn-success btn-sm">

                            <i class="bi bi-file-earmark-excel me-1"></i>
                            Excel

                        </a>


                        {{-- Back --}}
                        <a href="{{ route('student-class-enrollments.index') }}" class="btn btn-outline-secondary btn-sm">

                            <i class="bi bi-arrow-left me-1"></i>
                            Back

                        </a>

                    </div>

                </div>

            </div>


            {{-- =====================================================
            BODY
        ====================================================== --}}
            <div class="card-body">

                {{-- =================================================
                ALERTS
            ================================================== --}}
                @if (session('success'))
                    <div class="alert alert-success border-0 shadow-sm rounded-3">
                        <i class="bi bi-check-circle me-1"></i>
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger border-0 shadow-sm rounded-3">
                        <i class="bi bi-exclamation-circle me-1"></i>
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
                    class="row g-2 mb-4">

                    <div class="col-md-7">
                        <input type="text" name="search" class="form-control"
                            placeholder="Search Student ID / QR / Initial Name / Full Name / Mobile..."
                            value="{{ request('search') }}">
                    </div>


                    <div class="col-md-2">
                        <select name="per_page" class="form-select">
                            @foreach ([10, 20, 50, 100] as $count)
                                <option value="{{ $count }}"
                                    {{ (int) request('per_page', 20) === $count ? 'selected' : '' }}>
                                    {{ $count }}
                                </option>
                            @endforeach
                        </select>
                    </div>


                    <div class="col-md-1">
                        <button class="btn btn-primary w-100" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>


                    <div class="col-md-2">
                        <a href="{{ route('student-class-enrollments.category-students', [
                            'studentClass' => $studentClass->id,
                            'classCategory' => $classCategory->id,
                        ]) }}"
                            class="btn btn-outline-secondary w-100">
                            Reset
                        </a>
                    </div>

                </form>


                {{-- =================================================
                SUMMARY BADGES
            ================================================== --}}
                <div class="d-flex gap-2 mb-3 flex-wrap">

                    <span class="badge bg-primary">
                        Total: {{ $enrollments->total() }}
                    </span>

                    <span class="badge bg-success">
                        Active: {{ $enrollments->where('is_active', true)->count() }}
                    </span>

                    <span class="badge bg-secondary">
                        Inactive: {{ $enrollments->where('is_active', false)->count() }}
                    </span>

                    <span class="badge bg-warning text-dark">
                        Free Cards: {{ $enrollments->where('is_free_card', true)->count() }}
                    </span>

                </div>


                {{-- =================================================
                STUDENTS TABLE
            ================================================== --}}
                <div class="table-responsive">

                    <table class="table table-hover align-middle">

                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Student</th>
                                <th>Branch</th>
                                <th>Contact</th>
                                <th>Fee Option</th>
                                <th class="text-end">Fee</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
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
                                    $qrBadge = 'bg-secondary';

                                    if (
                                        $student &&
                                        $student->permanent_qr_active &&
                                        !empty($student->custom_id) &&
                                        $student->custom_id != '0'
                                    ) {
                                        $studentCode = $student->custom_id;
                                        $qrType = 'Permanent QR';
                                        $qrBadge = 'bg-success';
                                    } elseif ($student && $student->temporary_qr_code) {
                                        $studentCode = $student->temporary_qr_code;
                                        $qrType = 'Temporary QR';
                                        $qrBadge = 'bg-warning text-dark';
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
                                    <td class="text-muted">
                                        {{ $enrollments->firstItem() + $key }}
                                    </td>


                                    {{-- STUDENT --}}
                                    <td>
                                        <div class="fw-bold">
                                            {{ $student->full_name ?? '-' }}
                                        </div>

                                        <small class="text-muted d-block">
                                            {{ $student->initial_name ?? '-' }}
                                        </small>

                                        <small class="text-muted">
                                            ID: {{ $student->custom_id ?? '-' }}
                                        </small>
                                    </td>


                                    {{-- BRANCH --}}
                                    <td>
                                        @if ($enrollment->branch)
                                            <span class="badge bg-info text-dark">
                                                <i class="bi bi-building me-1"></i>
                                                {{ $enrollment->branch->name }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>


                                    {{-- CONTACT --}}
                                    <td>
                                        @if ($student && $student->mobile)
                                            <a href="tel:{{ $student->mobile }}">
                                                {{ $student->mobile }}
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>


                                    {{-- FEE OPTION --}}
                                    <td>
                                        @if ($enrollment->is_free_card)
                                            <span class="badge bg-dark">
                                                <i class="bi bi-gift me-1"></i>
                                                Free Card
                                            </span>
                                        @elseif($feeOption)
                                            <span class="badge bg-primary">
                                                {{ $feeOption->label }}
                                            </span>

                                            @if ($feeOption->is_default)
                                                <br>
                                                <small class="text-muted">
                                                    <i class="bi bi-star-fill text-warning"></i>
                                                    Default Option
                                                </small>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>


                                    {{-- FEE --}}
                                    <td class="text-end">
                                        @if ($enrollment->is_free_card)
                                            <span class="fw-bold text-success">
                                                Free
                                            </span>
                                        @elseif($feeOption)
                                            <span class="fw-bold text-primary">
                                                Rs. {{ number_format((float) $selectedFee, 2) }}
                                            </span>

                                            @if ((float) $feeOption->fee !== (float) $selectedFee)
                                                <br>
                                                <small class="text-muted">
                                                    Option: Rs. {{ number_format((float) $feeOption->fee, 2) }}
                                                </small>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>


                                    {{-- STATUS --}}
                                    <td>
                                        @if ($enrollment->is_active)
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle me-1"></i>
                                                Active
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">
                                                <i class="bi bi-x-circle me-1"></i>
                                                Inactive
                                            </span>

                                            @if ($enrollment->left_at)
                                                <br>
                                                <small class="text-muted">
                                                    Left: {{ $enrollment->left_at->format('Y-m-d') }}
                                                </small>
                                            @endif
                                        @endif
                                    </td>


                                    {{-- ACTIONS --}}
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1 flex-wrap">

                                            {{-- View --}}
                                            <a href="{{ route('student-class-enrollments.show', $enrollment) }}"
                                                class="btn btn-sm btn-outline-primary" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </a>


                                            {{-- Edit --}}
                                            <a href="{{ route('student-class-enrollments.edit', $enrollment) }}"
                                                class="btn btn-sm btn-outline-warning" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>


                                            {{-- Toggle --}}
                                            <form method="POST"
                                                action="{{ route('student-class-enrollments.toggle-active', $enrollment) }}"
                                                onsubmit="return confirm('Change enrollment status?');">
                                                @csrf
                                                @method('PATCH')

                                                @if ($enrollment->is_active)
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                        title="Deactivate">
                                                        <i class="bi bi-pause-fill"></i>
                                                    </button>
                                                @else
                                                    <button type="submit" class="btn btn-sm btn-outline-success"
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
                                    <td colspan="9" class="text-center text-muted py-5">
                                        <i class="bi bi-people fs-1 d-block mb-2 text-muted"></i>

                                        <div class="fw-semibold">
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
                <div class="mt-3">
                    {{ $enrollments->appends(request()->query())->links() }}
                </div>

            </div>

        </div>

    </div>

@endsection

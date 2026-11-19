@extends('layouts.app')

@section('title', 'Courses')
@section('page-title', 'Courses')

@section('content')
@php
    $canManage = in_array(strtoupper((string) optional(auth()->user()->userType)->code), ['SUPER_ADMIN', 'ADMIN', 'BRANCH_ADMIN'], true);
@endphp
<div class="dashboard-page">

    <div class="hero-section tw:mb-6!">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">nexoraEDU</span>
                <h1 class="hero-title">Courses</h1>
                <p class="hero-subtitle">Courses with their own teachers, fees, installments and attendance.</p>
            </div>
            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ route('course-payments.index') }}" class="nx-btn nx-btn-light"><i class="bi bi-receipt"></i> Course Payments</a>
                    @if ($canManage)
                        <a href="{{ route('course-teacher-payments.index') }}" class="nx-btn nx-btn-light"><i class="bi bi-person-badge"></i> Teacher Payments</a>
                        <a href="{{ route('courses.create') }}" class="nx-btn nx-btn-primary"><i class="bi bi-plus-lg"></i> New Course</a>
                    @endif
                </div>
                <small class="hero-updated">{{ now()->format('D, d M Y') }}</small>
            </div>
        </div>
    </div>

    @include('courses.partials.alerts')

    <div class="nx-card tw:mb-6!">
        <div class="nx-search">
            <form method="GET" action="{{ route('courses.index') }}">
                <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2">
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">
                        <div class="search-input-wrapper">
                            <i class="bi bi-search"></i>
                            <input type="text" name="search" class="ui-form-control custom-input" placeholder="Search course name / code" value="{{ request('search') }}">
                        </div>
                    </div>
                    @if ($branches->count() > 1)
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">
                            <select name="branch_id" class="ui-form-select custom-input">
                                <option value="">All branches</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ (string) request('branch_id') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[8.33333333%]"><button class="nx-btn nx-btn-primary tw:w-full!" type="submit" aria-label="Search"><i class="bi bi-search"></i></button></div>
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[16.66666667%]"><a href="{{ route('courses.index') }}" class="nx-btn nx-btn-light tw:w-full!">Reset</a></div>
                </div>
            </form>
        </div>

        <div class="ui-table-responsive">
            <table class="ui-table premium-table tw:mb-0!">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Dates &amp; time</th>
                        <th>Teachers</th>
                        <th class="tw:text-right!">Fee</th>
                        <th class="tw:text-right!">Students</th>
                        <th class="tw:text-right!">Collected</th>
                        <th>Status</th>
                        <th class="tw:text-right!">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($courses as $course)
                        <tr>
                            <td>
                                <a href="{{ route('courses.show', $course) }}" class="cell-title tw:[text-decoration:none]!">{{ $course->name }}</a>
                                <small class="cell-sub tw:block!">{{ $course->code ?: '—' }} · {{ $course->branch->name ?? '' }}</small>
                            </td>
                            <td>
                                <span class="cell-title">{{ $course->start_date->format('d M Y') }} – {{ $course->end_date->format('d M Y') }}</span>
                                @foreach ($course->days as $day)
                                    <small class="cell-sub tw:block!">{{ $day->label() }}</small>
                                @endforeach
                            </td>
                            <td><small>{{ $course->teachers->pluck('full_name')->implode(', ') ?: '—' }}</small></td>
                            <td class="tw:text-right! money">
                                <span class="cell-title">{{ number_format($course->full_fee, 2) }}</span>
                                <small class="cell-sub tw:block!">Once: {{ number_format($course->one_time_fee, 2) }}</small>
                            </td>
                            <td class="tw:text-right!">{{ $course->students_count }}</td>
                            <td class="tw:text-right! money">{{ number_format((float) $course->collected, 2) }}</td>
                            <td>@include('courses.partials.status-badge', ['status' => $course->status()])</td>
                            <td class="tw:text-right!">
                                <div class="action-buttons">
                                    <a href="{{ route('courses.show', $course) }}" class="action-btn" title="Open" aria-label="Open"><i class="bi bi-eye-fill"></i></a>
                                    @if ($canManage)
                                        <a href="{{ route('courses.edit', $course) }}" class="action-btn action-btn-warning" title="Edit" aria-label="Edit"><i class="bi bi-pencil-fill"></i></a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="empty-table">
                                <i class="bi bi-mortarboard tw:text-[length:calc(1.325rem_+_0.9vw)]! tw:xl:text-[length:2rem]! tw:block! tw:mb-2!"></i>
                                <strong class="tw:block!">No courses yet</strong>
                                @if ($canManage)
                                    <a href="{{ route('courses.create') }}" class="nx-btn nx-btn-primary tw:mt-4!"><i class="bi bi-plus-lg"></i> New Course</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($courses->hasPages())
            <div class="tw:p-4! tw:bs-border-t!">{{ $courses->links() }}</div>
        @endif
    </div>
</div>
@endsection

@include('courses.partials.styles')

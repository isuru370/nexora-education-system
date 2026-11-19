@extends('layouts.app')

@section('title', 'Teacher Dashboard')

@section('content')

    <div class="ui-container-fluid tw:py-6!">

        {{-- Page Header --}}
        <div class="tw:flex! tw:flex-wrap! tw:justify-between! tw:items-center! tw:mb-6!">

            <div>
                <h3 class="tw:font-bold! tw:mb-1!">
                    Teacher Dashboard
                </h3>

                <p class="tw:text-bs-secondary-color! tw:mb-0!">
                    Manage your classes, students and teaching activities
                </p>
            </div>

            <div class="tw:mt-2! tw:md:mt-0!">
                <span class="ui-badge tw:bg-bs-success-bg-subtle! tw:text-bs-success! tw:px-4! tw:py-2!">
                    <i class="bi bi-person-workspace tw:mr-1!"></i>
                    Teacher
                </span>
            </div>

        </div>


        {{-- Teacher Statistics --}}
        <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3 tw:mb-6!">

            {{-- My Classes --}}
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">

                <div class="ui-card tw:[border:0]! tw:shadow-bs-sm! tw:h-full!">

                    <div class="ui-card-body tw:p-6!">

                        <div class="tw:flex! tw:justify-between! tw:items-start!">

                            <div>
                                <p class="tw:text-bs-secondary-color! tw:mb-2!">
                                    My Classes
                                </p>

                                <h3 class="tw:font-bold! tw:mb-0!">
                                    {{ $classesCount ?? 0 }}
                                </h3>
                            </div>

                            <div class="tw:bg-bs-primary-bg-subtle! tw:text-bs-primary! tw:rounded-bs-lg! tw:p-4!">
                                <i class="bi bi-mortarboard tw:text-[length:calc(1.275rem_+_0.3vw)]! tw:xl:text-[length:1.5rem]!"></i>
                            </div>

                        </div>

                        @if (\App\Services\Web\Common\RouteAccess::can('student-classes.index'))
                            <div class="tw:mt-4!">

                                <a href="{{ route('student-classes.index') }}" class="tw:[text-decoration:none]! tw:text-[.875em]">

                                    View Classes

                                    <i class="bi bi-arrow-right tw:ml-1!"></i>

                                </a>

                            </div>
                        @endif

                    </div>

                </div>

            </div>


            {{-- My Students --}}
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">

                <div class="ui-card tw:[border:0]! tw:shadow-bs-sm! tw:h-full!">

                    <div class="ui-card-body tw:p-6!">

                        <div class="tw:flex! tw:justify-between! tw:items-start!">

                            <div>

                                <p class="tw:text-bs-secondary-color! tw:mb-2!">
                                    My Students
                                </p>

                                <h3 class="tw:font-bold! tw:mb-0!">
                                    {{ $studentsCount ?? 0 }}
                                </h3>

                            </div>

                            <div class="tw:bg-bs-success-bg-subtle! tw:text-bs-success! tw:rounded-bs-lg! tw:p-4!">
                                <i class="bi bi-people tw:text-[length:calc(1.275rem_+_0.3vw)]! tw:xl:text-[length:1.5rem]!"></i>
                            </div>

                        </div>

                        @if (\App\Services\Web\Common\RouteAccess::can('student-classes.index'))
                            <div class="tw:mt-4!">

                                <a href="{{ route('student-classes.index') }}" class="tw:[text-decoration:none]! tw:text-[.875em]">

                                    View Students

                                    <i class="bi bi-arrow-right tw:ml-1!"></i>

                                </a>

                            </div>
                        @endif

                    </div>

                </div>

            </div>


            {{-- Today's Classes --}}
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">

                <div class="ui-card tw:[border:0]! tw:shadow-bs-sm! tw:h-full!">

                    <div class="ui-card-body tw:p-6!">

                        <div class="tw:flex! tw:justify-between! tw:items-start!">

                            <div>

                                <p class="tw:text-bs-secondary-color! tw:mb-2!">
                                    Today's Classes
                                </p>

                                <h3 class="tw:font-bold! tw:mb-0!">
                                    {{ $todayClassesCount ?? 0 }}
                                </h3>

                            </div>

                            <div class="tw:bg-bs-warning-bg-subtle! tw:text-bs-warning! tw:rounded-bs-lg! tw:p-4!">
                                <i class="bi bi-calendar-day tw:text-[length:calc(1.275rem_+_0.3vw)]! tw:xl:text-[length:1.5rem]!"></i>
                            </div>

                        </div>

                        @if (\App\Services\Web\Common\RouteAccess::can('class-schedules.index'))
                            <div class="tw:mt-4!">

                                <a href="{{ route('class-schedules.index') }}" class="tw:[text-decoration:none]! tw:text-[.875em]">

                                    View Timetable

                                    <i class="bi bi-arrow-right tw:ml-1!"></i>

                                </a>

                            </div>
                        @endif

                    </div>

                </div>

            </div>


            {{-- Upcoming Exams --}}
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">

                <div class="ui-card tw:[border:0]! tw:shadow-bs-sm! tw:h-full!">

                    <div class="ui-card-body tw:p-6!">

                        <div class="tw:flex! tw:justify-between! tw:items-start!">

                            <div>

                                <p class="tw:text-bs-secondary-color! tw:mb-2!">
                                    Upcoming Exams
                                </p>

                                <h3 class="tw:font-bold! tw:mb-0!">
                                    {{ $upcomingExamsCount ?? 0 }}
                                </h3>

                            </div>

                            <div class="tw:bg-bs-danger-bg-subtle! tw:text-bs-danger! tw:rounded-bs-lg! tw:p-4!">
                                <i class="bi bi-file-earmark-text tw:text-[length:calc(1.275rem_+_0.3vw)]! tw:xl:text-[length:1.5rem]!"></i>
                            </div>

                        </div>

                        @if (\App\Services\Web\Common\RouteAccess::can('admin.exams.index'))
                            <div class="tw:mt-4!">

                                <a href="{{ route('admin.exams.index') }}" class="tw:[text-decoration:none]! tw:text-[.875em]">

                                    View Exams

                                    <i class="bi bi-arrow-right tw:ml-1!"></i>

                                </a>

                            </div>
                        @endif

                    </div>

                </div>

            </div>

        </div>


        {{-- Main Content --}}
        <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3 tw:mb-6!">

            {{-- Today's Classes --}}
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[66.66666667%]">

                <div class="ui-card tw:[border:0]! tw:shadow-bs-sm! tw:h-full!">

                    <div class="ui-card-header tw:bg-bs-white! tw:[border:0]! tw:px-6! tw:pt-6!">

                        <div class="tw:flex! tw:justify-between! tw:items-center!">

                            <div>

                                <h5 class="tw:font-bold! tw:mb-1!">
                                    Today's Classes
                                </h5>

                                <p class="tw:text-bs-secondary-color! tw:text-[.875em] tw:mb-0!">
                                    Your scheduled classes for today
                                </p>

                            </div>

                            @if (\App\Services\Web\Common\RouteAccess::can('class-schedules.index'))
                                <a href="{{ route('class-schedules.index') }}" class="ui-btn ui-btn-sm ui-btn-light">

                                    View All

                                </a>
                            @endif

                        </div>

                    </div>

                    <div class="ui-card-body tw:px-6! tw:pb-6!">

                        @if (isset($todayClasses) && $todayClasses->count())

                            <div class="ui-list-group ui-list-group-flush">

                                @foreach ($todayClasses as $class)
                                    <div class="ui-list-group-item tw:px-0! tw:py-4!">

                                        <div class="tw:flex! tw:items-center!">

                                            <div class="tw:bg-bs-primary-bg-subtle! tw:text-bs-primary! tw:rounded-bs-lg! tw:p-4! tw:mr-4!">

                                                <i class="bi bi-calendar-event tw:text-[length:1.25rem]!"></i>

                                            </div>

                                            <div class="tw:grow!">

                                                <div class="tw:font-semibold!">
                                                    {{ $class->studentClass->name ?? 'Class' }}
                                                </div>

                                                <small class="tw:text-bs-secondary-color!">

                                                    {{ $class->start_time ?? '--:--' }}

                                                    @if (isset($class->end_time))
                                                        - {{ $class->end_time }}
                                                    @endif

                                                </small>

                                            </div>

                                            @if (isset($class->status))
                                                <span class="ui-badge tw:bg-bs-success-bg-subtle! tw:text-bs-success!">
                                                    {{ ucfirst($class->status) }}
                                                </span>
                                            @endif

                                        </div>

                                    </div>
                                @endforeach

                            </div>
                        @else
                            <div class="tw:text-center! tw:py-12!">

                                <div class="tw:text-bs-secondary-color! tw:mb-4!">

                                    <i class="bi bi-calendar-x tw:text-[length:calc(1.375rem_+_1.5vw)]! tw:xl:text-[length:2.5rem]!"></i>

                                </div>

                                <h6 class="tw:font-semibold!">
                                    No classes scheduled today
                                </h6>

                                <p class="tw:text-bs-secondary-color! tw:text-[.875em] tw:mb-0!">
                                    You don't have any scheduled classes for today.
                                </p>

                            </div>

                        @endif

                    </div>

                </div>

            </div>


            {{-- Teacher Profile --}}
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[33.33333333%]">

                <div class="ui-card tw:[border:0]! tw:shadow-bs-sm! tw:h-full!">

                    <div class="ui-card-header tw:bg-bs-white! tw:[border:0]! tw:px-6! tw:pt-6!">

                        <h5 class="tw:font-bold! tw:mb-1!">
                            My Profile
                        </h5>

                        <p class="tw:text-bs-secondary-color! tw:text-[.875em] tw:mb-0!">
                            Teacher account information
                        </p>

                    </div>

                    <div class="ui-card-body tw:px-6! tw:pb-6!">

                        <div class="tw:text-center! tw:mb-6!">

                            <div class="tw:bg-bs-primary-bg-subtle! tw:text-bs-primary! tw:rounded-[50%]! tw:[display:inline-flex]! tw:items-center! tw:justify-center!"
                                style="width: 70px; height: 70px;">

                                <i class="bi bi-person-workspace tw:text-[length:calc(1.325rem_+_0.9vw)]! tw:xl:text-[length:2rem]!"></i>

                            </div>

                            <h5 class="tw:font-bold! tw:mt-4! tw:mb-1!">
                                {{ auth()->user()->name }}
                            </h5>

                            <p class="tw:text-bs-secondary-color! tw:text-[.875em] tw:mb-0!">
                                {{ auth()->user()->email }}
                            </p>

                        </div>


                        <div class="tw:bs-border-t! tw:pt-4!">

                            <div class="tw:flex! tw:justify-between! tw:mb-4!">

                                <span class="tw:text-bs-secondary-color!">
                                    Role
                                </span>

                                <span class="tw:font-semibold!">
                                    {{ auth()->user()->userType->name ?? 'Teacher' }}
                                </span>

                            </div>


                            <div class="tw:flex! tw:justify-between! tw:mb-4!">

                                <span class="tw:text-bs-secondary-color!">
                                    Status
                                </span>

                                <span class="ui-badge tw:bg-bs-success-bg-subtle! tw:text-bs-success!">
                                    Active
                                </span>

                            </div>


                            <div class="tw:flex! tw:justify-between!">

                                <span class="tw:text-bs-secondary-color!">
                                    Branches
                                </span>

                                <span class="tw:font-semibold!">

                                    {{ isset($branches) ? $branches->count() : 0 }}

                                </span>

                            </div>

                        </div>


                        <div class="tw:mt-6!">

                            <a href="{{ route('admin.profile.index') }}" class="ui-btn ui-btn-light tw:w-full!">

                                <i class="bi bi-person tw:mr-1!"></i>

                                View Profile

                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- Quick Actions --}}
        <div class="ui-card tw:[border:0]! tw:shadow-bs-sm!">

            <div class="ui-card-header tw:bg-bs-white! tw:[border:0]! tw:px-6! tw:pt-6!">

                <h5 class="tw:font-bold! tw:mb-1!">
                    Quick Actions
                </h5>

                <p class="tw:text-bs-secondary-color! tw:text-[.875em] tw:mb-0!">
                    Teaching features available to you
                </p>

            </div>


            <div class="ui-card-body tw:p-6!">

                <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2">


                    {{-- My Classes --}}
                    @if (\App\Services\Web\Common\RouteAccess::can('student-classes.index'))
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%] tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">

                            <a href="{{ route('student-classes.index') }}" class="tw:[text-decoration:none]!">

                                <div class="tw:bs-border! tw:rounded-bs-xl! tw:p-4! tw:h-full!">

                                    <div class="tw:bg-bs-primary-bg-subtle! tw:text-bs-primary! tw:rounded-bs-lg! tw:p-2! tw:[display:inline-flex]! tw:mb-4!">

                                        <i class="bi bi-mortarboard"></i>

                                    </div>

                                    <h6 class="tw:font-bold! tw:text-bs-dark! tw:mb-1!">
                                        My Classes
                                    </h6>

                                    <small class="tw:text-bs-secondary-color!">
                                        View assigned classes
                                    </small>

                                </div>

                            </a>

                        </div>
                    @endif


                    {{-- Timetable --}}
                    @if (\App\Services\Web\Common\RouteAccess::can('class-schedules.index'))
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%] tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">

                            <a href="{{ route('class-schedules.index') }}" class="tw:[text-decoration:none]!">

                                <div class="tw:bs-border! tw:rounded-bs-xl! tw:p-4! tw:h-full!">

                                    <div class="tw:bg-bs-warning-bg-subtle! tw:text-bs-warning! tw:rounded-bs-lg! tw:p-2! tw:[display:inline-flex]! tw:mb-4!">

                                        <i class="bi bi-calendar3"></i>

                                    </div>

                                    <h6 class="tw:font-bold! tw:text-bs-dark! tw:mb-1!">
                                        Timetable
                                    </h6>

                                    <small class="tw:text-bs-secondary-color!">
                                        View class timetable
                                    </small>

                                </div>

                            </a>

                        </div>
                    @endif


                    {{-- Attendance --}}
                    @if (\App\Services\Web\Common\RouteAccess::can('admin.attendance.index'))
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%] tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">

                            <a href="{{ route('admin.attendance.index') }}" class="tw:[text-decoration:none]!">

                                <div class="tw:bs-border! tw:rounded-bs-xl! tw:p-4! tw:h-full!">

                                    <div class="tw:bg-bs-success-bg-subtle! tw:text-bs-success! tw:rounded-bs-lg! tw:p-2! tw:[display:inline-flex]! tw:mb-4!">

                                        <i class="bi bi-calendar-check"></i>

                                    </div>

                                    <h6 class="tw:font-bold! tw:text-bs-dark! tw:mb-1!">
                                        Attendance
                                    </h6>

                                    <small class="tw:text-bs-secondary-color!">
                                        View attendance
                                    </small>

                                </div>

                            </a>

                        </div>
                    @endif


                    {{-- Exams --}}
                    @if (\App\Services\Web\Common\RouteAccess::can('admin.exams.index'))
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%] tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">

                            <a href="{{ route('admin.exams.index') }}" class="tw:[text-decoration:none]!">

                                <div class="tw:bs-border! tw:rounded-bs-xl! tw:p-4! tw:h-full!">

                                    <div class="tw:bg-bs-danger-bg-subtle! tw:text-bs-danger! tw:rounded-bs-lg! tw:p-2! tw:[display:inline-flex]! tw:mb-4!">

                                        <i class="bi bi-file-earmark-text"></i>

                                    </div>

                                    <h6 class="tw:font-bold! tw:text-bs-dark! tw:mb-1!">
                                        Exams
                                    </h6>

                                    <small class="tw:text-bs-secondary-color!">
                                        View and manage exams
                                    </small>

                                </div>

                            </a>

                        </div>
                    @endif


                    {{-- Student Images --}}
                    @if (\App\Services\Web\Common\RouteAccess::can('admin.student-images.index'))
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%] tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">

                            <a href="{{ route('admin.student-images.index') }}" class="tw:[text-decoration:none]!">

                                <div class="tw:bs-border! tw:rounded-bs-xl! tw:p-4! tw:h-full!">

                                    <div class="tw:bg-bs-info-bg-subtle! tw:text-bs-info! tw:rounded-bs-lg! tw:p-2! tw:[display:inline-flex]! tw:mb-4!">

                                        <i class="bi bi-images"></i>

                                    </div>

                                    <h6 class="tw:font-bold! tw:text-bs-dark! tw:mb-1!">
                                        Student Images
                                    </h6>

                                    <small class="tw:text-bs-secondary-color!">
                                        View student images
                                    </small>

                                </div>

                            </a>

                        </div>
                    @endif


                    {{-- Weekly Timetable --}}
                    @if (\App\Services\Web\Common\RouteAccess::can('class-time-table.weekly'))
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%] tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">

                            <a href="{{ route('class-time-table.weekly') }}" class="tw:[text-decoration:none]!">

                                <div class="tw:bs-border! tw:rounded-bs-xl! tw:p-4! tw:h-full!">

                                    <div class="tw:bg-bs-secondary-bg-subtle! tw:text-bs-secondary! tw:rounded-bs-lg! tw:p-2! tw:[display:inline-flex]! tw:mb-4!">

                                        <i class="bi bi-calendar-week"></i>

                                    </div>

                                    <h6 class="tw:font-bold! tw:text-bs-dark! tw:mb-1!">
                                        Weekly Timetable
                                    </h6>

                                    <small class="tw:text-bs-secondary-color!">
                                        View weekly schedule
                                    </small>

                                </div>

                            </a>

                        </div>
                    @endif


                    {{-- Profile --}}
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%] tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">

                        <a href="{{ route('admin.profile.index') }}" class="tw:[text-decoration:none]!">

                            <div class="tw:bs-border! tw:rounded-bs-xl! tw:p-4! tw:h-full!">

                                <div class="tw:bg-bs-dark-bg-subtle! tw:text-bs-dark! tw:rounded-bs-lg! tw:p-2! tw:[display:inline-flex]! tw:mb-4!">

                                    <i class="bi bi-person"></i>

                                </div>

                                <h6 class="tw:font-bold! tw:text-bs-dark! tw:mb-1!">
                                    My Profile
                                </h6>

                                <small class="tw:text-bs-secondary-color!">
                                    Manage your profile
                                </small>

                            </div>

                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

@endsection

@extends('layouts.app')

@section('title', 'User Dashboard')

@section('content')

<div class="ui-container-fluid tw:py-6!">

    @include('dashboard.partials.auto-disabled-students')

    {{-- Page Header --}}
    <div class="tw:flex! tw:flex-wrap! tw:justify-between! tw:items-center! tw:mb-6!">

        <div>
            <h3 class="tw:font-bold! tw:mb-1!">
                User Dashboard
            </h3>

            <p class="tw:text-bs-secondary-color! tw:mb-0!">
                Manage your assigned branch operations
            </p>
        </div>

        <div class="tw:mt-2! tw:md:mt-0!">
            <span class="ui-badge tw:bg-bs-primary-bg-subtle! tw:text-bs-primary! tw:px-4! tw:py-2!">
                <i class="bi bi-person tw:mr-1!"></i>
                User
            </span>
        </div>

    </div>


    {{-- Statistics --}}
    <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3 tw:mb-6!">

        {{-- Students --}}
        <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">

            <div class="ui-card tw:[border:0]! tw:shadow-bs-sm! tw:h-full!">

                <div class="ui-card-body tw:p-6!">

                    <div class="tw:flex! tw:justify-between! tw:items-start!">

                        <div>
                            <p class="tw:text-bs-secondary-color! tw:mb-2!">
                                Students
                            </p>

                            <h3 class="tw:font-bold! tw:mb-0!">
                                {{ $studentsCount ?? 0 }}
                            </h3>
                        </div>

                        <div class="tw:bg-bs-primary-bg-subtle! tw:text-bs-primary! tw:rounded-bs-lg! tw:p-4!">
                            <i class="bi bi-people tw:text-[length:calc(1.275rem_+_0.3vw)]! tw:xl:text-[length:1.5rem]!"></i>
                        </div>

                    </div>

                    @if(\App\Services\Web\Common\RouteAccess::can('students.index'))
                        <div class="tw:mt-4!">
                            <a href="{{ route('students.index') }}"
                               class="tw:[text-decoration:none]! tw:text-[.875em]">
                                View Students
                                <i class="bi bi-arrow-right tw:ml-1!"></i>
                            </a>
                        </div>
                    @endif

                </div>

            </div>

        </div>


        {{-- Classes --}}
        <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">

            <div class="ui-card tw:[border:0]! tw:shadow-bs-sm! tw:h-full!">

                <div class="ui-card-body tw:p-6!">

                    <div class="tw:flex! tw:justify-between! tw:items-start!">

                        <div>
                            <p class="tw:text-bs-secondary-color! tw:mb-2!">
                                Classes
                            </p>

                            <h3 class="tw:font-bold! tw:mb-0!">
                                {{ $classesCount ?? 0 }}
                            </h3>
                        </div>

                        <div class="tw:bg-bs-warning-bg-subtle! tw:text-bs-warning! tw:rounded-bs-lg! tw:p-4!">
                            <i class="bi bi-mortarboard tw:text-[length:calc(1.275rem_+_0.3vw)]! tw:xl:text-[length:1.5rem]!"></i>
                        </div>

                    </div>

                    @if(\App\Services\Web\Common\RouteAccess::can('student-classes.index'))
                        <div class="tw:mt-4!">
                            <a href="{{ route('student-classes.index') }}"
                               class="tw:[text-decoration:none]! tw:text-[.875em]">
                                View Classes
                                <i class="bi bi-arrow-right tw:ml-1!"></i>
                            </a>
                        </div>
                    @endif

                </div>

            </div>

        </div>


        {{-- Monthly Income --}}
        <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">

            <div class="ui-card tw:[border:0]! tw:shadow-bs-sm! tw:h-full!">

                <div class="ui-card-body tw:p-6!">

                    <div class="tw:flex! tw:justify-between! tw:items-start!">

                        <div>
                            <p class="tw:text-bs-secondary-color! tw:mb-2!">
                                Monthly Income
                            </p>

                            <h3 class="tw:font-bold! tw:mb-0!">
                                LKR {{ number_format($monthlyIncome ?? 0, 2) }}
                            </h3>
                        </div>

                        <div class="tw:bg-bs-success-bg-subtle! tw:text-bs-success! tw:rounded-bs-lg! tw:p-4!">
                            <i class="bi bi-cash-stack tw:text-[length:calc(1.275rem_+_0.3vw)]! tw:xl:text-[length:1.5rem]!"></i>
                        </div>

                    </div>

                    <div class="tw:mt-4!">
                        <span class="tw:text-bs-secondary-color! tw:text-[.875em]">
                            Current month
                        </span>
                    </div>

                </div>

            </div>

        </div>


        {{-- Today's Income --}}
        <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">

            <div class="ui-card tw:[border:0]! tw:shadow-bs-sm! tw:h-full!">

                <div class="ui-card-body tw:p-6!">

                    <div class="tw:flex! tw:justify-between! tw:items-start!">

                        <div>
                            <p class="tw:text-bs-secondary-color! tw:mb-2!">
                                Today's Income
                            </p>

                            <h3 class="tw:font-bold! tw:mb-0!">
                                LKR {{ number_format($todayIncome ?? 0, 2) }}
                            </h3>
                        </div>

                        <div class="tw:bg-bs-info-bg-subtle! tw:text-bs-info! tw:rounded-bs-lg! tw:p-4!">
                            <i class="bi bi-wallet2 tw:text-[length:calc(1.275rem_+_0.3vw)]! tw:xl:text-[length:1.5rem]!"></i>
                        </div>

                    </div>

                    <div class="tw:mt-4!">
                        <span class="tw:text-bs-secondary-color! tw:text-[.875em]">
                            Today's collection
                        </span>
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- Assigned Branches --}}
    <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3 tw:mb-6!">

        <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">

            <div class="ui-card tw:[border:0]! tw:shadow-bs-sm! tw:h-full!">

                <div class="ui-card-header tw:bg-bs-white! tw:[border:0]! tw:px-6! tw:pt-6!">

                    <h5 class="tw:font-bold! tw:mb-1!">
                        My Branches
                    </h5>

                    <p class="tw:text-bs-secondary-color! tw:text-[.875em] tw:mb-0!">
                        Branches assigned to your account
                    </p>

                </div>

                <div class="ui-card-body tw:px-6! tw:pb-6!">

                    @if(isset($branches) && $branches->count())

                        <div class="ui-list-group ui-list-group-flush">

                            @foreach($branches as $branch)

                                <div class="ui-list-group-item tw:px-0! tw:flex! tw:items-center!">

                                    <div class="tw:bg-bs-primary-bg-subtle! tw:text-bs-primary! tw:rounded-bs-lg! tw:p-2! tw:mr-4!">
                                        <i class="bi bi-building"></i>
                                    </div>

                                    <div class="tw:grow!">

                                        <div class="tw:font-semibold!">
                                            {{ $branch->name }}
                                        </div>

                                        <small class="tw:text-bs-secondary-color!">
                                            {{ $branch->code }}
                                        </small>

                                    </div>

                                    <span class="ui-badge tw:bg-bs-success-bg-subtle! tw:text-bs-success!">
                                        Active
                                    </span>

                                </div>

                            @endforeach

                        </div>

                    @else

                        <div class="tw:text-center! tw:py-6!">

                            <div class="tw:text-bs-secondary-color! tw:mb-2!">
                                <i class="bi bi-building tw:text-[length:calc(1.375rem_+_1.5vw)]! tw:xl:text-[length:2.5rem]!"></i>
                            </div>

                            <p class="tw:text-bs-secondary-color! tw:mb-0!">
                                No branches assigned.
                            </p>

                        </div>

                    @endif

                </div>

            </div>

        </div>


        {{-- Account Information --}}
        <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">

            <div class="ui-card tw:[border:0]! tw:shadow-bs-sm! tw:h-full!">

                <div class="ui-card-header tw:bg-bs-white! tw:[border:0]! tw:px-6! tw:pt-6!">

                    <h5 class="tw:font-bold! tw:mb-1!">
                        Account Information
                    </h5>

                    <p class="tw:text-bs-secondary-color! tw:text-[.875em] tw:mb-0!">
                        Your current account details
                    </p>

                </div>

                <div class="ui-card-body tw:px-6! tw:pb-6!">

                    <div class="tw:flex! tw:items-center! tw:mb-4!">

                        <div class="tw:bg-bs-primary-bg-subtle! tw:text-bs-primary! tw:rounded-[50%]! tw:p-4! tw:mr-4!">
                            <i class="bi bi-person tw:text-[length:calc(1.275rem_+_0.3vw)]! tw:xl:text-[length:1.5rem]!"></i>
                        </div>

                        <div>

                            <div class="tw:font-semibold!">
                                {{ auth()->user()->name }}
                            </div>

                            <small class="tw:text-bs-secondary-color!">
                                {{ auth()->user()->email }}
                            </small>

                        </div>

                    </div>

                    <div class="tw:bs-border-t! tw:pt-4!">

                        <div class="tw:flex! tw:justify-between! tw:mb-2!">
                            <span class="tw:text-bs-secondary-color!">
                                Role
                            </span>

                            <span class="tw:font-semibold!">
                                {{ auth()->user()->userType->name ?? 'User' }}
                            </span>
                        </div>

                        <div class="tw:flex! tw:justify-between!">
                            <span class="tw:text-bs-secondary-color!">
                                Status
                            </span>

                            <span class="ui-badge tw:bg-bs-success-bg-subtle! tw:text-bs-success!">
                                Active
                            </span>
                        </div>

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
                Access features based on your permissions
            </p>

        </div>

        <div class="ui-card-body tw:p-6!">

            <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2">

                {{-- Students --}}
                @if(\App\Services\Web\Common\RouteAccess::can('students.index'))

                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%] tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">

                        <a href="{{ route('students.index') }}"
                           class="tw:[text-decoration:none]!">

                            <div class="tw:bs-border! tw:rounded-bs-xl! tw:p-4! tw:h-full!">

                                <div class="tw:bg-bs-primary-bg-subtle! tw:text-bs-primary! tw:rounded-bs-lg! tw:p-2! tw:[display:inline-flex]! tw:mb-4!">
                                    <i class="bi bi-people"></i>
                                </div>

                                <h6 class="tw:font-bold! tw:text-bs-dark! tw:mb-1!">
                                    Students
                                </h6>

                                <small class="tw:text-bs-secondary-color!">
                                    Manage students
                                </small>

                            </div>

                        </a>

                    </div>

                @endif


                {{-- Classes --}}
                @if(\App\Services\Web\Common\RouteAccess::can('student-classes.index'))

                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%] tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">

                        <a href="{{ route('student-classes.index') }}"
                           class="tw:[text-decoration:none]!">

                            <div class="tw:bs-border! tw:rounded-bs-xl! tw:p-4! tw:h-full!">

                                <div class="tw:bg-bs-warning-bg-subtle! tw:text-bs-warning! tw:rounded-bs-lg! tw:p-2! tw:[display:inline-flex]! tw:mb-4!">
                                    <i class="bi bi-mortarboard"></i>
                                </div>

                                <h6 class="tw:font-bold! tw:text-bs-dark! tw:mb-1!">
                                    Classes
                                </h6>

                                <small class="tw:text-bs-secondary-color!">
                                    Manage classes
                                </small>

                            </div>

                        </a>

                    </div>

                @endif


                {{-- Attendance --}}
                @if(\App\Services\Web\Common\RouteAccess::can('admin.attendance.index'))

                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%] tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">

                        <a href="{{ route('admin.attendance.index') }}"
                           class="tw:[text-decoration:none]!">

                            <div class="tw:bs-border! tw:rounded-bs-xl! tw:p-4! tw:h-full!">

                                <div class="tw:bg-bs-success-bg-subtle! tw:text-bs-success! tw:rounded-bs-lg! tw:p-2! tw:[display:inline-flex]! tw:mb-4!">
                                    <i class="bi bi-calendar-check"></i>
                                </div>

                                <h6 class="tw:font-bold! tw:text-bs-dark! tw:mb-1!">
                                    Attendance
                                </h6>

                                <small class="tw:text-bs-secondary-color!">
                                    Manage attendance
                                </small>

                            </div>

                        </a>

                    </div>

                @endif


                {{-- Payments --}}
                @if(\App\Services\Web\Common\RouteAccess::can('new-payment.index'))

                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%] tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">

                        <a href="{{ route('new-payment.index') }}"
                           class="tw:[text-decoration:none]!">

                            <div class="tw:bs-border! tw:rounded-bs-xl! tw:p-4! tw:h-full!">

                                <div class="tw:bg-bs-info-bg-subtle! tw:text-bs-info! tw:rounded-bs-lg! tw:p-2! tw:[display:inline-flex]! tw:mb-4!">
                                    <i class="bi bi-credit-card"></i>
                                </div>

                                <h6 class="tw:font-bold! tw:text-bs-dark! tw:mb-1!">
                                    Payments
                                </h6>

                                <small class="tw:text-bs-secondary-color!">
                                    Manage payments
                                </small>

                            </div>

                        </a>

                    </div>

                @endif


                {{-- Exams --}}
                @if(\App\Services\Web\Common\RouteAccess::can('admin.exams.index'))

                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%] tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">

                        <a href="{{ route('admin.exams.index') }}"
                           class="tw:[text-decoration:none]!">

                            <div class="tw:bs-border! tw:rounded-bs-xl! tw:p-4! tw:h-full!">

                                <div class="tw:bg-bs-danger-bg-subtle! tw:text-bs-danger! tw:rounded-bs-lg! tw:p-2! tw:[display:inline-flex]! tw:mb-4!">
                                    <i class="bi bi-file-earmark-text"></i>
                                </div>

                                <h6 class="tw:font-bold! tw:text-bs-dark! tw:mb-1!">
                                    Exams
                                </h6>

                                <small class="tw:text-bs-secondary-color!">
                                    Manage exams
                                </small>

                            </div>

                        </a>

                    </div>

                @endif


                {{-- Timetable --}}
                @if(\App\Services\Web\Common\RouteAccess::can('class-time-table.weekly'))

                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%] tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">

                        <a href="{{ route('class-time-table.weekly') }}"
                           class="tw:[text-decoration:none]!">

                            <div class="tw:bs-border! tw:rounded-bs-xl! tw:p-4! tw:h-full!">

                                <div class="tw:bg-bs-secondary-bg-subtle! tw:text-bs-secondary! tw:rounded-bs-lg! tw:p-2! tw:[display:inline-flex]! tw:mb-4!">
                                    <i class="bi bi-calendar3"></i>
                                </div>

                                <h6 class="tw:font-bold! tw:text-bs-dark! tw:mb-1!">
                                    Timetable
                                </h6>

                                <small class="tw:text-bs-secondary-color!">
                                    View weekly timetable
                                </small>

                            </div>

                        </a>

                    </div>

                @endif


                {{-- Student Images --}}
                @if(\App\Services\Web\Common\RouteAccess::can('admin.student-images.index'))

                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%] tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">

                        <a href="{{ route('admin.student-images.index') }}"
                           class="tw:[text-decoration:none]!">

                            <div class="tw:bs-border! tw:rounded-bs-xl! tw:p-4! tw:h-full!">

                                <div class="tw:bg-bs-dark-bg-subtle! tw:text-bs-dark! tw:rounded-bs-lg! tw:p-2! tw:[display:inline-flex]! tw:mb-4!">
                                    <i class="bi bi-images"></i>
                                </div>

                                <h6 class="tw:font-bold! tw:text-bs-dark! tw:mb-1!">
                                    Student Images
                                </h6>

                                <small class="tw:text-bs-secondary-color!">
                                    Manage student images
                                </small>

                            </div>

                        </a>

                    </div>

                @endif


                {{-- Notifications --}}
                @if(\App\Services\Web\Common\RouteAccess::can('admin.notifications.index'))

                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%] tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">

                        <a href="{{ route('admin.notifications.index') }}"
                           class="tw:[text-decoration:none]!">

                            <div class="tw:bs-border! tw:rounded-bs-xl! tw:p-4! tw:h-full!">

                                <div class="tw:bg-bs-primary-bg-subtle! tw:text-bs-primary! tw:rounded-bs-lg! tw:p-2! tw:[display:inline-flex]! tw:mb-4!">
                                    <i class="bi bi-bell"></i>
                                </div>

                                <h6 class="tw:font-bold! tw:text-bs-dark! tw:mb-1!">
                                    Notifications
                                </h6>

                                <small class="tw:text-bs-secondary-color!">
                                    Manage notifications
                                </small>

                            </div>

                        </a>

                    </div>

                @endif

            </div>

        </div>

    </div>

</div>

@endsection
@extends('layouts.app')

@section('title', 'Upcoming Exams')
@section('page-title', 'Upcoming Exams')

@php
    $fmtTime = fn ($t) => $t ? \Carbon\Carbon::parse($t)->format('h:i A') : '-';
    $thisWeek = $upcomingExams->filter(fn ($e) => $e->exam_date->lte(today()->addDays(7)))->count();
@endphp

@section('content')
<div class="dashboard-page">

    {{-- STATS --}}
    <div class="stats-grid tw:mb-6!">
        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-blue"><i class="bi bi-calendar-event"></i></div>
            <div class="stat-info">
                <span class="stat-label">Upcoming</span>
                <h2 class="stat-number">{{ $stats['upcoming'] ?? 0 }}</h2>
                <span class="stat-change up">From today <span class="stat-change-note">onwards</span></span>
            </div>
        </div>

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-violet"><i class="bi bi-calendar-week"></i></div>
            <div class="stat-info">
                <span class="stat-label">Next 7 Days</span>
                <h2 class="stat-number">{{ $thisWeek }}</h2>
                <span class="stat-change neutral">Coming <span class="stat-change-note">this week</span></span>
            </div>
        </div>

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-amber"><i class="bi bi-play-circle-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Ongoing</span>
                <h2 class="stat-number">{{ $stats['ongoing'] ?? 0 }}</h2>
                <span class="stat-change neutral">Waiting <span class="stat-change-note">for marks</span></span>
            </div>
        </div>
    </div>

    <div class="nx-card tw:mb-6!">
        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-calendar-event tw:text-(color:--nx-blue)!"></i> Upcoming Exams</h5>
                <p class="nx-card-subtitle">Scheduled exams from today, nearest first</p>
            </div>

            <div class="header-buttons">
                <a href="{{ route('admin.exams.index') }}" class="nx-btn nx-btn-light">
                    <i class="bi bi-arrow-left"></i> All Exams
                </a>
                <a href="{{ route('admin.exams.create') }}" class="nx-btn nx-btn-primary">
                    <i class="bi bi-plus-lg"></i> Add Exam
                </a>
            </div>
        </div>

        {{-- SEARCH --}}
        <div class="nx-search">
            <form method="GET" action="{{ route('admin.exams.upcoming') }}">
                <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2">
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">
                        <div class="search-input-wrapper">
                            <i class="bi bi-search"></i>
                            <input type="text" name="search" class="ui-form-control custom-input"
                                placeholder="Search exam title..." value="{{ request('search') }}">
                        </div>
                    </div>

                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[33.33333333%]">
                        <select name="student_class_id" class="ui-form-select custom-input">
                            <option value="">All Classes</option>
                            @foreach ($classes ?? [] as $class)
                                <option value="{{ $class->id }}" {{ request('student_class_id') == $class->id ? 'selected' : '' }}>
                                    {{ $class->class_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[8.33333333%]">
                        <button type="submit" class="nx-btn nx-btn-primary tw:w-full!" aria-label="Search">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>

                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[8.33333333%]">
                        <a href="{{ route('admin.exams.upcoming') }}" class="nx-btn nx-btn-light tw:w-full!">Reset</a>
                    </div>
                </div>
            </form>
        </div>

        {{-- TABLE --}}
        <div class="ui-table-responsive">
            <table class="ui-table premium-table tw:mb-0!">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Exam Title</th>
                        <th>Class</th>
                        <th>Category</th>
                        <th>Hall</th>
                        <th>Time</th>
                        <th class="tw:text-right!">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($exams as $exam)
                        @php
                            $days = (int) today()->diffInDays($exam->exam_date, false);
                            [$whenText, $whenClass] = match (true) {
                                $days <= 0 => ['Today', 'nx-badge-green'],
                                $days === 1 => ['Tomorrow', 'nx-badge-amber'],
                                $days <= 7 => ["In {$days} days", 'nx-badge-blue'],
                                default => ["In {$days} days", 'nx-badge-gray'],
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="date-chip">
                                    <span class="date-chip-day">{{ $exam->exam_date->format('d') }}</span>
                                    <span class="date-chip-month">{{ $exam->exam_date->format('M') }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="cell-title">{{ $exam->title }}</div>
                                <span class="nx-badge tw:mt-1! {{ $whenClass }}">{{ $whenText }}</span>
                            </td>
                            <td>
                                <div class="cell-title">{{ $exam->studentClass->class_name ?? 'N/A' }}</div>
                                <small class="cell-sub">{{ $exam->studentClass->grade->grade_name ?? '' }}</small>
                            </td>
                            <td><span class="nx-badge nx-badge-blue">{{ $exam->category->category_name ?? 'N/A' }}</span></td>
                            <td class="cell-title">{{ $exam->hall->hall_name ?? '-' }}</td>
                            <td>
                                <div class="cell-title">{{ $exam->exam_date->format('l') }}</div>
                                <small class="cell-sub"><i class="bi bi-clock"></i> {{ $fmtTime($exam->start_time) }} - {{ $fmtTime($exam->end_time) }}</small>
                            </td>
                            <td class="tw:text-right!">
                                @include('exams.partials.actions', [
                                    'exam' => $exam,
                                    'showEdit' => true,
                                    'showDelete' => false,
                                    'showCancel' => true,
                                    'showMarkEntry' => false,
                                    'showResults' => false,
                                ])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty-table">
                                <i class="bi bi-calendar-x tw:text-[length:calc(1.325rem_+_0.9vw)]! tw:xl:text-[length:2rem]! tw:block! tw:mb-2!"></i>
                                <strong class="tw:block!">No upcoming exams</strong>
                                <span class="cell-sub">Scheduled exams from today will show here.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="tw:p-4! tw:bs-border-t!">
            {{ $exams->links() }}
        </div>
    </div>

</div>

{{-- CANCEL MODAL (filled from the clicked row) --}}
<div class="ui-modal ui-fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="ui-modal-dialog">
        <div class="ui-modal-content">
            <form id="cancelForm" method="POST" action="">
                @csrf
                <div class="ui-modal-header">
                    <h5 class="ui-modal-title" id="cancelModalLabel">
                        <i class="bi bi-exclamation-triangle-fill tw:text-(color:--nx-red)! tw:mr-2!"></i> Cancel Exam
                    </h5>
                    <button type="button" class="ui-btn-close" data-nx-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="ui-modal-body">
                    <p>Cancel <strong id="cancelExamTitle"></strong>?</p>
                    <label class="nx-label" for="cancel_reason">Reason for cancellation</label>
                    <textarea id="cancel_reason" name="cancel_reason" class="ui-form-control custom-input" rows="3" required
                        placeholder="e.g. Hall not available"></textarea>
                </div>
                <div class="ui-modal-footer">
                    <button type="button" class="nx-btn nx-btn-light" data-nx-dismiss="modal">Close</button>
                    <button type="submit" class="nx-btn nx-btn-danger">
                        <i class="bi bi-x-circle"></i> Cancel Exam
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection


@push('styles')
<style>
    .dashboard-page .date-chip {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 52px;
        height: 52px;
        border-radius: 10px;
        background: var(--nx-blue-soft);
        color: var(--nx-blue);
        line-height: 1;
    }
    .dashboard-page .date-chip-day { font-size: 1.15rem; font-weight: 800; }
    .dashboard-page .date-chip-month { font-size: .7rem; font-weight: 700; margin-top: 2px; }

    @media (max-width: 992px) {
        .dashboard-page .premium-table { min-width: 1000px; }
    }
</style>
@endpush


@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.nx-search select').forEach(function (el) {
            el.addEventListener('change', function () { this.closest('form').submit(); });
        });

        var modal = document.getElementById('cancelModal');
        if (modal) {
            modal.addEventListener('show.nx.modal', function (event) {
                var button = event.relatedTarget;
                document.getElementById('cancelExamTitle').textContent = button.getAttribute('data-exam-title');
                var form = document.getElementById('cancelForm');
                form.action = button.getAttribute('data-exam-route');
                form.querySelector('textarea').value = '';
            });
        }

        @if (session('success'))
            Swal.fire({ icon: 'success', title: @json(session('success')), timer: 3000, showConfirmButton: false, toast: true, position: 'top-end' });
        @endif
        @if (session('error'))
            Swal.fire({ icon: 'error', title: @json(session('error')), timer: 4000, toast: true, position: 'top-end' });
        @endif
    });
</script>
@endpush

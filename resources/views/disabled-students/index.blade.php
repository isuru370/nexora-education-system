@extends('layouts.app')

@section('title', 'Disabled Students')
@section('page-title', 'Disabled Students')

@section('content')
    @php
        $reasonBadge = [
            'auto' => ['nx-badge-amber', 'bi-clock-history'],
            'batch' => ['nx-badge-blue', 'bi-mortarboard'],
            'manual' => ['nx-badge-red', 'bi-hand-index'],
        ];
        $active = $filters['reason'] ?? '';
        $chip = fn ($key) => route('disabled-students.index', array_filter(array_merge($filters, ['reason' => $key ?: null, 'page' => null])));
    @endphp

    <div class="dashboard-page">

        <div class="hero-section tw:mb-6!">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">Students</span>
                    <h1 class="hero-title">Disabled Students</h1>
                    <p class="hero-subtitle">
                        Every student who cannot mark attendance or pay now, and why. Enable puts the student back,
                        and can put them back in the classes they were taken out of.
                    </p>
                </div>
                <div class="hero-right">
                    <small class="hero-updated">{{ number_format($counts['all']) }} disabled</small>
                </div>
            </div>
        </div>

        @foreach (['success' => 'nx-alert-green', 'error' => 'nx-alert-red'] as $key => $class)
            @if (session($key))
                <div class="nx-alert tw:mb-4! {{ $class }}">
                    <i class="bi {{ $key === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' }}"></i>
                    <span>{{ session($key) }}</span>
                </div>
            @endif
        @endforeach

        {{-- Reason chips --}}
        <div class="ds-chips tw:mb-4!">
            <a href="{{ $chip('') }}" class="ds-chip {{ $active === '' ? 'active' : '' }}">All <strong>{{ $counts['all'] }}</strong></a>
            @foreach ($reasons as $key => $label)
                <a href="{{ $chip($key) }}" class="ds-chip {{ $active === $key ? 'active' : '' }}">
                    <i class="bi {{ $reasonBadge[$key][1] }}"></i> {{ $label }} <strong>{{ $counts[$key] }}</strong>
                </a>
            @endforeach
        </div>

        {{-- Filters --}}
        <div class="nx-card tw:mb-6!">
            <div class="tw:p-4!">
                <form method="GET" action="{{ route('disabled-students.index') }}" class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2 tw:items-end!">
                    <input class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full" type="hidden" name="reason" value="{{ $active }}">
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[66.66666667%] tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">
                        <label class="nx-label" for="ds-search">Search student</label>
                        <input type="text" id="ds-search" name="search" class="ui-form-control nx-input"
                            value="{{ $filters['search'] ?? '' }}" placeholder="ID, name, guardian mobile or card">
                    </div>
                    @if ($branches->count() > 1)
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:lg:flex-[0_0_auto] tw:lg:w-[33.33333333%]">
                            <label class="nx-label" for="ds-branch">Branch</label>
                            <select id="ds-branch" name="branch_id" class="ui-form-select nx-input">
                                <option value="">All my branches</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ (int) ($filters['branch_id'] ?? 0) === (int) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%] tw:lg:flex-[0_0_auto] tw:lg:w-[16.66666667%]">
                        <button type="submit" class="nx-btn nx-btn-primary tw:w-full!"><i class="bi bi-search"></i> Search</button>
                    </div>
                </form>
            </div>
        </div>

        <form method="POST" action="{{ route('disabled-students.enable') }}" id="dsForm">
            @csrf

            <div class="nx-card tw:mb-6!">
                <div class="nx-card-header tw:flex-wrap! tw:gap-2!">
                    <div>
                        <h5 class="nx-card-title"><i class="bi bi-person-slash tw:text-(color:--nx-red)!"></i> Disabled students</h5>
                        <p class="nx-card-subtitle">Newest first &middot; {{ $students->total() }} found</p>
                    </div>
                    <div class="tw:flex! tw:flex-wrap! tw:items-center! tw:gap-4!">
                        <label class="ui-form-check tw:mb-0!">
                            <input type="hidden" name="restore_classes" value="0">
                            <input class="ui-form-check-input" type="checkbox" name="restore_classes" value="1" checked>
                            <span class="ui-form-check-label">Put back in the classes they were taken out of</span>
                        </label>
                        <button type="submit" class="nx-btn nx-btn-primary" id="dsBulk" disabled
                            onclick="return confirm('Enable the selected student(s)?');">
                            <i class="bi bi-person-check"></i> Enable selected (<span id="dsCount">0</span>)
                        </button>
                    </div>
                </div>

                <div class="ui-table-responsive">
                    <table class="ui-table premium-table tw:mb-0! tw:align-middle!">
                        <thead>
                            <tr>
                                <th style="width: 36px;"><input type="checkbox" class="ui-form-check-input" id="dsAll" aria-label="Select all"></th>
                                <th>Student</th>
                                <th>Grade / Branch</th>
                                <th>Guardian</th>
                                <th>Why disabled</th>
                                <th>Classes taken out</th>
                                <th class="tw:text-right!">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($students as $student)
                                <tr>
                                    <td><input type="checkbox" class="ui-form-check-input ds-pick" name="ids[]" value="{{ $student->id }}" aria-label="Select {{ $student->custom_id }}"></td>
                                    <td>
                                        <div class="cell-title">{{ $student->initial_name ?: $student->full_name }}</div>
                                        <small class="cell-sub">{{ $student->custom_id }}</small>
                                    </td>
                                    <td>
                                        <div>{{ optional($student->grade)->grade_name ?? '-' }}</div>
                                        <small class="cell-sub">{{ optional($student->branch)->name }}</small>
                                    </td>
                                    <td>{{ $student->guardian_mobile ?: '-' }}</td>
                                    <td>
                                        <span class="nx-badge {{ $reasonBadge[$student->disabled_reason][0] }}">
                                            <i class="bi {{ $reasonBadge[$student->disabled_reason][1] }}"></i> {{ $student->disabled_label }}
                                        </span>
                                        <small class="cell-sub tw:block! tw:mt-1!">{{ optional($student->disabled_on)->format('d M Y') }}</small>
                                    </td>
                                    <td>
                                        @if ($student->disabled_classes)
                                            <small>{{ implode(', ', array_slice($student->disabled_classes, 0, 3)) }}@if (count($student->disabled_classes) > 3) +{{ count($student->disabled_classes) - 3 }} more @endif</small>
                                        @else
                                            <small class="tw:text-(color:--nx-muted)!">-</small>
                                        @endif
                                    </td>
                                    <td class="tw:text-right! tw:whitespace-nowrap!">
                                        <a href="{{ route('students.show', $student->id) }}" class="nx-btn nx-btn-light ui-btn-sm" title="View student"><i class="bi bi-eye"></i></a>
                                        <button type="submit" name="only" value="{{ $student->id }}" class="nx-btn nx-btn-primary ui-btn-sm"
                                            onclick="return confirm('Enable {{ e($student->custom_id) }}?');">
                                            <i class="bi bi-person-check"></i> Enable
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="tw:text-center! tw:text-(color:--nx-muted)! tw:py-12!"><i class="bi bi-emoji-smile"></i> No disabled students.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($students->hasPages())
                    <div class="tw:p-4!">{{ $students->links('pagination::bootstrap-4') }}</div>
                @endif
            </div>
        </form>

        <div class="nx-alert nx-alert-blue">
            <i class="bi bi-info-circle-fill"></i>
            <span>
                Classes are put back only if the class is still active (a closed batch's classes stay closed - enroll the
                student in a new class from Class Management). Who enabled whom is saved.
            </span>
        </div>
    </div>

    <style>
        .ds-chips { display: flex; flex-wrap: wrap; gap: 8px; }
        .ds-chip {
            display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 999px;
            background: var(--nx-card-bg, #fff); border: 1px solid rgba(15, 23, 42, .1); color: inherit;
            text-decoration: none; font-size: .9rem;
        }
        .ds-chip strong { font-weight: 800; }
        .ds-chip.active { background: #2563eb; border-color: #2563eb; color: #fff; }
    </style>

    <script>
        (function () {
            const all = document.getElementById('dsAll');
            const picks = () => Array.from(document.querySelectorAll('.ds-pick'));
            const bulk = document.getElementById('dsBulk');
            const count = document.getElementById('dsCount');

            function refresh() {
                const n = picks().filter(p => p.checked).length;
                count.textContent = n;
                bulk.disabled = n === 0;
            }

            if (all) {
                all.addEventListener('change', () => { picks().forEach(p => p.checked = all.checked); refresh(); });
            }
            picks().forEach(p => p.addEventListener('change', refresh));
            refresh();
        })();
    </script>
@endsection

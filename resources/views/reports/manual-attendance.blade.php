@extends('layouts.app')

@section('title', 'Card Scan vs Manual Attendance')
@section('page-title', 'Card Scan vs Manual Attendance')

@php
    $range = $from->isSameDay($to) ? $from->format('d M Y') : $from->format('d M Y') . ' - ' . $to->format('d M Y');
    $pct = fn ($part, $all) => $all > 0 ? round($part / $all * 100) : 0;
@endphp

@section('content')
    <div class="dashboard-page">

        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge"><i class="bi bi-qr-code-scan"></i> Reports</span>
                    <h1 class="hero-title">Card Scan vs Manual Attendance</h1>
                    <p class="hero-subtitle">
                        How each student's attendance was marked: by scanning the ID card, or by typing the
                        Student ID (the student did not bring the card).
                    </p>
                </div>
                <div class="hero-right">
                    <small class="hero-updated">{{ $range }}</small>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="nx-card mb-4">
            <div class="p-3">
                <form method="GET" action="{{ route('reports.manual-attendance.index') }}" class="row g-3 align-items-end">
                    <div class="col-lg-3 col-md-6">
                        <label class="nx-label" for="ma-from">From</label>
                        <input type="date" id="ma-from" name="from" class="form-control nx-input" value="{{ $from->toDateString() }}">
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="nx-label" for="ma-to">To</label>
                        <input type="date" id="ma-to" name="to" class="form-control nx-input" value="{{ $to->toDateString() }}">
                    </div>
                    <div class="col-lg-4 col-md-8">
                        <label class="nx-label" for="ma-class">Class</label>
                        <select id="ma-class" name="class_id" class="form-select nx-input">
                            <option value="">All classes</option>
                            @foreach ($allClasses as $id => $name)
                                <option value="{{ $id }}" {{ (int) $classId === (int) $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <button type="submit" class="nx-btn nx-btn-primary w-100"><i class="bi bi-funnel"></i> Show</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Totals --}}
        <div class="stats-grid stats-grid-3 mb-4">
            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-blue"><i class="bi bi-people-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Attendance marked</span>
                    <h2 class="stat-number">{{ number_format($totals['total']) }}</h2>
                </div>
            </div>
            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-green"><i class="bi bi-qr-code-scan"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Card scanned</span>
                    <h2 class="stat-number">{{ number_format($totals['qr']) }}</h2>
                    <span class="stat-change up">{{ $pct($totals['qr'], $totals['total']) }}%</span>
                </div>
            </div>
            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-amber"><i class="bi bi-keyboard-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Typed (no card)</span>
                    <h2 class="stat-number">{{ number_format($totals['manual']) }}</h2>
                    <span class="stat-change down">{{ $pct($totals['manual'], $totals['total']) }}%</span>
                </div>
            </div>
        </div>

        {{-- Per class --}}
        <div class="nx-card mb-4">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-journal-bookmark-fill text-primary"></i> By class</h5>
                    <p class="nx-card-subtitle">Most typed first</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table premium-table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Class</th>
                            <th class="text-end">Marked</th>
                            <th class="text-end">Card scanned</th>
                            <th class="text-end">Typed (no card)</th>
                            <th class="text-end">Typed %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($classRows as $row)
                            <tr>
                                <td class="cell-title">
                                    <a href="{{ route('reports.manual-attendance.index', ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'class_id' => $row['class_id']]) }}">{{ $row['class'] }}</a>
                                </td>
                                <td class="text-end">{{ $row['total'] }}</td>
                                <td class="text-end">{{ $row['qr'] }}</td>
                                <td class="text-end fw-bold {{ $row['manual'] > 0 ? 'text-warning' : '' }}">{{ $row['manual'] }}</td>
                                <td class="text-end">{{ $pct($row['manual'], $row['total']) }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No attendance in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Students who came without the card --}}
        <div class="nx-card mb-4">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-person-exclamation text-warning"></i> Marked by typing the Student ID</h5>
                    <p class="nx-card-subtitle">{{ $students->count() }} students &middot; did not bring the ID card, most often first</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table premium-table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Class</th>
                            <th>Guardian mobile</th>
                            <th class="text-end">Typed</th>
                            <th class="text-end">Of classes attended</th>
                            <th>Last typed</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($students as $row)
                            <tr>
                                <td>
                                    <div class="cell-title">{{ $row['name'] }}</div>
                                    <small class="cell-sub">{{ $row['custom_id'] }}</small>
                                </td>
                                <td>{{ $row['class'] }}</td>
                                <td>{{ $row['guardian_mobile'] ?: '-' }}</td>
                                <td class="text-end fw-bold text-warning">{{ $row['manual'] }}</td>
                                <td class="text-end">{{ $row['total'] }}</td>
                                <td>{{ optional($row['last_manual'])->format('d M Y, h:i A') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Everyone's card was scanned in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="nx-alert nx-alert-blue">
            <i class="bi bi-info-circle-fill"></i>
            <span>
                "Typed" = the staff typed the Student ID in the app or on the web instead of scanning the card.
                The staff app records this from version 1.1.2; older app versions saved typed IDs as scans.
            </span>
        </div>
    </div>
@endsection

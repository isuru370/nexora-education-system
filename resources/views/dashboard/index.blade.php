@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@php
    $greeting = 'Good Evening';
    if (now()->hour < 12) {
        $greeting = 'Good Morning';
    } elseif (now()->hour < 17) {
        $greeting = 'Good Afternoon';
    }
@endphp

@section('content')
<div class="dashboard-page">

    {{-- HERO SECTION --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <div class="hero-greeting">
                    <span class="greeting-badge">{{ $greeting }}</span>
                    <h1 class="hero-title">
                        Welcome Back, {{ auth()->user()->name ?? 'Administrator' }}
                    </h1>
                    <p class="hero-subtitle">
                        Manage students, payments, attendance, classes, and ID cards from one dashboard.
                    </p>
                </div>
                <div class="hero-stats-mini">
                    <div class="mini-stat">
                        <span class="mini-stat-value">{{ $studentsCount ?? 0 }}</span>
                        <span class="mini-stat-label">Students</span>
                    </div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat">
                        <span class="mini-stat-value">{{ $teachersCount ?? 0 }}</span>
                        <span class="mini-stat-label">Teachers</span>
                    </div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat">
                        <span class="mini-stat-value">{{ $classesCount ?? 0 }}</span>
                        <span class="mini-stat-label">Classes</span>
                    </div>
                </div>
            </div>
            <div class="hero-right">
                <div class="hero-date-time">
                    <div class="date-box">
                        <i class="bi bi-calendar-event"></i>
                        <span>{{ now()->format('d M Y') }}</span>
                    </div>
                    <div class="time-box">
                        <i class="bi bi-clock"></i>
                        <span id="liveTime">{{ now()->format('h:i A') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ALERT --}}
    @if ($showStudentCardWarning ?? false)
        <div class="alert alert-dismissible fade show alert-card mb-4" role="alert">
            <div class="alert-icon">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div class="alert-content">
                <strong>Warning!</strong> Student cards are running low.
                Remaining stock: <span class="alert-highlight">{{ $availableStudentCardCount ?? 0 }}</span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- STATS CARDS --}}
    <div class="stats-grid">
        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper"><i class="bi bi-mortarboard-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Total Students</span>
                <h2 class="stat-number">{{ $studentsCount ?? 0 }}</h2>
            </div>
        </div>

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper"><i class="bi bi-person-workspace"></i></div>
            <div class="stat-info">
                <span class="stat-label">Total Teachers</span>
                <h2 class="stat-number">{{ $teachersCount ?? 0 }}</h2>
            </div>
        </div>

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper"><i class="bi bi-building"></i></div>
            <div class="stat-info">
                <span class="stat-label">Running Classes</span>
                <h2 class="stat-number">{{ $classesCount ?? 0 }}</h2>
            </div>
        </div>

        <div class="nx-card stat-card stat-card-income">
            <div class="stat-icon-wrapper"><i class="bi bi-cash-stack"></i></div>
            <div class="stat-info">
                <span class="stat-label">Today's Income</span>
                <h2 class="stat-number">Rs. {{ number_format($todayIncome ?? 0, 2) }}</h2>
            </div>
        </div>
    </div>

    {{-- QUICK ACTIONS --}}
    <div class="quick-actions-section mb-4">
        <div class="quick-actions-header">
            <div>
                <h5 class="section-title">
                    <i class="bi bi-lightning-fill text-warning"></i> Quick Actions
                </h5>
                <p class="section-subtitle">Quickly access frequently used functions</p>
            </div>
            <span class="badge-actions">⚡ 5 Actions</span>
        </div>

        <div class="quick-actions-grid">
            @if (auth()->check())
                <button type="button" class="quick-action-btn qa-primary" data-href="{{ route('student-card-registration.create') }}">
                    <div class="qa-icon"><i class="bi bi-person-plus-fill"></i></div>
                    <div class="qa-text">
                        <span class="qa-title">Add Student</span>
                        <span class="qa-desc">New enrollment</span>
                    </div>
                </button>

                <button type="button" class="quick-action-btn qa-success" data-href="{{ route('admin.new-payment.index') }}">
                    <div class="qa-icon"><i class="bi bi-credit-card-2-front-fill"></i></div>
                    <div class="qa-text">
                        <span class="qa-title">Add Payment</span>
                        <span class="qa-desc">Record payment</span>
                    </div>
                </button>

                <button type="button" class="quick-action-btn qa-warning" data-href="{{ route('student-classes.create') }}">
                    <div class="qa-icon"><i class="bi bi-calendar-plus-fill"></i></div>
                    <div class="qa-text">
                        <span class="qa-title">Create Class</span>
                        <span class="qa-desc">New class setup</span>
                    </div>
                </button>

                <button type="button" class="quick-action-btn qa-info" data-href="{{ route('student-class-management.index') }}">
                    <div class="qa-icon"><i class="bi bi-person-workspace"></i></div>
                    <div class="qa-text">
                        <span class="qa-title">Class Mgmt</span>
                        <span class="qa-desc">Manage enrollments</span>
                    </div>
                </button>

                <button type="button" class="quick-action-btn qa-dark" data-href="{{ route('admin.monthly-report.index') }}">
                    <div class="qa-icon"><i class="bi bi-file-earmark-bar-graph"></i></div>
                    <div class="qa-text">
                        <span class="qa-title">Reports</span>
                        <span class="qa-desc">View analytics</span>
                    </div>
                </button>
            @endif
        </div>
    </div>

    {{-- CHART + INVENTORY ROW --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-9">
            <div class="chart-premium-card">
                <div class="chart-premium-header">
                    <div>
                        <h5 class="chart-premium-title">
                            <i class="bi bi-graph-up-arrow text-primary"></i>
                            Institute Yearly Payment Report
                        </h5>
                        <p class="chart-premium-subtitle">Monthly payment analytics with dual dataset comparison</p>
                    </div>
                    <div class="chart-controls">
                        <div class="chart-legend">
                            <span class="legend-item"><span class="legend-dot blue"></span> Total Payments</span>
                            <span class="legend-item"><span class="legend-dot green"></span> Institute Income</span>
                        </div>
                        <select id="yearSelector" class="year-selector-premium">
                            @for ($y = 2022; $y <= now()->year; $y++)
                                <option value="{{ $y }}" {{ $y == now()->year ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
                <div class="chart-premium-body">
                    <div class="chart-container-premium">
                        <canvas id="yearlyPaymentChart"></canvas>
                        <div id="chartLoading" class="chart-loading-premium" style="display: none;">
                            <div class="spinner-premium"></div>
                        </div>
                    </div>
                    <div class="chart-premium-stats">
                        <div class="premium-stat-item">
                            <span class="premium-stat-label">Total Revenue</span>
                            <span class="premium-stat-value total" id="totalRevenue">Rs. 0.00</span>
                        </div>
                        <div class="premium-stat-divider"></div>
                        <div class="premium-stat-item">
                            <span class="premium-stat-label">Institute Income</span>
                            <span class="premium-stat-value total" id="instituteIncome">Rs. 0.00</span>
                        </div>
                        <div class="premium-stat-divider"></div>
                        <div class="premium-stat-item">
                            <span class="premium-stat-label">Best Month</span>
                            <span class="premium-stat-value" id="bestMonth">-</span>
                        </div>
                        <div class="premium-stat-divider"></div>
                        <div class="premium-stat-item">
                            <span class="premium-stat-label">Annual Growth</span>
                            <span class="premium-stat-value" id="growthRate">0%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Student Cards Stock --}}
        <div class="col-xl-3">
            <div class="card-stock-premium">
                <div class="card-stock-header">
                    <div>
                        <span class="card-stock-label">Available Student Cards</span>
                        <h2 class="card-stock-number">{{ $availableStudentCardCount ?? 0 }}</h2>
                    </div>
                    <div class="card-stock-icon"><i class="bi bi-person-vcard-fill"></i></div>
                </div>
                <div class="card-stock-status">
                    @if ($showStudentCardWarning ?? false)
                        <span class="status-badge warning">
                            <i class="bi bi-exclamation-triangle"></i> Low Stock
                        </span>
                    @else
                        <span class="status-badge success">
                            <i class="bi bi-check-circle"></i> Enough Stock
                        </span>
                    @endif
                </div>
                @if (($availableStudentCardCount ?? 0) < 20)
                    <div class="card-stock-progress">
                        <div class="stock-progress">
                            <div class="stock-progress-fill" style="width: {{ min(100, (($availableStudentCardCount ?? 0) / 50) * 100) }}%;"></div>
                        </div>
                        <span class="stock-progress-label">Reorder when stock reaches 20</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- BOTTOM SECTION --}}
    <div class="row g-4">
        {{-- Incomplete Registrations --}}
        <div class="col-xl-7">
            <div class="table-premium-card">
                <div class="table-premium-header">
                    <div>
                        <h5 class="table-premium-title">
                            <i class="bi bi-hourglass-split text-warning"></i> Incomplete Registrations
                        </h5>
                        <span class="table-premium-badge">
                            <i class="bi bi-hourglass-split"></i>
                            {{ $incompleteRegistrationCount ?? 0 }} Pending
                        </span>
                    </div>
                </div>
                <div class="table-premium-body">
                    <div class="table-responsive">
                        <table class="table premium-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Guardian Mobile</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($incompleteRegistrations ?? [] as $student)
                                    <tr>
                                        <td><span class="id-badge">{{ $student->custom_id ?? '-' }}</span></td>
                                        <td>
                                            <div class="student-name-cell">
                                                <span class="avatar-xs">{{ strtoupper(substr($student->initial_name ?? 'N', 0, 1)) }}</span>
                                                {{ $student->initial_name ?? '-' }}
                                            </div>
                                        </td>
                                        <td>{{ $student->guardian_mobile ?? '-' }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.students.edit', $student->id) }}" class="btn-complete">
                                                <i class="bi bi-check2-circle"></i> Complete
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center empty-table">
                                            <i class="bi bi-check-circle fs-2 d-block mb-2 text-success"></i>
                                            No incomplete registrations found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Latest Students --}}
        <div class="col-xl-5">
            <div class="table-premium-card">
                <div class="table-premium-header">
                    <div>
                        <h5 class="table-premium-title">
                            <i class="bi bi-clock-history text-info"></i> Latest Students
                        </h5>
                        <span class="table-premium-badge primary">
                            <i class="bi bi-clock"></i> Recent
                        </span>
                    </div>
                </div>
                <div class="table-premium-body">
                    <div class="table-responsive">
                        <table class="table premium-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Guardian Mobile</th>
                                    <th>Joined</th>
                                    <th class="text-end">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($latestStudents ?? [] as $student)
                                    <tr>
                                        <td><span class="id-badge">{{ $student->custom_id ?? '-' }}</span></td>
                                        <td>
                                            <div class="student-name-cell">
                                                <span class="avatar-xs">{{ strtoupper(substr($student->initial_name ?? 'N', 0, 1)) }}</span>
                                                {{ $student->initial_name ?? '-' }}
                                            </div>
                                        </td>
                                        <td>{{ $student->guardian_mobile ?? '-' }}</td>
                                        <td>{{ optional($student->created_at)->format('d M Y') }}</td>
                                        <td class="text-end">
                                            <span class="status-dot active"></span> Active
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center empty-table">
                                            <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
                                            No students found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function() {
    // Navigation handler
    document.querySelectorAll('button[data-href]').forEach(btn => {
        btn.addEventListener('click', function() {
            const href = this.getAttribute('data-href');
            if (href && href !== '#') window.location.href = href;
        });
    });

    // Live time update
    function updateTime() {
        const now = new Date();
        const timeStr = now.toLocaleString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });
        const el = document.getElementById('liveTime');
        if (el) el.textContent = timeStr;
    }
    updateTime();
    setInterval(updateTime, 30000);

    // Chart logic
    let yearlyChart = null;

    function formatCurrency(amount) {
        return 'Rs. ' + parseFloat(amount).toLocaleString('en-LK', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function showLoading(show) {
        const loader = document.getElementById('chartLoading');
        if (loader) loader.style.display = show ? 'flex' : 'none';
    }

    function updateStats(totalData, instituteData, labels) {
        const totalRevenue = totalData.reduce((a, b) => a + b, 0);
        const instituteRevenue = instituteData.reduce((a, b) => a + b, 0);

        let maxTotal = 0, maxIndex = 0;
        for (let i = 0; i < totalData.length; i++) {
            if (totalData[i] > maxTotal) {
                maxTotal = totalData[i];
                maxIndex = i;
            }
        }

        const firstHalf = totalData.slice(0, 6).reduce((a, b) => a + b, 0);
        const secondHalf = totalData.slice(6, 12).reduce((a, b) => a + b, 0);
        const growthRate = firstHalf > 0 ? ((secondHalf - firstHalf) / firstHalf * 100).toFixed(1) : 0;

        const totalEl = document.getElementById('totalRevenue');
        const instituteEl = document.getElementById('instituteIncome');
        const bestEl = document.getElementById('bestMonth');
        const growthEl = document.getElementById('growthRate');

        if (totalEl) totalEl.innerHTML = formatCurrency(totalRevenue);
        if (instituteEl) instituteEl.innerHTML = formatCurrency(instituteRevenue);
        if (bestEl) bestEl.innerHTML = `${labels[maxIndex]} (${formatCurrency(maxTotal)})`;
        if (growthEl) {
            growthEl.innerHTML = (growthRate >= 0 ? '+' : '') + growthRate + '%';
            growthEl.style.color = growthRate >= 0 ? '#10a37f' : '#dc2626';
        }
    }

    function loadYearlyReport(year) {
        showLoading(true);
        const url = `{{ route('admin.institute-yearly-report') }}?year=${year}`;

        fetch(url)
            .then(response => response.json())
            .then(result => {
                if (!result.success) throw new Error(result.message);

                const canvas = document.getElementById('yearlyPaymentChart');
                if (!canvas) return;
                const ctx = canvas.getContext('2d');
                if (yearlyChart) yearlyChart.destroy();

                const gradientTotal = ctx.createLinearGradient(0, 0, 0, 300);
                gradientTotal.addColorStop(0, 'rgba(37,99,235,0.18)');
                gradientTotal.addColorStop(1, 'rgba(37,99,235,0)');

                const gradientInstitute = ctx.createLinearGradient(0, 0, 0, 300);
                gradientInstitute.addColorStop(0, 'rgba(16,163,127,0.18)');
                gradientInstitute.addColorStop(1, 'rgba(16,163,127,0)');

                yearlyChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: result.labels,
                        datasets: [
                            {
                                label: 'Total Payments',
                                data: result.total_payments,
                                borderColor: '#2563eb',
                                backgroundColor: gradientTotal,
                                borderWidth: 2,
                                fill: true,
                                tension: 0.35,
                                pointRadius: 0,
                                pointHoverRadius: 4,
                            },
                            {
                                label: 'Institute Income',
                                data: result.institution_payments,
                                borderColor: '#10a37f',
                                backgroundColor: gradientInstitute,
                                borderWidth: 2,
                                fill: true,
                                tension: 0.35,
                                pointRadius: 0,
                                pointHoverRadius: 4,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { intersect: false, mode: 'index' },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#0f172a',
                                titleColor: '#f8fafc',
                                bodyColor: '#f8fafc',
                                padding: 12,
                                cornerRadius: 12,
                                callbacks: {
                                    label: (ctx) =>
                                        `${ctx.dataset.label}: Rs. ${ctx.raw.toLocaleString('en-LK', { minimumFractionDigits: 2 })}`
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: '#eef1f6' },
                                ticks: {
                                    color: '#6b7590',
                                    font: { size: 12 },
                                    callback: (val) => 'Rs. ' + val.toLocaleString()
                                }
                            },
                            x: { grid: { display: false } }
                        }
                    }
                });

                updateStats(result.total_payments, result.institution_payments, result.labels);
                showLoading(false);
            })
            .catch(() => showLoading(false));
    }

    const yearSelector = document.getElementById('yearSelector');
    if (yearSelector) {
        loadYearlyReport(yearSelector.value);
        yearSelector.addEventListener('change', () => loadYearlyReport(yearSelector.value));
    }
})();
</script>
@endsection

@push('styles')
<style>
    :root{
        --nx-navy:#141c3a; --nx-blue:#2563eb; --nx-blue-soft:#eef3ff;
        --nx-green:#10a37f; --nx-green-soft:#e9f8f2;
        --nx-amber:#b7791f; --nx-amber-soft:#fef6e4;
        --nx-bg:#f8fafc; --nx-card:#fff; --nx-border:#e5e9f0;
        --nx-text:#1e2640; --nx-muted:#6b7590; --nx-radius:12px;
    }

    .dashboard-page{
        font-family:Inter,system-ui,sans-serif;
        color:var(--nx-text);
        background:var(--nx-bg);
        padding:1.5rem;
        max-width:1440px;
        margin:0 auto;
        min-height:100vh;
    }
    .dashboard-page h1,.dashboard-page h2,.stat-number{font-family:Poppins,Inter,sans-serif}

    .nx-card{
        background:var(--nx-card);
        border:1px solid var(--nx-border);
        border-radius:var(--nx-radius);
        box-shadow:0 1px 2px rgba(20,28,58,.04);
    }

    @keyframes spin{to{transform:rotate(360deg)}}

    /* HERO */
    .hero-section{
        background:var(--nx-navy) radial-gradient(circle at 100% 0,rgba(37,99,235,.35),transparent 45%);
        color:#f1f4fb;
        border-radius:var(--nx-radius);
        padding:1.75rem;
        box-shadow:none;
        position:relative;
        overflow:hidden;
    }
    .hero-content{display:flex;justify-content:space-between;align-items:flex-start;gap:1.5rem;flex-wrap:wrap;position:relative;z-index:2}
    .hero-left{flex:1;min-width:280px}
    .hero-greeting{display:flex;flex-direction:column;gap:.25rem}
    .greeting-badge{background:none;padding:0;font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;opacity:.7;color:#f1f4fb}
    .hero-title{font-size:1.7rem;font-weight:600;margin:.25rem 0;color:#fff;line-height:1.2}
    .hero-subtitle{font-size:.875rem;opacity:.7;margin:0}
    .hero-stats-mini{display:flex;align-items:center;gap:1.5rem;margin-top:1rem}
    .mini-stat{display:flex;flex-direction:column}
    .mini-stat-value{font-size:1.125rem;font-weight:600}
    .mini-stat-label{font-size:.75rem;opacity:.6}
    .mini-stat-divider{width:1px;height:28px;background:rgba(255,255,255,.15)}
    .hero-right{display:flex;flex-direction:column;align-items:flex-end;gap:.75rem;flex-shrink:0}
    .hero-date-time{display:flex;align-items:center;gap:.5rem}
    .date-box,.time-box{
        background:rgba(255,255,255,.1);
        border-radius:8px;
        padding:.5rem .75rem;
        font-size:.875rem;
        display:flex;align-items:center;gap:.4rem;
    }

    /* ALERT */
    .alert-card{
        display:flex;gap:.75rem;align-items:center;
        background:var(--nx-amber-soft);
        border:1px solid rgba(183,121,31,.3);
        border-radius:8px;
        padding:.75rem 1rem;
        font-size:.875rem;
    }
    .alert-icon{color:var(--nx-amber);font-size:1.1rem;flex-shrink:0}
    .alert-content{flex:1;color:#7a5010}
    .alert-highlight{font-weight:600;color:var(--nx-amber)}
    .alert-card .btn-close{font-size:.7rem;opacity:.5}
    .alert-card .btn-close:hover{opacity:1}

    /* STATS GRID */
    .stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.25rem}
    .stat-card{display:flex;gap:.75rem;align-items:center;padding:1rem}
    .stat-icon-wrapper{
        width:40px;height:40px;border-radius:8px;
        display:grid;place-items:center;
        background:var(--nx-blue-soft);color:var(--nx-blue);
        font-size:1.15rem;flex-shrink:0;
    }
    .stat-card-income .stat-icon-wrapper{background:var(--nx-green-soft);color:var(--nx-green)}
    .stat-info{flex:1;min-width:0}
    .stat-label{font-size:.75rem;color:var(--nx-muted);display:block}
    .stat-number{font-size:1.25rem;font-weight:600;margin:0;color:var(--nx-text);line-height:1.3}

    /* QUICK ACTIONS */
    .quick-actions-section{
        background:#fff;
        border:1px solid var(--nx-border);
        border-radius:var(--nx-radius);
        padding:1.25rem;
        margin-bottom:1.25rem;
    }
    .quick-actions-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;flex-wrap:wrap;gap:.5rem}
    .section-title{font-weight:600;margin:0;color:var(--nx-text);font-size:1rem;display:flex;align-items:center;gap:.4rem}
    .section-subtitle{font-size:.8rem;color:var(--nx-muted);margin:0}
    .badge-actions{font-size:.7rem;font-weight:500;padding:.2rem .6rem;border-radius:6px;background:#f1f5f9;color:var(--nx-muted)}
    .quick-actions-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:.5rem}
    .quick-action-btn{
        display:flex;align-items:center;gap:.6rem;
        border:1px solid var(--nx-border);
        background:#fff;
        border-radius:8px;
        padding:.6rem .75rem;
        font-size:.875rem;font-weight:500;color:var(--nx-text);
        text-align:left;cursor:pointer;transition:all .15s ease;
    }
    .quick-action-btn .qa-icon{
        width:32px;height:32px;border-radius:6px;
        display:grid;place-items:center;
        background:var(--nx-blue-soft);color:var(--nx-blue);
        font-size:.95rem;flex-shrink:0;
    }
    .quick-action-btn .qa-text{flex:1;min-width:0}
    .quick-action-btn .qa-title{display:block;font-weight:500;font-size:.85rem}
    .quick-action-btn .qa-desc{display:block;font-size:.7rem;color:var(--nx-muted)}
    .quick-action-btn .qa-arrow{display:none}
    .quick-action-btn:hover{border-color:rgba(37,99,235,.4);background:var(--nx-blue-soft)}

    /* CHART */
    .chart-premium-card{
        background:#fff;
        border:1px solid var(--nx-border);
        border-radius:var(--nx-radius);
        box-shadow:none;
        height:100%;
        overflow:hidden;
    }
    .chart-premium-header{
        display:flex;flex-wrap:wrap;justify-content:space-between;gap:.75rem;
        padding:1rem 1.25rem;border-bottom:1px solid var(--nx-border);
    }
    .chart-premium-title{font-size:.9rem;font-weight:600;margin:0;color:var(--nx-text)}
    .chart-premium-subtitle{font-size:.75rem;color:var(--nx-muted);margin:0}
    .chart-controls{display:flex;align-items:center;gap:1rem;flex-wrap:wrap}
    .chart-legend{display:flex;align-items:center;gap:.75rem}
    .legend-item{display:flex;align-items:center;gap:.35rem;font-size:.75rem;color:var(--nx-muted)}
    .legend-dot{width:10px;height:10px;border-radius:3px;display:inline-block}
    .legend-dot.blue{background:var(--nx-blue)}
    .legend-dot.green{background:var(--nx-green)}
    .year-selector-premium{border:1px solid var(--nx-border);border-radius:6px;padding:.3rem .5rem;font-size:.875rem;background:#fff}
    .chart-premium-body{padding:0}
    .chart-container-premium{height:288px;padding:1rem .5rem 0;position:relative}
    .chart-loading-premium{position:absolute;inset:0;background:rgba(255,255,255,.9);display:flex;align-items:center;justify-content:center;z-index:10}
    .spinner-premium{width:32px;height:32px;border:3px solid #e2e8f0;border-top-color:var(--nx-blue);border-radius:50%;animation:spin .8s linear infinite}
    .chart-premium-stats{display:grid;grid-template-columns:repeat(4,1fr);border-top:1px solid var(--nx-border)}
    .premium-stat-divider{display:none}
    .premium-stat-item{padding:.75rem 1.25rem;border-right:1px solid var(--nx-border)}
    .premium-stat-item:last-child{border-right:0}
    .premium-stat-label{display:block;font-size:.75rem;color:var(--nx-muted)}
    .premium-stat-value{font-size:.875rem;font-weight:600;color:var(--nx-text)}
    .premium-stat-value.total{color:var(--nx-text)}

    /* STOCK CARD */
    .card-stock-premium{
        background:#fff;
        border:1px solid var(--nx-border);
        border-radius:var(--nx-radius);
        padding:1.25rem;
        height:100%;
        display:flex;flex-direction:column;
    }
    .card-stock-header{display:flex;justify-content:space-between;align-items:flex-start}
    .card-stock-label{font-size:.75rem;color:var(--nx-muted);text-transform:uppercase;letter-spacing:.05em}
    .card-stock-number{font-size:2.25rem;font-weight:600;margin:1.25rem 0 0;color:var(--nx-text);line-height:1.1}
    .card-stock-icon{
        width:40px;height:40px;border-radius:8px;
        background:var(--nx-amber-soft);color:var(--nx-amber);
        display:grid;place-items:center;font-size:1.1rem;
    }
    .card-stock-status{margin:.5rem 0 .75rem}
    .status-badge{display:inline-flex;align-items:center;gap:.35rem;padding:.2rem .6rem;border-radius:6px;font-size:.7rem;font-weight:500}
    .status-badge.success{background:var(--nx-green-soft);color:var(--nx-green)}
    .status-badge.warning{background:var(--nx-amber-soft);color:var(--nx-amber)}
    .card-stock-progress{margin-top:auto}
    .stock-progress{height:6px;background:#eef1f6;border-radius:99px;overflow:hidden}
    .stock-progress-fill{height:100%;background:var(--nx-amber);transition:width .8s ease}
    .stock-progress-label{display:block;font-size:.7rem;color:var(--nx-muted);margin-top:.4rem}

    /* TABLES */
    .table-premium-card{
        background:#fff;
        border:1px solid var(--nx-border);
        border-radius:var(--nx-radius);
        overflow:hidden;
        height:100%;
    }
    .table-premium-header{
        padding:1rem 1.25rem;
        display:flex;justify-content:space-between;align-items:center;
        border-bottom:1px solid var(--nx-border);
    }
    .table-premium-title{font-size:.9rem;font-weight:600;margin:0;color:var(--nx-text)}
    .table-premium-badge{
        font-size:.75rem;padding:.15rem .5rem;border-radius:4px;
        background:var(--nx-amber-soft);color:var(--nx-amber);
        display:inline-flex;align-items:center;gap:.3rem;
        margin-left:.5rem;
    }
    .table-premium-badge.primary{background:var(--nx-blue-soft);color:var(--nx-blue)}
    .table-premium-body{padding:0}
    .premium-table{margin:0;font-size:.875rem}
    .premium-table thead th{
        background:#f4f6fa;
        color:var(--nx-muted);
        font-size:.75rem;font-weight:500;
        padding:.5rem 1.25rem;border:0;
        text-transform:uppercase;letter-spacing:.03em;
    }
    .premium-table tbody td{
        padding:.75rem 1.25rem;
        border-top:1px solid var(--nx-border);
        white-space:nowrap;
        vertical-align:middle;
        color:var(--nx-text);
    }
    .premium-table tbody tr:last-child td{border-bottom:none}
    .premium-table tbody tr:hover{background:#fafbfd}
    .id-badge{
        font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        font-size:.75rem;
        background:#f1f3f7;
        padding:.1rem .35rem;
        border-radius:4px;
        color:var(--nx-text);
    }
    .student-name-cell{display:flex;align-items:center}
    .avatar-xs{
        width:24px;height:24px;border-radius:50%;
        background:var(--nx-blue-soft);color:var(--nx-blue);
        font-size:11px;font-weight:600;
        display:inline-grid;place-items:center;
        margin-right:.5rem;flex-shrink:0;
    }
    .btn-complete{
        font-size:.75rem;font-weight:500;
        color:var(--nx-blue);text-decoration:none;
        display:inline-flex;align-items:center;gap:.25rem;
    }
    .btn-complete:hover{text-decoration:underline;color:var(--nx-blue)}
    .status-dot{
        width:6px;height:6px;border-radius:50%;
        background:var(--nx-green);
        display:inline-block;margin-right:.35rem;
    }
    .empty-table{padding:2.5rem 1rem !important;color:var(--nx-muted)}

    /* RESPONSIVE */
    @media(max-width:1200px){
        .quick-actions-grid{grid-template-columns:repeat(3,1fr)}
    }
    @media(max-width:992px){
        .stats-grid{grid-template-columns:repeat(2,1fr)}
        .quick-actions-grid{grid-template-columns:repeat(3,1fr)}
    }
    @media(max-width:768px){
        .dashboard-page{padding:1rem}
        .hero-section{padding:1.25rem}
        .hero-title{font-size:1.35rem}
        .hero-content{flex-direction:column}
        .hero-right{align-items:flex-start;width:100%}
        .hero-date-time{flex-wrap:wrap}
        .stats-grid{grid-template-columns:repeat(2,1fr);gap:.75rem}
        .stat-card{padding:.75rem}
        .stat-icon-wrapper{width:36px;height:36px;font-size:1rem}
        .stat-number{font-size:1.05rem}
        .chart-premium-stats{grid-template-columns:1fr 1fr}
        .premium-stat-item{border-right:1px solid var(--nx-border);border-bottom:1px solid var(--nx-border)}
        .premium-stat-item:nth-child(2n){border-right:0}
        .premium-stat-item:nth-child(3),
        .premium-stat-item:nth-child(4){border-bottom:0}
        .chart-container-premium{height:240px}
        .premium-table thead th,
        .premium-table tbody td{padding:.5rem .6rem;font-size:.75rem}
    }
    @media(max-width:576px){
        .quick-actions-grid{grid-template-columns:repeat(2,1fr)}
        .stats-grid{grid-template-columns:1fr}
        .hero-stats-mini{gap:1rem;flex-wrap:wrap}
        .mini-stat-divider{display:none}
    }
</style>
@endpush
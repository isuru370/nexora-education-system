@extends('layouts.app')

@section('title', 'Payment Reminder')
@section('page-title', 'Payment Reminder Dashboard')

@section('content')

    @php
        // Sort: Unpaid first, then Free Card, then Paid.
        $sortedEnrollments = $enrollments->sortByDesc(function ($item) {
            $isFreeCard = !empty($item['is_free_card']);
            $isPaid = !empty($item['is_paid']);

            if ($isFreeCard) {
                $statusPriority = 1;
            } elseif (!$isPaid) {
                $statusPriority = 2;
            } else {
                $statusPriority = 0;
            }

            return ($statusPriority * 10000)
                + (int) $item['attendance_count'];
        })->values();

        $totalStudents = $sortedEnrollments->count();

        $paidCount = $sortedEnrollments
            ->filter(function ($item) {
                return empty($item['is_free_card'])
                    && !empty($item['is_paid']);
            })
            ->count();

        $unpaidCount = $sortedEnrollments
            ->filter(function ($item) {
                return empty($item['is_free_card'])
                    && empty($item['is_paid']);
            })
            ->count();

        $freeCardCount = $sortedEnrollments
            ->filter(function ($item) {
                return !empty($item['is_free_card']);
            })
            ->count();

        $totalUnpaidAmount = $sortedEnrollments
            ->filter(function ($item) {
                return empty($item['is_free_card'])
                    && empty($item['is_paid']);
            })
            ->sum('balance');
    @endphp

    <div class="payment-reminder-page">

        {{-- STATS --}}
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-icon blue">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <h3>{{ $totalStudents }}</h3>
                        <p>Total Students</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-icon green">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div>
                        <h3>{{ $paidCount }}</h3>
                        <p>Paid</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-icon red">
                        <i class="bi bi-x-circle-fill"></i>
                    </div>
                    <div>
                        <h3>{{ $unpaidCount }}</h3>
                        <p>Unpaid</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-icon orange">
                        <i class="bi bi-currency-rupee"></i>
                    </div>
                    <div>
                        <h3>Rs. {{ number_format($totalUnpaidAmount, 2) }}</h3>
                        <p>Total Unpaid Balance</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-icon purple">
                        <i class="bi bi-person-check-fill"></i>
                    </div>
                    <div>
                        <h3>{{ $freeCardCount }}</h3>
                        <p>Free Card</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- MAIN CARD --}}
        <div class="main-card">

            {{-- HEADER --}}
            <div class="main-card-header">
                <div>
                    <h4>Payment Reminder Management</h4>
                    <p>View payment status and send reminders to students</p>
                </div>

                <div class="header-buttons">
                    @if ($classId && $categoryFeeId && $sortedEnrollments->count() > 0)
                        <a href="{{ route('admin.payment-reminder.export-unpaid', ['class_id' => $classId, 'payment_month' => $paymentMonth, 'class_category_fee_id' => $categoryFeeId]) }}"
                            class="btn btn-success custom-btn">
                            <i class="bi bi-file-earmark-excel-fill"></i>
                            Export Unpaid
                        </a>

                        <a href="{{ route('admin.payment-reminder.export-all', ['class_id' => $classId, 'payment_month' => $paymentMonth, 'class_category_fee_id' => $categoryFeeId]) }}"
                            class="btn btn-info custom-btn">
                            <i class="bi bi-file-earmark-spreadsheet-fill"></i>
                            Export All
                        </a>
                    @endif
                </div>
            </div>

            {{-- ALERT --}}
            @if (session('success'))
                <div class="alert alert-success custom-alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger custom-alert">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>
                    {{ session('error') }}
                </div>
            @endif

            {{-- SEARCH / FILTER --}}
            <div class="search-card">
                <form method="GET" action="{{ route('admin.payment-reminder.index') }}" id="filterForm">
                    <div class="row g-3 align-items-center">
                        <div class="col-lg-4">
                            <div class="search-input-wrapper">
                                <i class="bi bi-search"></i>
                                <select name="class_id" id="class_id" class="form-control custom-input" required>
                                    <option value="">-- Select Class --</option>
                                    @foreach ($classes as $class)
                                        <option value="{{ $class->id }}"
                                            {{ $classId == $class->id ? 'selected' : '' }}>
                                            {{ $class->class_name }}
                                            ({{ $class->subject->subject_name ?? 'N/A' }} -
                                            {{ $class->grade->grade_name ?? 'N/A' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="search-input-wrapper">
                                <i class="bi bi-tags"></i>
                                <select name="class_category_fee_id" id="class_category_fee_id" class="form-control custom-input" required>
                                    <option value="">-- Select Class First --</option>
                                    @if ($classId && isset($classDetails['category']))
                                        <option value="{{ $classDetails['category']->id }}" selected>
                                            {{ $classDetails['category']->category->category_name ?? 'N/A' }}
                                        </option>
                                    @endif
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="search-input-wrapper">
                                <i class="bi bi-calendar3"></i>
                                <select name="payment_month" id="payment_month" class="form-control custom-input">
                                    @foreach ($availableMonths as $value => $label)
                                        <option value="{{ $value }}"
                                            {{ $paymentMonth == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-2">
                            <button class="btn btn-primary w-100 custom-btn" type="submit">
                                <i class="bi bi-funnel-fill"></i>
                                Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- RESULTS --}}
            @if ($classId && $categoryFeeId)

                {{-- Students Table --}}
                @if ($sortedEnrollments->count() > 0)
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <span class="text-muted">
                                Showing <strong>{{ $sortedEnrollments->count() }}</strong> students
                            </span>
                            <span class="badge bg-info ms-2">
                                <i class="bi bi-phone"></i> SMS to Guardian Mobile
                            </span>
                            <span class="badge bg-danger ms-2">
                                <i class="bi bi-arrow-up"></i> Unpaid First
                            </span>
                        </div>
                        <div>
                            <button class="btn btn-info custom-btn" id="sendSMSSelected">
                                <i class="bi bi-send"></i> SMS Selected
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table custom-table align-middle">
                            <thead>
                                <tr>
                                    <th width="40">
                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                    </th>
                                    <th>#</th>
                                    <th>Student</th>
                                    <th>Student ID</th>
                                    <th>Mobile</th>
                                    <th>Guardian Mobile</th>
                                    <th>Fee Option</th>
                                    <th class="text-end">Final Fee</th>
                                    <th class="text-end">Paid Amount</th>
                                    <th class="text-end">Balance</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">
                                        Attendance
                                        <i class="bi bi-arrow-down-short" title="Sorted by highest attendance"></i>
                                    </th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($sortedEnrollments as $index => $item)
                                    @php
                                        $student = $item['student'];
                                        $isPaid = $item['is_paid'];
                                        $isFreeCard = !empty($item['is_free_card']);
                                        $balance = isset($item['balance'])
                                            ? (float) $item['balance']
                                            : max(
                                                (float) $item['expected_fee']
                                                - (float) $item['paid_amount'],
                                                0
                                            );
                                        $feeOption = isset($item['fee_option'])
                                            ? $item['fee_option']
                                            : null;
                                    @endphp
                                    <tr class="{{ $isFreeCard ? 'table-row-free-card' : (!$isPaid ? 'table-row-unpaid' : 'table-row-paid') }}">
                                        <td>
                                            <input type="checkbox" class="student-checkbox form-check-input"
                                                value="{{ $student->id }}"
                                                data-student-name="{{ $student->full_name ?? $student->initial_name }}"
                                                data-guardian-mobile="{{ $student->guardian_mobile }}"
                                                {{ !$isPaid ? '' : 'disabled' }}>
                                        </td>

                                        <td>{{ $index + 1 }}</td>

                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="user-avatar">
                                                    {{ strtoupper(substr($student->full_name ?? $student->initial_name, 0, 1)) }}
                                                </div>

                                                <div>
                                                    <div class="user-name">
                                                        {{ $student->full_name ?? $student->initial_name }}
                                                    </div>
                                                    @if ($student->full_name)
                                                        <small class="text-muted">{{ $student->initial_name }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>

                                        <td>
                                            <span class="badge custom-badge bg-light text-dark border">
                                                {{ $student->custom_id }}
                                            </span>
                                        </td>

                                        <td>{{ $student->mobile ?? 'N/A' }}</td>

                                        <td>
                                            {{ $student->guardian_mobile ?? 'N/A' }}
                                            @if($student->guardian_mobile)
                                                <span class="badge bg-success ms-1">
                                                    <i class="bi bi-check-circle"></i> SMS
                                                </span>
                                            @endif
                                        </td>

                                        <td>
                                            @if ($feeOption)
                                                <div class="fee-option-name">
                                                    {{ $feeOption['label'] ?? 'N/A' }}
                                                </div>
                                                <small class="text-muted">
                                                    Option Fee:
                                                    Rs. {{ number_format((float) ($feeOption['fee'] ?? 0), 2) }}
                                                </small>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>

                                        <td class="text-end fw-semibold">
                                            Rs. {{ number_format((float) ($item['final_fee'] ?? $item['expected_fee']), 2) }}
                                        </td>

                                        <td class="text-end">
                                            Rs. {{ number_format((float) $item['paid_amount'], 2) }}
                                        </td>

                                        <td class="text-end fw-semibold {{ $balance > 0 ? 'text-danger' : 'text-success' }}">
                                            Rs. {{ number_format($balance, 2) }}
                                        </td>

                                        <td class="text-center">
                                            @if ($isFreeCard)
                                                <span class="badge free-card-badge">
                                                    <i class="bi bi-person-check-fill"></i> FREE CARD
                                                </span>
                                            @elseif ($isPaid)
                                                <span class="badge paid-badge">✓ PAID</span>
                                            @else
                                                <span class="badge unpaid-badge">✗ NOT PAID</span>
                                            @endif
                                        </td>

                                        <td class="text-center">
                                            <span class="attendance-badge">
                                                <i class="bi bi-calendar-check"></i>
                                                {{ $item['attendance_count'] }}
                                            </span>
                                            @if($loop->first && !$isPaid)
                                                <span class="badge bg-warning ms-1" title="Highest Attendance">
                                                    <i class="bi bi-star-fill"></i>
                                                </span>
                                            @endif
                                        </td>

                                        <td>
                                            <div class="action-buttons">
                                                @if (!$isPaid && !$isFreeCard)
                                                    <button class="action-btn sms-btn send-sms-btn"
                                                        data-student-id="{{ $student->id }}"
                                                        data-student-name="{{ $student->full_name ?? $student->initial_name }}"
                                                        data-guardian-mobile="{{ $student->guardian_mobile }}"
                                                        title="Send SMS">
                                                        <i class="bi bi-send"></i>
                                                    </button>

                                                    <button class="action-btn notify-btn send-reminder-btn"
                                                        data-student-id="{{ $student->id }}"
                                                        data-student-name="{{ $student->full_name ?? $student->initial_name }}"
                                                        data-guardian-mobile="{{ $student->guardian_mobile }}"
                                                        title="Send Notification">
                                                        <i class="bi bi-bell"></i>
                                                    </button>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Selected Count --}}
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <span class="text-muted">
                                Selected: <span id="selectedCount">0</span> students
                            </span>
                        </div>
                        <div class="col-md-6 text-end">
                            <span class="text-muted">
                                <i class="bi bi-info-circle"></i>
                                Only <span class="text-danger">NOT PAID</span> students can be selected.
                                Free Card students are excluded.
                            </span>
                            <span class="text-muted ms-3">
                                <i class="bi bi-arrow-up"></i>
                                Sorted by: <strong class="text-danger">Unpaid First</strong> → <strong>Highest Attendance</strong>
                            </span>
                        </div>
                    </div>
                @else
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <h5>No Students Found</h5>
                        <p>No students found for this class, category, and month.</p>
                    </div>
                @endif
            @else
                <div class="empty-state">
                    <i class="bi bi-funnel"></i>
                    <h5>Select Filters</h5>
                    <p>Please select a class, category, and month to view payment status.</p>
                </div>
            @endif

        </div>
    </div>

    {{-- Send Reminder Modal --}}
    <div class="modal fade" id="sendReminderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-send text-success"></i> Send SMS Reminder
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="sendReminderForm" action="{{ route('admin.payment-reminder.send') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="class_id" value="{{ $classId }}">
                        <input type="hidden" name="payment_month" value="{{ $paymentMonth }}">
                        <input type="hidden" name="class_category_fee_id" value="{{ $categoryFeeId }}">
                        <input type="hidden" name="reminder_type" id="reminderTypeInput" value="sms">

                        <div id="studentIdsContainer"></div>

                        <div class="form-group mb-3">
                            <label class="fw-semibold">Selected Students</label>
                            <div id="selectedStudentsList" class="form-control selected-students-list"></div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="fw-semibold">Reminder Type</label>
                            <div id="reminderTypeDisplay" class="form-control" style="background-color: #f8fafc;">
                                <i class="bi bi-send"></i> SMS (Guardian Mobile)
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="reminder_message" class="fw-semibold">Reminder Message</label>
                            <textarea name="message" id="reminder_message" class="form-control" rows="5"
                                placeholder="Enter custom reminder message...">Dear Parent/Guardian,

This is a reminder that the payment for {{ $classDetails['month'] ?? 'this month' }} is pending.

Student: [Student Name]
Class: {{ $classDetails['class']->class_name ?? 'Class' }}
Month: {{ $classDetails['month'] ?? 'this month' }}
Amount Due: Rs. [Amount]
Attendance: [Attendance] classes

Please settle the payment as soon as possible.
Thank you!</textarea>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Note:</strong> SMS will be sent to the <strong>Guardian's Mobile</strong> number only.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" id="sendReminderBtn">
                            <i class="bi bi-send"></i> Send SMS
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .payment-reminder-page {
        animation: fadeIn 0.4s ease;
    }

    .stats-card {
        background: #fff;
        border-radius: 24px;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
        border: 1px solid #eef2f7;
    }

    .stats-icon {
        width: 60px;
        height: 60px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: #fff;
        flex-shrink: 0;
    }

    .stats-icon.blue {
        background: linear-gradient(135deg, #2563eb, #3b82f6);
    }
    .stats-icon.green {
        background: linear-gradient(135deg, #10b981, #34d399);
    }
    .stats-icon.red {
        background: linear-gradient(135deg, #ef4444, #f87171);
    }
    .stats-icon.orange {
        background: linear-gradient(135deg, #f59e0b, #fbbf24);
    }
    .stats-icon.purple {
        background: linear-gradient(135deg, #7c3aed, #a78bfa);
    }

    .stats-card h3 {
        margin: 0;
        font-size: 1.6rem;
        font-weight: 700;
    }

    .stats-card p {
        margin: 0;
        color: #64748b;
        font-size: 0.9rem;
    }

    .main-card {
        background: #fff;
        border-radius: 28px;
        padding: 1.5rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    }

    .main-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.25rem;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .main-card-header h4 {
        margin: 0;
        font-weight: 700;
    }

    .main-card-header p {
        margin: 0;
        color: #64748b;
    }

    .header-buttons {
        display: flex;
        gap: .7rem;
        flex-wrap: wrap;
    }

    .custom-btn {
        border-radius: 14px;
        padding: .72rem 1.2rem;
        font-weight: 600;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: .45rem;
    }

    .custom-btn.btn-primary {
        background: linear-gradient(135deg, #2563eb, #3b82f6);
        color: #fff;
    }
    .custom-btn.btn-success {
        background: linear-gradient(135deg, #10b981, #34d399);
        color: #fff;
    }
    .custom-btn.btn-info {
        background: linear-gradient(135deg, #0ea5e9, #38bdf8);
        color: #fff;
    }

    .custom-alert {
        border-radius: 16px;
        border: 1px solid #bbf7d0;
        padding: 1rem 1.25rem;
        margin-bottom: 1.25rem;
    }
    .custom-alert.alert-danger {
        border-color: #fecaca;
    }

    .search-card {
        background: #f8fafc;
        border-radius: 20px;
        padding: 1rem;
        margin-bottom: 1.25rem;
        border: 1px solid #eef2f7;
    }

    .search-input-wrapper {
        position: relative;
    }

    .search-input-wrapper i {
        position: absolute;
        top: 50%;
        left: 15px;
        transform: translateY(-50%);
        color: #64748b;
        pointer-events: none;
        z-index: 5;
    }

    .custom-input {
        min-height: 48px;
        border-radius: 14px !important;
        border: 1px solid #e2e8f0;
        padding-left: 42px;
        box-shadow: none !important;
        background: #fff;
    }

    .custom-input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1) !important;
    }

    select.custom-input {
        padding-left: 42px;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 15px center;
        padding-right: 40px;
    }

    .custom-table thead th {
        border: none;
        background: #f8fafc;
        color: #475569;
        font-size: .82rem;
        text-transform: uppercase;
        letter-spacing: .03em;
        padding: 1rem;
        white-space: nowrap;
        font-weight: 600;
    }

    .custom-table tbody tr {
        transition: .2s ease;
    }

    .custom-table tbody tr:hover {
        background: #f8fafc;
    }

    .custom-table tbody td {
        padding: 1rem;
        border-color: #f1f5f9;
        vertical-align: middle;
    }

    /* Unpaid row - Red highlight */
    .table-row-unpaid {
        background-color: #fef2f2 !important;
        border-left: 4px solid #ef4444 !important;
    }
    .table-row-unpaid:hover {
        background-color: #fee2e2 !important;
    }

    /* Paid row - Green subtle */
    .table-row-paid {
        background-color: #f0fdf4 !important;
        border-left: 4px solid #22c55e !important;
    }
    .table-row-paid:hover {
        background-color: #dcfce7 !important;
    }

    .table-row-free-card {
        background-color: #f5f3ff !important;
        border-left: 4px solid #7c3aed !important;
    }

    .table-row-free-card:hover {
        background-color: #ede9fe !important;
    }

    .free-card-badge {
        background: #ede9fe;
        color: #6d28d9;
        padding: 5px 12px;
        border-radius: 20px;
        font-weight: 600;
    }

    .fee-option-name {
        font-weight: 600;
        color: #334155;
    }

    .user-avatar {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1rem;
        flex-shrink: 0;
    }

    .user-name {
        font-weight: 600;
    }

    .custom-badge {
        border-radius: 10px;
        padding: .4rem .7rem;
        font-size: .75rem;
        font-weight: 600;
    }

    .paid-badge {
        background: #d1fae5;
        color: #065f46;
        padding: 5px 12px;
        border-radius: 20px;
        font-weight: 600;
    }

    .unpaid-badge {
        background: #fee2e2;
        color: #991b1b;
        padding: 5px 12px;
        border-radius: 20px;
        font-weight: 600;
    }

    .attendance-badge {
        display: inline-block;
        background: #e0f2fe;
        color: #0369a1;
        padding: 3px 12px;
        border-radius: 14px;
        font-weight: 600;
        font-size: 0.85rem;
    }

    .action-buttons {
        display: flex;
        justify-content: center;
        gap: .5rem;
        flex-wrap: wrap;
    }

    .action-btn {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: .2s ease;
        background: transparent;
        cursor: pointer;
    }

    .action-btn:hover {
        transform: translateY(-2px);
    }

    .action-btn.sms-btn {
        background: #d1fae5;
        color: #059669;
    }

    .action-btn.sms-btn:hover {
        background: #059669;
        color: #fff;
    }

    .action-btn.notify-btn {
        background: #dbeafe;
        color: #2563eb;
    }

    .action-btn.notify-btn:hover {
        background: #2563eb;
        color: #fff;
    }

    .selected-students-list {
        max-height: 200px;
        overflow-y: auto;
        padding: 0;
    }

    .student-item {
        padding: 8px 12px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 14px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .student-item:last-child {
        border-bottom: none;
    }

    .student-item .badge-mobile {
        background-color: #e9ecef;
        padding: 2px 10px;
        border-radius: 10px;
        font-size: 11px;
        color: #6c757d;
    }

    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
    }

    .empty-state i {
        font-size: 3.5rem;
        color: #cbd5e1;
        margin-bottom: 1rem;
        display: inline-block;
    }

    .empty-state h5 {
        font-weight: 700;
        margin-bottom: .35rem;
    }

    .empty-state p {
        margin: 0;
        color: #64748b;
    }

    #class_category_fee_id:disabled {
        background-color: #e9ecef;
        cursor: not-allowed;
        opacity: 0.7;
    }

    @media (max-width: 768px) {
        .main-card-header {
            flex-direction: column;
            align-items: stretch;
        }

        .header-buttons {
            width: 100%;
        }

        .header-buttons a,
        .header-buttons button {
            flex: 1;
            justify-content: center;
        }

        .action-buttons {
            justify-content: flex-start;
        }

        .stats-card {
            padding: 1rem;
        }

        .stats-card h3 {
            font-size: 1.2rem;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    var classSelect = $('#class_id');
    var categorySelect = $('#class_category_fee_id');
    var categoryUrl = '{{ route("admin.payment-reminder.categories") }}';

    // ============================================
    // 1. LOAD CATEGORIES
    // ============================================
    function loadCategories(classId) {
        if (!classId) {
            categorySelect.html('<option value="">-- Select Class First --</option>');
            categorySelect.prop('disabled', true);
            return;
        }

        categorySelect.html('<option value="">Loading categories...</option>');
        categorySelect.prop('disabled', true);

        $.ajax({
            url: categoryUrl,
            type: 'GET',
            data: { class_id: classId },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    var options = '<option value="">-- Select Category --</option>';
                    $.each(response.data, function(index, category) {
                        var selected = '{{ $categoryFeeId }}' == category.id ? 'selected' : '';
                        var feeOptions = category.active_fee_options || category.activeFeeOptions || [];
                        var feeText = '';

                        if (feeOptions.length > 0) {
                            var feeLabels = [];

                            $.each(feeOptions, function(i, feeOption) {
                                feeLabels.push(
                                    feeOption.label + ' - Rs. ' +
                                    Number(feeOption.fee).toLocaleString('en-LK', {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    })
                                );
                            });

                            feeText = ' (' + feeLabels.join(' | ') + ')';
                        }

                        options += '<option value="' + category.id + '" ' + selected + '>' +
                            category.category.category_name + feeText +
                            '</option>';
                    });
                    categorySelect.html(options);
                    categorySelect.prop('disabled', false);
                } else {
                    categorySelect.html('<option value="">No categories available</option>');
                    categorySelect.prop('disabled', true);
                }
            },
            error: function() {
                categorySelect.html('<option value="">Error loading categories</option>');
                categorySelect.prop('disabled', true);
            }
        });
    }

    classSelect.on('change', function() {
        loadCategories($(this).val());
    });

    if (classSelect.val()) {
        loadCategories(classSelect.val());
    }

    // ============================================
    // 2. SELECT ALL
    // ============================================
    $('#selectAll').on('change', function() {
        $('.student-checkbox:not(:disabled)').prop('checked', this.checked);
        updateSelectedCount();
    });

    $('.student-checkbox').on('change', function() {
        updateSelectedCount();
        var total = $('.student-checkbox:not(:disabled)').length;
        var checked = $('.student-checkbox:not(:disabled):checked').length;
        $('#selectAll').prop('checked', total === checked && total > 0);
    });

    function updateSelectedCount() {
        var count = $('.student-checkbox:not(:disabled):checked').length;
        $('#selectedCount').text(count);
    }

    // ============================================
    // 3. SEND REMINDER - Individual SMS
    // ============================================
    $('.send-sms-btn').on('click', function() {
        var studentId = $(this).data('student-id');
        var studentName = $(this).data('student-name');
        var guardianMobile = $(this).data('guardian-mobile');
        var balance = Number($(this).data('balance') || 0);
        var feeOption = $(this).data('fee-option') || '';

        if (!guardianMobile) {
            alert('Guardian mobile number is not available for this student.');
            return;
        }

        $('#studentIdsContainer').empty();
        $('#studentIdsContainer').html(
            '<input type="hidden" name="student_ids[]" value="' + studentId + '">'
        );

        var optionHtml = feeOption
            ? '<small class="text-muted d-block">Fee Option: ' + feeOption + '</small>'
            : '';

        $('#selectedStudentsList').html(
            '<div class="student-item">' +
                '<div>' +
                    '<span><i class="bi bi-person"></i> <strong>' + studentName + '</strong></span>' +
                    optionHtml +
                    '<small class="text-danger d-block">Balance: Rs. ' +
                        balance.toLocaleString('en-LK', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }) +
                    '</small>' +
                '</div>' +
                '<span class="badge-mobile"><i class="bi bi-phone"></i> ' + guardianMobile + '</span>' +
            '</div>'
        );

        $('#reminderTypeDisplay').html('<i class="bi bi-send"></i> SMS to Guardian: ' + guardianMobile);
        $('#reminderTypeInput').val('sms');

        $('#sendReminderModal').modal('show');
    });

    // ============================================
    // 4. SEND REMINDER - Bulk SMS
    // ============================================
    $('#sendSMSSelected').on('click', function() {
        var selected = $('.student-checkbox:not(:disabled):checked');
        if (selected.length === 0) {
            alert('Please select at least one unpaid student.');
            return;
        }

        var studentIds = [];
        var studentNames = [];
        var guardianMobiles = [];
        var hasValidMobile = true;

        selected.each(function() {
            var name = $(this).data('student-name');
            var mobile = $(this).data('guardian-mobile');

            if (!mobile) {
                hasValidMobile = false;
                alert('Student "' + name + '" does not have a guardian mobile number.');
                return false;
            }

            studentIds.push($(this).val());
            studentNames.push(name);
            guardianMobiles.push(mobile);
        });

        if (!hasValidMobile || studentIds.length === 0) {
            return;
        }

        $('#studentIdsContainer').empty();

        var inputs = '';
        studentIds.forEach(function(id) {
            inputs += '<input type="hidden" name="student_ids[]" value="' + id + '">';
        });
        $('#studentIdsContainer').html(inputs);

        var listHtml = '';
        studentNames.forEach(function(name, index) {
            listHtml += '<div class="student-item">' +
                '<span><i class="bi bi-person"></i> ' + name + '</span>' +
                '<span class="badge-mobile"><i class="bi bi-phone"></i> ' + guardianMobiles[index] + '</span>' +
                '</div>';
        });
        listHtml += '<div class="student-item text-muted">Total: ' + studentNames.length + ' students</div>';

        $('#selectedStudentsList').html(listHtml);
        $('#reminderTypeDisplay').html('<i class="bi bi-send"></i> SMS to ' + guardianMobiles.length + ' guardians');
        $('#reminderTypeInput').val('sms');

        $('#sendReminderModal').modal('show');
    });

    // ============================================
    // 5. INDIVIDUAL REMINDER BUTTON (App Notification)
    // ============================================
    $('.send-reminder-btn').on('click', function() {
        var studentId = $(this).data('student-id');
        var studentName = $(this).data('student-name');
        var guardianMobile = $(this).data('guardian-mobile');

        $('#studentIdsContainer').empty();
        $('#studentIdsContainer').html(
            '<input type="hidden" name="student_ids[]" value="' + studentId + '">'
        );

        $('#selectedStudentsList').html(
            '<div class="student-item">' +
                '<span><i class="bi bi-person"></i> <strong>' + studentName + '</strong></span>' +
                '<span class="badge-mobile"><i class="bi bi-phone"></i> ' + guardianMobile + '</span>' +
            '</div>'
        );

        $('#reminderTypeDisplay').html('<i class="bi bi-bell"></i> App Notification (Coming Soon)');
        $('#reminderTypeInput').val('notification');

        $('#sendReminderModal').modal('show');
    });

    // ============================================
    // 6. RESET MODAL ON CLOSE
    // ============================================
    $('#sendReminderModal').on('hidden.bs.modal', function() {
        $('#studentIdsContainer').empty();
        $('#selectedStudentsList').html('');
        $('#reminderTypeDisplay').html('<i class="bi bi-send"></i> SMS (Guardian Mobile)');
        $('#reminderTypeInput').val('sms');
    });
});
</script>
@endpush
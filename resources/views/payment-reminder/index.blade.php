@extends('layouts.app')

@section('title', 'Payment Reminder')
@section('page-title', 'Payment Reminder')

@include('reports.partials.styles')

@section('content')
    @php
        $query = ['class_id' => $classId, 'class_category_fee_id' => $categoryFeeId, 'month' => $month->format('Y-m')];
    @endphp

    <div class="report-page">

        {{-- HERO --}}
        <div class="report-hero">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h3 class="fw-bold mb-1">Payment Reminder</h3>
                    <div class="sub">
                        Who has not paid the class fee for a month, and send them a reminder
                        @if ($report)
                            &nbsp;•&nbsp; <i class="bi bi-calendar-event"></i> {{ $report['month_label'] }}
                        @endif
                    </div>
                </div>
                @if ($report && $report['students']->isNotEmpty())
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('payment-reminder.export', ['format' => 'excel', 'status' => 'unpaid'] + $query) }}"
                            class="btn btn-light btn-export">
                            <i class="bi bi-file-earmark-spreadsheet text-success"></i> Unpaid (Excel)
                        </a>
                        <a href="{{ route('payment-reminder.export', ['format' => 'excel', 'status' => 'all'] + $query) }}"
                            class="btn btn-light btn-export">
                            <i class="bi bi-file-earmark-spreadsheet text-success"></i> All (Excel)
                        </a>
                        <a href="{{ route('payment-reminder.export', ['format' => 'pdf', 'status' => 'unpaid'] + $query) }}"
                            class="btn btn-light btn-export">
                            <i class="bi bi-filetype-pdf text-danger"></i> Unpaid (PDF)
                        </a>
                    </div>
                @endif
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success" style="border-radius: 16px;">
                <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger" style="border-radius: 16px;">
                <i class="bi bi-exclamation-circle-fill me-1"></i> {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger" style="border-radius: 16px;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        {{-- FILTER --}}
        <div class="report-card">
            <form method="GET" action="{{ route('payment-reminder.index') }}" id="filterForm" class="row g-3 align-items-end">
                <div class="col-lg-4 col-md-6">
                    <label class="report-label">Class</label>
                    <select name="class_id" id="classSelect" class="form-select report-input" required>
                        <option value="">-- Select class --</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}" {{ (int) $classId === (int) $class->id ? 'selected' : '' }}>
                                {{ $class->class_name }}
                                ({{ optional($class->subject)->subject_name ?? '-' }} / Grade {{ optional($class->grade)->grade_name ?? '-' }}{{ $class->branch ? ' / ' . $class->branch->name : '' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="report-label">Category</label>
                    <select name="class_category_fee_id" id="categorySelect" class="form-select report-input"
                        {{ $classId ? '' : 'disabled' }} required>
                        <option value="">{{ $classId ? '-- Select category --' : '-- Select class first --' }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" {{ (int) $categoryFeeId === (int) $category->id ? 'selected' : '' }}>
                                {{ optional($category->category)->category_name ?? 'Category #' . $category->id }}
                                @if ($category->activeFeeOptions->isNotEmpty())
                                    ({{ $category->activeFeeOptions->map(fn ($option) => $option->label . ' Rs. ' . number_format((float) $option->fee, 2))->implode(' | ') }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="report-label">Fee month</label>
                    <select name="month" class="form-select report-input">
                        @foreach ($months as $value => $label)
                            <option value="{{ $value }}" {{ $month->format('Y-m') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <button type="submit" class="btn btn-primary w-100 report-input">
                        <i class="bi bi-funnel"></i> Show
                    </button>
                </div>
            </form>
        </div>

        @if (!$report)
            <div class="report-card text-center text-muted py-5">
                <i class="bi bi-bell fs-1 d-block mb-2"></i>
                Select a class, a category and a fee month to see who has not paid.
            </div>
        @else
            @php
                $summary = $report['summary'];
                $students = $report['students'];
            @endphp

            {{-- STATS --}}
            <div class="row g-3 mb-3">
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card stat-red">
                        <div class="label">Not paid</div>
                        <div class="value">{{ $summary['unpaid'] }} <small class="fs-6 fw-normal">of {{ $summary['students'] }}</small></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card stat-amber">
                        <div class="label">Unpaid balance</div>
                        <div class="value">Rs. {{ number_format($summary['balance'], 2) }}</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card stat-green">
                        <div class="label">Paid ({{ $summary['paid'] }})</div>
                        <div class="value">Rs. {{ number_format($summary['collected'], 2) }}</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card stat-blue">
                        <div class="label">Free card{{ ($summary['no_attendance'] ?? 0) > 0 ? ' / No attendance' : '' }}</div>
                        <div class="value">{{ $summary['free_card'] }}{{ ($summary['no_attendance'] ?? 0) > 0 ? ' / ' . $summary['no_attendance'] : '' }}</div>
                    </div>
                </div>
            </div>

            <div class="report-card">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h6 class="mb-1">
                            {{ $report['class']->class_name }} • {{ optional($report['category']->category)->category_name }} • {{ $report['month_label'] }}
                        </h6>
                        <small class="text-muted">
                            Not paid first, most attended first.
                            @if ($summary['unpaid_without_mobile'] > 0)
                                <span class="text-danger">{{ $summary['unpaid_without_mobile'] }} unpaid student(s) have no guardian mobile.</span>
                            @endif
                        </small>
                    </div>
                    @if ($summary['unpaid'] > 0)
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-success btn-sm" data-send-selected="sms">
                                <i class="bi bi-chat-dots"></i> SMS selected (<span class="js-selected-count">0</span>)
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm" data-send-selected="notification">
                                <i class="bi bi-bell"></i> App notification
                            </button>
                        </div>
                    @endif
                </div>

                @if ($students->isEmpty())
                    <div class="text-center text-muted py-4">No students in this class category for {{ $report['month_label'] }}.</div>
                @else
                    <div class="table-responsive">
                        <table class="table report-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 36px;">
                                        @if ($summary['unpaid'] > 0)
                                            <input type="checkbox" class="form-check-input" id="selectAll" title="Select all unpaid">
                                        @endif
                                    </th>
                                    <th>Student</th>
                                    <th>Guardian Mobile</th>
                                    <th>Fee Option</th>
                                    <th class="text-end">Fee</th>
                                    <th class="text-end">Paid</th>
                                    <th class="text-end">Balance</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Attended</th>
                                    <th class="text-center">Remind</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($students as $row)
                                    @php $unpaid = $row['status'] === 'unpaid'; @endphp
                                    <tr>
                                        <td>
                                            @if ($unpaid)
                                                <input type="checkbox" class="form-check-input js-student" value="{{ $row['student_id'] }}"
                                                    data-name="{{ $row['student_name'] }}" data-mobile="{{ $row['guardian_mobile'] }}"
                                                    data-balance="{{ number_format($row['balance'], 2) }}">
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $row['student_name'] }}</div>
                                            <small class="text-muted">
                                                {{ $row['student_code'] }}
                                                @if ($row['left_at'])
                                                    • <span class="text-warning">left {{ $row['left_at'] }}</span>
                                                @endif
                                            </small>
                                        </td>
                                        <td>
                                            @if ($row['guardian_mobile'])
                                                {{ $row['guardian_mobile'] }}
                                            @else
                                                <span class="text-danger small">No number</span>
                                            @endif
                                        </td>
                                        <td>{{ $row['fee_option'] ?? '-' }}</td>
                                        <td class="text-end amount">{{ number_format($row['fee'], 2) }}</td>
                                        <td class="text-end amount">{{ number_format($row['paid'], 2) }}</td>
                                        <td class="text-end amount {{ $row['balance'] > 0 ? 'text-danger fw-semibold' : '' }}">
                                            {{ number_format($row['balance'], 2) }}
                                        </td>
                                        <td class="text-center">
                                            @if ($row['status'] === 'paid')
                                                <span class="badge bg-success">Paid</span>
                                            @elseif ($row['status'] === 'free_card')
                                                <span class="badge bg-secondary">Free Card</span>
                                            @elseif ($row['status'] === 'no_attendance')
                                                <span class="badge bg-light text-dark border" title="No attendance this month: no payment is taken">No Attendance</span>
                                            @else
                                                <span class="badge bg-danger">Not Paid</span>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $row['attendance'] }}</td>
                                        <td class="text-center text-nowrap">
                                            @if ($unpaid)
                                                <button type="button" class="btn btn-sm btn-outline-success" title="Send SMS"
                                                    data-send-one="sms" data-id="{{ $row['student_id'] }}">
                                                    <i class="bi bi-chat-dots"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-primary" title="Send app notification"
                                                    data-send-one="notification" data-id="{{ $row['student_id'] }}">
                                                    <i class="bi bi-bell"></i>
                                                </button>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td></td>
                                    <td colspan="3">Total ({{ $summary['students'] }} students)</td>
                                    <td class="text-end amount">{{ number_format($summary['expected'], 2) }}</td>
                                    <td class="text-end amount">{{ number_format($summary['collected'], 2) }}</td>
                                    <td class="text-end amount">{{ number_format($summary['balance'], 2) }}</td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>

            {{-- SEND MODAL --}}
            @if ($summary['unpaid'] > 0)
                <div class="modal fade" id="sendModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                        <form method="POST" action="{{ route('payment-reminder.send') }}" class="modal-content" id="sendForm">
                            @csrf
                            <input type="hidden" name="class_id" value="{{ $classId }}">
                            <input type="hidden" name="class_category_fee_id" value="{{ $categoryFeeId }}">
                            <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                            <input type="hidden" name="channel" id="channelInput" value="sms">
                            <div id="studentInputs"></div>

                            <div class="modal-header">
                                <h5 class="modal-title" id="sendTitle">Send reminder</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <label class="report-label">To</label>
                                <ul class="list-group mb-3" id="recipientList" style="max-height: 200px; overflow-y: auto;"></ul>

                                <label class="report-label" for="messageInput">Message</label>
                                <textarea name="message" id="messageInput" class="form-control" rows="8" maxlength="500">{{ old('message', $defaultMessage) }}</textarea>
                                <div class="d-flex justify-content-between mt-1">
                                    <small class="text-muted">
                                        Filled for each student: {{ implode(' ', $placeholders) }}
                                    </small>
                                    <small class="text-muted"><span id="messageLength">0</span>/500</small>
                                </div>
                                <div class="alert alert-info small mt-3 mb-0" id="channelNote"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-success" id="sendButton">
                                    <i class="bi bi-send"></i> Send
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        @endif

    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Changing the class reloads the page so its categories are shown
            const classSelect = document.getElementById('classSelect');
            const categorySelect = document.getElementById('categorySelect');

            classSelect.addEventListener('change', function () {
                categorySelect.value = '';
                categorySelect.disabled = true;
                if (classSelect.value) {
                    document.getElementById('filterForm').submit();
                }
            });

            const modalElement = document.getElementById('sendModal');
            if (!modalElement) {
                return;
            }

            const checkboxes = Array.from(document.querySelectorAll('.js-student'));
            const selectAll = document.getElementById('selectAll');
            const message = document.getElementById('messageInput');
            const messageLength = document.getElementById('messageLength');
            const modal = bootstrap.Modal.getOrCreateInstance(modalElement);

            function updateCount() {
                const count = checkboxes.filter((box) => box.checked).length;
                document.querySelectorAll('.js-selected-count').forEach((el) => el.textContent = count);
                if (selectAll) {
                    selectAll.checked = count > 0 && count === checkboxes.length;
                }
            }

            function openModal(channel, boxes) {
                if (boxes.length === 0) {
                    alert('Please select at least one unpaid student.');
                    return;
                }

                const isSms = channel === 'sms';
                document.getElementById('channelInput').value = channel;
                document.getElementById('sendTitle').textContent = isSms ? 'Send SMS reminder' : 'Send app notification';
                document.getElementById('channelNote').textContent = isSms
                    ? 'SMS goes to the guardian mobile. Students without a number are skipped.'
                    : 'The notification goes to the student app. Students who have not logged in to the app are skipped.';

                const inputs = document.getElementById('studentInputs');
                const list = document.getElementById('recipientList');
                inputs.replaceChildren();
                list.replaceChildren();

                boxes.forEach(function (box) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'student_ids[]';
                    input.value = box.value;
                    inputs.appendChild(input);

                    // textContent only: names come from the database
                    const item = document.createElement('li');
                    item.className = 'list-group-item d-flex justify-content-between py-1';
                    const name = document.createElement('span');
                    name.textContent = box.dataset.name + ' (Rs. ' + box.dataset.balance + ')';
                    const mobile = document.createElement('small');
                    mobile.className = box.dataset.mobile ? 'text-muted' : 'text-danger';
                    mobile.textContent = isSms ? (box.dataset.mobile || 'No number') : '';
                    item.append(name, mobile);
                    list.appendChild(item);
                });

                modal.show();
            }

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    checkboxes.forEach((box) => box.checked = selectAll.checked);
                    updateCount();
                });
            }

            checkboxes.forEach((box) => box.addEventListener('change', updateCount));

            document.querySelectorAll('[data-send-selected]').forEach(function (button) {
                button.addEventListener('click', function () {
                    openModal(button.dataset.sendSelected, checkboxes.filter((box) => box.checked));
                });
            });

            document.querySelectorAll('[data-send-one]').forEach(function (button) {
                button.addEventListener('click', function () {
                    openModal(button.dataset.sendOne, checkboxes.filter((box) => box.value === button.dataset.id));
                });
            });

            function updateLength() {
                messageLength.textContent = message.value.length;
            }
            message.addEventListener('input', updateLength);
            updateLength();

            // Stop double clicks sending twice
            document.getElementById('sendForm').addEventListener('submit', function () {
                const sendButton = document.getElementById('sendButton');
                sendButton.disabled = true;
                sendButton.textContent = 'Sending...';
            });

            updateCount();
        });
    </script>
@endpush

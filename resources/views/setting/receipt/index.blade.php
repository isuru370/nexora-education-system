@extends('layouts.app')

@section('title', 'Receipt')
@section('page-title', 'Receipt')

@section('content')
    <div class="dashboard-page">
        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">Receipt</h1>
                    <p class="hero-subtitle">
                        Choose what the class payment receipt shows (staff app print and web New Payment).
                        The institute name, "PAYMENT RECEIPT", the class name and the TOTAL always print.
                    </p>
                </div>
                <div class="hero-right">
                    <small class="hero-updated">Admin only</small>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="row g-4">
            <div class="col-lg-7">
                <form method="POST" action="{{ route('admin.setting.receipt.update') }}" id="receipt-form">
                    @csrf
                    @method('PUT')

                    <div class="nx-card mb-3">
                        <div class="nx-card-header">
                            <div>
                                <h5 class="nx-card-title"><i class="bi bi-receipt text-primary"></i> Shown on the receipt</h5>
                                <p class="nx-card-subtitle">Turn off what you do not want printed. The staff app follows this after it is updated.</p>
                            </div>
                        </div>
                        <div class="p-3 pt-0">
                            @foreach ($fields as $name => [$key, $label])
                                <div class="form-check form-switch d-flex align-items-center justify-content-between border-bottom py-2 ps-0">
                                    <label class="form-check-label" for="r-{{ $name }}">{{ $label }}</label>
                                    <span>
                                        <input type="hidden" name="{{ $name }}" value="0">
                                        <input class="form-check-input fs-5 ms-0 receipt-toggle" type="checkbox" role="switch"
                                            name="{{ $name }}" value="1" id="r-{{ $name }}" data-field="{{ $name }}"
                                            {{ old($name, $rules[$name]) ? 'checked' : '' }}>
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="nx-card mb-3">
                        <div class="nx-card-header">
                            <div>
                                <h5 class="nx-card-title"><i class="bi bi-chat-square-text text-info"></i> Footer line (optional)</h5>
                                <p class="nx-card-subtitle">Printed at the bottom, e.g. a phone number. Up to {{ \App\Support\ReceiptRules::FOOTER_MAX }} letters.</p>
                            </div>
                        </div>
                        <div class="p-3 pt-0">
                            <input type="text" name="footer" id="r-footer" class="form-control" maxlength="{{ \App\Support\ReceiptRules::FOOTER_MAX }}"
                                value="{{ old('footer', $rules['footer']) }}" placeholder="e.g. Call 077 123 4567">
                        </div>
                    </div>

                    <button type="submit" class="nx-btn nx-btn-primary">
                        <i class="bi bi-check-circle"></i> Save
                    </button>
                </form>
            </div>

            <div class="col-lg-5">
                <div class="nx-card">
                    <div class="nx-card-header">
                        <div>
                            <h5 class="nx-card-title"><i class="bi bi-eye"></i> Preview</h5>
                            <p class="nx-card-subtitle">Example data</p>
                        </div>
                    </div>
                    <div class="p-3 pt-0">
                        <pre id="receipt-preview" class="bg-light border rounded p-3 mb-0" style="font-size: 12px; white-space: pre-wrap;"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const name = @json(strtoupper(config('app.name')));
            const on = (f) => document.getElementById('r-' + f)?.checked;
            const line = '--------------------------------';

            function render() {
                const out = [name, 'PAYMENT RECEIPT', line];
                if (on('date')) out.push('Date  : 18 Nov 2026 07:45 AM');
                if (on('month')) out.push('Month : November 2026');
                if (on('date') || on('month')) out.push(line);
                if (on('student_name') || on('student_id')) {
                    out.push('STUDENT DETAILS');
                    if (on('student_name')) out.push('Name : K. Perera');
                    if (on('student_id')) out.push('ID   : SA08001');
                    out.push(line);
                }
                out.push('MATHS 10');
                if (on('grade')) out.push('Grade   : Grade 10');
                if (on('teacher')) out.push('Teacher : N. Perera');
                if (on('category')) out.push('Category: Theory');
                if (on('fee_option')) out.push('Option  : Normal');
                if (on('receipt_number')) out.push('Receipt : RCP-000123');
                if (on('fees')) out.push('Class fee          LKR 2000.00');
                out.push(line, '            TOTAL', '        LKR 2000.00', line);
                if (on('classes_count')) out.push('Classes Paid : 1');
                if (on('payment_method')) out.push('Payment : Cash');
                out.push('           Thank You');
                if (on('keep_note')) out.push('    Please keep this receipt');
                const footer = document.getElementById('r-footer').value.trim();
                if (footer) out.push(footer);
                document.getElementById('receipt-preview').textContent = out.join('\n');
            }

            document.querySelectorAll('.receipt-toggle').forEach((el) => el.addEventListener('change', render));
            document.getElementById('r-footer').addEventListener('input', render);
            render();
        })();
    </script>
@endpush

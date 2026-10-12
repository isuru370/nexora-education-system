{{--
|--------------------------------------------------------------------------
| Auto Print Admission Receipt - 58mm Thermal Printer
|--------------------------------------------------------------------------
|
| Session Keys (from StudentCardRegistrationService::register()):
|
|   session('success')           => true
|   session('student')           => App\Models\Student
|   session('portal_username')   => string
|   session('portal_password')   => string
|   session('admission_payment') => App\Models\AdmissionPayment | null
|
| This partial reads from those session keys directly.
|--------------------------------------------------------------------------
--}}

@php
    $student = session('student');
    $payment = session('admission_payment');

    // Receipt is only shown when admission payment exists
    $hasReceipt = !empty($payment);
@endphp

@if ($hasReceipt)
    <div id="auto-print-receipt" class="receipt-print-wrapper">

        <div class="receipt-card">

            {{-- HEADER --}}
            <div class="receipt-header">
                <div class="receipt-logo">
                    <i class="bi bi-receipt-cutoff"></i>
                </div>
                <div class="receipt-title">
                    <h4>PAYMENT RECEIPT</h4>
                    <p>{{ config('app.name', 'Nexora Education System') }}</p>
                </div>
            </div>

            {{-- RECEIPT META --}}
            <div class="receipt-meta">
                <div class="meta-row">
                    <span class="meta-label">Receipt No</span>
                    <span class="meta-value">
                        {{ $payment->receipt_number}}
                    </span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Date</span>
                    <span class="meta-value">
                        {{ \Carbon\Carbon::parse($payment->paid_at ?? $payment->created_at ?? now())->format('d M Y, h:i A') }}
                    </span>
                </div>
            </div>

            {{-- STUDENT INFO --}}
            <div class="receipt-section">
                <h6 class="section-heading">Student Information</h6>

                <div class="info-row">
                    <span class="info-label">Student Name</span>
                    <span class="info-value">
                        {{ $student->full_name ?? $student->initial_name ?? '-' }}
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">Student ID</span>
                    <span class="info-value">{{ $student->custom_id ?? '-' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Branch</span>
                    <span class="info-value">
                        {{ $student->branch->name ?? $payment->branch->name ?? '-' }}
                    </span>
                </div>
            </div>

            {{-- PAYMENT INFO --}}
            <div class="receipt-section">
                <h6 class="section-heading">Payment Information</h6>

                <div class="info-row">
                    <span class="info-label">Admission</span>
                    <span class="info-value">
                        {{ $payment->admission->name ?? '-' }}
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">Payment Method</span>
                    <span class="info-value">
                        {{ ucfirst($payment->payment_method ?? 'Cash') }}
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">Issued By</span>
                    <span class="info-value">
                        {{ $payment->collectedBy->name ?? auth()->user()->name ?? '-' }}
                    </span>
                </div>
            </div>

            {{-- TOTAL AMOUNT --}}
            <div class="receipt-total">
                <span class="total-label">TOTAL AMOUNT</span>
                <span class="total-value">
                    Rs. {{ number_format((float) ($payment->amount ?? 0), 2) }}
                </span>
            </div>

            {{-- FOOTER --}}
            <div class="receipt-footer">
                <p>Thank you for your payment.</p>
                <small>This is a computer-generated receipt.</small>
            </div>

        </div>

        {{-- PRINT BUTTON (for manual reprint) --}}
        <div class="receipt-actions no-print">
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="bi bi-printer"></i>
                Print Receipt
            </button>
            <button type="button" class="btn btn-light border" onclick="closeReceipt()">
                <i class="bi bi-x-circle"></i>
                Close
            </button>
        </div>

    </div>

    @push('styles')
    <style>
        /* ==========================================
           RECEIPT WRAPPER - Hidden on screen by default
        ========================================== */
        .receipt-print-wrapper {
            display: none;
            box-sizing: border-box;
            width: 58mm;
            max-width: 58mm;
            margin: 0 auto 1.5rem;
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            animation: receiptFadeIn .4s ease;
        }

        .receipt-print-wrapper.show {
            display: block;
        }

        @keyframes receiptFadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ==========================================
           RECEIPT CARD - 58mm Thermal Width
        ========================================== */
        .receipt-card {
            box-sizing: border-box;
            width: 100%;
            max-width: 58mm;
            padding: 3mm;
            margin: 0 auto;
            background: #fff;
            border-radius: 0;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .05);
            font-family: Arial, 'Segoe UI', system-ui, sans-serif;
            font-size: 10px;
            color: #0f172a;
            overflow-wrap: anywhere;
        }

        /* HEADER */
        .receipt-header {
            display: flex;
            align-items: center;
            gap: 2mm;
            padding-bottom: 3mm;
            margin-bottom: 3mm;
            border-bottom: 2px dashed #cbd5e1;
        }

        .receipt-logo {
            width: 10mm;
            height: 10mm;
            min-width: 10mm;
            border-radius: 2mm;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }

        .receipt-title {
            min-width: 0;
            flex: 1;
        }

        .receipt-title h4 {
            margin: 0;
            font-weight: 800;
            font-size: 12px;
            letter-spacing: 0;
            line-height: 1.2;
        }

        .receipt-title p {
            margin: 0;
            font-size: 9px;
            color: #64748b;
            overflow-wrap: anywhere;
            line-height: 1.2;
        }

        /* META */
        .receipt-meta {
            display: flex;
            flex-direction: column;
            gap: 1.5mm;
            padding: 2mm;
            background: #f8fafc;
            border-radius: 0;
            margin-bottom: 3mm;
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 2mm;
            font-size: 9px;
        }

        .meta-label {
            color: #64748b;
            font-weight: 600;
            flex-shrink: 0;
        }

        .meta-value {
            font-weight: 700;
            color: #0f172a;
            text-align: right;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        /* SECTIONS */
        .receipt-section {
            margin-bottom: 3mm;
        }

        .section-heading {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0;
            color: #2563eb;
            margin: 0 0 1.5mm;
            padding-bottom: 1mm;
            border-bottom: 1px solid #e2e8f0;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 1mm 0;
            font-size: 9px;
            gap: 2mm;
        }

        .info-label {
            color: #64748b;
            font-weight: 500;
            flex-shrink: 0;
        }

        .info-value {
            font-weight: 600;
            color: #0f172a;
            text-align: right;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        /* TOTAL */
        .receipt-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 2mm;
            padding: 3mm 2mm;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: #fff;
            border-radius: 0;
            margin: 3mm 0;
        }

        .total-label {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0;
            opacity: .9;
        }

        .total-value {
            font-size: 12px;
            font-weight: 800;
            text-align: right;
            overflow-wrap: anywhere;
        }

        /* FOOTER */
        .receipt-footer {
            text-align: center;
            padding-top: 3mm;
            border-top: 1px dashed #cbd5e1;
        }

        .receipt-footer p {
            margin: 0;
            font-size: 10px;
            font-weight: 600;
            color: #0f172a;
        }

        .receipt-footer small {
            color: #94a3b8;
            font-size: 8px;
        }

        /* ACTIONS */
        .receipt-actions {
            display: flex;
            justify-content: center;
            gap: .75rem;
            margin-top: 1.25rem;
        }

        /* ==========================================
           PRINT STYLES - 58mm Thermal Receipt
        ========================================== */
        @media print {
            @page {
                size: 58mm auto;
                margin: 0;
            }

            html,
            body {
                width: 58mm !important;
                min-width: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            body * {
                visibility: hidden !important;
            }

            #auto-print-receipt,
            #auto-print-receipt * {
                visibility: visible !important;
            }

            #auto-print-receipt {
                display: block !important;
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                width: 58mm !important;
                max-width: 58mm !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                background: #fff !important;
                animation: none !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }

            #auto-print-receipt .receipt-card {
                width: 58mm !important;
                max-width: 58mm !important;
                padding: 2mm !important;
                margin: 0 !important;
                border: none !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                background: #fff !important;
            }

            #auto-print-receipt .no-print {
                display: none !important;
            }

            #auto-print-receipt .receipt-logo,
            #auto-print-receipt .receipt-total {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
    @endpush

    @push('scripts')
    <script>
        (function() {
            // Show the receipt wrapper (it's hidden by default)
            const wrapper = document.getElementById('auto-print-receipt');
            if (wrapper) {
                wrapper.classList.add('show');
            }

            // Auto-open print dialog after page load
            window.addEventListener('load', function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            });

            // Close receipt (hide wrapper)
            window.closeReceipt = function() {
                if (wrapper) {
                    wrapper.classList.remove('show');
                }
            };
        })();
    </script>
    @endpush
@endif
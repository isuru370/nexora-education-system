@extends('layouts.app')

@section('title', 'Generate Temporary ID Cards')
@section('page-title', 'Generate Temporary ID Cards')

@push('styles')
<style>
    .tmp-page {
        animation: fadeIn .35s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .hero-card,
    .main-card {
        background: #fff;
        border-radius: 28px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .05);
        border: 1px solid #eef2f7;
        overflow: hidden;
    }

    .hero-card {
        padding: 1.35rem 1.5rem;
        margin-bottom: 1.5rem;
    }

    .hero-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .hero-actions {
        display: flex;
        gap: .7rem;
        flex-wrap: wrap;
    }

    .main-card {
        padding: 1.5rem;
    }

    .custom-btn {
        border-radius: 14px;
        padding: .7rem 1.15rem;
        font-weight: 600;
        border: none;
        transition: .2s ease;
    }

    .custom-btn:hover {
        transform: translateY(-2px);
    }

    .custom-input,
    .form-control,
    .input-group-text {
        border-radius: 14px;
        min-height: 52px;
        border: 1px solid #e2e8f0;
        box-shadow: none;
    }

    .form-control:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, .10);
    }

    /* =========================
        INPUT GROUP CUSTOM
    ========================== */
    .input-group-custom .input-group-text {
        background: linear-gradient(135deg, #2563eb, #3b82f6);
        color: #fff;
        border: none;
        font-weight: 700;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
        border-top-left-radius: 14px;
        border-bottom-left-radius: 14px;
        min-width: 70px;
        justify-content: center;
    }

    .input-group-custom .form-control {
        border-left: none;
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
        border-top-right-radius: 14px;
        border-bottom-right-radius: 14px;
        font-weight: 600;
        font-size: 1.05rem;
        letter-spacing: 1px;
    }

    .input-group-custom .form-control:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, .10);
    }

    /* =========================
        CALCULATION BOX
    ========================== */
    .calculation-box {
        background: linear-gradient(135deg, #f8fafc, #eff6ff);
        border-radius: 20px;
        padding: 1.5rem;
        border-left: 4px solid #2563eb;
    }

    .number-highlight {
        font-size: 1.75rem;
        font-weight: 800;
        color: #2563eb;
        line-height: 1.2;
        transition: .2s;
    }

    .number-highlight.success {
        color: #10b981;
    }

    .number-highlight.info {
        color: #0ea5e9;
    }

    .warning-text {
        color: #92400e;
        background: #fef3c7;
        border: 1px solid #fde68a;
        border-radius: 12px;
        padding: .75rem 1rem;
        font-size: .85rem;
        font-weight: 600;
        margin-top: 1rem;
    }

    /* =========================
        CARD PREVIEW
    ========================== */
    .preview-card {
        width: 100%;
        max-width: 340px;
        margin: 0 auto;
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        border-radius: 16px;
        padding: 1.25rem;
        color: #fff;
        box-shadow: 0 15px 30px rgba(37, 99, 235, .25);
    }

    .qr-placeholder {
        width: 80px;
        height: 80px;
        background: #fff;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .qr-placeholder i {
        color: #2563eb;
        font-size: 2rem;
    }

    .info-badge {
        background: #f1f5f9;
        border-radius: 999px;
        padding: .4rem .9rem;
        color: #2563eb;
        font-size: .75rem;
        font-weight: 700;
    }

    /* =========================
        BUTTON - GENERATE
    ========================== */
    .btn-generate {
        background: linear-gradient(135deg, #2563eb, #3b82f6);
        color: #fff;
        border: none;
        padding: .85rem 2rem;
        font-weight: 700;
        border-radius: 14px;
        transition: all .3s;
        font-size: 1rem;
    }

    .btn-generate:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(37, 99, 235, .35);
        color: #fff;
    }

    .btn-generate:disabled {
        opacity: .7;
        cursor: not-allowed;
    }

    @media (max-width: 768px) {
        .hero-content {
            flex-direction: column;
            align-items: stretch;
        }

        .hero-actions {
            width: 100%;
        }

        .hero-actions .btn {
            flex: 1;
        }

        .number-highlight {
            font-size: 1.35rem;
        }
    }
</style>
@endpush

@section('content')
    <div class="tmp-page">

        {{-- =========================================================
            HERO CARD
        ========================================================== --}}
        <div class="hero-card">
            <div class="hero-content">
                <div>
                    <h4 class="mb-1 fw-bold">Generate Temporary ID Cards</h4>
                    <p class="mb-0 text-muted">
                        Generate temporary ID cards with QR codes and export PDF.
                    </p>
                </div>

                <div class="hero-actions">
                    <a href="{{ route('temporary_id_cards.index') }}"
                       class="btn btn-outline-secondary custom-btn">
                        <i class="bi bi-arrow-left me-1"></i>
                        Back to List
                    </a>
                </div>
            </div>
        </div>

        {{-- =========================================================
            ALERTS
        ========================================================== --}}
        @if (session('success'))
            <div class="alert alert-success border-0 shadow-sm rounded-4">
                <i class="bi bi-check-circle-fill me-2"></i>
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger border-0 shadow-sm rounded-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm rounded-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Please fix the following errors:</strong>
                <ul class="mb-0 mt-2 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- =========================================================
            MAIN CONTENT
        ========================================================== --}}
        <div class="row g-4">

            {{-- FORM CARD --}}
            <div class="col-lg-8">
                <div class="main-card">

                    {{-- INFO --}}
                    <div class="alert alert-info border-0 bg-light d-flex align-items-start mb-4">
                        <i class="bi bi-info-circle-fill text-primary fs-4 me-3"></i>

                        <div>
                            <strong>Quick Guide</strong><br>
                            Enter number range like 001 to 9999. The system will automatically
                            continue from the next available TMP number.
                        </div>
                    </div>

                    {{-- FORM --}}
                    <form method="POST"
                          action="{{ route('temporary_id_cards.store') }}"
                          id="generateForm">
                        @csrf

                        <div class="row g-4 mb-4">

                            {{-- Start --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Start Number
                                </label>

                                <div class="input-group input-group-custom">
                                    <span class="input-group-text">TMP</span>

                                    <input type="text"
                                           name="start"
                                           class="form-control @error('start') is-invalid @enderror"
                                           placeholder="001"
                                           value="{{ old('start', '001') }}"
                                           maxlength="6"
                                           pattern="[0-9]+"
                                           required>
                                </div>

                                @error('start')
                                    <small class="text-danger d-block mt-1">{{ $message }}</small>
                                @else
                                    <small class="text-muted">Example: 001</small>
                                @enderror
                            </div>

                            {{-- End --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    End Number
                                </label>

                                <div class="input-group input-group-custom">
                                    <span class="input-group-text">TMP</span>

                                    <input type="text"
                                           name="end"
                                           class="form-control @error('end') is-invalid @enderror"
                                           placeholder="012"
                                           value="{{ old('end', '012') }}"
                                           maxlength="6"
                                           pattern="[0-9]+"
                                           required>
                                </div>

                                @error('end')
                                    <small class="text-danger d-block mt-1">{{ $message }}</small>
                                @else
                                    <small class="text-muted">Must be greater than start</small>
                                @enderror
                            </div>

                        </div>

                        {{-- CALCULATION --}}
                        <div class="calculation-box mb-4">
                            <div class="row text-center g-3">

                                <div class="col-4">
                                    <div class="text-muted small fw-semibold">Total Cards</div>
                                    <div class="number-highlight" id="totalCards">12</div>
                                </div>

                                <div class="col-4">
                                    <div class="text-muted small fw-semibold">Pages</div>
                                    <div class="number-highlight success" id="totalPages">1</div>
                                </div>

                                <div class="col-4">
                                    <div class="text-muted small fw-semibold">Per Page</div>
                                    <div class="number-highlight info">12</div>
                                </div>

                            </div>

                            <div id="largeNumberWarning"
                                 class="warning-text"
                                 style="display:none;">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                Large amount of cards selected. Please wait for processing.
                            </div>
                        </div>

                        {{-- SUBMIT --}}
                        <button type="submit"
                                class="btn btn-generate w-100"
                                id="submitBtn">
                            <i class="bi bi-upc-scan me-2"></i>
                            Generate Temporary ID Cards
                        </button>
                    </form>

                </div>
            </div>

            {{-- PREVIEW CARD --}}
            <div class="col-lg-4">
                <div class="main-card">

                    <h5 class="fw-bold mb-4">
                        <i class="bi bi-eye text-primary me-2"></i>
                        Card Preview
                    </h5>

                    <div class="preview-card">

                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-white rounded-circle p-2 me-2">
                                <span class="text-primary fw-bold">🏢</span>
                            </div>

                            <span class="fw-bold">TEMP ID</span>
                        </div>

                        <div class="d-flex gap-3 align-items-center">

                            <div class="qr-placeholder">
                                <i class="bi bi-qr-code"></i>
                            </div>

                            <div>
                                <div class="fw-bold fs-4">TMP001</div>
                                <div class="small opacity-75">#0001</div>
                                <div class="badge bg-white text-primary mt-2">
                                    Active
                                </div>
                            </div>

                        </div>

                        <div class="text-center small mt-3 pt-2 border-top border-white border-opacity-25">
                            Temporary ID Card
                        </div>

                    </div>

                    <div class="d-flex flex-wrap gap-2 justify-content-center mt-4">
                        <span class="info-badge">QR Enabled</span>
                        <span class="info-badge">PDF Export</span>
                        <span class="info-badge">12 Per Page</span>
                    </div>

                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const startInput     = document.querySelector('input[name="start"]');
        const endInput       = document.querySelector('input[name="end"]');
        const totalCardsSpan = document.getElementById('totalCards');
        const totalPagesSpan = document.getElementById('totalPages');
        const warningDiv     = document.getElementById('largeNumberWarning');
        const form           = document.getElementById('generateForm');
        const submitBtn      = document.getElementById('submitBtn');

        function updateCalculation() {
            let startVal = startInput.value.replace(/\D/g, '');
            let endVal   = endInput.value.replace(/\D/g, '');

            startInput.value = startVal;
            endInput.value   = endVal;

            let start = startVal ? parseInt(startVal) : 1;
            let end   = endVal ? parseInt(endVal) : 12;

            if (isNaN(start) || start < 1) start = 1;
            if (isNaN(end) || end < start) end = start;

            const total = (end - start) + 1;
            const pages = Math.ceil(total / 12);

            totalCardsSpan.textContent = total.toLocaleString();
            totalPagesSpan.textContent = pages.toLocaleString();

            if (total > 1000) {
                warningDiv.style.display = 'block';
            } else {
                warningDiv.style.display = 'none';
            }
        }

        startInput.addEventListener('input', updateCalculation);
        endInput.addEventListener('input', updateCalculation);

        updateCalculation();

        // Form submit loading state
        form.addEventListener('submit', function () {
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                Generating...
            `;
        });
    });
</script>
@endpush
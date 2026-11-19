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
    .ui-form-control,
    .ui-input-group-text {
        border-radius: 14px;
        min-height: 52px;
        border: 1px solid #e2e8f0;
        box-shadow: none;
    }

    .ui-form-control:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, .10);
    }

    /* =========================
        INPUT GROUP CUSTOM
    ========================== */
    .input-group-custom .ui-input-group-text {
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

    .input-group-custom .ui-form-control {
        border-left: none;
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
        border-top-right-radius: 14px;
        border-bottom-right-radius: 14px;
        font-weight: 600;
        font-size: 1.05rem;
        letter-spacing: 1px;
    }

    .input-group-custom .ui-form-control:focus {
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

        .hero-actions .ui-btn {
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
                    <h4 class="tw:mb-1! tw:font-bold!">Generate Temporary ID Cards</h4>
                    <p class="tw:mb-0! tw:text-bs-secondary-color!">
                        Generate temporary ID cards with QR codes and export PDF.
                    </p>
                </div>

                <div class="hero-actions">
                    <a href="{{ route('temporary_id_cards.index') }}"
                       class="ui-btn ui-btn-outline-secondary custom-btn">
                        <i class="bi bi-arrow-left tw:mr-1!"></i>
                        Back to List
                    </a>
                </div>
            </div>
        </div>

        {{-- =========================================================
            ALERTS
        ========================================================== --}}
        @if (session('success'))
            <div class="ui-alert ui-alert-success tw:[border:0]! tw:shadow-bs-sm! tw:rounded-bs-xl!">
                <i class="bi bi-check-circle-fill tw:mr-2!"></i>
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="ui-alert ui-alert-danger tw:[border:0]! tw:shadow-bs-sm! tw:rounded-bs-xl!">
                <i class="bi bi-exclamation-triangle-fill tw:mr-2!"></i>
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="ui-alert ui-alert-danger tw:[border:0]! tw:shadow-bs-sm! tw:rounded-bs-xl!">
                <i class="bi bi-exclamation-triangle-fill tw:mr-2!"></i>
                <strong>Please fix the following errors:</strong>
                <ul class="tw:mb-0! tw:mt-2! tw:pl-4!">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- =========================================================
            MAIN CONTENT
        ========================================================== --}}
        <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3">

            {{-- FORM CARD --}}
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[66.66666667%]">
                <div class="main-card">

                    {{-- INFO --}}
                    <div class="ui-alert ui-alert-info tw:[border:0]! tw:bg-bs-light! tw:flex! tw:items-start! tw:mb-6!">
                        <i class="bi bi-info-circle-fill tw:text-bs-primary! tw:text-[length:calc(1.275rem_+_0.3vw)]! tw:xl:text-[length:1.5rem]! tw:mr-4!"></i>

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

                        <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3 tw:mb-6!">

                            {{-- Start --}}
                            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                                <label class="ui-form-label tw:font-semibold!">
                                    Start Number
                                </label>

                                <div class="ui-input-group input-group-custom">
                                    <span class="ui-input-group-text">TMP</span>

                                    <input type="text"
                                           name="start"
                                           class="ui-form-control @error('start') ui-is-invalid @enderror"
                                           placeholder="001"
                                           value="{{ old('start', '001') }}"
                                           maxlength="6"
                                           pattern="[0-9]+"
                                           required>
                                </div>

                                @error('start')
                                    <small class="tw:text-bs-danger! tw:block! tw:mt-1!">{{ $message }}</small>
                                @else
                                    <small class="tw:text-bs-secondary-color!">Example: 001</small>
                                @enderror
                            </div>

                            {{-- End --}}
                            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                                <label class="ui-form-label tw:font-semibold!">
                                    End Number
                                </label>

                                <div class="ui-input-group input-group-custom">
                                    <span class="ui-input-group-text">TMP</span>

                                    <input type="text"
                                           name="end"
                                           class="ui-form-control @error('end') ui-is-invalid @enderror"
                                           placeholder="012"
                                           value="{{ old('end', '012') }}"
                                           maxlength="6"
                                           pattern="[0-9]+"
                                           required>
                                </div>

                                @error('end')
                                    <small class="tw:text-bs-danger! tw:block! tw:mt-1!">{{ $message }}</small>
                                @else
                                    <small class="tw:text-bs-secondary-color!">Must be greater than start</small>
                                @enderror
                            </div>

                        </div>

                        {{-- CALCULATION --}}
                        <div class="calculation-box tw:mb-6!">
                            <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2 tw:text-center!">

                                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[33.33333333%]">
                                    <div class="tw:text-bs-secondary-color! tw:text-[.875em] tw:font-semibold!">Total Cards</div>
                                    <div class="number-highlight" id="totalCards">12</div>
                                </div>

                                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[33.33333333%]">
                                    <div class="tw:text-bs-secondary-color! tw:text-[.875em] tw:font-semibold!">Pages</div>
                                    <div class="number-highlight success" id="totalPages">1</div>
                                </div>

                                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[33.33333333%]">
                                    <div class="tw:text-bs-secondary-color! tw:text-[.875em] tw:font-semibold!">Per Page</div>
                                    <div class="number-highlight info">12</div>
                                </div>

                            </div>

                            <div id="largeNumberWarning"
                                 class="warning-text"
                                 style="display:none;">
                                <i class="bi bi-exclamation-triangle-fill tw:mr-1!"></i>
                                Large amount of cards selected. Please wait for processing.
                            </div>
                        </div>

                        {{-- SUBMIT --}}
                        <button type="submit"
                                class="ui-btn btn-generate tw:w-full!"
                                id="submitBtn">
                            <i class="bi bi-upc-scan tw:mr-2!"></i>
                            Generate Temporary ID Cards
                        </button>
                    </form>

                </div>
            </div>

            {{-- PREVIEW CARD --}}
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[33.33333333%]">
                <div class="main-card">

                    <h5 class="tw:font-bold! tw:mb-6!">
                        <i class="bi bi-eye tw:text-bs-primary! tw:mr-2!"></i>
                        Card Preview
                    </h5>

                    <div class="preview-card">

                        <div class="tw:flex! tw:items-center! tw:mb-4!">
                            <div class="tw:bg-bs-white! tw:rounded-[50%]! tw:p-2! tw:mr-2!">
                                <span class="tw:text-bs-primary! tw:font-bold!">🏢</span>
                            </div>

                            <span class="tw:font-bold!">TEMP ID</span>
                        </div>

                        <div class="tw:flex! tw:gap-4! tw:items-center!">

                            <div class="qr-placeholder">
                                <i class="bi bi-qr-code"></i>
                            </div>

                            <div>
                                <div class="tw:font-bold! tw:text-[length:calc(1.275rem_+_0.3vw)]! tw:xl:text-[length:1.5rem]!">TMP001</div>
                                <div class="tw:text-[.875em] tw:[opacity:0.75]!">#0001</div>
                                <div class="ui-badge tw:bg-bs-white! tw:text-bs-primary! tw:mt-2!">
                                    Active
                                </div>
                            </div>

                        </div>

                        <div class="tw:text-center! tw:text-[.875em] tw:mt-4! tw:pt-2! tw:[border-top:1px_solid_rgba(255,255,255,0.25)]! tw:[border-right-color:rgba(255,255,255,0.25)]! tw:[border-bottom-color:rgba(255,255,255,0.25)]! tw:[border-left-color:rgba(255,255,255,0.25)]!">
                            Temporary ID Card
                        </div>

                    </div>

                    <div class="tw:flex! tw:flex-wrap! tw:gap-2! tw:justify-center! tw:mt-6!">
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
                <span class="ui-spinner-border ui-spinner-border-sm tw:mr-2!" role="status" aria-hidden="true"></span>
                Generating...
            `;
        });
    });
</script>
@endpush
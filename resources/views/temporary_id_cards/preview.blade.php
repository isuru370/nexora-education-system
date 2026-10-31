@extends('layouts.app')

@section('title', 'Temporary ID Cards Preview')
@section('page-title', 'Temporary ID Cards Preview')

@push('styles')
<style>
    :root {
        --blue: #0b3d91;
        --blue-dark: #052c74;
        --green: #79d11a;
        --green-dark: #38a51e;
        --teal: #0fa7b8;
        --card-shadow: 0 10px 30px rgba(0, 0, 0, .08);
    }

    /* =========================
        PAGE WRAPPER
    ========================== */
    .tmp-page {
        animation: fadeIn .35s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* =========================
        HERO CARD
    ========================== */
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

    /* =========================
        FORM
    ========================== */
    .preview-form-card {
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .05);
        border: 1px solid #eef2f7;
        padding: 1rem;
        margin-bottom: 1.25rem;
    }

    .custom-input {
        border-radius: 14px;
        min-height: 48px;
        border: 1px solid #e2e8f0;
        box-shadow: none;
    }

    .custom-input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, .10);
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

    /* =========================
        INFO BAR
    ========================== */
    .info-bar {
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .05);
        border: 1px solid #eef2f7;
        padding: 1rem 1.25rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: .75rem;
        margin-bottom: 1.25rem;
    }

    /* =========================
        PREVIEW WRAPPER
    ========================== */
    .preview-wrapper {
        background: #f1f5f9;
        padding: 24px;
        border-radius: 24px;
    }

    .a4-page {
        width: 29.7cm;
        min-height: 21cm;
        margin: 0 auto 30px;
        background: #fff;
        padding: 0.4cm;
        border-radius: 18px;
        box-shadow: var(--card-shadow);
        page-break-after: always;
    }

    .cards-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: .35cm;
    }

    /* =========================
        ID CARD (layout: Card Designer)
    ========================== */
    .cards-grid .nx-id-card {
        margin: auto;
        box-shadow: 0 5px 20px rgba(0, 0, 0, .12);
    }

    .page-footer {
        text-align: center;
        margin-top: 10px;
        font-size: 13px;
        color: #64748b;
    }

    /* =========================
        EMPTY STATE
    ========================== */
    .no-cards {
        background: #fff;
        border-radius: 18px;
        padding: 48px 24px;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .05);
    }

    .no-cards .icon {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #eff6ff;
        color: #2563eb;
        margin-bottom: 1rem;
        font-size: 1.75rem;
    }

    /* =========================
        PRINT
    ========================== */
    @media print {
        @page {
            size: A4 landscape;
            margin: 0.4cm;
        }

        body {
            margin: 0;
            background: #fff;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .no-print {
            display: none !important;
        }

        .preview-wrapper {
            padding: 0;
            background: #fff;
        }

        .cards-grid .nx-id-card {
            box-shadow: none;
        }

        .a4-page {
            box-shadow: none;
            margin: 0;
            border-radius: 0;
            width: auto;
            min-height: auto;
        }
    }

    /* =========================
        RESPONSIVE
    ========================== */
    @media (max-width: 992px) {
        .a4-page {
            width: 100%;
            min-height: auto;
            padding: 12px;
        }

        .cards-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .preview-wrapper {
            overflow-x: auto;
        }
    }

    @media (max-width: 576px) {
        .cards-grid {
            grid-template-columns: 1fr;
        }

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

        .preview-wrapper {
            padding: 12px;
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
                    <h4 class="mb-1 fw-bold">Temporary ID Cards Preview</h4>
                    <p class="mb-0 text-muted">
                        Preview and download temporary ID cards as PDF.
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
            FORM + ALERTS
        ========================================================== --}}
        <div class="preview-form-card no-print">

            <form method="POST"
                  action="{{ route('temporary_id_cards.preview-generate') }}">
                @csrf

                <div class="row g-3 align-items-end">

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-hash me-1"></i> Start TMP Number
                        </label>

                        <input type="text"
                               name="start"
                               class="form-control custom-input"
                               placeholder="TMP001"
                               value="{{ $start ?? old('start') }}"
                               required>

                        @error('start')
                            <small class="text-danger d-block mt-1">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-hash me-1"></i> End TMP Number
                        </label>

                        <input type="text"
                               name="end"
                               class="form-control custom-input"
                               placeholder="TMP012"
                               value="{{ $end ?? old('end') }}"
                               required>

                        @error('end')
                            <small class="text-danger d-block mt-1">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-md-4 d-grid">
                        <button type="submit" class="btn btn-success custom-btn">
                            <i class="bi bi-search me-1"></i>
                            Generate Preview
                        </button>
                    </div>

                </div>
            </form>

            @if (session('error'))
                <div class="alert alert-danger border-0 mt-3 mb-0 rounded-4">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    {{ session('error') }}
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success border-0 mt-3 mb-0 rounded-4">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    {{ session('success') }}
                </div>
            @endif

        </div>

        {{-- =========================================================
            INFO BAR + DOWNLOAD PDF
        ========================================================== --}}
        @if (isset($codes) && $codes->count())
            <div class="info-bar no-print">

                <div class="text-muted">
                    <i class="bi bi-collection me-1"></i>
                    <strong>Total Cards:</strong> {{ $codes->count() }}
                    <span class="mx-2">|</span>
                    <strong>Total Pages:</strong> {{ ceil($codes->count() / 12) }}
                    <span class="mx-2">|</span>
                    <strong>Layout:</strong> 3 × 4 Cards Per Page
                </div>

                <form method="POST"
                      action="{{ route('temporary_id_cards.download-pdf') }}"
                      class="d-inline">
                    @csrf
                    <input type="hidden" name="start" value="{{ $start }}">
                    <input type="hidden" name="end" value="{{ $end }}">

                    <button type="submit" class="btn btn-primary custom-btn">
                        <i class="bi bi-file-pdf me-1"></i>
                        Download PDF
                    </button>
                </form>

            </div>
        @endif

        {{-- =========================================================
            PREVIEW
        ========================================================== --}}
        <div class="preview-wrapper">
            @if (isset($codes) && $codes->count())

                @php($cardDesign = \App\Support\CardDesign::get(\App\Support\CardDesign::TEMPORARY))
                @foreach ($codes->chunk(12) as $pageIndex => $pageCodes)
                    <div class="a4-page">
                        <div class="cards-grid">
                            @foreach ($pageCodes as $item)
                                @include('id-cards.card', [
                                    'card' => \App\Support\CardDesign::card(\App\Support\CardDesign::TEMPORARY, [
                                        'code'   => $item['code'],
                                        'qr_src' => $item['qr_base64'],
                                    ], false, $cardDesign),
                                ])
                            @endforeach
                        </div>

                        <div class="page-footer no-print">
                            Page {{ $pageIndex + 1 }} of {{ ceil($codes->count() / 12) }}
                        </div>
                    </div>
                @endforeach

            @else
                <div class="no-cards">
                    <div class="icon">
                        <i class="bi bi-credit-card-2-front"></i>
                    </div>
                    <h4 class="fw-bold mb-2">No preview cards yet</h4>
                    <p class="text-muted mb-0">
                        Enter a Start TMP Number and End TMP Number, then click Generate Preview.
                    </p>
                </div>
            @endif
        </div>

    </div>
@endsection
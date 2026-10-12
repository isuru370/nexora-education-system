@extends('layouts.app')

@section('title', 'Student Card Preview')

@section('content')

    @php
        use SimpleSoftwareIO\QrCode\Facades\QrCode;
    @endphp

    <div class="container-fluid py-4">

        {{-- ===========================
        HEADER
    ============================ --}}

        <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">

            <h4 class="mb-3 mb-md-0">
                <i class="bi bi-person-vcard text-primary me-2"></i>
                Student Card Preview
            </h4>

            <div class="d-flex flex-wrap gap-2 no-print">

                @if (auth()->check() && auth()->user()->isSuperAdmin())
                    <button class="btn btn-outline-primary" id="selectAllBtn">
                        <i class="bi bi-check2-all"></i>
                        Select All
                    </button>

                    <button class="btn btn-outline-secondary" id="deselectAllBtn">
                        <i class="bi bi-x-circle"></i>
                        Clear
                    </button>

                    <button class="btn btn-success" id="bulkDownloadBtn" disabled>
                        <i class="bi bi-download"></i>
                        Download Selected
                        (<span id="downloadCount">0</span>)
                    </button>

                    <button class="btn btn-primary" onclick="window.print()">
                        <i class="bi bi-printer"></i>
                        Print
                    </button>
                @else
                @endif

            </div>

        </div>

        {{-- ===========================
        Selected Count
    ============================ --}}

        @if (auth()->check() && auth()->user()->isSuperAdmin())
            <div class="alert alert-info no-print py-2 d-flex align-items-center gap-2">

                <i class="bi bi-check-circle-fill"></i>

                <span>
                    Selected Cards :
                    <strong id="selectedCount">0</strong>
                    /
                    {{ $cards->count() }}
                </span>

            </div>
        @endif

        {{-- ===========================
        Cards
    ============================ --}}

        <div class="card-wrapper">

            @foreach ($cards as $card)
                @php
                    // ============================================
                    // Branch-wise Background Selection
                    // ============================================
                    $branchId = $card->branch_id ?? 1;
                    $branchBackground = 'id_card_bg_' . $branchId . '.png';

                    $branchBackgroundPath = public_path(
                        'storage/id/' . $branchBackground
                    );

                    // Fallback to default background if branch image is missing
                    if (!file_exists($branchBackgroundPath)) {
                        $branchBackground = 'id_card_bg.png';
                    }
                @endphp

                <div class="card-item student-card" data-id="{{ $card->id }}" data-card-id="{{ $card->id }}"
                    data-qr="{{ $card->qr_code }}" data-branch-id="{{ $branchId }}">

                    {{-- Checkbox --}}

                    <div class="card-toolbar no-print">

                        <div class="form-check">

                            @if (auth()->check() && auth()->user()->isSuperAdmin())
                                <input class="form-check-input student-select" type="checkbox" value="{{ $card->id }}">
                            @else
                                <span class="text-muted" style="font-size: 12px;">
                                    <i class="bi bi-lock"></i>
                                </span>
                            @endif

                        </div>

                        @if (auth()->check() && auth()->user()->isSuperAdmin())
                            <button class="btn btn-sm btn-success download-single-btn" data-card-id="{{ $card->id }}"
                                data-student-key="{{ $card->card_number }}">

                                <i class="bi bi-download"></i>
                                Download

                            </button>
                        @endif

                    </div>

                    {{-- =====================
                        CARD
                    ====================== --}}

                    <div class="id-card student-id-card branch-{{ $branchId }}">

                        <img src="{{ asset('storage/id/' . $branchBackground) }}" class="card-bg"
                            alt="Branch {{ $branchId }} Card Background">

                        {{-- QR --}}
                        <div class="qr-image">

                            {!! QrCode::format('svg')->size(180)->margin(0)->generate($card->qr_code) !!}

                        </div>

                        {{-- QR TEXT --}}
                        <div class="qr-text">

                            {{ $card->qr_code }}

                        </div>

                    </div>

                </div>
            @endforeach

        </div>

        {{-- ===========================
        Pagination
    ============================ --}}

        <div class="mt-4 d-flex justify-content-center no-print">

            {{ $cards->withQueryString()->links() }}

        </div>

    </div>

@endsection

@push('styles')
    <style>
        /* ==========================================
                                       PAGE
                                    ========================================== */

        body {
            background: #f0f2f5;
        }

        /* ==========================================
                                       HEADER
                                    ========================================== */

        h4 i {
            font-size: 1.5rem;
        }

        .btn {
            border-radius: 10px;
            font-weight: 600;
            padding: 0.6rem 1.2rem;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.12);
        }

        .btn-outline-primary:hover {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            border-color: #2563eb;
            color: #fff;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #059669, #10b981);
            border-color: #059669;
        }

        /* ==========================================
                                       SELECTED COUNT ALERT
                                    ========================================== */

        .alert-info {
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            border: none;
            border-radius: 14px;
            color: #1e40af;
            font-weight: 500;
        }

        .alert-info i {
            font-size: 1.2rem;
        }

        /* ==========================================
                                       TOOLBAR
                                    ========================================== */

        .card-toolbar {

            width: 100%;

            display: flex;
            justify-content: space-between;
            align-items: center;

            margin-bottom: 10px;

            padding: 0 4px;

        }

        /* ==========================================
                                       CARD WRAPPER
                                    ========================================== */

        .card-wrapper {

            display: flex;
            flex-wrap: wrap;
            justify-content: center;

            gap: 25px;

        }

        .card-item {

            display: flex;
            flex-direction: column;
            align-items: center;

        }

        /* ==========================================
                                       SELECTED CARD
                                    ========================================== */

        .student-card {

            transition: all 0.3s ease;

        }

        .student-card:hover {
            transform: translateY(-4px);
        }

        .student-card.selected .id-card {

            border: 3px solid #0d6efd;

            box-shadow: 0 0 25px rgba(13, 110, 253, .35);

        }

        /* ==========================================
                                       CHECKBOX
                                    ========================================== */

        .student-select {

            width: 22px;
            height: 22px;

            cursor: pointer;

            border-radius: 6px;

            transition: all 0.2s ease;

        }

        .student-select:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        .form-check {
            padding-left: 0;
        }

        /* ==========================================
                                       DOWNLOAD BUTTON
                                    ========================================== */

        .download-single-btn {

            font-size: 12px;
            border-radius: 8px;
            padding: 0.4rem 0.9rem;
            font-weight: 600;
            background: linear-gradient(135deg, #059669, #10b981);
            border: none;
            color: #fff;
            transition: all 0.3s ease;
        }

        .download-single-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(5, 150, 105, 0.4);
            background: linear-gradient(135deg, #047857, #059669);
        }

        .download-single-btn i {
            margin-right: 4px;
        }

        /* ==========================================
                                       CR80 CARD
                                    ========================================== */

        .id-card {
            position: relative;
            width: 3.375in;
            height: 2.125in;
            overflow: hidden;
            background: #fff;

            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .15);
        }

        .id-card:hover {
            box-shadow: 0 8px 35px rgba(0, 0, 0, .2);
        }

        /* ==========================================
                                       BACKGROUND
                                    ========================================== */

        .card-bg {

            position: absolute;
            inset: 0;

            width: 100%;
            height: 100%;

            object-fit: cover;

        }

        /* ==========================================
           QR IMAGE - DEFAULT (Branch 1 / Fallback)
        ========================================== */

        .qr-image {
            position: absolute;

            top: 33%;
            right: 7.3%;

            width: 24%;
            height: 45%;

            display: flex;
            justify-content: center;
            align-items: center;

            padding: 0;

            z-index: 2;
        }

        /* QR SVG */
        .qr-image svg {
            width: 78%;
            height: 78%;
            display: block;
        }

        /* ==========================================
           QR TEXT - DEFAULT (Branch 1 / Fallback)
        ========================================== */

        .qr-text {
            position: absolute;

            top: 44%;
            right: 44%;

            width: 24%;
            height: 8%;

            display: flex;
            justify-content: center;
            align-items: center;

            text-align: center;

            font-size: 13px;
            font-weight: 800;

            color: #111;

            letter-spacing: .3px;

            white-space: nowrap;

            overflow: hidden;

            padding: 0;

            z-index: 2;
        }

        /* ==========================================
           ==========================================
           BRANCH-SPECIFIC CARD DESIGNS
           ==========================================
           ==========================================
           ඔබේ id_card_bg_N.png images වල QR එක
           තියෙන ස්ථානය අනුව පහත values සකසන්න.
           ========================================== */

        /* ==========================================
           BRANCH 1 - QR on the right, text below
        ========================================== */

        .id-card.branch-1 .qr-image {
            top: 33%;
            right: 7.3%;
            width: 24%;
            height: 45%;
        }

        .id-card.branch-1 .qr-image svg {
            width: 78%;
            height: 78%;
        }

        .id-card.branch-1 .qr-text {
            top: 44%;
            right: 44%;
            width: 24%;
            height: 8%;
            font-size: 13px;
        }

        /* ==========================================
           BRANCH 2 - QR on top-right, text below QR
        ========================================== */

        .id-card.branch-2 .qr-image {
            position: absolute;
            top: 26.7%;
            right: 11.3%;
            width: 23%;
            height: 42%;

            display: flex;
            justify-content: center;
            align-items: center;

            padding: 0;
            z-index: 2;
        }

        .id-card.branch-2 .qr-image svg {
            width: 75%;
            height: 75%;
            display: block;
        }

        .id-card.branch-2 .qr-text {
            position: absolute;
            top: 44%;
            right: 40%;
            width: 24%;
            height: 7%;

            display: flex;
            justify-content: center;
            align-items: center;

            text-align: center;

            font-size: 14px;
            font-weight: 800;

            color: #111;
            letter-spacing: .3px;

            white-space: nowrap;
            overflow: hidden;

            padding: 0;
            z-index: 2;
        }

        /* ==========================================
                                       PAGINATION
                                    ========================================== */

        .pagination {

            margin-top: 20px;

        }

        .pagination .page-link {
            border: none;
            margin: 0 3px;
            border-radius: 10px !important;
            color: #1a1a2e;
            font-weight: 500;
            padding: 0.6rem 1rem;
            transition: all 0.3s ease;
        }

        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: #fff;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
        }

        .pagination .page-item:hover .page-link {
            background: #eef2f7;
        }

        /* ==========================================
                                       PRINT
                                    ========================================== */

        @media print {

            @page {

                size: 3.375in 2.125in;

                margin: 0;

            }

            body {

                background: #fff !important;

            }

            .no-print {

                display: none !important;

            }

            .container-fluid {

                padding: 0 !important;

            }

            .card-wrapper {

                display: block;

            }

            .card-item {
                display: block;
                margin: 0;
                padding: 0;
            }

            .id-card {

                width: 3.375in;
                height: 2.125in;

                margin: 0;

                border: none;

                border-radius: 0;

                box-shadow: none;

                page-break-after: always;

            }

        }

        /* ==========================================
                                       RESPONSIVE - TABLET
                                    ========================================== */

        @media (max-width: 768px) {

            .id-card {
                width: 100%;
                max-width: 3.375in;
                height: auto;
                aspect-ratio: 3.375 / 2.125;
            }

            /* Branch 1 responsive */
            .id-card.branch-1 .qr-image {
                width: 24%;
                height: 40%;
                top: 33%;
                right: 7.3%;
            }

            .id-card.branch-1 .qr-text {
                font-size: 7px;
                top: 44%;
                right: 44%;
                width: 24%;
            }

            /* Branch 2 responsive */
            .id-card.branch-2 .qr-image {
                width: 23%;
                height: 38%;
                top: 40%;
                right: 4.5%;
            }

            .id-card.branch-2 .qr-text {
                font-size: 7px;
                top: 40%;
                right: 60%;
                width: 24%;
                height: 7%;
            }

            .card-wrapper {
                gap: 20px;
            }

            .btn {
                padding: 0.4rem 0.8rem;
                font-size: 0.8rem;
            }

        }

        /* ==========================================
                                       RESPONSIVE - MOBILE
                                    ========================================== */

        @media (max-width: 480px) {

            .id-card {
                max-width: 100%;
                border-radius: 8px;
            }

            /* Small screen QR positioning */
            .id-card.branch-1 .qr-image,
            .id-card.branch-2 .qr-image,
            .id-card.branch-3 .qr-image,
            .id-card.branch-4 .qr-image {
                height: 36%;
                padding: 4px;
            }

            .id-card.branch-1 .qr-text,
            .id-card.branch-2 .qr-text,
            .id-card.branch-3 .qr-text,
            .id-card.branch-4 .qr-text {
                font-size: 6px;
                height: 7%;
            }

            .card-toolbar {
                margin-bottom: 8px;
            }

            .download-single-btn {
                font-size: 10px;
                padding: 0.3rem 0.7rem;
            }

            .student-select {
                width: 18px;
                height: 18px;
            }

            .card-wrapper {
                gap: 15px;
            }

            .btn {
                padding: 0.3rem 0.6rem;
                font-size: 0.7rem;
                border-radius: 8px;
            }

            .alert-info {
                font-size: 0.8rem;
                padding: 0.5rem 0.8rem !important;
            }

        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            @if (auth()->check() && auth()->user()->isSuperAdmin())
                const selected = new Set();

                const selectAllBtn = document.getElementById('selectAllBtn');
                const deselectAllBtn = document.getElementById('deselectAllBtn');
                const bulkDownloadBtn = document.getElementById('bulkDownloadBtn');
                const selectedCount = document.getElementById('selectedCount');
                const downloadCount = document.getElementById('downloadCount');

                function updateCounter() {

                    selectedCount.innerText = selected.size;
                    downloadCount.innerText = selected.size;

                    bulkDownloadBtn.disabled = selected.size === 0;

                }

                /*==========================
                    SINGLE SELECT
                ==========================*/

                document.querySelectorAll('.student-select').forEach(function(cb) {

                    cb.addEventListener('change', function() {

                        const card = this.closest('.student-card');

                        if (this.checked) {

                            selected.add(this.value);
                            card.classList.add('selected');

                        } else {

                            selected.delete(this.value);
                            card.classList.remove('selected');

                        }

                        updateCounter();

                    });

                });

                /*==========================
                    SELECT ALL
                ==========================*/

                selectAllBtn.addEventListener('click', function() {

                    document.querySelectorAll('.student-select').forEach(function(cb) {

                        cb.checked = true;

                        selected.add(cb.value);

                        cb.closest('.student-card')
                            .classList.add('selected');

                    });

                    updateCounter();

                });

                /*==========================
                    CLEAR
                ==========================*/

                deselectAllBtn.addEventListener('click', function() {

                    selected.clear();

                    document.querySelectorAll('.student-select').forEach(function(cb) {

                        cb.checked = false;

                        cb.closest('.student-card')
                            .classList.remove('selected');

                    });

                    updateCounter();

                });

                /*==========================
                    SINGLE DOWNLOAD
                ==========================*/

                document.querySelectorAll('.download-single-btn').forEach(function(btn) {

                    btn.addEventListener('click', function(e) {

                        e.preventDefault();
                        e.stopPropagation();

                        const originalText = this.innerHTML;
                        this.innerHTML = '<i class="bi bi-hourglass-split"></i> Downloading...';
                        this.disabled = true;

                        const cardItem = this.closest('.card-item');
                        const card = cardItem.querySelector('.id-card');
                        const qr = cardItem.dataset.qr;

                        // Remove blue border before screenshot
                        cardItem.classList.remove('selected');

                        html2canvas(card, {
                            scale: 3,
                            useCORS: true,
                            backgroundColor: '#ffffff',
                            logging: false,
                            allowTaint: true,
                            width: card.scrollWidth,
                            height: card.scrollHeight
                        }).then(function(canvas) {

                            // Restore border
                            cardItem.classList.add('selected');

                            const link = document.createElement('a');
                            link.download = `ID_CARD_${qr}.png`;
                            link.href = canvas.toDataURL('image/png');
                            link.click();

                            btn.innerHTML = '<i class="bi bi-download"></i> Download';
                            btn.disabled = false;

                        }).catch(function(error) {

                            cardItem.classList.add('selected');

                            console.error(error);

                            btn.innerHTML = '<i class="bi bi-download"></i> Download';
                            btn.disabled = false;

                        });

                    });

                });

                /*==========================
                    BULK DOWNLOAD
                ==========================*/

                bulkDownloadBtn.addEventListener('click', function() {

                    if (selected.size === 0) {
                        alert('Please select cards');
                        return;
                    }

                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="bi bi-hourglass-split"></i> Downloading...';
                    this.disabled = true;

                    const zip = new JSZip();
                    let index = 1;
                    const checked = document.querySelectorAll('.student-select:checked');
                    let completed = 0;

                    const promises = [];

                    checked.forEach(function(cb) {

                        const cardItem = cb.closest('.card-item');
                        const card = cardItem.querySelector('.id-card');
                        const qr = cardItem.dataset.qr;

                        // Remove blue border
                        cardItem.classList.remove('selected');

                        const promise = html2canvas(card, {
                            scale: 3,
                            useCORS: true,
                            backgroundColor: '#ffffff',
                            logging: false,
                            allowTaint: true,
                            width: card.scrollWidth,
                            height: card.scrollHeight
                        }).then(function(canvas) {

                            // Restore border
                            cardItem.classList.add('selected');

                            const img = canvas.toDataURL('image/png').split(',')[1];

                            zip.file(`ID_CARD_${qr}.png`, img, {
                                base64: true
                            });

                        }).catch(function(error) {

                            cardItem.classList.add('selected');
                            console.error(error);

                        });

                        promises.push(promise);

                    });

                    Promise.all(promises).then(function() {
                        return zip.generateAsync({
                            type: 'blob'
                        });
                    }).then(function(content) {
                        const link = document.createElement('a');
                        link.href = URL.createObjectURL(content);
                        link.download = 'Student_ID_Cards.zip';
                        link.click();
                        URL.revokeObjectURL(link.href);

                        selected.clear();
                        document.querySelectorAll('.student-select').forEach(function(
                            cb) {
                            cb.checked = false;
                            cb.closest('.student-card').classList.remove(
                                'selected');
                        });
                        updateCounter();

                        bulkDownloadBtn.innerHTML = originalText;
                        bulkDownloadBtn.disabled = true;

                    }).catch(function(error) {
                        console.error('Bulk download error:', error);
                        alert('Error downloading cards. Please try again.');
                        bulkDownloadBtn.innerHTML = originalText;
                        bulkDownloadBtn.disabled = false;
                        updateCounter();
                    });

                });
            @endif

        });
    </script>
@endpush
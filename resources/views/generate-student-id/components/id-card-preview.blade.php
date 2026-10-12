<div class="id-card-preview-wrap">
    <div class="id-scale-box">

        @php
            // ==================== NAME FORMATTING ====================
            $initialName = strtoupper($studentData['initial_name'] ?? $studentData['name']);

            // Format: N.M.D.P.S.SURANGIKA -> N.M.D.P.S.<br>SURANGIKA
            if (preg_match('/^(.*\.)([^.]+)$/', $initialName, $match)) {
                $displayName = $match[1] . '<br>' . $match[2];
            } else {
                // Format: N M D P S SURANGIKA -> N M D P S<br>SURANGIKA
                $parts = preg_split('/\s+/', $initialName);

                if (count($parts) > 2) {
                    $last = array_pop($parts);
                    $displayName = implode(' ', $parts) . '<br>' . $last;
                } else {
                    $displayName = $initialName;
                }
            }

            // ==================== BRANCH & ADDRESS ====================
            $branchName = $studentData['branch_name'] ?? 'Main Branch';
            $address = $studentData['address'] ?? '';

            // ==================== IMAGE ====================
            $imgUrl = $studentData['img_url'] ?? asset('storage/logo/black_logo3.png');

            if ($imgUrl && !str_starts_with($imgUrl, 'http')) {
                $imgUrl = ltrim($imgUrl, '/');
                $imgUrl = str_starts_with($imgUrl, 'storage/') ? asset($imgUrl) : asset('storage/' . $imgUrl);
            }
        @endphp

        <div class="student-id-card" id="id-card-{{ $studentData['custom_id'] }}">

            <!-- Background -->
            <img src="{{ asset('storage/id/idcard_bg.png') }}" class="card-bg" alt="Background">

            <!-- ✅ Student Photo -->
            <div class="student-photo">
                <img src="{{ $imgUrl }}" alt="Student Photo" crossorigin="anonymous"
                    onerror="this.onerror=null;this.src='{{ asset('storage/logo/black_logo3.png') }}'">
            </div>

            <!-- Student ID -->
            <div class="student-id">
                {{ $studentData['custom_id'] }}
            </div>

            <!-- Student Initial Name -->
            <div class="student-name">
                {!! $displayName !!}
            </div>

            <!-- ✅ Branch Name -->
            <div class="student-branch">
                {{ $branchName }}
            </div>

            <!-- ✅ Address -->
            @if ($address)
                <div class="student-address">
                    {{ $address }}
                </div>
            @endif

            <!-- QR -->
            <div class="card-qr">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=400x400&data={{ urlencode($qrData) }}"
                    alt="QR">
            </div>

        </div>

    </div>
</div>

<style>
    /* ==================================================
   STUDENT ID CARD
================================================== */
    .student-id-card {
        position: relative;
        width: 85.6mm;
        height: 54mm;
        overflow: hidden;
    }

    /* ==================================================
   BACKGROUND
================================================== */
    .card-bg {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* ==================================================
   ✅ STUDENT PHOTO (Left side)
================================================== */
    .student-photo {
        position: absolute;
        top: 13mm;
        left: 4.5mm;
        width: 19mm;
        height: 24mm;
        background: #c8d4e0;
        border-radius: 1mm;
        overflow: hidden;
        z-index: 20;
        box-shadow: 0 0.5mm 1.5mm rgba(0, 0, 0, 0.2);
        border: 0.3mm solid #1d4ed8;
    }

    .student-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    /* ==================================================
   STUDENT ID
================================================== */
    .student-id {
        position: absolute;

        /* ↑ ID උඩට */
        top: 20.8mm;

        /* ← → */
        left: 33mm;

        width: 40mm;

        font-size: 5mm;
        font-weight: 700;

        color: #143d8d;

        text-align: left;

        z-index: 20;
    }

    /* ==================================================
   STUDENT NAME
================================================== */
    .student-name {
        position: absolute;

        /* ↑ ↓ */
        top: 28mm;

        /* ← → */
        left: 33mm;

        width: 42mm;

        min-height: 8mm;

        font-size: 3.2mm;
        font-weight: 700;

        color: #222;

        text-transform: uppercase;

        line-height: 1.2;

        white-space: normal;

        word-break: break-word;

        overflow-wrap: break-word;

        z-index: 20;
    }

    /* ==================================================
   ✅ BRANCH NAME (below student name)
================================================== */
    .student-branch {
        position: absolute;

        top: 38.5mm;

        left: 33mm;

        width: 42mm;

        font-size: 2.8mm;
        font-weight: 700;

        color: #1d4ed8;

        text-transform: uppercase;

        letter-spacing: 0.1mm;

        line-height: 1.2;

        white-space: nowrap;

        overflow: hidden;

        text-overflow: ellipsis;

        z-index: 20;
    }

    /* ==================================================
   ✅ ADDRESS (bottom)
================================================== */
    .student-address {
        position: absolute;

        top: 43mm;

        left: 33mm;

        width: 42mm;

        max-height: 8mm;

        font-size: 2.2mm;
        font-weight: 500;

        color: #374151;

        line-height: 1.2;

        white-space: normal;

        word-break: break-word;

        overflow-wrap: break-word;

        overflow: hidden;

        z-index: 20;
    }

    /* ==================================================
   QR CODE
================================================== */
    .card-qr {
        position: absolute;

        /* ↑ ↓ */
        top: 15mm;

        /* ← → */
        right: 4mm;

        width: 24mm;
        height: 24mm;

        background: #fff;

        padding: .8mm;

        border: .5mm solid #2450d4;

        border-radius: 1.2mm;

        z-index: 20;
    }

    .card-qr img {
        width: 100%;
        height: 100%;
        display: block;
    }

    /* ==================================================
   MOBILE
================================================== */
    @media (max-width: 768px) {
        .student-id {
            font-size: 3mm;
        }

        .student-name {
            font-size: 3mm;
        }

        .student-branch {
            font-size: 2.5mm;
        }

        .student-address {
            font-size: 2mm;
        }

        .card-qr {
            width: 22mm;
            height: 22mm;
        }

        .student-photo {
            width: 17mm;
            height: 22mm;
        }
    }
</style>

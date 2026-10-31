@php
    // ==================== ✅ SAFE STUDENT PROPERTIES ====================
    $cardId    = (int) data_get($student, 'id', 0);
    $createdAt = $student->created_at?->format('d/m/Y') ?? '-';

    // ==================== STUDENT DATA ====================
    $customId = $student->custom_id ?? 'SA09001';
    $name     = $student->initial_name ?? 'Student Name';

    // ==================== ADDRESS ====================
    $address1 = trim((string) ($student->address1 ?? ''));
    $address2 = trim((string) ($student->address2 ?? ''));
    $address3 = trim((string) ($student->address3 ?? ''));

    $address1 = str_replace(['"', "'", '&quot;'], '', $address1);
    $address2 = str_replace(['"', "'", '&quot;'], '', $address2);
    $address3 = str_replace(['"', "'", '&quot;'], '', $address3);

    $addressParts = array_filter([$address1, $address2, $address3]);
    $address      = implode(', ', $addressParts) ?: 'Address not available';

    // ==================== BRANCH ====================
    $branchName = $student->branch?->name ?? 'Main Branch';
    $branchCode = $student->branch?->code ?? '';

    // ==================== STUDENT DATA ARRAY ====================
    $studentData = [
        'id'           => $cardId,
        'custom_id'    => $customId,
        'name'         => $name,
        'initial_name' => $student->initial_name,
        'address'      => $address,
        'img_url'      => $student->img_url,
        'branch_name'  => $branchName,
        'branch_code'  => $branchCode,
        'grade_name'   => $student->grade?->grade_name ?? '',
        'mobile'       => $student->mobile ?? '',
    ];

    // ==================== KEYS ====================
    $studentKey = $customId ?? $cardId;

    // ==================== IMAGE ====================
    $defaultImage = asset('storage/logo/black_logo3.png');
    $studentImage = $studentData['img_url'] ?? $defaultImage;

    if ($studentImage && !str_starts_with($studentImage, 'http')) {
        $studentImage = ltrim($studentImage, '/');
        $studentImage = str_starts_with($studentImage, 'storage/')
            ? asset($studentImage)
            : asset('storage/' . $studentImage);
    }

    // ==================== QR ====================
    $qrData = $customId;

    // ==================== PERMISSIONS ====================
    $isAdmin = $isAdmin ?? false;
@endphp

<div class="col-xl-4 col-lg-6 col-md-6 col-sm-12 mb-4 student-card"
     data-id="{{ $studentKey }}"
     data-student='@json($studentData)'
     data-card-id="{{ $cardId }}">

    <div class="premium-student-card h-100">

        {{-- ==================== TOP BAR ==================== --}}
        <div class="student-top d-flex justify-content-between align-items-start gap-2">

            <div class="student-info">
                <div class="student-id-badge mb-1">
                    <i class="fas fa-id-card me-1"></i>
                    {{ $studentData['custom_id'] }}
                </div>

                {{-- ✅ Branch Info --}}
                @if ($branchName)
                    <div class="student-card-badge mt-1">
                        <small class="text-muted">
                            <i class="fas fa-building me-1"></i>
                            {{ $branchName }}
                            @if ($branchCode)
                                <span class="text-primary">({{ $branchCode }})</span>
                            @endif
                        </small>
                    </div>
                @endif
            </div>

            {{-- ✅ Checkbox --}}
            @if ($isAdmin)
                <div class="form-check">
                    <input type="checkbox"
                           class="form-check-input student-select"
                           value="{{ $studentKey }}"
                           id="student_{{ $studentKey }}">
                </div>
            @endif

        </div>

        {{-- ==================== ID CARD PREVIEW ==================== --}}
        <div class="id-card-preview-wrap">
            <div class="id-scale-box">
                @include('generate-student-id.components.id-card-preview', [
                    'studentImage' => $studentImage,
                    'qrData'       => $qrData,
                    'studentData'  => $studentData,
                    'defaultImage' => $defaultImage,
                ])
            </div>
        </div>

        {{-- ==================== ACTION AREA ==================== --}}
        <div class="student-action-area px-3 pb-3 pt-2">

            {{-- Name + Date --}}
            <div class="d-flex justify-content-between mb-2">
                <small class="text-muted">
                    <i class="fas fa-user me-1"></i>
                    {{ Str::limit($studentData['name'], 24) }}
                </small>

                <small class="text-muted">
                    <i class="fas fa-calendar me-1"></i>
                    {{ $createdAt }}
                </small>
            </div>

            {{-- ✅ Download ID Card Button --}}
            <button type="button"
                    class="btn download-btn text-white w-100 download-single-btn"
                    data-card-id="{{ $cardId }}"
                    data-student-key="{{ $studentKey }}">
                <i class="fas fa-download me-1"></i> Download ID Card
            </button>

        </div>

    </div>
</div>
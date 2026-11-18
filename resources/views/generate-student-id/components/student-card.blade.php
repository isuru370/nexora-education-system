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

<div class="bs-col-xl-4 ui-col-xl-4 ui-col-lg-6 ui-col-md-6 ui-col-sm-12 tw:mb-6! student-card"
     data-id="{{ $studentKey }}"
     data-student='@json($studentData)'
     data-card-id="{{ $cardId }}">

    <div class="premium-student-card tw:h-full!">

        {{-- ==================== TOP BAR ==================== --}}
        <div class="student-top tw:flex! tw:justify-between! tw:items-start! tw:gap-2!">

            <div class="student-info">
                <div class="student-id-badge tw:mb-1!">
                    <i class="fas fa-id-card tw:mr-1!"></i>
                    {{ $studentData['custom_id'] }}
                </div>

                @if ($student->permanent_qr_active)
                    <span class="ui-badge tw:rounded-bs-pill! tw:bg-bs-success-bg-subtle! tw:text-(color:--nx-green)! tw:[border:1px_solid_#a3cfbb]!">
                        <i class="fas fa-check-circle tw:mr-1!"></i> Permanent card
                    </span>
                @else
                    <span class="ui-badge tw:rounded-bs-pill! tw:bg-bs-warning-bg-subtle! tw:text-bs-warning-text-emphasis! tw:[border:1px_solid_#ffe69c]!">
                        <i class="fas fa-clock tw:mr-1!"></i> Temporary card
                    </span>
                @endif

                {{-- ✅ Branch Info --}}
                @if ($branchName)
                    <div class="student-card-badge tw:mt-1!">
                        <small class="tw:text-(color:--nx-muted)!">
                            <i class="fas fa-building tw:mr-1!"></i>
                            {{ $branchName }}
                            @if ($branchCode)
                                <span class="tw:text-(color:--nx-blue)!">({{ $branchCode }})</span>
                            @endif
                        </small>
                    </div>
                @endif
            </div>

            {{-- ✅ Checkbox --}}
            @if ($isAdmin)
                <div class="ui-form-check">
                    <input type="checkbox"
                           class="ui-form-check-input student-select"
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
        <div class="student-action-area tw:px-4! tw:pb-4! tw:pt-2!">

            {{-- Name + Date --}}
            <div class="tw:flex! tw:justify-between! tw:mb-2!">
                <small class="tw:text-(color:--nx-muted)!">
                    <i class="fas fa-user tw:mr-1!"></i>
                    {{ Str::limit($studentData['name'], 24) }}
                </small>

                <small class="tw:text-(color:--nx-muted)!">
                    <i class="fas fa-calendar tw:mr-1!"></i>
                    {{ $createdAt }}
                </small>
            </div>

            {{-- Downloaded before -> the next download needs a card fee --}}
            @php
                $downloadInfo = ($downloads ?? collect())->get($student->id);
            @endphp
            @if ($downloadInfo)
                <div class="id-download-note tw:mb-2!" data-downloaded-note="{{ $cardId }}">
                    <i class="bi bi-arrow-repeat"></i>
                    Downloaded {{ $downloadInfo->times }}&times; &middot; last {{ \Carbon\Carbon::parse($downloadInfo->last_at)->format('d M Y') }}
                    <span class="tw:block!">Next download: card fee</span>
                </div>
            @endif

            {{-- ✅ Download ID Card Button --}}
            <button type="button"
                    class="ui-btn download-btn tw:text-bs-white! tw:w-full! download-single-btn"
                    data-card-id="{{ $cardId }}"
                    data-student-key="{{ $studentKey }}">
                <i class="fas fa-download tw:mr-1!"></i> Download ID Card
            </button>

        </div>

    </div>
</div>
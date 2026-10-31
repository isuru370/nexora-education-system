{{-- Permanent student ID card, laid out on the Card Designer page (App\Support\CardDesign) --}}
@php
    $cardDesign = $cardDesign ?? \App\Support\CardDesign::get(\App\Support\CardDesign::PERMANENT);

    $card = \App\Support\CardDesign::card(\App\Support\CardDesign::PERMANENT, [
        'student_id' => $studentData['custom_id'] ?? '',
        'name'       => $studentData['initial_name'] ?? $studentData['name'] ?? '',
        'branch'     => $studentData['branch_name'] ?? '',
        'address'    => $studentData['address'] ?? '',
        'grade'      => $studentData['grade_name'] ?? '',
        'mobile'     => $studentData['mobile'] ?? '',
        'qr'         => $qrData ?? ($studentData['custom_id'] ?? ''),
        'photo'      => $studentImage ?? null,
    ], false, $cardDesign);
@endphp

@include('id-cards.card', [
    'card'  => $card,
    'class' => 'student-id-card',
    'attrs' => 'id="id-card-' . e($studentData['custom_id'] ?? '') . '"',
])

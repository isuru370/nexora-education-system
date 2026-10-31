<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Card - {{ $student->custom_id ?? '' }}</title>
    <style>
        html, body { margin: 0; padding: 0; background: #dde6f0; }
        body { display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        @media print {
            @page { size: 85.6mm 54mm; margin: 0; }
            html, body { background: #fff; min-height: auto; display: block; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
    </style>
</head>

<body>
    @php
        $photo = $student->img_url ?? null;
        if ($photo && !str_starts_with($photo, 'http')) {
            $photo = ltrim($photo, '/');
            $photo = str_starts_with($photo, 'storage/') || str_starts_with($photo, 'uploads/')
                ? asset($photo)
                : asset('storage/' . $photo);
        }

        $card = \App\Support\CardDesign::card(\App\Support\CardDesign::PERMANENT, [
            'student_id' => $student->custom_id ?? '',
            'name'       => $student->initial_name ?? '',
            'branch'     => $student->branch?->name ?? '',
            'address'    => collect([$student->address1, $student->address2, $student->address3])->filter()->implode(', '),
            'grade'      => $student->grade?->grade_name ?? '',
            'mobile'     => $student->mobile ?? '',
            'qr'         => $student->custom_id ?? '',
            'photo'      => $photo,
        ]);
    @endphp

    @include('id-cards.card', ['card' => $card])
</body>

</html>

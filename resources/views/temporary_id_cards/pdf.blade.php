{{-- Temporary cards PDF (dompdf): 12 per A4 landscape page, layout from the Card Designer --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Temporary ID Cards PDF</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            font-family: DejaVu Sans, sans-serif;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            background: #ffffff;
        }

        /* PAGE */
        .page {
            width: 29.7cm;
            height: 20.5cm;

            padding-top: 0.34cm;

            display: block;
            position: relative;

            page-break-after: always;
            break-after: page;

        }

        .page:last-child {
            page-break-after: auto;
            break-after: auto;
        }

        /* TABLE */
        table.cards-table {
            width: 26.67cm;
            height: 20.32cm;

            margin: 0 auto;

            border-collapse: collapse;
            border-spacing: 0;
            table-layout: fixed;

            page-break-inside: avoid;
            break-inside: avoid;
        }

        tr {
            height: 5.08cm;
        }

        td.card-cell {
            width: 8.89cm;
            height: 5.08cm;
            padding: 0;
            vertical-align: top;
        }

        /* EMPTY CELL */
        .empty {
            width: 8.89cm;
            height: 5.08cm;
        }

    </style>
</head>

<body>

    @php
        $cardDesign = \App\Support\CardDesign::get(\App\Support\CardDesign::TEMPORARY);
    @endphp

    @foreach($codes->chunk(12) as $pageCodes)
        @php
            $pageCodes = $pageCodes->values();
        @endphp

        <div class="page">
            <table class="cards-table">
                @for($row = 0; $row < 4; $row++)
                    <tr>
                        @for($col = 0; $col < 3; $col++)
                            @php
                                $index = ($row * 3) + $col;
                                $item = $pageCodes[$index] ?? null;
                            @endphp

                            <td class="card-cell">
                                @if($item)
                                    @include('id-cards.card', [
                                        'card' => \App\Support\CardDesign::card(\App\Support\CardDesign::TEMPORARY, [
                                            'code'   => $item['code'],
                                            'qr_src' => $item['qr_base64'],
                                        ], true, $cardDesign),
                                    ])
                                @else
                                    <div class="empty"></div>
                                @endif
                            </td>
                        @endfor
                    </tr>
                @endfor
            </table>
        </div>
    @endforeach

</body>

</html>

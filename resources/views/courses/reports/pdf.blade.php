<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $report['title'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #0f172a; }
        h1 { font-size: 16px; margin: 0; }
        h2 { font-size: 12px; margin: 14px 0 6px; }
        .muted { color: #475569; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 4px 5px; text-align: left; }
        th { background: #e2e8f0; }
        .r { text-align: right; }
        .summary td { border: 0; padding: 2px 8px 2px 0; }
        tfoot td { font-weight: bold; background: #f1f5f9; }
    </style>
</head>
<body>
    <h1>{{ config('institution.name', config('app.name')) }} - {{ $report['title'] }}</h1>
    <div class="muted">{{ $report['subtitle'] }} · generated {{ now()->format('Y-m-d H:i') }}</div>

    <table class="summary" style="margin-top:8px; width:auto">
        @foreach (collect($report['summary'])->chunk(2) as $pair)
            <tr>
                @foreach ($pair as $label => $value)
                    <td class="muted">{{ $label }}</td><td><strong>{{ $value }}</strong></td>
                @endforeach
            </tr>
        @endforeach
    </table>

    @foreach ($report['tables'] as $table)
        <h2>{{ $table['title'] }}</h2>
        <table>
            <thead>
                <tr>@foreach ($table['columns'] as $key => $heading)<th class="{{ in_array($key, $table['money'], true) ? 'r' : '' }}">{{ $heading }}</th>@endforeach</tr>
            </thead>
            <tbody>
                @forelse ($table['rows'] as $row)
                    <tr>
                        @foreach ($table['columns'] as $key => $heading)
                            @if (in_array($key, $table['money'], true))
                                <td class="r">{{ number_format((float) $row[$key], 2) }}</td>
                            @else
                                <td>{{ $row[$key] }}</td>
                            @endif
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($table['columns']) }}" class="muted">Nothing to show.</td></tr>
                @endforelse
            </tbody>
            @if ($table['total'] && count($table['rows']))
                <tfoot>
                    <tr>
                        @foreach ($table['columns'] as $key => $heading)
                            @if ($loop->first)
                                <td>Total</td>
                            @elseif (in_array($key, $table['money'], true))
                                <td class="r">{{ number_format(collect($table['rows'])->sum($key), 2) }}</td>
                            @else
                                <td></td>
                            @endif
                        @endforeach
                    </tr>
                </tfoot>
            @endif
        </table>
    @endforeach
</body>
</html>

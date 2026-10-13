{{--
    Generic report table.
    $table = ['headings' => [], 'columns' => [], 'money' => [], 'total' => column|null]
    $rows  = Collection or LengthAwarePaginator of arrays
--}}
@php
    $money = $table['money'] ?? [];
        $noTotal = $table['no_total'] ?? [];
    $items = $rows instanceof \Illuminate\Contracts\Pagination\Paginator ? $rows->getCollection() : collect($rows);
    $isPaginated = $rows instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;
@endphp

<div class="table-responsive">
    <table class="table report-table align-middle mb-0">
        <thead>
            <tr>
                @foreach ($table['headings'] as $index => $heading)
                    <th class="{{ in_array($table['columns'][$index], $money, true) ? 'text-end' : '' }}">{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $row)
                <tr>
                    @foreach ($table['columns'] as $column)
                        @php $value = data_get($row, $column); @endphp
                        @if (in_array($column, $money, true))
                            <td class="text-end amount">{{ number_format((float) $value, 2) }}</td>
                        @else
                            <td>{{ $value ?? '-' }}</td>
                        @endif
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($table['columns']) }}" class="text-center text-muted py-5">
                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                        No records found for the selected filters.
                    </td>
                </tr>
            @endforelse
        </tbody>

        @if (!empty($table['total']) && $items->isNotEmpty())
            <tfoot>
                <tr>
                    @foreach ($table['columns'] as $index => $column)
                        @if ($index === 0)
                            <td class="fw-bold">{{ $isPaginated ? 'Page total' : 'Total' }}</td>
                        @elseif (in_array($column, $money, true) && !in_array($column, $noTotal, true))
                            <td class="text-end amount fw-bold">{{ number_format($items->sum($column), 2) }}</td>
                        @else
                            <td></td>
                        @endif
                    @endforeach
                </tr>
            </tfoot>
        @endif
    </table>
</div>

@if ($isPaginated && $rows->hasPages())
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
        <small class="text-muted">
            Showing {{ $rows->firstItem() }}-{{ $rows->lastItem() }} of {{ $rows->total() }} records
        </small>
        {{ $rows->links() }}
    </div>
@elseif ($isPaginated)
    <small class="text-muted d-block mt-3">{{ $rows->total() }} records</small>
@endif

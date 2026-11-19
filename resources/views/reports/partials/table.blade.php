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

<div class="ui-table-responsive">
    <table class="ui-table report-table tw:align-middle! tw:mb-0!">
        <thead>
            <tr>
                @foreach ($table['headings'] as $index => $heading)
                    <th class="{{ in_array($table['columns'][$index], $money, true) ? 'tw:text-right!' : '' }}">{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $row)
                <tr>
                    @foreach ($table['columns'] as $column)
                        @php $value = data_get($row, $column); @endphp
                        @if (in_array($column, $money, true))
                            <td class="tw:text-right! amount">{{ number_format((float) $value, 2) }}</td>
                        @else
                            <td>{{ $value ?? '-' }}</td>
                        @endif
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($table['columns']) }}" class="tw:text-center! tw:text-(color:--nx-muted)! tw:py-12!">
                        <i class="bi bi-inbox tw:text-[length:calc(1.325rem_+_0.9vw)]! tw:xl:text-[length:2rem]! tw:block! tw:mb-2!"></i>
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
                            <td class="tw:font-bold!">{{ $isPaginated ? 'Page total' : 'Total' }}</td>
                        @elseif (in_array($column, $money, true) && !in_array($column, $noTotal, true))
                            <td class="tw:text-right! amount tw:font-bold!">{{ number_format($items->sum($column), 2) }}</td>
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
    <div class="tw:flex! tw:justify-between! tw:items-center! tw:flex-wrap! tw:gap-2! tw:mt-4!">
        <small class="tw:text-(color:--nx-muted)!">
            Showing {{ $rows->firstItem() }}-{{ $rows->lastItem() }} of {{ $rows->total() }} records
        </small>
        {{ $rows->links() }}
    </div>
@elseif ($isPaginated)
    <small class="tw:text-(color:--nx-muted)! tw:block! tw:mt-4!">{{ $rows->total() }} records</small>
@endif

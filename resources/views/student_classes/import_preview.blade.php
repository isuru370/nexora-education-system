@extends('layouts.app')

@section('title', 'Import Preview')
@section('page-title', 'Import Preview')

@section('content')
@php
    $s = $result['summary'];
    $badge = [
        'new' => ['nx-badge-green', 'New'],
        'updated' => ['nx-badge-amber', 'Update'],
        'unchanged' => ['nx-badge-gray', 'No change'],
        'error' => ['nx-badge-red', 'Error'],
    ];
    $tiles = [
        ['New classes', $s['new_classes'], 'icon-green', 'bi-plus-circle-fill'],
        ['Classes updated', $s['updated_classes'], 'icon-amber', 'bi-pencil-fill'],
        ['New fee options', $s['new_options'] + $s['new_categories'], 'icon-blue', 'bi-tags-fill'],
        ['Fee options updated', $s['updated_options'], 'icon-amber', 'bi-cash-coin'],
        ['Errors', $s['errors'], 'icon-red', 'bi-exclamation-triangle-fill'],
    ];
@endphp
<div class="dashboard-page">

    <div class="hero-section tw:mb-6!">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">{{ $fileName }}</span>
                <h1 class="hero-title">Check before saving</h1>
                <p class="hero-subtitle">
                    @if ($result['errors'])
                        {{ $result['errors'] }} row(s) have errors. Fix them in the file and upload it again. Nothing was saved.
                    @else
                        No errors. Nothing is saved until you press Confirm.
                    @endif
                </p>
            </div>
        </div>
    </div>

    <div class="stats-grid tw:mb-6!">
        @foreach ($tiles as [$label, $value, $tone, $icon])
            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper {{ $tone }}"><i class="bi {{ $icon }}"></i></div>
                <div class="stat-info">
                    <span class="stat-label">{{ $label }}</span>
                    <h2 class="stat-number">{{ $value }}</h2>
                </div>
            </div>
        @endforeach
    </div>

    <div class="nx-card tw:mb-6!">
        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-list-ol tw:text-(color:--nx-blue)!"></i> Rows</h5>
                <p class="nx-card-subtitle">Row numbers are the lines in your file.</p>
            </div>
            <div class="header-buttons">
                <a href="{{ route('class-import.index') }}" class="nx-btn nx-btn-light"><i class="bi bi-arrow-left"></i> Upload another file</a>
                @if ($token)
                    <form method="POST" action="{{ route('class-import.apply') }}" class="tw:[display:inline]!" onsubmit="this.querySelector('button').disabled = true;">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <button class="nx-btn nx-btn-primary"><i class="bi bi-check2-circle"></i> Confirm and save</button>
                    </form>
                @endif
            </div>
        </div>
        <div class="ui-table-responsive">
            <table class="ui-table premium-table tw:mb-0!">
                <thead>
                    <tr>
                        <th>Row</th>
                        <th>Class</th>
                        <th>Category / option</th>
                        <th class="tw:text-right!">Fee</th>
                        <th>Status</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($result['rows'] as $line => $r)
                        @php [$cls, $label] = $badge[$r['status']] ?? $badge['unchanged']; @endphp
                        <tr class="{{ $r['status'] === 'error' ? 'ui-table-danger' : '' }}">
                            <td class="row-number">{{ $line }}</td>
                            <td>
                                <div class="cell-title">{{ $r['row']['class_name'] ?: '-' }}</div>
                                <small class="cell-sub">{{ $r['row']['branch'] }} · {{ $r['row']['teacher_id'] }}</small>
                            </td>
                            <td>
                                <div class="cell-title">{{ $r['row']['category'] ?: '-' }}</div>
                                <small class="cell-sub">{{ $r['row']['option_label'] }}</small>
                            </td>
                            <td class="tw:text-right!">{{ $r['row']['fee'] !== '' ? number_format((float) str_replace(',', '', $r['row']['fee']), 2) : '' }}</td>
                            <td><span class="nx-badge {{ $cls }}">{{ $label }}</span></td>
                            <td class="tw:text-[.875em]">
                                @foreach ($r['changes'] as $change)
                                    <div class="{{ $r['status'] === 'error' ? 'tw:text-(color:--nx-red)! tw:font-semibold!' : '' }}">{{ $change }}</div>
                                @endforeach
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

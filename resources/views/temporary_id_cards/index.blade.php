@extends('layouts.app')

@section('title', 'Temporary ID Cards')
@section('page-title', 'Temporary ID Cards')

@push('styles')
    <style>
        .tmp-page {
            animation: fadeIn .35s ease;
        }

        .hero-card,
        .main-card,
        .stat-card {
            background: #fff;
            border-radius: 28px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .05);
            border: 1px solid #eef2f7;
            overflow: hidden;
        }

        .hero-card {
            padding: 1.35rem 1.5rem;
            margin-bottom: 1.5rem;
        }

        .hero-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .hero-actions {
            display: flex;
            gap: .7rem;
            flex-wrap: wrap;
        }

        .main-card {
            padding: 1.5rem;
        }

        .stat-card {
            padding: 1.1rem 1.2rem;
            height: 100%;
        }

        .stat-label {
            color: #64748b;
            font-size: .85rem;
            font-weight: 600;
            margin-bottom: .35rem;
        }

        .stat-value {
            font-size: 1.45rem;
            font-weight: 800;
            line-height: 1.1;
        }

        .filter-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 22px;
            padding: 1.1rem;
            margin-bottom: 1.25rem;
        }

        .custom-input,
        .ui-form-select,
        .ui-form-control {
            border-radius: 14px;
            min-height: 48px;
            border: 1px solid #e2e8f0;
            box-shadow: none;
        }

        .custom-input:focus,
        .ui-form-select:focus,
        .ui-form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, .10);
        }

        .custom-btn {
            border-radius: 14px;
            padding: .7rem 1.15rem;
            font-weight: 600;
            border: none;
            transition: .2s ease;
        }

        .custom-btn:hover {
            transform: translateY(-2px);
        }

        .ui-btn-primary {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
        }

        .ui-btn-success {
            background: linear-gradient(135deg, #16a34a, #22c55e);
        }

        .ui-btn-danger {
            background: linear-gradient(135deg, #dc2626, #ef4444);
        }

        .ui-btn-dark {
            background: linear-gradient(135deg, #0f172a, #1e293b);
        }

        .ui-table thead th {
            background: #f8fafc;
            color: #334155;
            font-size: .82rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }

        .ui-table tbody td {
            vertical-align: middle;
        }

        .badge-status {
            padding: .45rem .75rem;
            border-radius: 999px;
            font-weight: 700;
            font-size: .78rem;
        }

        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-downloaded {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .badge-active {
            background: #dcfce7;
            color: #166534;
        }

        .badge-expired {
            background: #fee2e2;
            color: #991b1b;
        }

        .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
        }

        .action-btn {
            border-radius: 12px;
            padding: .45rem .7rem;
            font-weight: 600;
        }

        .empty-state {
            padding: 3rem 1rem;
            text-align: center;
            color: #64748b;
        }

        .empty-state .icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #eff6ff;
            color: #2563eb;
            margin-bottom: 1rem;
            font-size: 1.75rem;
        }

        @media (max-width: 768px) {
            .hero-content {
                flex-direction: column;
                align-items: stretch;
            }

            .hero-actions {
                width: 100%;
            }

            .hero-actions .ui-btn {
                flex: 1;
            }
        }
    </style>
@endpush

@section('content')
    <div class="tmp-page">

        {{-- =========================================================
            HERO CARD
        ========================================================== --}}
        <div class="hero-card">
            <div class="hero-content">
                <div>
                    <h4 class="tw:mb-1! tw:font-bold!">Temporary ID Cards</h4>
                    <p class="tw:mb-0! tw:text-bs-secondary-color!">Manage temporary ID card numbers, status, preview, and PDF export.</p>
                </div>

                <div class="hero-actions">
                    
                        @cardaccess('generate')
                        <a href="{{ route('temporary_id_cards.create') }}" class="ui-btn ui-btn-primary custom-btn">
                            <i class="bi bi-plus-circle tw:mr-1!"></i> Generate New
                        </a>
                        @endcardaccess

                        @cardaccess('download')
                        <a href="{{ route('temporary_id_cards.pdf') }}" class="ui-btn ui-btn-danger custom-btn">
                            <i class="bi bi-file-pdf tw:mr-1!"></i> Download PDF
                        </a>

                        <a href="{{ route('temporary_id_cards.preview') }}" class="ui-btn ui-btn-success custom-btn">
                            <i class="bi bi-eye tw:mr-1!"></i> Preview
                        </a>
                        @endcardaccess

                        @cardaccess('manage')
                        <a href="{{ route('temporary_id_cards.update-status') }}" class="ui-btn ui-btn-dark custom-btn">
                            <i class="bi bi-arrow-repeat tw:mr-1!"></i> Change Status
                        </a>
                        @endcardaccess
                   

                    <a href="{{ route('temporary_id_cards.index') }}" class="ui-btn ui-btn-secondary custom-btn">
                        <i class="bi bi-arrow-clockwise tw:mr-1!"></i> Refresh
                    </a>
                </div>
            </div>
        </div>

        {{-- =========================================================
            ALERTS
        ========================================================== --}}
        @if (session('success'))
            <div class="ui-alert ui-alert-success tw:[border:0]! tw:shadow-bs-sm! tw:rounded-bs-xl!">
                <i class="bi bi-check-circle-fill tw:mr-2!"></i>
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="ui-alert ui-alert-danger tw:[border:0]! tw:shadow-bs-sm! tw:rounded-bs-xl!">
                <i class="bi bi-exclamation-triangle-fill tw:mr-2!"></i>
                {{ session('error') }}
            </div>
        @endif

        {{-- =========================================================
            STATS
        ========================================================== --}}
        <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2 tw:mb-6!">
            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[50%] tw:md:flex-[0_0_auto] tw:md:w-[16.66666667%]">
                <div class="stat-card">
                    <div class="stat-label">Total Cards</div>
                    <div class="stat-value">{{ number_format($counts->total_count ?? 0) }}</div>
                </div>
            </div>

            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[50%] tw:md:flex-[0_0_auto] tw:md:w-[16.66666667%]">
                <div class="stat-card">
                    <div class="stat-label">Pending</div>
                    <div class="stat-value">{{ number_format($counts->pending_count ?? 0) }}</div>
                </div>
            </div>

            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[50%] tw:md:flex-[0_0_auto] tw:md:w-[16.66666667%]">
                <div class="stat-card">
                    <div class="stat-label">Downloaded</div>
                    <div class="stat-value">{{ number_format($counts->downloaded_count ?? 0) }}</div>
                </div>
            </div>

            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[50%] tw:md:flex-[0_0_auto] tw:md:w-[16.66666667%]">
                <div class="stat-card">
                    <div class="stat-label">Issued</div>
                    <div class="stat-value">{{ number_format($counts->issued_count ?? 0) }}</div>
                </div>
            </div>

            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[50%] tw:md:flex-[0_0_auto] tw:md:w-[16.66666667%]">
                <div class="stat-card">
                    <div class="stat-label">Active</div>
                    <div class="stat-value">{{ number_format($counts->active_count ?? 0) }}</div>
                </div>
            </div>

            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[50%] tw:md:flex-[0_0_auto] tw:md:w-[16.66666667%]">
                <div class="stat-card">
                    <div class="stat-label">Expired</div>
                    <div class="stat-value">{{ number_format($counts->expired_count ?? 0) }}</div>
                </div>
            </div>
        </div>

        {{-- =========================================================
            MAIN CARD
        ========================================================== --}}
        <div class="main-card">

            {{-- FILTER --}}
            <div class="filter-card">
                <form method="GET" action="{{ route('temporary_id_cards.index') }}">
                    <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2 tw:items-end!">
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[41.66666667%]">
                            <label class="ui-form-label tw:font-semibold!">Search</label>
                            <input type="text" name="search" value="{{ $search ?? '' }}"
                                class="ui-form-control custom-input" placeholder="Search TMP number or card number">
                        </div>

                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[25%]">
                            <label class="ui-form-label tw:font-semibold!">Status</label>
                            <select name="status" class="ui-form-select custom-input">
                                <option value="">All Status</option>
                                <option value="pending" {{ (($status ?? '') === 'pending') ? 'selected' : '' }}>Pending</option>
                                <option value="downloaded" {{ (($status ?? '') === 'downloaded') ? 'selected' : '' }}>Downloaded</option>
                                <option value="issued" {{ (($status ?? '') === 'issued') ? 'selected' : '' }}>Issued</option>
                                <option value="active" {{ (($status ?? '') === 'active') ? 'selected' : '' }}>Active</option>
                                <option value="expired" {{ (($status ?? '') === 'expired') ? 'selected' : '' }}>Expired</option>
                            </select>
                        </div>

                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%] tw:flex! tw:gap-2!">
                            <button type="submit" class="ui-btn ui-btn-primary custom-btn tw:grow!">
                                <i class="bi bi-search tw:mr-1!"></i> Search
                            </button>

                            <a href="{{ route('temporary_id_cards.index') }}"
                                class="ui-btn ui-btn-light tw:bs-border! custom-btn">
                                <i class="bi bi-x-circle tw:mr-1!"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            {{-- TABLE --}}
            <div class="ui-table-responsive">
                <table class="ui-table ui-table-hover tw:align-middle! tw:mb-0!">
                    <thead>
                        <tr>
                            <th style="width: 80px;">#</th>
                            <th>Temporary ID Number</th>
                            <th>Card Number</th>
                            <th>Status</th>
                            <th>Issue Date</th>
                            <th>Updated</th>
                            <th style="width: 240px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($temporaryIdCards as $card)
                            <tr>
                                <td>
                                    {{ $loop->iteration + ($temporaryIdCards->currentPage() - 1) * $temporaryIdCards->perPage() }}
                                </td>

                                <td class="mono tw:font-semibold!">
                                    {{ $card->temporary_id_number }}
                                </td>

                                <td class="mono">
                                    {{ $card->card_number }}
                                </td>

                                <td>
                                    @if ($card->status === 'pending')
                                        <span class="badge-status badge-pending">
                                            <i class="bi bi-hourglass-split tw:mr-1!"></i> Pending
                                        </span>
                                    @elseif($card->status === 'downloaded')
                                        <span class="badge-status badge-downloaded">
                                            <i class="bi bi-download tw:mr-1!"></i> Downloaded
                                        </span>
                                    @elseif($card->status === 'issued')
                                        <span class="badge-status badge-active">
                                            <i class="bi bi-send-check tw:mr-1!"></i> Issued
                                        </span>
                                    @elseif($card->status === 'active')
                                        <span class="badge-status badge-active">
                                            <i class="bi bi-check-circle tw:mr-1!"></i> Active
                                        </span>
                                    @elseif($card->status === 'expired')
                                        <span class="badge-status badge-expired">
                                            <i class="bi bi-x-circle tw:mr-1!"></i> Expired
                                        </span>
                                    @else
                                        <span class="badge-status badge-expired">
                                            {{ ucfirst($card->status) }}
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    {{ optional($card->created_at)->format('Y-m-d H:i') ?? '-' }}
                                </td>

                                <td>
                                    {{ optional($card->updated_at)->format('Y-m-d H:i') ?? '-' }}
                                </td>

                                <td>
                                    <div class="tw:flex! tw:flex-wrap! tw:gap-2!">

                                        <button type="button" class="ui-btn ui-btn-outline-primary ui-btn-sm action-btn"
                                            data-nx-toggle="modal" data-nx-target="#tmpPreviewModal{{ $card->id }}">
                                            <i class="bi bi-eye tw:mr-1!"></i> Preview
                                        </button>

                                    </div>

                                    {{-- Preview Modal --}}
                                    @include('temporary_id_cards.partials.preview-modal', [
                                        'card' => $card,
                                    ])
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <div class="icon">
                                            <i class="bi bi-credit-card-2-front"></i>
                                        </div>
                                        <h5 class="tw:font-bold! tw:mb-1!">No temporary ID cards found</h5>
                                        <p class="tw:mb-0!">Try adjusting your search or generate a new set of cards.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            <div class="tw:flex! tw:justify-between! tw:items-center! tw:flex-wrap! tw:gap-4! tw:mt-6!">
                <div class="tw:text-bs-secondary-color! tw:text-[.875em]">
                    Showing {{ $temporaryIdCards->firstItem() ?? 0 }} to {{ $temporaryIdCards->lastItem() ?? 0 }}
                    of {{ $temporaryIdCards->total() }} records
                </div>

                <div>
                    {{ $temporaryIdCards->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
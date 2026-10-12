@php
    $user = auth()->user();
    $showBulkActions = ($user && $user->email === 'admin@nexorait.lk');
@endphp

@if($showBulkActions)
    <div class="bulk-actions mb-4" id="bulkActionsBar">

        <div class="row align-items-center g-3">

            {{-- ==================== LEFT SIDE - COUNTER ==================== --}}
            <div class="col-lg-6 col-md-12">
                <div class="bulk-counter-wrap">
                    <div class="bulk-counter-icon">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div class="bulk-counter-text">
                        <h5 class="mb-0 fw-bold">
                            <span id="selectedCount">0</span> students selected
                        </h5>
                        <small class="text-muted d-block" id="selectedInfo">
                            Select cards to download in bulk
                        </small>
                    </div>
                </div>
            </div>

            {{-- ==================== RIGHT SIDE - BUTTONS ==================== --}}
            <div class="col-lg-6 col-md-12">
                <div class="bulk-btn-wrap">

                    <button type="button"
                            class="btn btn-outline-primary bulk-btn"
                            id="selectAllBtn">
                        <i class="fas fa-check-double me-1"></i>
                        Select All Pending
                    </button>

                    <button type="button"
                            class="btn btn-outline-secondary bulk-btn"
                            id="deselectAllBtn">
                        <i class="fas fa-times me-1"></i>
                        Deselect All
                    </button>

                    <button type="button"
                            class="btn btn-success bulk-btn bulk-download-btn"
                            id="bulkDownloadBtn"
                            disabled>
                        <i class="fas fa-download me-1"></i>
                        Download (<span id="downloadCount">0</span>)
                    </button>

                </div>
            </div>

        </div>

    </div>
@else
    {{-- ==================== INFO MESSAGE FOR NON-ADMIN ==================== --}}
    <div class="alert alert-info border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center">
        <i class="fas fa-info-circle me-2 fs-5"></i>
        <span>Bulk download actions are available for administrators only.</span>
    </div>
@endif

@push('styles')
<style>
    /* ==================================================
       BULK ACTIONS BAR
    ================================================== */
    .bulk-actions {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        padding: 1.25rem 1.5rem;
        border-radius: 22px;
        border: 1px solid #edf2f7;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
        transition: all 0.25s ease;
    }

    .bulk-actions:hover {
        box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
    }

    /* ==================================================
       COUNTER - LEFT SIDE
    ================================================== */
    .bulk-counter-wrap {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .bulk-counter-icon {
        width: 52px;
        height: 52px;
        min-width: 52px;
        border-radius: 14px;
        background: linear-gradient(135deg, #0f4bb5, #3b82f6);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        box-shadow: 0 4px 12px rgba(15, 75, 181, 0.25);
        transition: transform 0.25s ease;
    }

    .bulk-actions:hover .bulk-counter-icon {
        transform: scale(1.05);
    }

    .bulk-counter-text {
        flex: 1;
        min-width: 0;
    }

    .bulk-counter-text h5 {
        font-size: 1.05rem;
        color: #0f172a;
        line-height: 1.2;
    }

    #selectedCount,
    #downloadCount {
        color: #0f4bb5;
        font-weight: 800;
    }

    .bulk-counter-text small {
        font-size: 0.82rem;
        color: #64748b !important;
        margin-top: 0.15rem;
    }

    /* ==================================================
       BUTTONS - RIGHT SIDE
    ================================================== */
    .bulk-btn-wrap {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .bulk-btn {
        border-radius: 14px;
        padding: 0.65rem 1.1rem;
        font-weight: 700;
        font-size: 0.85rem;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        white-space: nowrap;
    }

    .bulk-btn:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.1);
    }

    .bulk-btn:disabled {
        opacity: 0.55;
        cursor: not-allowed;
        transform: none !important;
        box-shadow: none !important;
    }

    /* Success Button - Custom */
    .bulk-download-btn {
        background: linear-gradient(135deg, #16a34a, #22c55e);
        border: none;
        color: #fff;
    }

    .bulk-download-btn:hover:not(:disabled) {
        background: linear-gradient(135deg, #15803d, #16a34a);
        box-shadow: 0 6px 20px rgba(22, 163, 74, 0.3);
    }

    /* ==================================================
       INFO ALERT (Non-Admin)
    ================================================== */
    .alert-info {
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
        color: #1e40af;
        font-weight: 500;
    }

    /* ==================================================
       RESPONSIVE - Tablet
    ================================================== */
    @media (max-width: 992px) {
        .bulk-btn-wrap {
            justify-content: flex-start;
        }
    }

    /* ==================================================
       RESPONSIVE - Mobile
    ================================================== */
    @media (max-width: 768px) {
        .bulk-actions {
            padding: 1rem;
            border-radius: 18px;
        }

        .bulk-counter-icon {
            width: 44px;
            height: 44px;
            min-width: 44px;
            font-size: 1.1rem;
            border-radius: 12px;
        }

        .bulk-counter-text h5 {
            font-size: 0.95rem;
        }

        .bulk-counter-text small {
            font-size: 0.75rem;
        }

        .bulk-btn-wrap {
            flex-direction: column;
            width: 100%;
            gap: 0.5rem;
        }

        .bulk-btn {
            width: 100%;
            justify-content: center;
            padding: 0.7rem 1rem;
            font-size: 0.85rem;
        }
    }

    @media (max-width: 480px) {
        .bulk-actions {
            padding: 0.85rem;
            border-radius: 16px;
        }

        .bulk-counter-icon {
            width: 40px;
            height: 40px;
            min-width: 40px;
            font-size: 1rem;
        }

        .bulk-counter-text h5 {
            font-size: 0.88rem;
        }

        .bulk-counter-text small {
            font-size: 0.7rem;
        }

        .bulk-btn {
            font-size: 0.78rem;
            padding: 0.6rem 0.9rem;
            border-radius: 12px;
        }
    }
</style>
@endpush
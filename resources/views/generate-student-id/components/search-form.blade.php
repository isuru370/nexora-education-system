@php
    $searchValue = request('search', '');
    $hasSearch   = !empty($searchValue);
    $totalStudents = $totalStudents ?? null;
@endphp

<div class="premium-card search-card mb-4">

    <form method="GET"
          action="{{ url()->current() }}"
          class="search-form"
          id="searchForm">

        <div class="row g-3 align-items-end">

            {{-- ==================== SEARCH INPUT ==================== --}}
            <div class="col-lg-9 col-md-8">

                <label for="search" class="form-label fw-semibold search-label">
                    <i class="fas fa-search me-1"></i>
                    Search Students
                    @if($hasSearch)
                        <span class="search-active-badge">
                            <i class="fas fa-filter"></i> Filtered
                        </span>
                    @endif
                </label>

                <div class="search-input-wrapper">
                    <i class="fas fa-search search-input-icon"></i>

                    <input type="text"
                           class="form-control custom-input search-input {{ $hasSearch ? 'has-value' : '' }}"
                           id="search"
                           name="search"
                           value="{{ $searchValue }}"
                           placeholder="Search by student ID or name..."
                           autocomplete="off">

                    @if($hasSearch)
                        <button type="button"
                                class="search-clear-btn"
                                onclick="clearSearch()"
                                title="Clear search">
                            <i class="fas fa-times"></i>
                        </button>
                    @endif
                </div>

                @if($hasSearch)
                    <small class="text-muted d-block mt-2 search-hint">
                        <i class="fas fa-info-circle me-1"></i>
                        Searching for: <strong>"{{ $searchValue }}"</strong>
                    </small>
                @endif

            </div>

            {{-- ==================== ACTION BUTTONS ==================== --}}
            <div class="col-lg-3 col-md-4">
                <div class="d-flex gap-2">

                    <button type="submit"
                            class="btn btn-primary custom-btn flex-fill search-btn">
                        <i class="fas fa-search me-1"></i>
                        Search
                    </button>

                    @if($hasSearch)
                        <a href="{{ url()->current() }}"
                           class="btn btn-outline-secondary custom-btn search-reset-btn"
                           title="Reset search">
                            <i class="fas fa-redo"></i>
                        </a>
                    @endif

                </div>
            </div>

        </div>

    </form>

</div>

@push('scripts')
<script>
    /**
     * Clear search input and submit form
     */
    function clearSearch() {
        const input = document.getElementById('search');
        if (input) {
            input.value = '';
            input.focus();
            document.getElementById('searchForm')?.submit();
        }
    }

    /**
     * Press 'Escape' key to clear search
     */
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('search');

        if (searchInput) {
            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && this.value.length > 0) {
                    this.value = '';
                    this.form?.submit();
                }
            });

            // Auto-focus on empty search (page load)
            if (!this.value && window.location.search.includes('search=')) {
                searchInput.focus();
            }
        }
    });
</script>
@endpush

@push('styles')
<style>
    /* ==================================================
       SEARCH CARD
    ================================================== */
    .search-card {
        padding: 1.5rem;
        background: linear-gradient(135deg, #ffffff 0%, #fbfdff 100%);
    }

    /* ==================================================
       SEARCH LABEL
    ================================================== */
    .search-label {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem;
        color: #0f172a;
        font-size: 0.92rem;
        margin-bottom: 0.55rem;
    }

    .search-label > i {
        color: #0f4bb5;
    }

    /* Filter Badge */
    .search-active-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 0.2rem 0.6rem;
        border-radius: 999px;
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
        color: #1e40af;
        border: 1px solid #bfdbfe;
        animation: badgePulse 0.4s ease;
    }

    @keyframes badgePulse {
        0% { transform: scale(0.9); opacity: 0; }
        100% { transform: scale(1); opacity: 1; }
    }

    /* ==================================================
       SEARCH INPUT WRAPPER
    ================================================== */
    .search-input-wrapper {
        position: relative;
    }

    /* Search Icon (Left) */
    .search-input-icon {
        position: absolute;
        left: 18px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 0.95rem;
        pointer-events: none;
        transition: color 0.2s ease;
        z-index: 3;
    }

    .search-input-wrapper:focus-within .search-input-icon {
        color: #0f4bb5;
    }

    /* Search Input */
    .search-input {
        padding-left: 48px !important;
        padding-right: 48px !important;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .search-input.has-value {
        border-color: #bfdbfe;
        background: #f8fbff;
    }

    /* Clear Button (Right) */
    .search-clear-btn {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: #f1f5f9;
        color: #64748b;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 0.8rem;
        z-index: 3;
    }

    .search-clear-btn:hover {
        background: #fee2e2;
        color: #dc2626;
        transform: translateY(-50%) scale(1.1);
    }

    .search-clear-btn:active {
        transform: translateY(-50%) scale(0.95);
    }

    /* Search Hint */
    .search-hint {
        font-size: 0.78rem;
        color: #64748b !important;
        animation: hintFadeIn 0.3s ease;
    }

    .search-hint strong {
        color: #0f4bb5;
    }

    @keyframes hintFadeIn {
        from { opacity: 0; transform: translateY(-4px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* ==================================================
       SEARCH BUTTONS
    ================================================== */
    .search-btn {
        height: 52px;
        border-radius: 14px;
        font-weight: 700;
        background: linear-gradient(135deg, #0f4bb5, #3b82f6);
        border: none;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(15, 75, 181, 0.15);
    }

    .search-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(15, 75, 181, 0.28);
        background: linear-gradient(135deg, #0d3f99, #2563eb);
    }

    .search-reset-btn {
        height: 52px;
        width: 52px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        border: 2px solid #e2e8f0;
        color: #64748b;
        font-weight: 700;
        transition: all 0.2s ease;
        background: #fff;
    }

    .search-reset-btn:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #0f4bb5;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
    }

    /* ==================================================
       RESPONSIVE
    ================================================== */
    @media (max-width: 768px) {
        .search-card {
            padding: 1rem;
        }

        .search-label {
            font-size: 0.85rem;
        }

        .search-input,
        .search-btn,
        .search-reset-btn {
            height: 48px;
            font-size: 0.9rem;
        }

        .search-reset-btn {
            width: 48px;
        }

        .search-active-badge {
            font-size: 0.68rem;
        }
    }

    @media (max-width: 480px) {
        .search-card {
            padding: 0.85rem;
        }

        .search-input {
            padding-left: 42px !important;
            padding-right: 42px !important;
            font-size: 0.85rem;
        }

        .search-input-icon {
            left: 14px;
            font-size: 0.85rem;
        }

        .search-clear-btn {
            width: 26px;
            height: 26px;
            right: 10px;
            font-size: 0.72rem;
        }

        .search-btn,
        .search-reset-btn {
            height: 44px;
            font-size: 0.82rem;
            border-radius: 12px;
        }

        .search-reset-btn {
            width: 44px;
        }

        .search-hint {
            font-size: 0.72rem;
        }
    }
</style>
@endpush
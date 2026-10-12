@if($students->hasPages())
    <div class="pagination-wrapper mt-4">

        {{-- ==================== RESULTS INFO ==================== --}}
        <div class="pagination-info">
            <div class="pagination-info-icon">
                <i class="fas fa-list-ol"></i>
            </div>
            <div class="pagination-info-text">
                Showing
                <strong>{{ $students->firstItem() }}</strong>
                to
                <strong>{{ $students->lastItem() }}</strong>
                of
                <strong>{{ $students->total() }}</strong>
                students
            </div>
        </div>

        {{-- ==================== PAGINATION LINKS ==================== --}}
        <nav aria-label="Student ID Cards Pagination" class="pagination-nav">
            {{ $students->appends(request()->query())->links('pagination::bootstrap-5') }}
        </nav>

    </div>
@endif

@push('styles')
<style>
    /* ==================================================
       PAGINATION WRAPPER
    ================================================== */
    .pagination-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        padding: 1.25rem 1.5rem;
        background: #fff;
        border-radius: 20px;
        border: 1px solid #edf2f7;
        box-shadow: 0 6px 20px rgba(15, 23, 42, 0.04);
    }

    /* ==================================================
       RESULTS INFO
    ================================================== */
    .pagination-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: #64748b;
        font-size: 0.88rem;
        font-weight: 500;
    }

    .pagination-info-icon {
        width: 38px;
        height: 38px;
        min-width: 38px;
        border-radius: 10px;
        background: rgba(15, 75, 181, 0.08);
        color: #0f4bb5;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
    }

    .pagination-info-text {
        line-height: 1.4;
    }

    .pagination-info-text strong {
        color: #0f172a;
        font-weight: 800;
    }

    /* ==================================================
       PAGINATION LINKS
    ================================================== */
    .pagination-nav {
        margin: 0;
    }

    .pagination {
        margin: 0;
        gap: 0.35rem;
        flex-wrap: wrap;
    }

    .pagination .page-item {
        margin: 0;
    }

    .pagination .page-link {
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 42px;
        height: 42px;
        padding: 0.5rem 0.85rem;
        border: 1px solid #e2e8f0;
        border-radius: 12px !important;
        color: #475569;
        font-weight: 700;
        font-size: 0.88rem;
        background: #fff;
        transition: all 0.2s ease;
        box-shadow: none;
    }

    .pagination .page-link:hover:not(.disabled) {
        background: #f1f5f9;
        color: #0f4bb5;
        border-color: #cbd5e1;
        transform: translateY(-1px);
    }

    .pagination .page-link:focus {
        box-shadow: 0 0 0 4px rgba(15, 75, 181, 0.1);
        outline: none;
    }

    /* Active Page */
    .pagination .page-item.active .page-link {
        background: linear-gradient(135deg, #0f4bb5, #3b82f6);
        color: #fff;
        border-color: #0f4bb5;
        box-shadow: 0 4px 14px rgba(15, 75, 181, 0.3);
        transform: translateY(-1px);
    }

    .pagination .page-item.active .page-link:hover {
        background: linear-gradient(135deg, #0d3f99, #2563eb);
        color: #fff;
        border-color: #0d3f99;
    }

    /* Disabled (Previous / Next) */
    .pagination .page-item.disabled .page-link {
        background: #f8fafc;
        color: #cbd5e1;
        border-color: #f1f5f9;
        cursor: not-allowed;
        opacity: 0.7;
    }

    .pagination .page-item.disabled .page-link:hover {
        transform: none;
        background: #f8fafc;
        color: #cbd5e1;
        border-color: #f1f5f9;
    }

    /* Previous / Next arrows */
    .pagination .page-item:first-child .page-link,
    .pagination .page-item:last-child .page-link {
        font-weight: 900;
    }

    /* ==================================================
       RESPONSIVE
    ================================================== */
    @media (max-width: 768px) {
        .pagination-wrapper {
            flex-direction: column;
            align-items: stretch;
            padding: 1rem;
            border-radius: 16px;
        }

        .pagination-info {
            justify-content: center;
            font-size: 0.82rem;
        }

        .pagination-nav {
            display: flex;
            justify-content: center;
        }

        .pagination .page-link {
            min-width: 38px;
            height: 38px;
            padding: 0.4rem 0.7rem;
            font-size: 0.82rem;
        }
    }

    @media (max-width: 480px) {
        .pagination-wrapper {
            padding: 0.85rem;
            border-radius: 14px;
        }

        .pagination-info-icon {
            width: 34px;
            height: 34px;
            min-width: 34px;
            font-size: 0.85rem;
        }

        .pagination-info-text {
            font-size: 0.75rem;
        }

        .pagination {
            gap: 0.25rem;
        }

        .pagination .page-link {
            min-width: 34px;
            height: 34px;
            padding: 0.3rem 0.55rem;
            font-size: 0.75rem;
            border-radius: 10px !important;
        }
    }
</style>
@endpush
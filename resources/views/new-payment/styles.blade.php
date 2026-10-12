<style>
/* =============================================================
   NEW PAYMENT PAGE — STYLES
============================================================= */

/* =========================================
   1. QR READER
========================================= */
#qr-reader {
    width: 100%;
    max-width: 360px;
    margin: 0 auto;
    min-height: 220px;
}

#qr-reader video {
    max-height: 230px;
    object-fit: cover;
    border-radius: 10px;
}

#qr-reader #qr-reader__scan_region {
    min-height: 180px;
}


/* =========================================
   2. SEARCH BOXES
========================================= */
.search-box {
    background: #fff;
    transition: border-color .2s ease, box-shadow .2s ease;
}

.search-box:hover {
    border-color: #cbd5e1 !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, .04);
}

kbd {
    background: #f1f5f9;
    color: #334155;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: .8em;
    border: 1px solid #cbd5e1;
    box-shadow: 0 1px 0 #cbd5e1;
    font-family: inherit;
}

.input-group-text {
    border-radius: 8px 0 0 8px !important;
    border-right: none;
}

#manual-code {
    border-left: none;
    border-right: none;
}

#manual-code:focus {
    border-color: #2563eb;
    box-shadow: none;
}

.input-group .btn {
    border-radius: 0 8px 8px 0 !important;
}


/* =========================================
   3. STUDENT INFO CARD
========================================= */
.student-avatar {
    object-fit: cover;
    flex-shrink: 0;
    transition: transform .2s ease;
}

.student-avatar:hover {
    transform: scale(1.05);
}

.student-contact-row {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.student-contact-row .contact-item {
    display: inline-flex;
    align-items: center;
    gap: .25rem;
}

.student-contact-row .contact-item i {
    color: #64748b;
    font-size: .85rem;
}


/* =========================================
   4. TABLE (Global)
========================================= */
.table td {
    vertical-align: middle;
}


/* =========================================
   5. PAYMENT STATUS BADGES
========================================= */
.payment-status-paid {
    color: #10b981;
    font-weight: 700;
    background: #ecfdf5;
    padding: 4px 14px;
    border-radius: 20px;
    display: inline-block;
    font-size: .75rem;
}

.payment-status-unpaid {
    color: #ef4444;
    font-weight: 700;
    background: #fef2f2;
    padding: 4px 14px;
    border-radius: 20px;
    display: inline-block;
    font-size: .75rem;
}


/* =========================================
   6. RECEIPT PAPER (58mm)
========================================= */
.receipt-paper {
    width: 58mm;
    margin: auto;
    padding: 4mm;
    background: #fff;
    color: #000;
    font-family: monospace;
    font-size: 11px;
    line-height: 1.45;
}

.receipt-line {
    border-top: 1px dashed #000;
    margin: 7px 0;
}

.receipt-summary {
    line-height: 1.6;
}

.receipt-item {
    line-height: 1.45;
    word-break: break-word;
}

.receipt-total {
    font-size: 14px;
    font-weight: 700;
}

.text-center {
    text-align: center;
}


/* =========================================
   7. MODALS
========================================= */
.modal-content {
    border-radius: 20px;
    border: none;
    box-shadow: 0 20px 60px rgba(0, 0, 0, .15);
}

.modal-header {
    padding: 1.25rem 1.5rem;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    padding: 1rem 1.5rem;
}

.receipt-modal-dialog {
    max-width: 420px;
    width: 100%;
}


/* =========================================
   8. CLASS PAYMENT CARD
========================================= */
.class-payment-card {
    height: 100%;
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 16px;
    padding: 18px;
    display: flex;
    flex-direction: column;
    transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
}

.class-payment-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, .08);
    border-color: #d9dee3;
}

.class-payment-card-header {
    padding-bottom: 15px;
    border-bottom: 1px solid #f0f1f3;
}

.class-payment-title {
    font-size: 15px;
    font-weight: 700;
    color: #212529;
    line-height: 1.3;
}

.class-payment-subject {
    font-size: 12px;
    color: #6c757d;
}

.class-payment-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    padding: 16px 0;
}

.class-payment-detail {
    min-width: 0;
}

.class-payment-detail-label {
    font-size: 11px;
    color: #8a9199;
    margin-bottom: 3px;
}

.class-payment-detail-value {
    font-size: 13px;
    font-weight: 600;
    color: #343a40;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.class-payment-fee {
    font-size: 14px;
    font-weight: 700;
}

.class-payment-card-footer {
    margin-top: auto;
    padding-top: 13px;
    border-top: 1px solid #f0f1f3;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.class-payment-card-footer .btn {
    min-width: 75px;
}

.class-payment-card .class-checkbox {
    width: 18px;
    height: 18px;
    cursor: pointer;
}


/* =========================================
   9. LAST PAYMENT BOX
========================================= */
.last-payment-box {
    background: #f8f9fa;
    border: 1px solid #eef0f2;
    border-radius: 12px;
    padding: 12px;
    margin-bottom: 15px;
}

.last-payment-header {
    margin-bottom: 8px;
}

.last-payment-label {
    font-size: 11px;
    font-weight: 600;
    color: #6c757d;
}

.last-payment-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.last-payment-content strong {
    font-size: 13px;
}

.last-payment-receipt {
    margin-top: 8px;
    padding-top: 7px;
    border-top: 1px dashed #dee2e6;
    font-size: 10px;
    color: #8a9199;
}

.last-payment-empty {
    opacity: .75;
}


/* =========================================
   10. EMPTY STATE
========================================= */
.classes-empty-state {
    min-height: 220px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 2rem 1rem;
}

.classes-empty-icon {
    width: 58px;
    height: 58px;
    border-radius: 14px;
    background: #f1f3f5;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
    font-size: 26px;
    color: #adb5bd;
}


/* =========================================
   11. RESPONSIVE — Tablet (≤ 767px)
========================================= */
@media (max-width: 767px) {
    .card-header .d-flex {
        flex-wrap: wrap;
    }

    .class-payment-details {
        grid-template-columns: 1fr 1fr;
    }
}


/* =========================================
   12. RESPONSIVE — Mobile (≤ 575px)
========================================= */
@media (max-width: 575px) {
    /* Class Payment Card */
    .class-payment-details {
        grid-template-columns: 1fr;
    }

    .class-payment-card {
        padding: 14px;
    }

    .class-payment-card-footer {
        flex-direction: column;
        align-items: stretch;
    }

    .class-payment-card-footer .btn {
        width: 100%;
    }

    /* QR Reader */
    #qr-reader {
        max-width: 100%;
    }

    /* Search Boxes */
    .search-box {
        padding: .75rem !important;
    }

    .input-group .btn span:not(.spinner-border) {
        display: none;
    }

    /* Student Avatar */
    .student-avatar {
        width: 70px !important;
        height: 70px !important;
    }

    .student-contact-row {
        flex-direction: column;
        gap: .4rem;
    }

    /* Modal */
    .receipt-modal-dialog {
        max-width: 100%;
        margin: .5rem;
    }

    #receiptModal .modal-body {
        padding: 1rem;
    }

    #receiptModal .modal-footer {
        padding: .75rem 1rem 1.25rem;
    }

    #paymentModal .modal-footer {
        flex-direction: column-reverse;
        gap: .5rem;
    }

    #paymentModal .modal-footer .btn {
        width: 100%;
    }

    #paymentModal .modal-body {
        padding: 1rem;
    }
}
</style>
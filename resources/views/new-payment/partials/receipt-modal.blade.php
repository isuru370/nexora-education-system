{{-- =============================================================
     Receipt Modal
============================================================= --}}
<div class="modal fade"
     id="receiptModal"
     tabindex="-1"
     aria-labelledby="receiptModalLabel"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered receipt-modal-dialog">

        <div class="modal-content">

            {{-- =========================================================
                 Header
            ========================================================== --}}
            <div class="modal-header border-0">

                <div>
                    <h6 class="modal-title fw-bold text-success" id="receiptModalLabel">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        Payment Successful!
                    </h6>

                    <small class="text-muted">
                        Receipt generated and sent to printer
                    </small>
                </div>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"></button>

            </div>


            {{-- =========================================================
                 Body — Receipt Preview
            ========================================================== --}}
            <div class="modal-body">

                <div id="receipt-preview"></div>

            </div>


            {{-- =========================================================
                 Footer — Print Again
            ========================================================== --}}
            <div class="modal-footer border-0">

                <button type="button"
                        id="print-receipt-btn"
                        class="btn btn-primary w-100">
                    <i class="bi bi-printer me-1"></i>
                    Print Receipt Again
                </button>

            </div>

        </div>

    </div>

</div>
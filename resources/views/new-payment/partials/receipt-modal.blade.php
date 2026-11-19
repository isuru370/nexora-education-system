{{-- =============================================================
     Receipt Modal
============================================================= --}}
<div class="ui-modal ui-fade"
     id="receiptModal"
     tabindex="-1"
     aria-labelledby="receiptModalLabel"
     aria-hidden="true">

    <div class="ui-modal-dialog ui-modal-dialog-centered receipt-modal-dialog">

        <div class="ui-modal-content">

            {{-- =========================================================
                 Header
            ========================================================== --}}
            <div class="ui-modal-header tw:[border:0]!">

                <div>
                    <h6 class="ui-modal-title tw:font-bold! tw:text-bs-success!" id="receiptModalLabel">
                        <i class="bi bi-check-circle-fill tw:mr-2!"></i>
                        Payment Successful!
                    </h6>

                    <small class="tw:text-bs-secondary-color!">
                        Receipt generated and sent to printer
                    </small>
                </div>

                <button type="button"
                        class="ui-btn-close"
                        data-nx-dismiss="modal"
                        aria-label="Close"></button>

            </div>


            {{-- =========================================================
                 Body — Receipt Preview
            ========================================================== --}}
            <div class="ui-modal-body">

                <div id="receipt-preview"></div>

            </div>


            {{-- =========================================================
                 Footer — Print Again
            ========================================================== --}}
            <div class="ui-modal-footer tw:[border:0]!">

                <button type="button"
                        id="print-receipt-btn"
                        class="ui-btn ui-btn-primary tw:w-full!">
                    <i class="bi bi-printer tw:mr-1!"></i>
                    Print Receipt Again
                </button>

            </div>

        </div>

    </div>

</div>
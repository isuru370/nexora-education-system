{{-- =============================================================
     Payment Modal
============================================================= --}}
<div class="modal fade"
     id="paymentModal"
     tabindex="-1"
     aria-labelledby="paymentModalLabel"
     aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content">

            {{-- =========================================================
                 Header
            ========================================================== --}}
            <div class="modal-header border-0">

                <div>
                    <h5 class="modal-title fw-bold" id="paymentModalLabel">
                        <i class="bi bi-credit-card-2-front-fill me-2 text-primary"></i>
                        Make Payment
                    </h5>

                    <small class="text-muted">
                        Press <kbd>Enter</kbd> to confirm payment
                    </small>
                </div>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"></button>

            </div>


            {{-- =========================================================
                 Body
            ========================================================== --}}
            <div class="modal-body">

                <form id="payment-form">

                    {{-- Hidden Fields --}}
                    <input type="hidden" id="payment-enrollment-id" name="enrollment_id">
                    <input type="hidden" id="payment-code"          name="code">
                    <input type="hidden" id="payment-mark-method"   name="mark_method">


                    {{-- =================================================
                         Class / Category Info Box
                    ================================================== --}}
                    <div class="alert alert-light border mb-4">

                        <div class="row g-2">

                            <div class="col-md-6">
                                <small class="text-muted d-block">
                                    Class
                                </small>

                                <div id="modal-class-name" class="fw-semibold">
                                    -
                                </div>
                            </div>

                            <div class="col-md-6">
                                <small class="text-muted d-block">
                                    Category
                                </small>

                                <div id="modal-category-name" class="fw-semibold">
                                    -
                                </div>
                            </div>

                        </div>

                    </div>


                    {{-- =================================================
                         Form Fields
                    ================================================== --}}
                    <div class="row g-3">

                        {{-- Payment Month --}}
                        <div class="col-md-6">
                            <label for="payment-month" class="form-label fw-semibold">
                                Payment Month <span class="text-danger">*</span>
                            </label>

                            <input type="month"
                                   id="payment-month"
                                   name="payment_month"
                                   class="form-control"
                                   required>
                        </div>


                        {{-- Payment Method --}}
                        <div class="col-md-6">
                            <label for="payment-method" class="form-label fw-semibold">
                                Payment Method
                            </label>

                            <select id="payment-method" class="form-select">
                                <option value="cash">💵 Cash</option>
                                <option value="card">💳 Card</option>
                                <option value="bank_transfer">🏦 Bank Transfer</option>
                                <option value="online">🌐 Online</option>
                                <option value="cheque">📄 Cheque</option>
                                <option value="other">📌 Other</option>
                            </select>
                        </div>


                        {{-- Class Fee --}}
                        <div class="col-md-4">
                            <label for="payment-class-fee" class="form-label fw-semibold">
                                Class Fee
                            </label>

                            <input type="number"
                                   id="payment-class-fee"
                                   class="form-control bg-light"
                                   readonly>
                        </div>


                        {{-- Hall Fee --}}
                        <div class="col-md-4">
                            <label for="payment-hall-fee" class="form-label fw-semibold">
                                Hall Fee
                            </label>

                            <input type="number"
                                   id="payment-hall-fee"
                                   class="form-control bg-light"
                                   readonly>
                        </div>


                        {{-- Total Fee --}}
                        <div class="col-md-4">
                            <label for="payment-final-fee" class="form-label fw-semibold">
                                Total Fee
                            </label>

                            <input type="number"
                                   id="payment-final-fee"
                                   class="form-control bg-light fw-bold"
                                   readonly>
                        </div>


                        {{-- Discount --}}
                        <div class="col-md-4">
                            <label for="payment-discount" class="form-label fw-semibold">
                                Discount
                            </label>

                            <input type="number"
                                   id="payment-discount"
                                   class="form-control"
                                   value="0"
                                   min="0"
                                   step="0.01">
                        </div>


                        {{-- Amount --}}
                        <div class="col-md-4">
                            <label for="payment-amount" class="form-label fw-semibold">
                                Amount <span class="text-danger">*</span>
                            </label>

                            <input type="number"
                                   id="payment-amount"
                                   class="form-control"
                                   min="0"
                                   step="0.01"
                                   required>
                        </div>


                        {{-- Payment Rules: attendance this month, fee after the rules, tute --}}
                        <div class="col-12 d-none" id="payment-month-info">
                            <div class="border rounded p-2 bg-light small d-flex flex-wrap gap-3 align-items-center">
                                <span><i class="bi bi-calendar-check text-success"></i>
                                    Attendance: <strong id="pmi-attendance">-</strong></span>
                                <span id="pmi-rule" class="d-none"><i class="bi bi-percent text-primary"></i>
                                    <strong id="pmi-rule-text"></strong></span>
                                <span id="pmi-paid" class="d-none text-danger fw-semibold"><i class="bi bi-exclamation-triangle"></i>
                                    <span id="pmi-paid-text"></span></span>
                            </div>
                            <div id="pmi-tute" class="form-check mt-2 d-none">
                                <input class="form-check-input" type="checkbox" id="payment-give-tute">
                                <label class="form-check-label" for="payment-give-tute" id="pmi-tute-label">Give tute</label>
                            </div>
                        </div>


                        {{-- Note --}}
                        <div class="col-12">
                            <label for="payment-note" class="form-label fw-semibold">
                                Note
                            </label>

                            <textarea id="payment-note"
                                      class="form-control"
                                      rows="2"
                                      placeholder="Optional note"></textarea>
                        </div>

                    </div>

                </form>

            </div>


            {{-- =========================================================
                 Footer
            ========================================================== --}}
            <div class="modal-footer border-0">

                <button type="button"
                        class="btn btn-light border"
                        data-bs-dismiss="modal">
                    Cancel
                </button>

                <button type="button"
                        id="confirm-payment-btn"
                        class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i>
                    Confirm Payment
                </button>

            </div>

        </div>

    </div>

</div>
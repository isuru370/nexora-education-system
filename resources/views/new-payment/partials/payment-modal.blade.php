{{-- =============================================================
     Payment Modal
============================================================= --}}
<div class="ui-modal ui-fade"
     id="paymentModal"
     tabindex="-1"
     aria-labelledby="paymentModalLabel"
     aria-hidden="true">

    <div class="ui-modal-dialog ui-modal-lg ui-modal-dialog-centered">

        <div class="ui-modal-content">

            {{-- =========================================================
                 Header
            ========================================================== --}}
            <div class="ui-modal-header tw:[border:0]!">

                <div>
                    <h5 class="ui-modal-title tw:font-bold!" id="paymentModalLabel">
                        <i class="bi bi-credit-card-2-front-fill tw:mr-2! tw:text-bs-primary!"></i>
                        Make Payment
                    </h5>

                    <small class="tw:text-bs-secondary-color!">
                        Press <kbd>Enter</kbd> to confirm payment
                    </small>
                </div>

                <button type="button"
                        class="ui-btn-close"
                        data-nx-dismiss="modal"
                        aria-label="Close"></button>

            </div>


            {{-- =========================================================
                 Body
            ========================================================== --}}
            <div class="ui-modal-body">

                <form id="payment-form">

                    {{-- Hidden Fields --}}
                    <input type="hidden" id="payment-enrollment-id" name="enrollment_id">
                    <input type="hidden" id="payment-code"          name="code">
                    <input type="hidden" id="payment-mark-method"   name="mark_method">


                    {{-- =================================================
                         Class / Category Info Box
                    ================================================== --}}
                    <div class="ui-alert ui-alert-light tw:bs-border! tw:mb-6!">

                        <div class="tw:flex tw:flex-wrap tw:-mt-2 tw:-mx-1">

                            <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                                <small class="tw:text-bs-secondary-color! tw:block!">
                                    Class
                                </small>

                                <div id="modal-class-name" class="tw:font-semibold!">
                                    -
                                </div>
                            </div>

                            <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                                <small class="tw:text-bs-secondary-color! tw:block!">
                                    Category
                                </small>

                                <div id="modal-category-name" class="tw:font-semibold!">
                                    -
                                </div>
                            </div>

                        </div>

                    </div>


                    {{-- =================================================
                         Form Fields
                    ================================================== --}}
                    <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2">

                        {{-- Payment Month --}}
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                            <label for="payment-month" class="ui-form-label tw:font-semibold!">
                                Payment Month <span class="tw:text-bs-danger!">*</span>
                            </label>

                            <input type="month"
                                   id="payment-month"
                                   name="payment_month"
                                   class="ui-form-control"
                                   required>
                        </div>


                        {{-- Payment Method --}}
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                            <label for="payment-method" class="ui-form-label tw:font-semibold!">
                                Payment Method
                            </label>

                            <select id="payment-method" class="ui-form-select">
                                <option value="cash">💵 Cash</option>
                                <option value="card">💳 Card</option>
                                <option value="bank_transfer">🏦 Bank Transfer</option>
                                <option value="online">🌐 Online</option>
                                <option value="cheque">📄 Cheque</option>
                                <option value="other">📌 Other</option>
                            </select>
                        </div>


                        {{-- Class Fee --}}
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                            <label for="payment-class-fee" class="ui-form-label tw:font-semibold!">
                                Class Fee
                            </label>

                            <input type="number"
                                   id="payment-class-fee"
                                   class="ui-form-control tw:bg-bs-light!"
                                   readonly>
                        </div>


                        {{-- Hall Fee --}}
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                            <label for="payment-hall-fee" class="ui-form-label tw:font-semibold!">
                                Hall Fee
                            </label>

                            <input type="number"
                                   id="payment-hall-fee"
                                   class="ui-form-control tw:bg-bs-light!"
                                   readonly>
                        </div>


                        {{-- Total Fee --}}
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                            <label for="payment-final-fee" class="ui-form-label tw:font-semibold!">
                                Total Fee
                            </label>

                            <input type="number"
                                   id="payment-final-fee"
                                   class="ui-form-control tw:bg-bs-light! tw:font-bold!"
                                   readonly>
                        </div>


                        {{-- Discount --}}
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                            <label for="payment-discount" class="ui-form-label tw:font-semibold!">
                                Discount
                            </label>

                            <input type="number"
                                   id="payment-discount"
                                   class="ui-form-control"
                                   value="0"
                                   min="0"
                                   step="0.01">
                            {{-- Payment Rules > multi-class discount (filled by the script) --}}
                            <small id="payment-multi-class-note" class="tw:block! tw:mt-1! tw:text-(color:--nx-green)! tw:font-semibold! tw:hidden!"></small>
                        </div>


                        {{-- Amount --}}
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                            <label for="payment-amount" class="ui-form-label tw:font-semibold!">
                                Amount <span class="tw:text-bs-danger!">*</span>
                            </label>

                            <input type="number"
                                   id="payment-amount"
                                   class="ui-form-control"
                                   min="0"
                                   step="0.01"
                                   required>
                        </div>


                        {{-- Payment Rules: attendance this month, fee after the rules, tute --}}
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[100%] tw:hidden!" id="payment-month-info">
                            <div class="tw:bs-border! tw:rounded-bs! tw:p-2! tw:bg-bs-light! tw:text-[.875em] bs-d-flex tw:flex! tw:flex-wrap! tw:gap-4! tw:items-center!">
                                <span><i class="bi bi-calendar-check tw:text-bs-success!"></i>
                                    Attendance: <strong id="pmi-attendance">-</strong></span>
                                <span id="pmi-noattend" class="tw:hidden! tw:text-bs-secondary! tw:font-semibold!"><i class="bi bi-calendar-x"></i>
                                    No attendance this month: no payment is taken</span>
                                <span id="pmi-paid" class="tw:hidden! tw:text-bs-danger! tw:font-semibold!"><i class="bi bi-exclamation-triangle"></i>
                                    <span id="pmi-paid-text"></span></span>
                            </div>
                            <div id="pmi-option" class="tw:mt-2! tw:hidden!" style="max-width: 360px">
                                <label for="payment-fee-option" class="ui-form-label tw:font-semibold! tw:mb-1!">Fee option for this month</label>
                                <select id="payment-fee-option" class="ui-form-select"></select>
                            </div>
                            <div id="pmi-tute" class="ui-form-check tw:mt-2! tw:hidden!">
                                <input class="ui-form-check-input" type="checkbox" id="payment-give-tute">
                                <label class="ui-form-check-label" for="payment-give-tute" id="pmi-tute-label">Give tute</label>
                            </div>
                        </div>


                        {{-- Note --}}
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[100%]">
                            <label for="payment-note" class="ui-form-label tw:font-semibold!">
                                Note
                            </label>

                            <textarea id="payment-note"
                                      class="ui-form-control"
                                      rows="2"
                                      placeholder="Optional note"></textarea>
                        </div>

                    </div>

                </form>

            </div>


            {{-- =========================================================
                 Footer
            ========================================================== --}}
            <div class="ui-modal-footer tw:[border:0]!">

                <button type="button"
                        class="ui-btn ui-btn-light tw:bs-border!"
                        data-nx-dismiss="modal">
                    Cancel
                </button>

                <button type="button"
                        id="confirm-payment-btn"
                        class="ui-btn ui-btn-primary">
                    <i class="bi bi-check-circle tw:mr-1!"></i>
                    Confirm Payment
                </button>

            </div>

        </div>

    </div>

</div>
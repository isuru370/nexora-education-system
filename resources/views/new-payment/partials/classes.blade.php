{{-- =============================================================
     Enrolled Classes — Card Container
============================================================= --}}
<div class="ui-card tw:shadow-bs-sm! tw:[border:0]!">

    {{-- =========================================================
         Header
    ========================================================== --}}
    <div class="ui-card-header tw:bg-bs-white! tw:[border:0]! tw:pt-6! tw:px-6!">

        <div class="bs-d-flex tw:flex! tw:justify-between! tw:items-center! tw:gap-4! tw:flex-wrap!">

            {{-- Left: Title --}}
            <div>
                <h5 class="tw:font-semibold! tw:mb-1!">
                    <i class="bi bi-journal-bookmark-fill tw:mr-2! tw:text-bs-primary!"></i>
                    Enrolled Classes
                </h5>

                <small class="tw:text-bs-secondary-color!">
                    Select classes to make payment
                </small>
            </div>


            {{-- Right: Select All + Bulk Button --}}
            <div class="bs-d-flex tw:flex! tw:items-center! tw:gap-2! tw:flex-wrap!">

                <div class="ui-form-check tw:mb-0!">
                    <input type="checkbox"
                           id="select-all"
                           class="ui-form-check-input">

                    <label for="select-all"
                           class="ui-form-check-label tw:text-[.875em] tw:text-bs-secondary-color!">
                        Select All
                    </label>
                </div>

                <button type="button"
                        id="bulk-payment-btn"
                        class="ui-btn ui-btn-primary ui-btn-sm"
                        disabled>
                    <i class="bi bi-credit-card tw:mr-1!"></i>
                    Pay Selected
                </button>

            </div>

        </div>

    </div>


    {{-- =========================================================
         Body
    ========================================================== --}}
    <div class="ui-card-body tw:px-6! tw:pb-6!">

        <div id="classes-card-container" class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2">

            {{-- Initial Empty State --}}
            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[100%]">
                <div class="classes-empty-state">

                    <div class="classes-empty-icon">
                        <i class="bi bi-inbox"></i>
                    </div>

                    <div class="tw:font-semibold! tw:text-bs-secondary-color!">
                        Search a student to view classes
                    </div>

                    <small class="tw:text-bs-secondary-color!">
                        Enrolled classes will appear here
                    </small>

                </div>
            </div>

        </div>

    </div>

</div>
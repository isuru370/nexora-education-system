{{-- =============================================================
     Student Search — QR + Manual Entry
============================================================= --}}
<div class="ui-card tw:shadow-bs-sm! tw:[border:0]! tw:mb-6!">

    <div class="ui-card-body">

        <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3">

            {{-- =========================================================
                 QR Scanner
            ========================================================== --}}
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">

                <div class="tw:bs-border! tw:rounded-bs-lg! tw:p-4! tw:h-full! search-box">

                    {{-- Header --}}
                    <div class="bs-d-flex tw:flex! tw:justify-between! tw:items-start! tw:mb-4!">

                        <div>
                            <h6 class="tw:font-semibold! tw:mb-1!">
                                <i class="bi bi-qr-code-scan tw:mr-1! tw:text-bs-primary!"></i>
                                QR Scanner
                            </h6>

                            <small class="tw:text-bs-secondary-color!">
                                Scan student QR code
                            </small>
                        </div>

                        <span class="ui-badge tw:bg-bs-primary!">
                            <i class="bi bi-camera-video tw:mr-1!"></i>
                            QR
                        </span>

                    </div>


                    {{-- QR Reader --}}
                    <div class="bs-text-center tw:text-center!">

                        <div id="qr-reader" class="tw:mx-auto!"></div>

                        <div id="qr-reader-status"
                             class="tw:text-[.875em] tw:text-bs-secondary-color! tw:mt-2!">
                            <i class="bi bi-hourglass-split tw:mr-1!"></i>
                            Starting camera...
                        </div>

                    </div>

                </div>

            </div>


            {{-- =========================================================
                 Manual Entry
            ========================================================== --}}
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">

                <div class="tw:bs-border! tw:rounded-bs-lg! tw:p-4! tw:h-full! search-box">

                    {{-- Header --}}
                    <div class="bs-d-flex tw:flex! tw:justify-between! tw:items-start! tw:mb-4!">

                        <div>
                            <h6 class="tw:font-semibold! tw:mb-1!">
                                <i class="bi bi-keyboard tw:mr-1!"></i>
                                Manual Entry
                            </h6>

                            <small class="tw:text-bs-secondary-color!">
                                Press <kbd>F2</kbd> to focus
                            </small>
                        </div>

                        <span class="ui-badge tw:bg-bs-secondary!">
                            <i class="bi bi-keyboard tw:mr-1!"></i>
                            F2
                        </span>

                    </div>


                    {{-- Search Form --}}
                    <form id="manual-search-form">

                        <label for="manual-code" class="ui-form-label tw:font-semibold!">
                            Student ID / QR Code
                        </label>

                        <div class="ui-input-group">

                            <span class="ui-input-group-text tw:bg-bs-light!">
                                <i class="bi bi-search"></i>
                            </span>

                            <input type="text"
                                   id="manual-code"
                                   class="ui-form-control"
                                   placeholder="Enter Student ID or QR code"
                                   autocomplete="off"
                                   autofocus>

                            <button type="submit"
                                    class="ui-btn ui-btn-primary">
                                <i class="bi bi-search tw:mr-1!"></i>
                                Search
                            </button>

                        </div>

                        <div class="ui-form-text">
                            <i class="bi bi-info-circle tw:mr-1!"></i>
                            Press <kbd>Enter</kbd> to search
                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>
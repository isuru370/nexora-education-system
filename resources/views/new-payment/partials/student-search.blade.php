{{-- =============================================================
     Student Search — QR + Manual Entry
============================================================= --}}
<div class="card shadow-sm border-0 mb-4">

    <div class="card-body">

        <div class="row g-4">

            {{-- =========================================================
                 QR Scanner
            ========================================================== --}}
            <div class="col-lg-6">

                <div class="border rounded-3 p-3 h-100 search-box">

                    {{-- Header --}}
                    <div class="d-flex justify-content-between align-items-start mb-3">

                        <div>
                            <h6 class="fw-semibold mb-1">
                                <i class="bi bi-qr-code-scan me-1 text-primary"></i>
                                QR Scanner
                            </h6>

                            <small class="text-muted">
                                Scan student QR code
                            </small>
                        </div>

                        <span class="badge bg-primary">
                            <i class="bi bi-camera-video me-1"></i>
                            QR
                        </span>

                    </div>


                    {{-- QR Reader --}}
                    <div class="text-center">

                        <div id="qr-reader" class="mx-auto"></div>

                        <div id="qr-reader-status"
                             class="small text-muted mt-2">
                            <i class="bi bi-hourglass-split me-1"></i>
                            Starting camera...
                        </div>

                    </div>

                </div>

            </div>


            {{-- =========================================================
                 Manual Entry
            ========================================================== --}}
            <div class="col-lg-6">

                <div class="border rounded-3 p-3 h-100 search-box">

                    {{-- Header --}}
                    <div class="d-flex justify-content-between align-items-start mb-3">

                        <div>
                            <h6 class="fw-semibold mb-1">
                                <i class="bi bi-keyboard me-1"></i>
                                Manual Entry
                            </h6>

                            <small class="text-muted">
                                Press <kbd>F2</kbd> to focus
                            </small>
                        </div>

                        <span class="badge bg-secondary">
                            <i class="bi bi-keyboard me-1"></i>
                            F2
                        </span>

                    </div>


                    {{-- Search Form --}}
                    <form id="manual-search-form">

                        <label for="manual-code" class="form-label fw-semibold">
                            Student ID / QR Code
                        </label>

                        <div class="input-group">

                            <span class="input-group-text bg-light">
                                <i class="bi bi-search"></i>
                            </span>

                            <input type="text"
                                   id="manual-code"
                                   class="form-control"
                                   placeholder="Enter Student ID or QR code"
                                   autocomplete="off"
                                   autofocus>

                            <button type="submit"
                                    class="btn btn-primary">
                                <i class="bi bi-search me-1"></i>
                                Search
                            </button>

                        </div>

                        <div class="form-text">
                            <i class="bi bi-info-circle me-1"></i>
                            Press <kbd>Enter</kbd> to search
                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>
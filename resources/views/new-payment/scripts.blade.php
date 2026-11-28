<script src="https://unpkg.com/html5-qrcode"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | Elements
    |--------------------------------------------------------------------------
    */

    const loading         = document.getElementById('payment-loading');
    const errorBox        = document.getElementById('payment-error');
    const studentSection  = document.getElementById('student-section');
    const bulkButton      = document.getElementById('bulk-payment-btn');
    const selectAll       = document.getElementById('select-all');

    /*
    |--------------------------------------------------------------------------
    | State
    |--------------------------------------------------------------------------
    */

    let currentStudent         = null;
    let currentMarkMethod      = null;
    let currentClasses         = [];
    let selectedEnrollmentIds  = [];
    let html5QrCode            = null;

    /*
    |--------------------------------------------------------------------------
    | URLs
    |--------------------------------------------------------------------------
    */

    const readUrl     = @json(route('new-payment.read'));
    const payUrl      = @json(route('new-payment.pay'));
    const bulkPayUrl  = @json(route('new-payment.bulk-pay'));
    const monthInfoUrl = @json(route('new-payment.month-info'));

    // Multi-class discount (Payment Rules): several classes paid at once get
    // the amount off each class fee. The server works it out too.
    const multiClassRule = @json(\App\Support\MultiClassDiscount::all());

    /** enrollment_id => discount, for the classes picked together */
    function multiClassDiscounts(enrollmentIds) {
        const result = {};
        if (!multiClassRule.enabled || enrollmentIds.length < 2) return result;

        const items = enrollmentIds.map(function (id) {
            return currentClasses.find(function (item) { return Number(item.enrollment_id) === Number(id); });
        }).filter(function (item) {
            return item && !item.is_free_card && Number(item.class_fee ?? item.final_fee ?? 0) > 0;
        });

        // different classes per branch
        const perBranch = {};
        items.forEach(function (item) {
            const branch = String(item.branch_id ?? '');
            perBranch[branch] = perBranch[branch] || {};
            perBranch[branch][String(item.class_id ?? item.class_name) + '|' + String(item.class_category_fee_id ?? item.category_name)] = true;
        });

        items.forEach(function (item) {
            if (Object.keys(perBranch[String(item.branch_id ?? '')]).length >= Number(multiClassRule.min_classes)) {
                result[item.enrollment_id] = Math.min(Number(multiClassRule.amount), Number(item.class_fee ?? item.final_fee ?? 0));
            }
        });

        return result;
    }

    // Payment Rules details for the open single-class payment (or null)
    let monthInfo = null;

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    function showLoading() {
        if (loading) loading.classList.remove('tw:hidden!');
        if (errorBox) errorBox.classList.add('tw:hidden!');
    }

    function hideLoading() {
        if (loading) loading.classList.add('tw:hidden!');
    }

    function showError(message) {
        hideLoading();
        if (errorBox) {
            errorBox.textContent = message;
            errorBox.classList.remove('tw:hidden!');
        }
    }

    function clearError() {
        if (errorBox) {
            errorBox.classList.add('tw:hidden!');
            errorBox.textContent = '';
        }
    }

    function formatMoney(value) {
        return Number(value || 0).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    /*
    |--------------------------------------------------------------------------
    | Read Student
    |--------------------------------------------------------------------------
    */

    async function readStudent(code, markMethod) {

        code = String(code || '').trim();

        if (!code) {
            showError('Please enter Student ID or scan QR.');
            return;
        }

        showLoading();
        clearError();
        currentMarkMethod = markMethod;

        try {
            const response = await fetch(readUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken()
                },
                body: JSON.stringify({ code: code })
            });

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                throw new Error(result.message || 'Student not found.');
            }

            currentStudent = result.data.student;
            currentClasses = result.data.classes || [];

            renderStudent();
            renderClasses();

            if (markMethod === 'qr_web') {
                const firstUnpaid = currentClasses.find(function (item) {
                    return item.payment_status !== 'paid';
                });

                if (firstUnpaid) {
                    setTimeout(function () {
                        openPaymentModal([Number(firstUnpaid.enrollment_id)]);
                    }, 200);
                }
            }

        } catch (error) {
            if (studentSection) studentSection.classList.add('tw:hidden!');
            showError(error.message);
        } finally {
            hideLoading();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Render Student
    |--------------------------------------------------------------------------
    */

    function renderStudent() {

        if (!currentStudent) return;

        const student = currentStudent;

        const nameEl     = document.getElementById('student-name');
        const idEl       = document.getElementById('student-custom-id');
        const mobileEl   = document.getElementById('student-mobile');
        const guardianEl = document.getElementById('student-guardian');
        const imageEl    = document.getElementById('student-image');

        if (nameEl)     nameEl.textContent     = student.initial_name || student.full_name || '-';
        if (idEl)       idEl.textContent       = student.custom_id || '-';
        if (mobileEl)   mobileEl.textContent   = student.mobile || '-';
        if (guardianEl) guardianEl.textContent = student.guardian_name || student.guardian_mobile || '-';

        if (imageEl) {
            imageEl.src = student.image_url || '{{ asset('images/default-student.png') }}';
        }

        if (studentSection) studentSection.classList.remove('tw:hidden!');
    }

    /*
    |--------------------------------------------------------------------------
    | Render Classes
    |--------------------------------------------------------------------------
    */

    function renderClasses() {

        const container = document.getElementById('classes-card-container');

        if (!container) {
            console.error('Classes card container not found.');
            return;
        }

        container.innerHTML = '';

        selectedEnrollmentIds = [];

        if (selectAll) selectAll.checked = false;

        updateBulkButton();

        /*
        |--------------------------------------------------------------------------
        | No Classes
        |--------------------------------------------------------------------------
        */

        if (!currentClasses.length) {
            container.innerHTML = `
                <div class="ui-col-12">
                    <div class="classes-empty-state">
                        <div class="classes-empty-icon">
                            <i class="bi bi-journal-x"></i>
                        </div>
                        <div class="tw:font-semibold! tw:text-bs-secondary-color!">
                            No active classes found
                        </div>
                        <small class="tw:text-bs-secondary-color!">
                            This student has no active class enrollments.
                        </small>
                    </div>
                </div>
            `;
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Render Cards
        |--------------------------------------------------------------------------
        */

        currentClasses.forEach(function (item) {

            const status = item.payment_status || 'unpaid';

            const statusClass = status === 'paid'
                ? 'payment-status-paid'
                : 'payment-status-unpaid';

            /*
            |--------------------------------------------------------------------------
            | Last Payment
            |--------------------------------------------------------------------------
            */

            let lastPaymentHtml = '';

            if (item.last_payment) {

                const last = item.last_payment;

                lastPaymentHtml = `
                    <div class="last-payment-box">
                        <div class="last-payment-header">
                            <span class="last-payment-label">
                                <i class="bi bi-receipt tw:mr-1!"></i>
                                Last Payment
                            </span>
                        </div>

                        <div class="last-payment-content">
                            <div>
                                <div class="tw:text-[.875em] tw:text-bs-secondary-color!">Month</div>
                                <strong>
                                    ${escapeHtml(last.payment_month_name || last.payment_month || '-')}
                                </strong>
                            </div>

                            <div class="tw:text-right!">
                                <div class="tw:text-[.875em] tw:text-bs-secondary-color!">Amount</div>
                                <strong class="tw:text-bs-success!">
                                    Rs. ${formatMoney(last.amount)}
                                </strong>
                            </div>
                        </div>

                        <div class="last-payment-receipt">
                            <i class="bi bi-upc-scan tw:mr-1!"></i>
                            Receipt: ${escapeHtml(last.receipt_number || '-')}
                        </div>
                    </div>
                `;

            } else {

                lastPaymentHtml = `
                    <div class="last-payment-box last-payment-empty">
                        <div>
                            <span class="last-payment-label">
                                <i class="bi bi-receipt tw:mr-1!"></i>
                                Last Payment
                            </span>
                        </div>
                        <div class="tw:text-[.875em] tw:text-bs-secondary-color! tw:mt-1!">
                            No payment recorded yet
                        </div>
                    </div>
                `;
            }

            /*
            |--------------------------------------------------------------------------
            | Card
            |--------------------------------------------------------------------------
            */

            container.insertAdjacentHTML(
                'beforeend',
                `
                <div class="ui-col-12 ui-col-md-6 ui-col-xl-4">
                    <div class="class-payment-card">

                        {{-- =====================================================
                             Card Header
                             ===================================================== --}}
                        <div class="class-payment-card-header">
                            <div class="bs-d-flex tw:flex! tw:items-start! tw:gap-4!">

                                {{-- Checkbox --}}
                                <div class="tw:pt-1!">
                                    <input
                                        type="checkbox"
                                        class="ui-form-check-input class-checkbox"
                                        value="${item.enrollment_id}"
                                    >
                                </div>

                                {{-- Class Info --}}
                                <div class="tw:grow!">
                                    <div class="bs-d-flex tw:flex! tw:justify-between! tw:items-start! tw:gap-2!">
                                        <div>
                                            <h6 class="class-payment-title tw:mb-1!">
                                                ${escapeHtml(item.class_name || '-')}
                                            </h6>

                                            <div class="class-payment-subject">
                                                <i class="bi bi-book tw:mr-1!"></i>
                                                ${escapeHtml(item.subject || '-')}
                                            </div>
                                        </div>

                                        {{-- Status --}}
                                        <span class="${statusClass}">
                                            ${escapeHtml(status.toUpperCase())}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- =====================================================
                             Class Details
                             ===================================================== --}}
                        <div class="class-payment-details">

                            {{-- Category --}}
                            <div class="class-payment-detail">
                                <div class="class-payment-detail-label">
                                    <i class="bi bi-tag tw:mr-1!"></i>
                                    Category
                                </div>
                                <div class="class-payment-detail-value">
                                    ${escapeHtml(item.category_name || '-')}
                                </div>
                            </div>

                            {{-- Grade --}}
                            <div class="class-payment-detail">
                                <div class="class-payment-detail-label">
                                    <i class="bi bi-mortarboard tw:mr-1!"></i>
                                    Grade
                                </div>
                                <div class="class-payment-detail-value">
                                    ${escapeHtml(item.grade || '-')}
                                </div>
                            </div>

                            {{-- Teacher --}}
                            <div class="class-payment-detail">
                                <div class="class-payment-detail-label">
                                    <i class="bi bi-person-badge tw:mr-1!"></i>
                                    Teacher
                                </div>
                                <div class="class-payment-detail-value">
                                    ${escapeHtml(item.teacher_initials || '-')}
                                </div>
                            </div>

                            {{-- Fee --}}
                            <div class="class-payment-detail">
                                <div class="class-payment-detail-label">
                                    <i class="bi bi-cash-stack tw:mr-1!"></i>
                                    Monthly Fee
                                </div>
                                <div class="class-payment-fee">
                                    Rs. ${formatMoney(item.total_fee ?? item.final_fee ?? 0)}
                                    ${Number(item.hall_fee || 0) > 0 ? `
                                        <div class="tw:text-[.875em] tw:text-bs-secondary-color! tw:mt-1!">
                                            Class: Rs. ${formatMoney(item.class_fee ?? item.final_fee ?? 0)}<br>
                                            Hall: Rs. ${formatMoney(item.hall_fee)}
                                        </div>
                                    ` : ''}
                                </div>
                            </div>

                        </div>

                        {{-- Last Payment --}}
                        ${lastPaymentHtml}

                        {{-- Footer --}}
                        <div class="class-payment-card-footer">
                            <div class="tw:text-[.875em] tw:text-bs-secondary-color!">
                                ${status === 'paid'
                                    ? '<i class="bi bi-check-circle-fill tw:text-bs-success! tw:mr-1!"></i> Payment completed'
                                    : '<i class="bi bi-exclamation-circle tw:text-bs-warning! tw:mr-1!"></i> Payment pending'
                                }
                            </div>

                            <button
                                type="button"
                                class="ui-btn ui-btn-primary ui-btn-sm pay-single-btn"
                                data-enrollment-id="${item.enrollment_id}"
                            >
                                <i class="bi bi-credit-card tw:mr-1!"></i>
                                Pay
                            </button>
                        </div>

                    </div>
                </div>
                `
            );
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Single Pay
    |--------------------------------------------------------------------------
    */

    document.addEventListener('click', function (event) {
        const button = event.target.closest('.pay-single-btn');
        if (!button) return;

        const enrollmentId = Number(button.dataset.enrollmentId);
        openPaymentModal([enrollmentId]);
    });

    /*
    |--------------------------------------------------------------------------
    | Checkbox
    |--------------------------------------------------------------------------
    */

    document.addEventListener('change', function (event) {
        if (!event.target.classList.contains('class-checkbox')) return;
        updateSelectedEnrollments();
    });

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.class-checkbox').forEach(function (checkbox) {
                checkbox.checked = selectAll.checked;
            });
            updateSelectedEnrollments();
        });
    }

    function updateSelectedEnrollments() {
        selectedEnrollmentIds = Array.from(
            document.querySelectorAll('.class-checkbox:checked')
        ).map(function (checkbox) {
            return Number(checkbox.value);
        });

        updateBulkButton();
    }

    function updateBulkButton() {
        if (!bulkButton) return;

        bulkButton.disabled = selectedEnrollmentIds.length === 0;

        bulkButton.innerHTML = selectedEnrollmentIds.length > 1
            ? `<i class="bi bi-credit-card tw:mr-1!"></i> Pay Selected (${selectedEnrollmentIds.length})`
            : `<i class="bi bi-credit-card tw:mr-1!"></i> Pay Selected`;
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk Button
    |--------------------------------------------------------------------------
    */

    if (bulkButton) {
        bulkButton.addEventListener('click', function () {
            if (!selectedEnrollmentIds.length) return;
            openPaymentModal(selectedEnrollmentIds);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Open Payment Modal
    |--------------------------------------------------------------------------
    */

    function openPaymentModal(enrollmentIds) {

        if (!enrollmentIds || !enrollmentIds.length) return;

        const firstEnrollment = currentClasses.find(function (item) {
            return Number(item.enrollment_id) === Number(enrollmentIds[0]);
        });

        if (!firstEnrollment) return;

        const modalElement = document.getElementById('paymentModal');
        if (!modalElement) {
            console.error('Payment modal not found.');
            return;
        }

        const form = modalElement.querySelector('#payment-form');
        if (!form) {
            console.error('Payment form not found.');
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Hidden fields
        |--------------------------------------------------------------------------
        */

        const paymentCode         = form.querySelector('#payment-code');
        const paymentEnrollmentId = form.querySelector('#payment-enrollment-id');
        const paymentMarkMethod   = form.querySelector('#payment-mark-method');

        if (!paymentCode || !paymentEnrollmentId || !paymentMarkMethod) {
            console.error('Payment hidden fields are missing.');
            return;
        }

        paymentCode.value         = currentStudent ? currentStudent.custom_id : '';
        paymentEnrollmentId.value = enrollmentIds.join(',');
        paymentMarkMethod.value   = currentMarkMethod || 'manual_web';

        /*
        |--------------------------------------------------------------------------
        | Class / Category — use modal's existing elements
        |--------------------------------------------------------------------------
        */

        const classNameElement    = document.getElementById('modal-class-name');
        const categoryNameElement = document.getElementById('modal-category-name');

        if (classNameElement) {
            classNameElement.textContent = enrollmentIds.length > 1
                ? `${enrollmentIds.length} Classes Selected`
                : (firstEnrollment.class_name || '-');
        }

        if (categoryNameElement) {
            categoryNameElement.textContent = enrollmentIds.length > 1
                ? 'Bulk Payment'
                : (firstEnrollment.category_name || '-');
        }

        /*
        |--------------------------------------------------------------------------
        | Fee Summary
        |--------------------------------------------------------------------------
        */

        const feeSummary = enrollmentIds.reduce(function (summary, id) {

            const item = currentClasses.find(function (classItem) {
                return Number(classItem.enrollment_id) === Number(id);
            });

            if (!item) return summary;

            const classFee = Number(item.class_fee ?? item.final_fee ?? 0);
            const hallFee  = Number(item.hall_fee ?? 0);
            const totalFee = Number(item.total_fee ?? (classFee + hallFee));

            summary.classFee += classFee;
            summary.hallFee  += hallFee;
            summary.totalFee += totalFee;

            return summary;

        }, { classFee: 0, hallFee: 0, totalFee: 0 });

        /*
        |--------------------------------------------------------------------------
        | Payment Inputs
        |--------------------------------------------------------------------------
        */

        const classFeeInput = form.querySelector('#payment-class-fee');
        const hallFeeInput  = form.querySelector('#payment-hall-fee');
        const finalFeeInput = form.querySelector('#payment-final-fee');
        const discountInput = form.querySelector('#payment-discount');
        const amountInput   = form.querySelector('#payment-amount');
        const noteInput     = form.querySelector('#payment-note');

        if (!finalFeeInput || !discountInput || !amountInput || !noteInput) {
            console.error('Payment input fields are missing.');
            return;
        }

        if (classFeeInput) classFeeInput.value = feeSummary.classFee.toFixed(2);
        if (hallFeeInput)  hallFeeInput.value  = feeSummary.hallFee.toFixed(2);

        const multiClass = Object.values(multiClassDiscounts(enrollmentIds)).reduce(function (a, b) { return a + b; }, 0);

        finalFeeInput.value = feeSummary.totalFee.toFixed(2);
        discountInput.value = multiClass.toFixed(2);
        amountInput.value   = (feeSummary.totalFee - multiClass).toFixed(2);
        noteInput.value     = '';

        // the multi-class discount is fixed (Payment Rules)
        discountInput.readOnly = multiClass > 0 || enrollmentIds.length > 1;
        const multiNote = document.getElementById('payment-multi-class-note');
        if (multiNote) {
            multiNote.textContent = multiClass > 0
                ? `Multi-class discount: ${Object.keys(multiClassDiscounts(enrollmentIds)).length} classes x Rs. ${formatMoney(multiClassRule.amount)} = Rs. ${formatMoney(multiClass)}`
                : '';
            multiNote.classList.toggle('tw:hidden!', multiClass <= 0);
        }

        setCurrentMonth();
        resetMonthInfo();

        NxUi.Modal
            .getOrCreateInstance(modalElement)
            .show();

        if (enrollmentIds.length === 1) {
            loadMonthInfo();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Payment Rules: attendance this month, fee after the rules, tute
    |--------------------------------------------------------------------------
    */

    function resetMonthInfo() {
        monthInfo = null;
        document.getElementById('payment-month-info')?.classList.add('tw:hidden!');
        const discount = document.getElementById('payment-discount');
        if (discount) discount.readOnly = false;
        const tute = document.getElementById('payment-give-tute');
        if (tute) tute.checked = false;
    }

    async function loadMonthInfo() {
        const enrollmentIds = (document.getElementById('payment-enrollment-id')?.value || '')
            .split(',').map(Number).filter(Boolean);
        const month = document.getElementById('payment-month')?.value || '';

        if (enrollmentIds.length !== 1 || !month || !currentStudent) {
            resetMonthInfo();
            return;
        }

        try {
            const response = await fetch(monthInfoUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken()
                },
                body: JSON.stringify({
                    code: currentStudent.custom_id,
                    enrollment_id: enrollmentIds[0],
                    payment_month: month + '-01'
                })
            });

            const result = await response.json();
            if (!response.ok || result.status !== 'success') {
                resetMonthInfo();
                return;
            }

            monthInfo = result.data;
            applyMonthInfo();
        } catch (error) {
            resetMonthInfo();
        }
    }

    function rulesActive() {
        return monthInfo && (monthInfo.fee_option_enabled || monthInfo.custom_amount);
    }

    function selectedOption() {
        if (!monthInfo || !monthInfo.fee_option_enabled) return null;
        const id = Number(document.getElementById('payment-fee-option')?.value || 0);
        return monthInfo.fee_options.find(function (option) { return Number(option.id) === id; }) || null;
    }

    function setAmountFromOption() {
        const option = selectedOption();
        const amountInput = document.getElementById('payment-amount');
        const discountInput = document.getElementById('payment-discount');
        const total = Number(document.getElementById('payment-final-fee')?.value || 0);
        if (!amountInput || !discountInput) return;

        const amount = option ? Number(option.amount) : total;
        amountInput.value = amount.toFixed(2);
        discountInput.value = Math.max(total - amount, 0).toFixed(2);
    }

    function applyMonthInfo() {
        const box = document.getElementById('payment-month-info');
        if (!box || !monthInfo) return;

        box.classList.remove('tw:hidden!');
        document.getElementById('pmi-attendance').textContent =
            monthInfo.attendance_count + ' (' + monthInfo.payment_month_name + ')';

        document.getElementById('pmi-noattend').classList.toggle('tw:hidden!', !monthInfo.no_attendance);

        const paid = document.getElementById('pmi-paid');
        if (monthInfo.already_paid) {
            paid.classList.remove('tw:hidden!');
            document.getElementById('pmi-paid-text').textContent =
                'Already paid (' + (monthInfo.paid.receipt_number || '') + ')';
        } else {
            paid.classList.add('tw:hidden!');
        }

        // Fee options of the class category; the student's own one first selected
        const optionBox = document.getElementById('pmi-option');
        const select = document.getElementById('payment-fee-option');
        if (monthInfo.fee_option_enabled) {
            optionBox.classList.remove('tw:hidden!');
            select.innerHTML = monthInfo.fee_options.map(function (option) {
                return '<option value="' + option.id + '"' + (option.is_student_option ? ' selected' : '') + '>'
                    + escapeHtml(option.label) + ' - Rs. ' + formatMoney(option.amount)
                    + (option.is_student_option ? ' (student)' : '') + '</option>';
            }).join('');
        } else {
            optionBox.classList.add('tw:hidden!');
            select.innerHTML = '';
        }

        const tuteBox = document.getElementById('pmi-tute');
        const tuteInput = document.getElementById('payment-give-tute');
        if (monthInfo.tute.enabled) {
            tuteBox.classList.remove('tw:hidden!');
            tuteInput.checked = false;
            tuteInput.disabled = monthInfo.tute.issued;
            document.getElementById('pmi-tute-label').textContent = monthInfo.tute.issued
                ? 'Tute already given (' + (monthInfo.tute.issued_at || '') + ')'
                : 'Give ' + monthInfo.payment_month_name + ' tute';
        } else {
            tuteBox.classList.add('tw:hidden!');
        }

        // Amount follows the option; the discount is worked out from the amount
        const discountInput = document.getElementById('payment-discount');
        if (rulesActive() && discountInput) {
            discountInput.readOnly = true;
            setAmountFromOption();
        }
    }

    document.getElementById('payment-fee-option')?.addEventListener('change', setAmountFromOption);

    document.getElementById('payment-month')?.addEventListener('change', loadMonthInfo);

    document.getElementById('payment-amount')?.addEventListener('input', function () {
        if (!rulesActive()) return;
        const total = Number(document.getElementById('payment-final-fee')?.value || 0);
        const discountInput = document.getElementById('payment-discount');
        if (discountInput) {
            discountInput.value = Math.max(total - Number(this.value || 0), 0).toFixed(2);
        }
    });

    function setCurrentMonth() {
        const monthInput = document.getElementById('payment-month');
        if (!monthInput) {
            console.error('Payment month input not found.');
            return;
        }

        const now   = new Date();
        const year  = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');

        monthInput.value = `${year}-${month}`;
    }

    /*
    |--------------------------------------------------------------------------
    | Discount Listener (only ONE declaration)
    |--------------------------------------------------------------------------
    */

    const discountField = document.getElementById('payment-discount');

    if (discountField) {
        discountField.addEventListener('input', function () {
            const finalFee = Number(
                document.getElementById('payment-final-fee')?.value || 0
            );
            const discount = Number(this.value || 0);
            const payable  = Math.max(finalFee - discount, 0);

            const amountEl = document.getElementById('payment-amount');
            if (amountEl) amountEl.value = payable.toFixed(2);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Confirm Payment
    |--------------------------------------------------------------------------
    */

    const confirmBtn = document.getElementById('confirm-payment-btn');

    if (confirmBtn) {
        confirmBtn.addEventListener('click', async function () {

            const enrollmentInput = document.getElementById('payment-enrollment-id');

            if (!enrollmentInput) {
                alert('Payment form not loaded properly.');
                return;
            }

            const enrollmentIds = enrollmentInput.value
                .split(',')
                .map(Number)
                .filter(Boolean);

            if (!enrollmentIds.length) {
                alert('Please select a class.');
                return;
            }

            const month = document.getElementById('payment-month')?.value || '';

            if (!month) {
                alert('Please select payment month.');
                return;
            }

            const paymentMethod = document.getElementById('payment-method')?.value || 'cash';
            const discount      = Number(document.getElementById('payment-discount')?.value || 0);
            const note          = document.getElementById('payment-note')?.value || '';
            const markMethod    = document.getElementById('payment-mark-method')?.value || 'manual_web';

            const button = this;
            button.disabled = true;
            button.innerHTML = `<span class="ui-spinner-border ui-spinner-border-sm tw:mr-2!"></span> Processing...`;

            try {

                if (enrollmentIds.length === 1) {

                    const amount = Number(
                        document.getElementById('payment-amount')?.value || 0
                    );

                    const data = {
                        code: currentStudent ? currentStudent.custom_id : '',
                        enrollment_id: enrollmentIds[0],
                        amount: amount,
                        payment_month: month + '-01',
                        mark_method: markMethod,
                        payment_method: paymentMethod,
                        // With Payment Rules the server saves the rest as a discount (with the reason)
                        discount_amount: rulesActive() ? 0 : discount,
                        give_tute: !!document.getElementById('payment-give-tute')?.checked,
                        fee_option_id: selectedOption() ? selectedOption().id : null,
                        note: note
                    };

                    const response = await fetch(payUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken()
                        },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();

                    if (!response.ok || result.status !== 'success') {
                        throw new Error(result.message || 'Payment failed.');
                    }

                    const modal = NxUi.Modal.getInstance(
                        document.getElementById('paymentModal')
                    );
                    if (modal) modal.hide();

                    showReceipt(result.data.receipt);

                    setTimeout(function () {
                        printReceipt(result.data.receipt);
                    }, 350);

                    await readStudent(
                        currentStudent ? currentStudent.custom_id : '',
                        currentMarkMethod
                    );

                } else {

                    const multiClass = multiClassDiscounts(enrollmentIds);

                    const payments = enrollmentIds.map(function (id) {

                        const enrollment = currentClasses.find(function (item) {
                            return Number(item.enrollment_id) === Number(id);
                        });

                        const totalFee = Number(
                            enrollment?.total_fee ?? (
                                Number(enrollment?.class_fee ?? enrollment?.final_fee ?? 0) +
                                Number(enrollment?.hall_fee ?? 0)
                            )
                        );

                        return {
                            code: currentStudent ? currentStudent.custom_id : '',
                            enrollment_id: id,
                            // the server takes the multi-class discount off the class fee
                            amount: Math.max(totalFee - Number(multiClass[id] || 0), 0),
                            payment_month: month + '-01',
                            mark_method: markMethod,
                            payment_method: paymentMethod,
                            discount_amount: 0,
                            note: note
                        };
                    });

                    const response = await fetch(bulkPayUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken()
                        },
                        body: JSON.stringify({ payments: payments })
                    });

                    const result = await response.json();

                    if (!response.ok || result.status !== 'success') {
                        throw new Error(result.message || 'Bulk payment failed.');
                    }

                    const modal = NxUi.Modal.getInstance(
                        document.getElementById('paymentModal')
                    );
                    if (modal) modal.hide();

                    showReceipt(result.data.receipt);

                    setTimeout(function () {
                        printReceipt(result.data.receipt);
                    }, 350);

                    await readStudent(
                        currentStudent ? currentStudent.custom_id : '',
                        currentMarkMethod
                    );
                }

            } catch (error) {
                alert(error.message);
            } finally {
                button.disabled = false;
                button.innerHTML = `<i class="bi bi-check-circle tw:mr-1!"></i> Confirm Payment`;
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Receipt HTML
    |--------------------------------------------------------------------------
    */

    function buildReceiptHtml(receipt) {

        // Server (ReceiptService) sends: student_details, classes, summary
        const student = receipt?.student_details || receipt?.student || {};
        const rows    = receipt?.classes || receipt?.payments || [];

        // Settings > Receipt (missing = shown)
        const rules = window.NX_RECEIPT || {};
        const show  = (field) => rules[field] !== false;
        const lines = (items) => items.filter(Boolean).join('');

        let html = `
            <div id="receipt-print-area" class="receipt-paper">
                <div class="bs-text-center tw:text-center!">
                    <strong>${escapeHtml(@json(strtoupper(config('app.name'))))}</strong><br>
                    PAYMENT RECEIPT
                </div>
                <div class="receipt-line"></div>
                ${lines([
                    show('student_id') && `Student ID: ${escapeHtml(student.custom_id || '-')}<br>`,
                    show('student_name') && `Student: ${escapeHtml(student.name || student.initial_name || student.full_name || '-')}<br>`,
                    show('date') && `Date: ${escapeHtml(receipt.payment_date || '-')}<br>`,
                    show('date') && `Time: ${escapeHtml(receipt.payment_time || '-')}<br>`,
                ])}
                <div class="receipt-line"></div>
        `;

        rows.forEach(function (row) {

            const classFee = Number(row.class_fee ?? row.final_fee ?? 0);
            const hallFee  = Number(row.hall_fee || 0);

            html += `
                <div class="receipt-item">
                    <strong>${escapeHtml(row.class_name || '-')}</strong><br>
                    ${lines([
                        show('category') && `Category: ${escapeHtml(row.category_name || row.category || '-')}<br>`,
                        show('grade') && `Grade: ${escapeHtml(row.grade || '-')}<br>`,
                        show('teacher') && `Teacher: ${escapeHtml(row.teacher_name || row.teacher || '-')}<br>`,
                        show('fee_option') && (row.fee_option_name || row.fee_option) && `Option: ${escapeHtml(row.fee_option_name || row.fee_option)}<br>`,
                        show('month') && `Month: ${escapeHtml(row.payment_month || '-')}<br>`,
                        show('receipt_number') && `Receipt: ${escapeHtml(row.receipt_no || row.receipt_number || '-')}<br>`,
                        show('fees') && `Class Fee: Rs. ${formatMoney(classFee)}<br>`,
                        show('fees') && hallFee > 0 && `Hall Fee: Rs. ${formatMoney(hallFee)}<br>`,
                    ])}
                </div>
                <div class="receipt-line"></div>
            `;
        });

        const totalClassFee = Number(receipt.summary?.total_class_fee || 0);
        const totalHallFee  = Number(receipt.summary?.total_hall_fee || 0);
        const totalFee      = Number(receipt.summary?.total_final_fee ?? (totalClassFee + totalHallFee));

        html += `
                <div class="receipt-summary">
                    ${show('fees') && totalHallFee > 0 ? `Hall Fee: Rs. ${formatMoney(totalHallFee)}<br>` : ''}
                    <strong>TOTAL FEE: Rs. ${formatMoney(totalFee)}</strong>
                    ${Number(receipt.multi_class_discount || 0) > 0 ? `<br>Multi-class discount: - Rs. ${formatMoney(receipt.multi_class_discount)}<br><strong>TOTAL PAID: Rs. ${formatMoney(receipt.summary?.total_paid ?? (totalFee - Number(receipt.multi_class_discount)))}</strong>` : ''}
                </div>
                <div class="receipt-line"></div>
                ${lines([
                    show('classes_count') && `Classes Paid: ${rows.length}<br>`,
                    show('payment_method') && `Payment: ${escapeHtml(receipt.payment_method || '-')}<br>`,
                ])}
                <br>
                <div class="bs-text-center tw:text-center!">Thank You!</div>
                ${show('keep_note') ? '<div class="bs-text-center tw:text-center!">Please keep this receipt</div>' : ''}
                ${rules.footer ? `<div class="bs-text-center tw:text-center!">${escapeHtml(rules.footer)}</div>` : ''}
            </div>
        `;

        return html;
    }

    function showReceipt(receipt) {
        if (!receipt) return;

        const previewEl = document.getElementById('receipt-preview');
        if (previewEl) previewEl.innerHTML = buildReceiptHtml(receipt);

        const modalElement = document.getElementById('receiptModal');
        if (modalElement) NxUi.Modal.getOrCreateInstance(modalElement).show();
    }

    function printReceipt(receipt) {
        if (!receipt) return;

        const printWindow = window.open('', '_blank', 'width=400,height=700');
        if (!printWindow) {
            alert('Please allow popups to automatically print the receipt.');
            return;
        }

        printWindow.document.open();
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Payment Receipt</title>
                <style>
                    @page{size:58mm auto;margin:0}
                    html,body{margin:0;padding:0;width:58mm}
                    body{font-family:monospace;font-size:11px;color:#000;background:#fff}
                    .receipt-paper{width:58mm;box-sizing:border-box;padding:3mm}
                    .receipt-line{border-top:1px dashed #000;margin:6px 0}
                    .receipt-summary{line-height:1.6}
                    .receipt-item{line-height:1.45;word-break:break-word}
                    .bs-text-center{text-align:center}
                </style>
            </head>
            <body>${buildReceiptHtml(receipt)}</body>
            </html>
        `);
        printWindow.document.close();
        printWindow.focus();

        setTimeout(function () {
            printWindow.print();
            setTimeout(function () {
                printWindow.close();
            }, 500);
        }, 300);
    }

    /*
    |--------------------------------------------------------------------------
    | Print Again
    |--------------------------------------------------------------------------
    */

    const printBtn = document.getElementById('print-receipt-btn');

    if (printBtn) {
        printBtn.addEventListener('click', function () {

            const receiptElement = document.getElementById('receipt-print-area');
            if (!receiptElement) return;

            const printWindow = window.open('', '_blank', 'width=400,height=700');
            if (!printWindow) {
                alert('Please allow popups to print the receipt.');
                return;
            }

            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Payment Receipt</title>
                    <style>
                        @page{size:58mm auto;margin:0}
                        html,body{margin:0;padding:0;width:58mm}
                        body{font-family:monospace;font-size:11px}
                        .receipt-paper{width:58mm;box-sizing:border-box;padding:3mm}
                        .receipt-line{border-top:1px dashed #000;margin:6px 0}
                        .receipt-summary{line-height:1.6}
                        .receipt-item{line-height:1.45}
                        .bs-text-center{text-align:center}
                    </style>
                </head>
                <body>${receiptElement.outerHTML}</body>
                </html>
            `);
            printWindow.document.close();
            printWindow.focus();

            setTimeout(function () {
                printWindow.print();
                setTimeout(function () {
                    printWindow.close();
                }, 500);
            }, 300);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Keyboard Shortcuts
    |--------------------------------------------------------------------------
    */

    document.addEventListener('keydown', function (event) {

        if (event.key === 'F2') {
            event.preventDefault();

            const input = document.getElementById('manual-code');
            if (input) {
                input.focus();
                input.select();
            }
            return;
        }

        if (event.key === 'Enter') {
            const modal = document.getElementById('paymentModal');

            if (!modal || !modal.classList.contains('show')) return;
            if (event.target?.tagName === 'TEXTAREA') return;

            event.preventDefault();

            const confirmBtn = document.getElementById('confirm-payment-btn');
            if (confirmBtn && !confirmBtn.disabled) confirmBtn.click();
        }
    });

    /*
    |--------------------------------------------------------------------------
    | Manual Search
    |--------------------------------------------------------------------------
    */

    const searchForm = document.getElementById('manual-search-form');

    if (searchForm) {
        searchForm.addEventListener('submit', function (event) {
            event.preventDefault();

            const code = document.getElementById('manual-code')?.value || '';
            readStudent(code, 'manual_web');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | QR Scanner
    |--------------------------------------------------------------------------
    */

    function startQrScanner() {

        const readerEl = document.getElementById('qr-reader');
        const statusEl = document.getElementById('qr-reader-status');

        if (!readerEl) return;

        html5QrCode = new Html5Qrcode('qr-reader');

        Html5Qrcode.getCameras()
            .then(function (devices) {

                if (!devices.length) {
                    if (statusEl) statusEl.textContent = '❌ No camera found.';
                    return;
                }

                const cameraId = devices[0].id;

                html5QrCode.start(
                    cameraId,
                    {
                        fps: 10,
                        qrbox: { width: 180, height: 180 }
                    },
                    function (decodedText) {
                        readStudent(decodedText, 'qr_web');
                    },
                    function () {
                        // Ignore scan errors
                    }
                );

                if (statusEl) statusEl.innerHTML = '✅ Camera ready';

            })
            .catch(function (error) {
                if (statusEl) statusEl.textContent = '❌ Camera permission required.';
                console.error(error);
            });
    }

    startQrScanner();

});
</script>
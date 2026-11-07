<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // ==================================================
    // CONFIGURATION
    // ==================================================
    const CAPTURE_SCALE   = 3;
    const IMAGE_TIMEOUT   = 15000;

    // ==================================================
    // STATE
    // ==================================================
    const selectedStudents = new Set();

    // Card replacement fees (Admissions items ticked "Use for card replacement")
    const CARD_FEES = @json(($cardFees ?? collect())->map(fn ($f) => ['id' => $f->id, 'name' => $f->name, 'amount' => (float) $f->amount])->values());
    const DOWNLOAD_URL = @json(url('generate-student-id')) + '/';
    const BULK_DOWNLOAD_URL = @json(route('generate-student-id.bulk-download'));
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    async function postJson(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify(body || {}),
        });
        let data = {};
        try { data = await res.json(); } catch (e) {}
        return { status: res.status, data };
    }

    const escapeHtml = (text) => String(text ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    /**
     * Ask which card fee to charge (2nd download). Resolves to
     * {admission_id, payment_method} or null when cancelled.
     */
    function askCardFee(info, studentLabel) {
        return new Promise(resolve => {
            if (!CARD_FEES.length) {
                alert('This card was already downloaded. Add a card fee first: Admissions > New > tick "Use for card replacement" (Rs. 0 for free).');
                resolve(null);
                return;
            }

            const overlay = document.createElement('div');
            overlay.className = 'cf-overlay';
            overlay.innerHTML = `
                <div class="cf-box" role="dialog" aria-modal="true" aria-labelledby="cfTitle">
                    <h5 id="cfTitle"><i class="bi bi-person-vcard text-primary"></i> Card fee</h5>
                    <div class="cf-sub">
                        ${escapeHtml(studentLabel)}'s ID card was already downloaded
                        ${info.times ? escapeHtml(info.times) + ' time(s)' : ''}${info.last_at ? ' (last ' + escapeHtml(info.last_at) + ')' : ''}.
                        Choose the fee for the new card.
                    </div>
                    <label for="cfItem">Fee</label>
                    <select id="cfItem" class="form-select mb-3">
                        ${CARD_FEES.map(f => `<option value="${f.id}">${escapeHtml(f.name)} - ${f.amount > 0 ? 'Rs. ' + f.amount.toFixed(2) : 'Free'}</option>`).join('')}
                    </select>
                    <label for="cfMethod">Payment method</label>
                    <select id="cfMethod" class="form-select mb-4">
                        <option value="cash" selected>Cash</option>
                        <option value="bank_transfer">Bank transfer</option>
                        <option value="card">Card</option>
                        <option value="online">Online</option>
                    </select>
                    <div class="d-flex gap-2 justify-content-end">
                        <button type="button" class="btn btn-light" data-cf="cancel">Cancel</button>
                        <button type="button" class="btn btn-primary" data-cf="ok"><i class="bi bi-download"></i> Save fee &amp; download</button>
                    </div>
                </div>`;

            const close = (value) => { overlay.remove(); resolve(value); };
            overlay.addEventListener('click', e => {
                if (e.target === overlay || e.target.closest('[data-cf="cancel"]')) close(null);
                if (e.target.closest('[data-cf="ok"]')) {
                    close({
                        admission_id: Number(overlay.querySelector('#cfItem').value),
                        payment_method: overlay.querySelector('#cfMethod').value,
                    });
                }
            });
            document.addEventListener('keydown', function onKey(e) {
                if (e.key === 'Escape') { document.removeEventListener('keydown', onKey); close(null); }
            });

            document.body.appendChild(overlay);
            overlay.querySelector('#cfItem').focus();
        });
    }

    /**
     * Record the download on the server. 1st time free; later ones ask for
     * the card fee. Returns true when the card may be downloaded.
     */
    async function recordDownload(studentId, studentLabel) {
        let res = await postJson(DOWNLOAD_URL + studentId + '/download');

        if (res.status === 402 && res.data.payment_required) {
            const fee = await askCardFee(res.data, studentLabel);
            if (!fee) return false;

            res = await postJson(DOWNLOAD_URL + studentId + '/download', fee);
            if (res.status === 200 && res.data.payment) {
                showToast(`Card fee saved: ${res.data.payment.item} (receipt ${res.data.payment.receipt_number})`, 'success');
            }
        }

        if (res.status !== 200 || !res.data.success) {
            showToast(res.data.message || 'Could not record the download. Try again.', 'error');
            return false;
        }

        return true;
    }
    let isBulkProcessing   = false;
    let isSingleProcessing = false;

    // ==================================================
    // DOM REFERENCES
    // ==================================================
    const $selectedCount   = document.getElementById('selectedCount');
    const $downloadCount   = document.getElementById('downloadCount');
    const $selectAllBtn    = document.getElementById('selectAllBtn');
    const $deselectAllBtn  = document.getElementById('deselectAllBtn');
    const $bulkDownloadBtn = document.getElementById('bulkDownloadBtn');
    const $selectedInfo    = document.getElementById('selectedInfo');

    // ==================================================
    // CARD SCALING
    // ==================================================
    function scaleCards() {
        document.querySelectorAll('.id-scale-box').forEach(box => {
            const card = box.querySelector('.student-id-card');
            if (!card) return;

            const boxW  = box.clientWidth;
            const cardW = card.offsetWidth;

            if (cardW <= 0 || boxW <= 0) return;

            const scale = Math.min(boxW / cardW, 1);

            card.style.transform = `scale(${scale})`;
            card.style.transformOrigin = 'top left';

            box.style.height = (card.offsetHeight * scale) + 'px';

            const wrap = box.closest('.id-card-preview-wrap');
            if (wrap) {
                wrap.style.height = (card.offsetHeight * scale + 24) + 'px';
            }
        });
    }

    scaleCards();
    window.addEventListener('resize', debounce(scaleCards, 200));

    // ==================================================
    // UTILITY - Debounce
    // ==================================================
    function debounce(fn, delay) {
        let timer = null;
        return function (...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), delay);
        };
    }

    // ==================================================
    // PREPARE CARD FOR CAPTURE
    // ==================================================
    function prepareCardForCapture(cardElement) {
        const originalStyles = new Map();

        const gradientElements = cardElement.querySelectorAll(
            '.card-inst-name, .card-inst-sub'
        );

        gradientElements.forEach(el => {
            if (el && el.style) {
                originalStyles.set(el, {
                    background: el.style.background || '',
                    webkitBackgroundClip: el.style.webkitBackgroundClip || '',
                    color: el.style.color || ''
                });

                el.style.background = 'none';
                el.style.webkitBackgroundClip = 'unset';
                el.style.color = '#1d4ed8';
            }
        });

        return originalStyles;
    }

    // ==================================================
    // RESTORE CARD STYLES
    // ==================================================
    function restoreCardStyles(cardElement, originalStyles) {
        originalStyles.forEach((styles, el) => {
            if (el && el.style) {
                if (styles.background !== undefined) {
                    el.style.background = styles.background;
                }
                if (styles.webkitBackgroundClip !== undefined) {
                    el.style.webkitBackgroundClip = styles.webkitBackgroundClip;
                }
                if (styles.color !== undefined) {
                    el.style.color = styles.color;
                }
            }
        });
    }

    // ==================================================
    // CONVERT CARD TO CANVAS
    // ==================================================
    async function convertCardToCanvas(cardElement) {
        return html2canvas(cardElement, {
            scale: CAPTURE_SCALE,
            backgroundColor: '#ffffff',
            useCORS: true,
            logging: false,
            allowTaint: false,
            imageTimeout: IMAGE_TIMEOUT,
        });
    }

    // ==================================================
    // TRIGGER DOWNLOAD
    // ==================================================
    function triggerDownload(href, filename) {
        const link = document.createElement('a');
        link.download = filename;
        link.href = href;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // ==================================================
    // SINGLE DOWNLOAD
    // ✅ Status update ඉවත් කළා
    // ==================================================
    async function downloadSingleCard(cardId, studentKey, buttonElement) {
        if (isSingleProcessing) return;

        const card = document.querySelector(
            `.student-card[data-id="${studentKey}"]`
        );

        if (!card) {
            alert('Card not found');
            return;
        }

        const cardElement = card.querySelector('.student-id-card');

        if (!cardElement) {
            alert('ID card element not found');
            return;
        }

        isSingleProcessing = true;

        // record it first (asks for the card fee from the 2nd download)
        try {
            const allowed = await recordDownload(cardId, studentKey);
            if (!allowed) {
                isSingleProcessing = false;
                return;
            }
        } catch (error) {
            isSingleProcessing = false;
            showToast('Could not record the download: ' + error.message, 'error');
            return;
        }

        const originalText = buttonElement.innerHTML;
        buttonElement.innerHTML =
            '<i class="fas fa-spinner fa-spin"></i> Generating...';
        buttonElement.disabled = true;

        let originalStyles = null;

        try {
            originalStyles = prepareCardForCapture(cardElement);
            cardElement.classList.add('html2canvas-capture');

            const canvas = await convertCardToCanvas(cardElement);

            // ✅ File download පමණයි — status update නැහැ
            triggerDownload(canvas.toDataURL('image/png'), `ID_${studentKey}.png`);

            // ✅ Success message පමණයි
            showToast('ID Card downloaded successfully!', 'success');

            // ❌ Status update fetch call එක ඉවත් කළා

        } catch (error) {
            console.error('Download error:', error);
            showToast('Download failed: ' + error.message, 'error');
        } finally {
            if (originalStyles) {
                restoreCardStyles(cardElement, originalStyles);
            }

            cardElement.classList.remove('html2canvas-capture');
            buttonElement.innerHTML = originalText;
            buttonElement.disabled = false;
            isSingleProcessing = false;
        }
    }

    // ==================================================
    // BULK DOWNLOAD
    // ✅ Status update ඉවත් කළා
    // ==================================================
    async function downloadBulkCards() {
        if (isBulkProcessing) return;

        const selectedCheckboxes = document.querySelectorAll(
            '.student-select:checked'
        );

        if (selectedCheckboxes.length === 0) {
            showToast('Please select at least one student', 'error');
            return;
        }

        isBulkProcessing = true;

        const cards = [];

        for (const checkbox of selectedCheckboxes) {
            const studentKey = checkbox.value;

            const card = document.querySelector(
                `.student-card[data-id="${studentKey}"]`
            );

            if (card) {
                cards.push({
                    key: studentKey,
                    element: card.querySelector('.student-id-card'),
                    cardId: card.getAttribute('data-card-id'),
                });
            }
        }

        if (cards.length === 0) {
            isBulkProcessing = false;
            showToast('No valid cards found for download', 'error');
            return;
        }

        // Only cards never downloaded before go in the ZIP; the others need a
        // card fee and are downloaded one by one
        try {
            const res = await postJson(BULK_DOWNLOAD_URL, { ids: cards.map(c => Number(c.cardId)) });
            if (res.status !== 200) throw new Error(res.data.message || 'server error');

            const allowed = new Set((res.data.allowed || []).map(Number));
            const skipped = cards.filter(c => !allowed.has(Number(c.cardId)));
            cards.splice(0, cards.length, ...cards.filter(c => allowed.has(Number(c.cardId))));

            if (skipped.length) {
                showToast(`${skipped.length} card(s) skipped: already downloaded - download them one by one with the card fee (${skipped.map(c => c.key).join(', ')}).`, 'error');
            }
        } catch (error) {
            isBulkProcessing = false;
            showToast('Could not record the downloads: ' + error.message, 'error');
            return;
        }

        if (cards.length === 0) {
            isBulkProcessing = false;
            return;
        }

        const originalText = $bulkDownloadBtn.innerHTML;
        $bulkDownloadBtn.innerHTML =
            '<i class="fas fa-spinner fa-spin"></i> Generating ZIP...';
        $bulkDownloadBtn.disabled = true;

        const allOriginalStyles = [];

        try {
            const images = [];

            for (let i = 0; i < cards.length; i++) {
                $bulkDownloadBtn.innerHTML = `
                    <i class="fas fa-spinner fa-spin"></i>
                    Processing ${i + 1}/${cards.length}...
                `;

                const originalStyles = prepareCardForCapture(cards[i].element);
                allOriginalStyles.push({
                    element: cards[i].element,
                    styles: originalStyles,
                });

                cards[i].element.classList.add('html2canvas-capture');

                const canvas = await convertCardToCanvas(cards[i].element);

                images.push({
                    key: cards[i].key,
                    data: canvas.toDataURL('image/png'),
                });
            }

            // Build ZIP
            const zip = new JSZip();

            images.forEach(img => {
                const base64Data = img.data.split(',')[1];
                zip.file(`ID_${img.key}.png`, base64Data, { base64: true });
            });

            const zipBlob = await zip.generateAsync({ type: 'blob' });

            const filename =
                `ID_Cards_${new Date().toISOString().slice(0, 19).replace(/:/g, '-')}.zip`;

            const blobUrl = URL.createObjectURL(zipBlob);
            triggerDownload(blobUrl, filename);
            URL.revokeObjectURL(blobUrl);

            // ✅ Success message පමණයි
            showToast(`${cards.length} ID Cards downloaded successfully!`, 'success');

            // ❌ Bulk status update fetch call එක ඉවත් කළා

        } catch (error) {
            console.error('Bulk download error:', error);
            showToast('Bulk download failed: ' + error.message, 'error');
        } finally {
            allOriginalStyles.forEach(({ element, styles }) => {
                restoreCardStyles(element, styles);
                element.classList.remove('html2canvas-capture');
            });

            $bulkDownloadBtn.innerHTML = originalText;
            $bulkDownloadBtn.disabled = false;
            isBulkProcessing = false;
        }
    }

    // ==================================================
    // TOAST NOTIFICATION (Simple)
    // ==================================================
    function showToast(message, type = 'success') {
        // Simple alert fallback
        // SweetAlert2 භාවිතා කරන්නේ නම් replace කරන්න
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: type,
                title: type === 'success' ? 'Success' : 'Error',
                text: message,
                timer: type === 'success' ? 2000 : undefined,
                showConfirmButton: type !== 'success',
                confirmButtonColor: '#0f4bb5',
            });
        } else {
            // Fallback — simple alert
            if (type === 'error') {
                alert(message);
            } else {
                console.log('✅', message);
            }
        }
    }

    // ==================================================
    // UPDATE BULK ACTIONS UI
    // ==================================================
    function updateBulkActions() {
        const count = selectedStudents.size;
        const totalAvailable = document.querySelectorAll(
            '.student-select:not(:disabled)'
        ).length;

        if ($selectedCount) $selectedCount.textContent = count;
        if ($downloadCount) $downloadCount.textContent = count;
        if ($bulkDownloadBtn) $bulkDownloadBtn.disabled = count === 0;

        if ($selectedInfo) {
            if (count === 0) {
                $selectedInfo.textContent = 'Select cards to download in bulk';
            } else if (count === totalAvailable && totalAvailable > 0) {
                $selectedInfo.textContent = 'All available cards selected';
            } else {
                $selectedInfo.textContent =
                    `${count} of ${totalAvailable} available cards selected`;
            }
        }
    }

    // ==================================================
    // CHECKBOX HANDLERS
    // ==================================================
    document.querySelectorAll('.student-select').forEach(cb => {
        if (cb.disabled) return;

        cb.addEventListener('change', function () {
            const card = this.closest('.student-card');

            if (this.checked) {
                selectedStudents.add(this.value);
                if (card) card.classList.add('selected');
            } else {
                selectedStudents.delete(this.value);
                if (card) card.classList.remove('selected');
            }

            updateBulkActions();
        });
    });

    // ==================================================
    // SELECT ALL
    // ==================================================
    if ($selectAllBtn) {
        $selectAllBtn.addEventListener('click', () => {
            document.querySelectorAll('.student-select:not(:disabled)').forEach(cb => {
                cb.checked = true;
                selectedStudents.add(cb.value);

                const card = cb.closest('.student-card');
                if (card) card.classList.add('selected');
            });

            updateBulkActions();
        });
    }

    // ==================================================
    // DESELECT ALL
    // ==================================================
    if ($deselectAllBtn) {
        $deselectAllBtn.addEventListener('click', () => {
            document.querySelectorAll('.student-select').forEach(cb => {
                cb.checked = false;

                const card = cb.closest('.student-card');
                if (card) card.classList.remove('selected');
            });

            selectedStudents.clear();
            updateBulkActions();
        });
    }

    // ==================================================
    // SINGLE DOWNLOAD — Event Delegation
    // ==================================================
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.download-single-btn');
        if (!btn) return;

        e.preventDefault();
        e.stopPropagation();

        const cardId     = btn.getAttribute('data-card-id');
        const studentKey = btn.getAttribute('data-student-key');

        downloadSingleCard(cardId, studentKey, btn);
    });

    // ==================================================
    // BULK DOWNLOAD BUTTON
    // ==================================================
    if ($bulkDownloadBtn) {
        $bulkDownloadBtn.addEventListener('click', function (e) {
            e.preventDefault();
            downloadBulkCards();
        });
    }

    // ==================================================
    // INITIAL STATE
    // ==================================================
    updateBulkActions();
});
</script>
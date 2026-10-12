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
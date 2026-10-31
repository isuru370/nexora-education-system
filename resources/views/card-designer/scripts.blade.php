<script>
(function () {
    'use strict';

    const CARD = { w: {{ $design['width'] }}, h: {{ $design['height'] }} };
    const fields = @json($design['fields']);
    const defaults = @json($defaults);
    const EDITABLE = @json(\App\Support\CardDesign::EDITABLE);
    const PX_PER_MM = 96 / 25.4;

    const stage = document.getElementById('cdStage');
    const scaleBox = document.getElementById('cdScale');
    const card = document.getElementById('cdCard');
    const list = document.getElementById('cdList');
    const form = document.getElementById('cdForm');
    const propsBody = document.getElementById('cdPropsBody');
    const propsTitle = document.getElementById('cdPropsTitle');

    let scale = 1;
    let selected = null;

    const round = (v) => Math.round(v * 10) / 10;
    const clamp = (v, min, max) => Math.min(max, Math.max(min, v));
    const mm = (v) => round(v) + 'mm';
    const boxOf = (key) => card.querySelector('.nx-id-field[data-field="' + key + '"]');

    /* Same as CardDesign::style() / innerStyle() in PHP */
    function applyStyle(key) {
        const f = fields[key];
        const el = boxOf(key);
        if (!el) return;

        const border = f.frame ? 0.4 : 0;
        const s = el.style;
        s.left = mm(f.x);
        s.top = mm(f.y);
        s.width = mm(Math.max(0, f.w - 2 * border));
        s.height = mm(Math.max(0, f.h - 2 * border));
        s.background = f.bg || 'transparent';
        s.border = border ? mm(border) + ' solid ' + f.frame : 'none';
        s.borderRadius = f.radius > 0 ? mm(f.radius) : '0';

        if (f.kind === 'text') {
            s.fontSize = mm(f.size);
            s.color = f.color;
            s.fontWeight = f.bold ? '700' : '400';
            s.textAlign = f.align;
            s.textTransform = f.upper ? 'uppercase' : 'none';
            if (f.lines === 1) {
                s.whiteSpace = 'nowrap';
                s.lineHeight = mm(Math.max(0, f.h - 2 * border));
            } else {
                s.whiteSpace = 'normal';
                s.lineHeight = '1.2';
            }
            if (f.source === null) {
                const textNode = Array.from(el.childNodes).find((n) => n.nodeType === Node.TEXT_NODE);
                if (textNode) textNode.nodeValue = f.text;
                else el.insertBefore(document.createTextNode(f.text), el.firstChild);
            }
        } else {
            const img = el.querySelector('img');
            if (img) {
                const pad = Math.min(f.padding, (f.w - 2 * border) / 2.5, (f.h - 2 * border) / 2.5);
                img.style.left = mm(pad);
                img.style.top = mm(pad);
                img.style.width = mm(Math.max(0, f.w - 2 * border - 2 * pad));
                img.style.height = mm(Math.max(0, f.h - 2 * border - 2 * pad));
            }
        }

        el.classList.toggle('cd-off', !f.on);
    }

    function fitCard() {
        const available = stage.clientWidth - 48;
        const cardPx = CARD.w * PX_PER_MM;
        scale = Math.max(0.5, available / cardPx);
        card.style.transform = 'scale(' + scale + ')';
        scaleBox.style.width = (cardPx * scale) + 'px';
        scaleBox.style.height = (CARD.h * PX_PER_MM * scale) + 'px';
    }

    /* ---------------- Properties panel ---------------- */

    const input = (id) => document.getElementById(id);

    function showProps() {
        list.querySelectorAll('.list-group-item').forEach((li) => li.classList.toggle('active', li.dataset.field === selected));
        card.querySelectorAll('.nx-id-field').forEach((el) => el.classList.toggle('cd-active', el.dataset.field === selected));

        if (!selected) {
            propsBody.hidden = true;
            propsTitle.textContent = 'Select a field';
            return;
        }

        const f = fields[selected];
        propsBody.hidden = false;
        propsTitle.textContent = f.label + (f.on ? '' : ' (hidden)');

        ['x', 'y', 'w', 'h', 'size', 'radius', 'padding'].forEach((p) => input('p-' + p).value = round(f[p]));
        input('p-text').value = f.text || '';
        input('p-color').value = f.color || '#222222';
        input('p-align').value = f.align;
        input('p-bold').checked = !!f.bold;
        input('p-upper').checked = !!f.upper;
        input('p-bg-on').checked = !!f.bg;
        input('p-bg').value = f.bg || '#ffffff';
        input('p-frame-on').checked = !!f.frame;
        input('p-frame').value = f.frame || '#1d4ed8';

        propsBody.querySelectorAll('.cd-text-only').forEach((el) => el.hidden = f.kind !== 'text');
        propsBody.querySelectorAll('.cd-own-text').forEach((el) => el.hidden = !(f.kind === 'text' && f.source === null));
        propsBody.querySelectorAll('.cd-box-only').forEach((el) => el.hidden = f.kind === 'text');
    }

    function keepOnCard(f) {
        f.w = clamp(f.w, 1, CARD.w);
        f.h = clamp(f.h, 1, CARD.h);
        f.x = clamp(f.x, 0, CARD.w - f.w);
        f.y = clamp(f.y, 0, CARD.h - f.h);
    }

    function select(key) {
        selected = key;
        showProps();
    }

    propsBody.querySelectorAll('[data-prop]').forEach((el) => {
        el.addEventListener('input', () => {
            if (!selected) return;
            const f = fields[selected];
            const prop = el.dataset.prop;
            if (el.type === 'checkbox') f[prop] = el.checked;
            else if (el.type === 'number') f[prop] = parseFloat(el.value) || 0;
            else f[prop] = el.value;
            if (['x', 'y', 'w', 'h'].includes(prop)) keepOnCard(f);
            applyStyle(selected);
        });
    });

    [['p-bg-on', 'p-bg', 'bg'], ['p-frame-on', 'p-frame', 'frame']].forEach(([onId, colorId, prop]) => {
        const update = () => {
            if (!selected) return;
            fields[selected][prop] = input(onId).checked ? input(colorId).value : '';
            applyStyle(selected);
        };
        input(onId).addEventListener('change', update);
        input(colorId).addEventListener('input', () => { input(onId).checked = true; update(); });
    });

    input('cdFieldDefault').addEventListener('click', () => {
        if (!selected) return;
        const on = fields[selected].on;
        fields[selected] = Object.assign({}, defaults[selected], { on: on });
        applyStyle(selected);
        showProps();
    });

    /* ---------------- Field list ---------------- */

    list.addEventListener('click', (e) => {
        const li = e.target.closest('.list-group-item');
        if (!li) return;
        if (e.target.classList.contains('cd-on')) {
            fields[li.dataset.field].on = e.target.checked;
            applyStyle(li.dataset.field);
        }
        select(li.dataset.field);
    });

    /* ---------------- Drag and resize ---------------- */

    card.querySelectorAll('.nx-id-field').forEach((el) => {
        const handle = document.createElement('span');
        handle.className = 'cd-handle';
        el.appendChild(handle);

        el.addEventListener('pointerdown', (e) => {
            e.preventDefault();
            const key = el.dataset.field;
            select(key);

            const f = fields[key];
            const start = { x: e.clientX, y: e.clientY, fx: f.x, fy: f.y, fw: f.w, fh: f.h };
            const resizing = e.target === handle;
            el.setPointerCapture(e.pointerId);

            const move = (ev) => {
                const dx = (ev.clientX - start.x) / (scale * PX_PER_MM);
                const dy = (ev.clientY - start.y) / (scale * PX_PER_MM);
                if (resizing) {
                    f.w = clamp(round(start.fw + dx), 1, CARD.w - f.x);
                    f.h = clamp(round(start.fh + dy), 1, CARD.h - f.y);
                } else {
                    f.x = clamp(round(start.fx + dx), 0, CARD.w - f.w);
                    f.y = clamp(round(start.fy + dy), 0, CARD.h - f.h);
                }
                applyStyle(key);
                ['x', 'y', 'w', 'h'].forEach((p) => input('p-' + p).value = round(f[p]));
            };
            const up = () => {
                el.removeEventListener('pointermove', move);
                el.removeEventListener('pointerup', up);
                el.removeEventListener('pointercancel', up);
            };
            el.addEventListener('pointermove', move);
            el.addEventListener('pointerup', up);
            el.addEventListener('pointercancel', up);
        });
    });

    document.addEventListener('keydown', (e) => {
        if (!selected || !e.key.startsWith('Arrow')) return;
        if (['INPUT', 'SELECT', 'TEXTAREA'].includes(document.activeElement.tagName)) return;
        e.preventDefault();
        const step = e.shiftKey ? 0.1 : 0.5;
        const f = fields[selected];
        if (e.key === 'ArrowLeft') f.x -= step;
        if (e.key === 'ArrowRight') f.x += step;
        if (e.key === 'ArrowUp') f.y -= step;
        if (e.key === 'ArrowDown') f.y += step;
        f.x = round(f.x); f.y = round(f.y);
        keepOnCard(f);
        applyStyle(selected);
        showProps();
    });

    /* ---------------- Background ---------------- */

    const bgImg = () => card.querySelector('.nx-id-card-bg');

    document.getElementById('cdBackground').addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;
        let img = bgImg();
        if (!img) {
            img = document.createElement('img');
            img.className = 'nx-id-card-bg';
            img.alt = '';
            img.style.cssText = 'position:absolute;left:0;top:0;width:100%;height:100%;display:block;object-fit:cover;';
            card.insertBefore(img, card.firstChild);
        }
        img.src = URL.createObjectURL(file);
        document.getElementById('cdRemoveBg').value = '0';
    });

    const removeBtn = document.getElementById('cdRemoveBgBtn');
    if (removeBtn) {
        removeBtn.addEventListener('click', () => {
            document.getElementById('cdRemoveBg').value = '1';
            document.getElementById('cdBackground').value = '';
            const img = bgImg();
            if (img) img.src = @json(asset(\App\Support\CardDesign::DEFAULT_BACKGROUNDS[$type]));
            removeBtn.disabled = true;
            removeBtn.innerHTML = '<i class="bi bi-check"></i> Default template (save to keep)';
        });
    }

    /* ---------------- Save ---------------- */

    form.addEventListener('submit', () => {
        const out = {};
        Object.keys(fields).forEach((key) => {
            out[key] = {};
            EDITABLE.forEach((p) => { out[key][p] = fields[key][p]; });
        });
        document.getElementById('cdFields').value = JSON.stringify(out);
    });

    Object.keys(fields).forEach(applyStyle);
    fitCard();
    window.addEventListener('resize', fitCard);
})();
</script>

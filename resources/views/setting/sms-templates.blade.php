@extends('layouts.app')

@section('title', 'SMS Messages')
@section('page-title', 'SMS Messages')

@php
    $iconColors = ['icon-blue', 'icon-green', 'icon-violet', 'icon-amber', 'icon-red', 'icon-blue'];
    $jsTemplates = $templates->map(fn ($t) => [
        'default' => $t['default'],
        'placeholders' => $t['placeholders'],
        'required' => $t['required'],
    ]);
@endphp

@section('content')
<div class="dashboard-page">

    {{-- HERO --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge"><i class="bi bi-chat-dots-fill"></i> Settings</span>
                <h1 class="hero-title">SMS Messages</h1>
                <p class="hero-subtitle">
                    Change what the SMS to parents says. The counter shows how many SMS each message uses and
                    what it costs. SMS are sent in English only.
                </p>
            </div>
            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ route('admin.setting.notifications') }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-bell-fill"></i> Parent Notifications
                    </a>
                    <a href="{{ route('admin.setting.index') }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-arrow-left"></i> Settings
                    </a>
                </div>
                <small class="hero-updated">Applies to all branches</small>
            </div>
        </div>
    </div>

    {{-- ALERTS --}}
    @if (session('success'))
        <div class="nx-alert nx-alert-green mb-4">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="nx-alert nx-alert-red mb-4">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Test SMS: filled by the "Send test" buttons --}}
    <form id="sms-test-form" method="POST" action="{{ route('admin.setting.sms-templates.test') }}" class="d-none">
        @csrf
        <input type="hidden" name="key" value="">
        <input type="hidden" name="body" value="">
    </form>

    <form method="POST" action="{{ route('admin.setting.sms-templates.update') }}" id="sms-templates-form">
        @csrf
        @method('PUT')

        {{-- COST + TEST NUMBER --}}
        <div class="nx-card mb-4">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-calculator text-primary"></i> SMS cost</h5>
                    <p class="nx-card-subtitle">
                        1 SMS = {{ $limits['single'] }} characters. A longer message is sent in parts of
                        {{ $limits['part'] }} characters, and every part is charged as 1 SMS.
                    </p>
                </div>
                <button type="submit" class="nx-btn nx-btn-primary">
                    <i class="bi bi-save"></i> Save
                </button>
            </div>
            <div class="p-3">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4 col-lg-3">
                        <label class="nx-label" for="sms-cost">Price of 1 SMS (Rs.)</label>
                        <input type="number" step="0.01" min="0" max="100" id="sms-cost" name="cost"
                            class="form-control nx-input" value="{{ old('cost', number_format($cost, 2, '.', '')) }}" required>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <label class="nx-label" for="sms-test-mobile">Test SMS to (your number)</label>
                        <input type="text" id="sms-test-mobile" name="mobile" form="sms-test-form"
                            class="form-control nx-input" value="{{ old('mobile') }}" placeholder="07XXXXXXXX" inputmode="tel">
                    </div>
                    <div class="col-md-4 col-lg-6">
                        <small class="cell-sub d-block">
                            "Send test SMS" sends the text in the box (with the sample details) to this number.
                            Characters like <code>[ ] { } ~ ^ | \</code> count as 2.
                        </small>
                    </div>
                </div>
            </div>
        </div>

        {{-- TEMPLATES --}}
        @foreach ($templates as $key => $template)
            @php
                $body = old("templates.{$key}", $template['body']);
                $hasError = $errors->has("templates.{$key}");
            @endphp

            <div class="nx-card mb-4 sms-card" data-sms-card data-key="{{ $key }}">
                <div class="nx-card-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon-wrapper {{ $iconColors[$loop->index % count($iconColors)] }} sms-icon">
                            <i class="bi {{ $template['icon'] }}"></i>
                        </div>
                        <div>
                            <h5 class="nx-card-title mb-0">
                                {{ $template['label'] }}
                                @if ($template['custom'])
                                    <span class="nx-badge nx-badge-blue">Edited</span>
                                @else
                                    <span class="nx-badge nx-badge-gray">Default</span>
                                @endif
                            </h5>
                            <p class="nx-card-subtitle mb-0">{{ $template['when'] }}</p>
                            @if ($template['edited'] && $template['edited']->updatedBy)
                                <small class="cell-sub">Changed by {{ $template['edited']->updatedBy->name }},
                                    {{ $template['edited']->updated_at?->diffForHumans() }}</small>
                            @endif
                        </div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="nx-btn nx-btn-light nx-btn-sm" data-reset>
                            <i class="bi bi-arrow-counterclockwise"></i> Reset to default
                        </button>
                        <button type="submit" form="sms-test-form" class="nx-btn nx-btn-light nx-btn-sm" data-test>
                            <i class="bi bi-send"></i> Send test SMS
                        </button>
                    </div>
                </div>

                <div class="p-3">
                    <div class="row g-3">
                        <div class="col-lg-7">
                            <label class="nx-label" for="tpl-{{ $key }}">Message</label>
                            <textarea id="tpl-{{ $key }}" name="templates[{{ $key }}]" rows="9"
                                maxlength="{{ $limits['max'] }}" spellcheck="false"
                                class="form-control nx-input sms-textarea {{ $hasError ? 'is-invalid' : '' }}"
                                data-textarea>{{ $body }}</textarea>

                            <div class="sms-chips mt-2">
                                <small class="cell-sub me-1">Insert:</small>
                                @foreach (array_keys($template['placeholders']) as $placeholder)
                                    <button type="button" class="sms-chip {{ isset($template['required'][$placeholder]) ? 'is-required' : '' }}"
                                        data-insert="{{ $placeholder }}">{{ $placeholder }}</button>
                                @endforeach
                            </div>

                            @if ($template['required'])
                                <small class="cell-sub d-block mt-2">
                                    <i class="bi bi-lock-fill text-warning"></i>
                                    Must be in the text: <strong>{{ implode(', ', array_keys($template['required'])) }}</strong>.
                                    @if ($key === 'payment')
                                        The hall fee line is sent when the class hall price is not 0, and left out when it is 0.
                                    @endif
                                </small>
                            @endif
                            <small class="cell-sub d-block mt-1">
                                A line is left out when its detail is empty (for example no hall, no reason).
                            </small>
                        </div>

                        <div class="col-lg-5">
                            <label class="nx-label">Preview (sample student)</label>
                            <div class="sms-phone">
                                <div class="sms-bubble" data-preview></div>
                            </div>

                            <div class="sms-stats mt-2" data-stats>
                                <div><span data-chars>0</span><small>characters</small></div>
                                <div><span data-parts>0</span><small>SMS</small></div>
                                <div><span data-cost>0.00</span><small>Rs. / parent</small></div>
                                <div><span data-cost100>0</span><small>Rs. / 100 parents</small></div>
                            </div>
                            <div class="sms-bar mt-2"><div class="sms-bar-fill" data-bar></div></div>
                            <small class="cell-sub d-block mt-1" data-left></small>
                            <div class="sms-warn mt-2" data-warn hidden></div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        <div class="d-flex justify-content-end mb-4">
            <button type="submit" class="nx-btn nx-btn-primary">
                <i class="bi bi-save"></i> Save SMS Messages
            </button>
        </div>
    </form>

    <div class="nx-alert nx-alert-blue">
        <i class="bi bi-info-circle-fill"></i>
        <div>
            <strong>Good to know</strong>
            <ul class="mb-0 mt-1 ps-3">
                <li>Which messages go by SMS (or to the parent app) is chosen in <a href="{{ route('admin.setting.notifications') }}">Parent Notifications</a>.</li>
                <li>The preview uses a sample student. A longer student or class name adds characters, so a few parents may get one more SMS part.</li>
                <li>Sinhala / Tamil letters are removed before sending: an English SMS holds 160 characters, a Sinhala one only 70.</li>
                <li>Leave a box empty (or Reset to default) to use the default text.</li>
            </ul>
        </div>
    </div>

</div>
@endsection


@push('styles')
<style>
    .dashboard-page .sms-icon { width: 42px; height: 42px; font-size: 1.05rem; flex: 0 0 42px; }
    .dashboard-page .sms-card .nx-card-header { flex-wrap: wrap; gap: .75rem; }
    .dashboard-page .sms-textarea {
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: .86rem;
        line-height: 1.5;
        min-height: 200px;
    }
    .dashboard-page .sms-chips { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem; }
    .dashboard-page .sms-chip {
        border: 1px dashed var(--nx-border);
        background: var(--nx-head-bg);
        color: var(--nx-blue);
        border-radius: 999px;
        padding: .2rem .6rem;
        font-size: .74rem;
        font-weight: 700;
        cursor: pointer;
    }
    .dashboard-page .sms-chip:hover { border-style: solid; }
    .dashboard-page .sms-chip.is-required { border-color: #f59e0b; color: #b45309; }

    .dashboard-page .sms-phone {
        background: var(--nx-head-bg);
        border: 1px solid var(--nx-border);
        border-radius: 14px;
        padding: .9rem;
        min-height: 120px;
    }
    .dashboard-page .sms-bubble {
        background: var(--nx-card-bg);
        border-radius: 12px 12px 12px 4px;
        padding: .65rem .8rem;
        font-size: .84rem;
        line-height: 1.45;
        white-space: pre-wrap;
        word-break: break-word;
        box-shadow: var(--nx-shadow);
    }

    .dashboard-page .sms-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .5rem;
    }
    .dashboard-page .sms-stats > div {
        border: 1px solid var(--nx-border);
        border-radius: 10px;
        padding: .45rem .5rem;
        text-align: center;
    }
    .dashboard-page .sms-stats span { display: block; font-weight: 800; font-size: 1.05rem; }
    .dashboard-page .sms-stats small { color: var(--nx-muted); font-size: .68rem; }
    .dashboard-page .sms-stats.is-multi [data-parts],
    .dashboard-page .sms-stats.is-multi [data-cost] { color: #b45309; }

    .dashboard-page .sms-bar { height: 6px; border-radius: 99px; background: var(--nx-head-bg); overflow: hidden; }
    .dashboard-page .sms-bar-fill { height: 100%; width: 0; background: #16a34a; transition: width .15s ease; }
    .dashboard-page .sms-bar-fill.is-multi { background: #f59e0b; }

    .dashboard-page .sms-warn {
        font-size: .78rem;
        border-radius: 10px;
        padding: .45rem .65rem;
        background: #fef2f2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }

    @media (max-width: 575px) {
        .dashboard-page .sms-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
</style>
@endpush


@push('scripts')
<script>
(function () {
    // Same rules as App\Support\SmsText / SmsTemplates::render()
    const TEMPLATES = @json($jsTemplates);
    const SINGLE = {{ $limits['single'] }};
    const PART = {{ $limits['part'] }};
    const EXTENDED = ['^', '{', '}', '\\', '[', ']', '~', '|'];
    const REPLACE = {
        '\r\n': '\n', '\r': '\n', '\t': ' ', ' ': ' ',
        '‘': "'", '’': "'", '‚': "'", '“': '"', '”': '"', '„': '"',
        '–': '-', '—': '-', '•': '-', '…': '...', '`': "'",
    };

    const costInput = document.getElementById('sms-cost');

    function plain(text) {
        Object.keys(REPLACE).forEach(k => { text = text.split(k).join(REPLACE[k]); });
        return text;
    }

    function english(text) {
        text = plain(text).replace(/[^\x20-\x7E\n]/g, '');
        return text.replace(/ {2,}/g, ' ').replace(/ +\n/g, '\n').replace(/\n{3,}/g, '\n\n').trim();
    }

    function smsLength(text) {
        let length = text.length;
        for (const ch of text) { if (EXTENDED.includes(ch)) length++; }
        return length;
    }

    function smsParts(length) {
        if (length === 0) return 0;
        return length <= SINGLE ? 1 : Math.ceil(length / PART);
    }

    function insertLine(template, line, after) {
        const lines = template.split('\n');
        if (after) {
            const i = lines.findIndex(l => l.includes(after));
            if (i >= 0) { lines.splice(i + 1, 0, line); return lines.join('\n'); }
        }
        const last = lines.length - 1;
        if (last > 0 && /thank/i.test(lines[last])) lines.splice(last, 0, line); else lines.push(line);
        return lines.join('\n');
    }

    function render(key, template) {
        const def = TEMPLATES[key];
        const values = def.placeholders;
        template = template.replace(/\r\n?/g, '\n');

        Object.keys(def.required || {}).forEach(ph => {
            if (!template.includes(ph) && String(values[ph] || '') !== '') {
                template = insertLine(template, def.required[ph].line, def.required[ph].after);
            }
        });

        const out = [];
        template.split('\n').forEach(line => {
            const found = line.match(/\[[A-Za-z ]+\]/g) || [];
            const empty = found.some(ph => Object.prototype.hasOwnProperty.call(values, ph) && String(values[ph]).trim() === '');
            if (empty) return;
            out.push(line.replace(/\[[A-Za-z ]+\]/g, ph => Object.prototype.hasOwnProperty.call(values, ph) ? String(values[ph]).trim() : ph));
        });

        return english(out.join('\n'));
    }

    function money(n) {
        return n.toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function update(card) {
        const key = card.dataset.key;
        const def = TEMPLATES[key];
        const textarea = card.querySelector('[data-textarea]');
        const raw = textarea.value.trim() === '' ? def.default : textarea.value;

        const message = render(key, raw);
        const length = smsLength(message);
        const parts = smsParts(length);
        const price = Math.max(0, parseFloat(costInput.value) || 0);

        card.querySelector('[data-preview]').textContent = message || '(empty)';
        card.querySelector('[data-chars]').textContent = length;
        card.querySelector('[data-parts]').textContent = parts;
        card.querySelector('[data-cost]').textContent = money(parts * price);
        card.querySelector('[data-cost100]').textContent = money(parts * price * 100);
        card.querySelector('[data-stats]').classList.toggle('is-multi', parts > 1);

        const limit = parts <= 1 ? SINGLE : parts * PART;
        const bar = card.querySelector('[data-bar]');
        bar.style.width = Math.min(100, (length / limit) * 100) + '%';
        bar.classList.toggle('is-multi', parts > 1);
        card.querySelector('[data-left]').textContent = parts <= 1
            ? (SINGLE - length) + ' characters left in 1 SMS.'
            : (limit - length) + ' characters left before SMS ' + (parts + 1) + '. (1 SMS = up to ' + SINGLE + ' characters)';

        // problems (also checked when saving)
        const problems = [];
        if (/[^\x20-\x7E\n]/.test(plain(raw))) problems.push('Sinhala / Tamil or other non-English letters will be removed. Use English only.');
        const unknown = (raw.match(/\[[^\]\n]*\]/g) || []).filter(ph => !Object.prototype.hasOwnProperty.call(def.placeholders, ph));
        if (unknown.length) problems.push('Unknown placeholder(s): ' + [...new Set(unknown)].join(', ') + ' - use the buttons below the box.');
        Object.keys(def.required || {}).forEach(ph => { if (!raw.includes(ph)) problems.push(ph + ' must be in the text.'); });

        const warn = card.querySelector('[data-warn]');
        warn.hidden = problems.length === 0;
        warn.innerHTML = '';
        problems.forEach(p => { const d = document.createElement('div'); d.textContent = p; warn.appendChild(d); });
    }

    const cards = Array.from(document.querySelectorAll('[data-sms-card]'));

    cards.forEach(card => {
        const textarea = card.querySelector('[data-textarea]');
        const key = card.dataset.key;

        textarea.addEventListener('input', () => update(card));

        card.querySelectorAll('[data-insert]').forEach(btn => btn.addEventListener('click', () => {
            const text = btn.dataset.insert;
            const start = textarea.selectionStart ?? textarea.value.length;
            const end = textarea.selectionEnd ?? textarea.value.length;
            textarea.value = textarea.value.slice(0, start) + text + textarea.value.slice(end);
            textarea.focus();
            textarea.setSelectionRange(start + text.length, start + text.length);
            update(card);
        }));

        card.querySelector('[data-reset]').addEventListener('click', () => {
            textarea.value = TEMPLATES[key].default;
            update(card);
        });

        card.querySelector('[data-test]').addEventListener('click', () => {
            const form = document.getElementById('sms-test-form');
            form.querySelector('[name=key]').value = key;
            form.querySelector('[name=body]').value = textarea.value.trim() === '' ? TEMPLATES[key].default : textarea.value;
        });

        update(card);
    });

    costInput.addEventListener('input', () => cards.forEach(update));
})();
</script>
@endpush

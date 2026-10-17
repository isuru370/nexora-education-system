<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Contact Administrator - {{ config('app.name', 'Nexora Education') }}</title>

    {{-- Bootstrap --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Bootstrap Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    {{-- Google Font --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* ============================================================
           Contact Administrator — Nexora theme aligned
           Glassmorphism + two-panel identity preserved.
           ============================================================ */

        :root {
            --primary: #1d4ed8;
            --primary-2: #3b82f6;
            --dark-1: #020617;
            --dark-2: #0f172a;
            --dark-3: #1e3a8a;
            --panel: rgba(255, 255, 255, .08);
            --panel-border: rgba(255, 255, 255, .12);
            --text-soft: rgba(255, 255, 255, .72);
            --muted: #475569;
            --border: #d1d8e3;
            --success: #047857;
            --whatsapp: #16a34a;
            --facebook: #1877f2;
            --email: #6d28d9;
            --radius: 12px;
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            min-height: 100vh;
        }

        body {
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(29, 78, 216, .22), transparent 35%),
                radial-gradient(circle at bottom right, rgba(59, 130, 246, .16), transparent 35%),
                linear-gradient(135deg, var(--dark-1), var(--dark-2), var(--dark-3));
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            color: var(--text-soft);
        }

        .floating-bg {
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .floating-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, .05);
            animation: float 10s infinite ease-in-out;
        }

        .floating-circle:nth-child(1) {
            width: 220px; height: 220px; top: -60px; left: -60px;
        }
        .floating-circle:nth-child(2) {
            width: 170px; height: 170px; bottom: -50px; right: -30px; animation-delay: 2s;
        }
        .floating-circle:nth-child(3) {
            width: 120px; height: 120px; top: 50%; left: 12%; animation-delay: 1s;
        }
        .floating-circle:nth-child(4) {
            width: 90px; height: 90px; top: 18%; right: 18%; animation-delay: 3s;
        }

        @keyframes float {
            0%   { transform: translateY(0); }
            50%  { transform: translateY(-22px); }
            100% { transform: translateY(0); }
        }

        .contact-shell {
            width: 100%;
            max-width: 1180px;
            position: relative;
            z-index: 5;
        }

        .glass-card {
            border-radius: var(--radius);
            overflow: hidden;
            background: rgba(255, 255, 255, .08);
            border: 1px solid var(--panel-border);
            backdrop-filter: blur(22px);
            -webkit-backdrop-filter: blur(22px);
            box-shadow:
                0 20px 50px rgba(0, 0, 0, .45),
                inset 0 1px 0 rgba(255, 255, 255, .08);
        }

        /* ============================================================
           LEFT PANEL (dark)
           ============================================================ */
        .left-panel {
            position: relative;
            color: #fff;
            padding: 32px 24px;
            background: linear-gradient(180deg, rgba(15, 23, 42, .95), rgba(30, 41, 59, .92));
        }

        .left-panel::before {
            content: "";
            position: absolute;
            top: 0; right: 0;
            width: 0; height: 0;
            border-top: 120px solid rgba(255, 255, 255, .06);
            border-left: 120px solid transparent;
        }

        .profile-wrap { text-align: center; }

        .profile-image-wrapper {
            position: relative;
            width: 130px; height: 130px;
            margin: 0 auto 18px;
        }

        .profile-image {
            width: 130px; height: 130px;
            border-radius: 50%;
            object-fit: cover;
            object-position: center 5%;
            border: 3px solid rgba(255, 255, 255, .18);
            box-shadow: 0 18px 35px rgba(0, 0, 0, .28);
            background: #fff;
        }

        .online-badge {
            position: absolute;
            bottom: 8px; right: 8px;
            width: 30px; height: 30px;
            border-radius: 50%;
            background: var(--success);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid #1e293b;
            color: #fff;
            font-size: 11px;
        }

        .profile-name {
            font-size: 1.4rem;
            font-weight: 800;
            margin-bottom: 4px;
            line-height: 1.2;
        }

        .profile-role {
            font-size: .8rem;
            color: rgba(255, 255, 255, .74);
            margin-bottom: 14px;
        }

        .profile-description {
            margin-top: 14px;
            font-size: .8rem;
            line-height: 1.7;
            color: rgba(255, 255, 255, .74);
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .55rem .9rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, .10);
            border: 1px solid rgba(255, 255, 255, .08);
            color: #fff;
            font-weight: 600;
            font-size: .8rem;
        }

        .section-title {
            font-size: .8rem;
            font-weight: 800;
            color: rgba(255, 255, 255, .95);
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 22px 0 12px;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .section-title i {
            font-size: .9rem;
        }

        .divider {
            height: 1px;
            background: rgba(255, 255, 255, .14);
            margin: 10px 0 14px;
        }

        .info-card {
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: var(--radius);
            padding: 12px 14px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .info-icon {
            width: 40px; height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(180deg, #6d28d9, #5b21b6);
            flex: 0 0 40px;
        }

        .info-icon i {
            font-size: .85rem;
            color: #fff;
        }

        .info-label {
            font-size: .65rem;
            color: rgba(255, 255, 255, .68);
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: .04em;
            font-weight: 600;
        }

        .info-value {
            font-size: .8rem;
            font-weight: 700;
            color: #fff;
            line-height: 1.3;
        }

        .hours-box {
            display: flex;
            gap: 10px;
            margin-top: 12px;
            flex-wrap: wrap;
        }

        .hour-card {
            flex: 1;
            min-width: 120px;
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: var(--radius);
            padding: 12px;
            text-align: center;
            min-height: 62px;
        }

        .hour-title {
            font-size: .65rem;
            color: rgba(255, 255, 255, .72);
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: .04em;
            font-weight: 600;
        }

        .hour-value {
            font-size: .78rem;
            font-weight: 800;
            color: #fff;
        }

        .response-pill {
            margin-top: 16px;
            background: linear-gradient(90deg, #047857, #16a34a);
            color: #fff;
            border-radius: 999px;
            padding: 10px 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: .72rem;
            font-weight: 700;
        }

        .social-row {
            display: flex;
            gap: 12px;
            margin-top: 18px;
            justify-content: center;
        }

        .social-btn {
            width: 44px; height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            text-decoration: none;
            transition: transform .15s ease;
            box-shadow: 0 10px 20px rgba(0, 0, 0, .18);
        }

        .social-btn:hover {
            transform: translateY(-3px);
            color: #fff;
        }

        .facebook-btn { background: linear-gradient(135deg, #1877f2, #1d4ed8); }
        .whatsapp-btn { background: linear-gradient(135deg, #22c55e, #16a34a); }
        .email-btn    { background: linear-gradient(135deg, #7c3aed, #6d28d9); }

        /* ============================================================
           RIGHT PANEL (light)
           ============================================================ */
        .right-panel {
            background: #fff;
            padding: 30px 26px;
            color: #0f172a;
        }

        .form-title {
            font-size: 1.75rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .form-title i {
            color: var(--primary);
        }

        .form-subtitle {
            font-size: .82rem;
            color: var(--muted);
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .form-label {
            font-size: .72rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .form-control,
        .form-select {
            border: 1px solid var(--border);
            border-radius: var(--radius);
            min-height: 44px;
            font-size: .85rem;
            box-shadow: none !important;
            color: #0f172a;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(29, 78, 216, .12) !important;
        }

        textarea.form-control {
            min-height: 132px;
            resize: vertical;
        }

        .input-icon { position: relative; }

        .input-icon .icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: .85rem;
            z-index: 2;
        }

        .input-icon .form-control,
        .input-icon .form-select {
            padding-left: 36px;
        }

        .field-error {
            font-size: .72rem;
            color: #b91c1c;
            margin-top: 4px;
        }

        /* UPLOAD BOX */
        .upload-box {
            display: block;
            border: 1.5px dashed var(--border);
            border-radius: var(--radius);
            background: #f4f6fa;
            padding: 18px;
            text-align: center;
            transition: border-color .15s ease, background .15s ease;
            cursor: pointer;
        }

        .upload-box:hover {
            border-color: var(--primary);
            background: #eef3ff;
        }

        .upload-icon {
            width: 48px; height: 48px;
            border-radius: 50%;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #dbe6ff;
            color: var(--primary);
            font-size: 1.1rem;
        }

        .upload-text {
            font-size: .82rem;
            color: #334155;
            margin-bottom: 4px;
            font-weight: 600;
        }

        .upload-subtext {
            font-size: .68rem;
            color: var(--muted);
        }

        /* ACTION BUTTONS */
        .action-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .btn-action {
            height: 44px;
            border-radius: var(--radius);
            border: none;
            font-size: .82rem;
            font-weight: 700;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            transition: transform .15s ease;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            color: #fff;
        }

        .btn-call     { background: linear-gradient(90deg, #047857, #0e7490); }
        .btn-whatsapp { background: linear-gradient(90deg, #16a34a, #15803d); }

        .bottom-actions {
            display: grid;
            grid-template-columns: 1fr 1fr 1.35fr;
            gap: 10px;
        }

        .btn-soft {
            height: 42px;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            background: #fff;
            color: #475569;
            font-size: .82rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all .15s ease;
            cursor: pointer;
        }

        .btn-soft:hover {
            background: #f4f6fa;
            transform: translateY(-2px);
        }

        .btn-send {
            border: none;
            color: #fff;
            background: linear-gradient(90deg, #1d4ed8, #3b82f6);
            box-shadow: 0 12px 24px rgba(29, 78, 216, .25);
        }

        .btn-send:hover {
            opacity: .96;
            color: #fff;
        }

        /* ALERTS */
        .contact-alert {
            border-radius: var(--radius);
            padding: 1rem 1.25rem;
            margin-bottom: 1.25rem;
            font-size: .85rem;
            border: 1px solid transparent;
        }

        .contact-alert-success {
            background: #e9f8f2;
            border-color: rgba(4, 120, 87, .2);
            color: #047857;
        }

        .contact-alert-danger {
            background: #fdecec;
            border-color: rgba(185, 28, 28, .2);
            color: #b91c1c;
        }

        .contact-alert ul {
            margin: 0;
            padding-left: 1.25rem;
        }

        /* RESPONSIVE */
        @media (min-width: 992px) {
            .contact-shell .glass-card {
                min-height: 780px;
            }
        }

        @media (max-width: 991.98px) {
            body {
                overflow: auto;
                align-items: flex-start;
            }
            .left-panel {
                border-bottom-left-radius: 0;
                border-bottom-right-radius: 0;
            }
            .right-panel {
                padding-top: 18px;
            }
        }

        @media (max-width: 575.98px) {
            body { padding: 8px; }
            .contact-shell { border-radius: 14px; }
            .right-panel, .left-panel { padding: 18px 14px; }
            .profile-name { font-size: 1.1rem; }
            .action-row, .bottom-actions { grid-template-columns: 1fr; }
            .hours-box { flex-direction: column; }
            .form-title { font-size: 1.4rem; }
        }
    </style>
</head>

<body>

    <div class="floating-bg">
        <div class="floating-circle"></div>
        <div class="floating-circle"></div>
        <div class="floating-circle"></div>
        <div class="floating-circle"></div>
    </div>

    <div class="contact-shell">
        <div class="glass-card">
            <div class="row g-0">

                {{-- ===================================================== --}}
                {{-- LEFT PANEL --}}
                {{-- ===================================================== --}}
                <div class="col-lg-4 left-panel">
                    <div class="profile-wrap">
                        <div class="profile-image-wrapper">
                            <img src="{{ asset('storage/chief_administrative_officer_image/image.jpeg') }}"
                                alt="Chief Administrative Officer" class="profile-image"
                                onerror="this.onerror=null;this.src='{{ asset('storage/logo/black_logo3.png') }}'">
                            <div class="online-badge">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                        </div>

                        <div class="profile-name">Miss. Dhananjya Dharshani</div>
                        <div class="profile-role">Chief Administrative Officer</div>

                        <div class="profile-description">
                            Dedicated to supporting students, parents, and academic excellence through modern
                            educational administration and digital innovation.
                        </div>

                        <div class="social-row">
                            <a href="https://www.facebook.com/share/1DBMkR7WK5" target="_blank"
                                class="social-btn facebook-btn" aria-label="Facebook">
                                <i class="bi bi-facebook"></i>
                            </a>
                            <a href="https://wa.me/94766499254" target="_blank"
                                class="social-btn whatsapp-btn" aria-label="WhatsApp">
                                <i class="bi bi-whatsapp"></i>
                            </a>
                            <a href="mailto:info@nexorait.lk" class="social-btn email-btn" aria-label="Email">
                                <i class="bi bi-envelope-fill"></i>
                            </a>
                        </div>
                    </div>

                    <div class="section-title mt-4">
                        <i class="bi bi-person-lines-fill"></i> Contact Information
                    </div>
                    <div class="divider"></div>

                    <div class="info-card">
                        <div class="info-icon"><i class="bi bi-envelope-fill"></i></div>
                        <div>
                            <div class="info-label">Support Email</div>
                            <div class="info-value">info@nexorait.lk</div>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-icon"><i class="bi bi-telephone-fill"></i></div>
                        <div>
                            <div class="info-label">Hotline Number</div>
                            <div class="info-value">+94 76 89 71 213</div>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-icon"><i class="bi bi-whatsapp"></i></div>
                        <div>
                            <div class="info-label">WhatsApp</div>
                            <div class="info-value">+94 76 64 99 254</div>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-icon"><i class="bi bi-geo-alt-fill"></i></div>
                        <div>
                            <div class="info-label">Office Address</div>
                            <div class="info-value">Mirigama, Sri Lanka</div>
                        </div>
                    </div>

                    <div class="section-title mt-4">
                        <i class="bi bi-clock-fill"></i> Office Hours
                    </div>
                    <div class="divider"></div>

                    <div class="hours-box">
                        <div class="hour-card">
                            <div class="hour-title">Monday - Sunday</div>
                            <div class="hour-value">8:00 AM - 5:00 PM</div>
                        </div>
                        <div class="hour-card">
                            <div class="hour-title">Public Holidays</div>
                            <div class="hour-value">Closed</div>
                        </div>
                    </div>

                    <div class="response-pill">
                        <i class="bi bi-check-circle-fill"></i>
                        Typical response time: Within 24 hours
                    </div>
                </div>

                {{-- ===================================================== --}}
                {{-- RIGHT PANEL --}}
                {{-- ===================================================== --}}
                <div class="col-lg-8 right-panel">
                    <div class="form-title">
                        <i class="bi bi-send-fill"></i> Send a Message
                    </div>
                    <div class="form-subtitle">
                        Fill out the form below and we'll get back to you as soon as possible.
                    </div>

                    @if (session('success'))
                        <div class="contact-alert contact-alert-success">
                            <i class="bi bi-check-circle-fill me-1"></i>
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="contact-alert contact-alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('contact_administrator.send') }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf

                        <div class="row g-3">

                            {{-- FULL NAME --}}
                            <div class="col-md-6">
                                <label class="form-label">Full Name *</label>
                                <div class="input-icon">
                                    <i class="bi bi-person-fill icon"></i>
                                    <input type="text" name="full_name" class="form-control"
                                        placeholder="Enter your full name" value="{{ old('full_name') }}">
                                </div>
                                @error('full_name')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- EMAIL --}}
                            <div class="col-md-6">
                                <label class="form-label">Email Address *</label>
                                <div class="input-icon">
                                    <i class="bi bi-envelope-fill icon"></i>
                                    <input type="email" name="email" class="form-control"
                                        placeholder="Enter your email" value="{{ old('email') }}">
                                </div>
                                @error('email')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- PHONE --}}
                            <div class="col-md-6">
                                <label class="form-label">Phone Number *</label>
                                <div class="input-icon">
                                    <i class="bi bi-telephone-fill icon"></i>
                                    <input type="text" name="phone" class="form-control"
                                        placeholder="Enter your phone number" value="{{ old('phone') }}">
                                </div>
                                @error('phone')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- SUBJECT --}}
                            <div class="col-md-6">
                                <label class="form-label">Subject *</label>
                                <div class="input-icon">
                                    <i class="bi bi-tag-fill icon"></i>
                                    <select name="subject" class="form-select">
                                        <option value="">Select a subject</option>
                                        <option value="Admission" {{ old('subject') === 'Admission' ? 'selected' : '' }}>Admission</option>
                                        <option value="Course Inquiry" {{ old('subject') === 'Course Inquiry' ? 'selected' : '' }}>Course Inquiry</option>
                                        <option value="Payment Issue" {{ old('subject') === 'Payment Issue' ? 'selected' : '' }}>Payment Issue</option>
                                        <option value="Technical Support" {{ old('subject') === 'Technical Support' ? 'selected' : '' }}>Technical Support</option>
                                        <option value="Other" {{ old('subject') === 'Other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                </div>
                                @error('subject')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- MESSAGE --}}
                            <div class="col-12">
                                <label class="form-label">Message *</label>
                                <textarea name="message" class="form-control"
                                    placeholder="Type your message here...">{{ old('message') }}</textarea>
                                @error('message')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- ATTACHMENT --}}
                            <div class="col-12">
                                <label class="form-label">Attachment (Optional)</label>
                                <input type="file" name="attachment" class="form-control d-none" id="attachmentInput">
                                <label for="attachmentInput" class="upload-box w-100">
                                    <div class="upload-icon">
                                        <i class="bi bi-cloud-arrow-up-fill"></i>
                                    </div>
                                    <div class="upload-text">Click to upload or drag and drop</div>
                                    <div class="upload-subtext">Supported: PDF, DOC, DOCX, JPG, PNG (Max: 5MB)</div>
                                </label>
                                @error('attachment')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- QUICK ACTIONS --}}
                            <div class="col-12">
                                <div class="action-row">
                                    <a href="tel:+94766499254" class="btn-action btn-call">
                                        <i class="bi bi-telephone-fill"></i> Call Now
                                    </a>
                                    <a href="https://wa.me/94766499254" target="_blank" class="btn-action btn-whatsapp">
                                        <i class="bi bi-whatsapp"></i> WhatsApp
                                    </a>
                                </div>
                            </div>

                            {{-- FORM ACTIONS --}}
                            <div class="col-12 mt-2">
                                <div class="bottom-actions">
                                    <button type="reset" class="btn-soft">
                                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                                    </button>

                                    <button type="button" class="btn-soft"
                                        onclick="document.getElementById('attachmentInput').value='';">
                                        <i class="bi bi-eraser-fill"></i> Clear
                                    </button>

                                    <button type="submit" class="btn-soft btn-send">
                                        <i class="bi bi-send-fill"></i> Send Message
                                    </button>
                                </div>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</body>

</html>
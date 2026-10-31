{{--
    Blank A4 student registration form (permanent ID card institutes):
    print it, the parent fills it in, then the cashier enters it on
    Student Registration in the same order.
--}}
@php
    $institute = \App\Support\InstituteInfo::name();
    $tagline = \App\Support\InstituteInfo::tagline();
    $logo = \App\Support\InstituteInfo::logoUrl();
    $contactMobile = config('services.institute.contact_mobile');
    $contactEmail = config('services.institute.contact_email');
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Registration Form - {{ $institute }}</title>
    <style>
        @page { size: A4; margin: 8mm 11mm; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            color: #0f172a;
            background: #e2e8f0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .toolbar {
            position: sticky; top: 0; z-index: 5;
            display: flex; gap: 8px; justify-content: center; align-items: center; flex-wrap: wrap;
            padding: 10px 16px; background: #0f172a; color: #fff;
        }
        .toolbar button, .toolbar a {
            font: inherit; font-weight: 600; border: 0; border-radius: 8px; padding: 8px 16px;
            cursor: pointer; text-decoration: none; background: #2563eb; color: #fff;
        }
        .toolbar a { background: #334155; }
        .toolbar small { color: #cbd5e1; }

        .sheet {
            width: 210mm; min-height: 297mm; margin: 16px auto; padding: 10mm 12mm;
            background: #fff; box-shadow: 0 10px 30px rgba(15, 23, 42, .18);
        }

        /* Header */
        .head { display: flex; align-items: center; gap: 4mm; border-bottom: 2.5px solid #1d4ed8; padding-bottom: 2mm; }
        .head img { width: 18mm; height: 18mm; object-fit: contain; }
        .head .inst { flex: 1; }
        .head .inst-name { font-size: 15pt; font-weight: 800; color: #1e3a8a; line-height: 1.15; }
        .head .inst-sub { font-size: 8.5pt; color: #475569; margin-top: 1mm; }
        .head .photo {
            width: 24mm; height: 28mm; border: 1.2px dashed #64748b; border-radius: 2mm;
            display: flex; align-items: center; justify-content: center; text-align: center;
            font-size: 8pt; color: #64748b; padding: 2mm;
        }
        .title-row { display: flex; justify-content: space-between; align-items: flex-end; margin: 2mm 0 .5mm; }
        .title { font-size: 13pt; font-weight: 800; letter-spacing: .3px; }
        .form-no { font-size: 9pt; color: #475569; }
        .hint { font-size: 8.3pt; color: #475569; margin: 0 0 1mm; }

        /* Sections */
        .section { margin-top: 1.8mm; border: 1px solid #cbd5e1; border-radius: 2mm; overflow: hidden; break-inside: avoid; }
        .section-title {
            background: #1e3a8a; color: #fff; font-weight: 700; font-size: 9.5pt;
            padding: 1.1mm 3mm; letter-spacing: .3px;
        }
        .section-body { padding: .6mm 3mm 1.6mm; }
        .row { display: flex; gap: 4mm; }
        .field { flex: 1; margin-top: 1mm; min-width: 0; }
        .field label { display: block; font-size: 7.8pt; font-weight: 700; color: #334155; margin-bottom: .6mm; }
        .field label .req { color: #dc2626; }
        .field label .opt { font-weight: 400; color: #64748b; }
        .line { border-bottom: 1px solid #475569; height: 5.3mm; }
        .boxes { display: flex; gap: 0; }
        .boxes span { width: 5.2mm; height: 5.6mm; border: 1px solid #475569; border-left-width: 0; }
        .boxes span:first-child { border-left-width: 1px; }
        .boxes .gap { border: 0; width: 2mm; }
        .ticks { display: flex; gap: 5mm; flex-wrap: wrap; padding-top: 1mm; font-size: 9.5pt; }
        .ticks span::before {
            content: ''; display: inline-block; width: 3.6mm; height: 3.6mm; border: 1px solid #475569;
            border-radius: .6mm; margin-right: 1.4mm; vertical-align: -0.6mm;
        }
        .w-2 { flex: 2; } .w-3 { flex: 3; } .w-auto { flex: 0 0 auto; }

        table.classes { width: 100%; border-collapse: collapse; margin-top: 2mm; }
        table.classes th, table.classes td { border: 1px solid #94a3b8; padding: 1.2mm 2mm; text-align: left; }
        table.classes th { background: #f1f5f9; font-size: 8.3pt; }
        table.classes td { height: 6mm; padding-top: 0; padding-bottom: 0; }

        .declaration { font-size: 8.8pt; line-height: 1.45; color: #1e293b; }
        .sign-row { display: flex; gap: 8mm; margin-top: 5mm; }
        .sign-row div { flex: 1; text-align: center; font-size: 8.3pt; color: #334155; border-top: 1px dotted #334155; padding-top: 1mm; }

        .office { background: #f8fafc; border-style: dashed; }
        .office .section-title { background: #475569; }

        .foot { margin-top: 2mm; font-size: 7.8pt; color: #64748b; text-align: center; }

        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { width: auto; min-height: auto; margin: 0; padding: 0; box-shadow: none; }
        }
        @media (max-width: 820px) {
            .sheet { width: auto; margin: 0; padding: 16px; }
        }
    </style>
</head>

<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print / Save as PDF</button>
        <a href="{{ route('student-card-registration.create') }}">Back to Registration</a>
        <small>A4, one page. In the print window choose "Save as PDF" to keep a copy.</small>
    </div>

    <div class="sheet">
        {{-- ================= Header ================= --}}
        <div class="head">
            @if ($logo)
                <img src="{{ $logo }}" alt="Logo" onerror="this.style.display='none'">
            @endif
            <div class="inst">
                <div class="inst-name">{{ $institute }}</div>
                @if ($tagline)
                    <div class="inst-sub">{{ $tagline }}</div>
                @endif
                @if ($contactMobile || $contactEmail)
                    <div class="inst-sub">{{ collect([$contactMobile, $contactEmail])->filter()->implode('  |  ') }}</div>
                @endif
            </div>
            <div class="photo">Paste a recent<br>passport-size<br>photo here<br>(optional)</div>
        </div>

        <div class="title-row">
            <div class="title">STUDENT REGISTRATION FORM</div>
            <div class="form-no">Date: ______ / ______ / __________</div>
        </div>
        <p class="hint">Please write clearly in CAPITAL letters. Fields marked <strong style="color:#dc2626">*</strong> are required.</p>

        {{-- ================= Student ================= --}}
        <div class="section">
            <div class="section-title">1. STUDENT DETAILS</div>
            <div class="section-body">
                <div class="field">
                    <label>Full Name <span class="req">*</span></label>
                    <div class="line"></div>
                </div>
                <div class="row">
                    <div class="field w-2">
                        <label>Name with Initials <span class="req">*</span> <span class="opt">(e.g. K.M.S. PERERA)</span></label>
                        <div class="line"></div>
                    </div>
                    <div class="field">
                        <label>Gender <span class="req">*</span></label>
                        <div class="ticks"><span>Male</span><span>Female</span></div>
                    </div>
                </div>
                <div class="row">
                    <div class="field w-auto">
                        <label>Date of Birth <span class="opt">(DD / MM / YYYY)</span></label>
                        <div class="boxes"><span></span><span></span><i class="gap"></i><span></span><span></span><i class="gap"></i><span></span><span></span><span></span><span></span></div>
                    </div>
                    <div class="field">
                        <label>Student NIC <span class="opt">(if any)</span></label>
                        <div class="boxes">@for ($i = 0; $i < 12; $i++)<span></span>@endfor</div>
                    </div>
                </div>
                <div class="row">
                    <div class="field w-2">
                        <label>School</label>
                        <div class="line"></div>
                    </div>
                    <div class="field">
                        <label>Grade <span class="req">*</span></label>
                        <div class="line"></div>
                    </div>
                </div>
                <div class="row">
                    <div class="field w-auto">
                        <label>Student Mobile <span class="req">*</span></label>
                        <div class="boxes">@for ($i = 0; $i < 10; $i++)<span></span>@endfor</div>
                    </div>
                    <div class="field w-auto">
                        <label>WhatsApp Number <span class="opt">(if different)</span></label>
                        <div class="boxes">@for ($i = 0; $i < 10; $i++)<span></span>@endfor</div>
                    </div>
                </div>
                <div class="field">
                    <label>Address <span class="req">*</span></label>
                    <div class="line"></div>
                </div>
                <div class="row">
                    <div class="field"><div class="line"></div></div>
                    <div class="field"><div class="line"></div></div>
                </div>
                <div class="row">
                    <div class="field">
                        <label>Email <span class="opt">(optional)</span></label>
                        <div class="line"></div>
                    </div>
                    <div class="field">
                        <label>Class Type <span class="req">*</span></label>
                        <div class="ticks"><span>Physical (Offline)</span><span>Online</span><span>Both (Hybrid)</span></div>
                    </div>
                    @if ($branches->count() > 1)
                        <div class="field">
                            <label>Branch <span class="req">*</span></label>
                            <div class="ticks">
                                @foreach ($branches as $branch)
                                    <span>{{ $branch->name }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ================= Guardian ================= --}}
        <div class="section">
            <div class="section-title">2. PARENT / GUARDIAN DETAILS</div>
            <div class="section-body">
                <div class="row">
                    <div class="field"><label>First Name</label><div class="line"></div></div>
                    <div class="field"><label>Last Name</label><div class="line"></div></div>
                </div>
                <div class="row">
                    <div class="field w-auto">
                        <label>Parent / Guardian Mobile <span class="req">*</span></label>
                        <div class="boxes">@for ($i = 0; $i < 10; $i++)<span></span>@endfor</div>
                    </div>
                    <div class="field">
                        <label>Parent / Guardian NIC</label>
                        <div class="boxes">@for ($i = 0; $i < 12; $i++)<span></span>@endfor</div>
                    </div>
                </div>
                <p class="hint" style="margin:2mm 0 0">
                    Payment, attendance SMS and the Parent Portal login are sent to this mobile number.
                </p>
            </div>
        </div>

        {{-- ================= Classes ================= --}}
        <div class="section">
            <div class="section-title">3. CLASSES TO JOIN</div>
            <div class="section-body">
                <table class="classes">
                    <thead>
                        <tr><th style="width:6%">#</th><th>Subject</th><th style="width:32%">Teacher</th><th style="width:24%">Day &amp; Time</th></tr>
                    </thead>
                    <tbody>
                        @for ($i = 1; $i <= 3; $i++)
                            <tr><td>{{ $i }}</td><td></td><td></td><td></td></tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ================= Declaration ================= --}}
        <div class="section">
            <div class="section-title">4. DECLARATION</div>
            <div class="section-body">
                <p class="declaration" style="margin:2mm 0 0">
                    I confirm that the details above are correct. I agree to follow the rules of {{ $institute }},
                    to pay the class fees on time, and that the student ID card must be shown for attendance and
                    payments. A lost card is replaced for a fee.
                </p>
                <div class="sign-row">
                    <div>Student's Signature</div>
                    <div>Parent / Guardian's Signature</div>
                    <div>Date</div>
                </div>
            </div>
        </div>

        {{-- ================= Office use ================= --}}
        <div class="section office">
            <div class="section-title">FOR OFFICE USE ONLY</div>
            <div class="section-body">
                <div class="row">
                    <div class="field"><label>Student ID</label><div class="line"></div></div>
                    <div class="field"><label>ID Card No. (QR)</label><div class="line"></div></div>
                    <div class="field"><label>Receipt No.</label><div class="line"></div></div>
                    <div class="field"><label>Registered By / Date</label><div class="line"></div></div>
                </div>
                <div class="ticks" style="margin-top:2mm;gap:4mm;font-size:9pt">
                    <span>Admission paid Rs. ________</span><span>Not paid</span>
                    <span>Photo taken</span><span>Card given</span><span>Entered in system</span>
                </div>
            </div>
        </div>

        <div class="foot">{{ $institute }} &middot; Student Registration Form</div>
    </div>
</body>

</html>

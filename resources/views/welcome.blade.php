@php
    $brand = 'NexoraIT EDU';
    $demoEmail = config('demo.email');
    $demoPassword = config('demo.password');
    $demoUrl = config('demo.url') ?: route('login', $demoEmail ? ['demo' => 1] : []);
    $phone = config('institution.phone');
    $email = config('institution.email');

    $webFeatures = [
        ['fa-user-graduate', 'Student registration', 'Full profiles with photo, guardian details, grade and school. Temporary QR for new students until the permanent card is ready.'],
        ['fa-chalkboard-user', 'Classes and fees', 'Classes per teacher, subject and grade, with fee categories and fee options such as normal, half or free card.'],
        ['fa-calendar-days', 'Schedules and halls', 'Weekly timetable patterns, class halls, today\'s classes, and cancelled or rescheduled days.'],
        ['fa-clipboard-check', 'Attendance reports', 'Daily attendance, monthly class attendance and per-student history, ready as PDF.'],
        ['fa-receipt', 'Payments and receipts', 'Monthly class fees, admission fees, receipt numbers, reprints and payment history for every student.'],
        ['fa-bell', 'Payment reminders', 'See who has not paid this month and send SMS reminders to parents in one go.'],
        ['fa-coins', 'Teacher salaries', 'Teacher and organizer shares worked out from each payment, with advances, deductions and payslips.'],
        ['fa-chart-line', 'Income and expenses', 'Daily collection, monthly reports, institute expenses and extra income in one place.'],
        ['fa-id-card', 'Student ID cards', 'Card stock per branch, card assignment and reissue, and printable card sheets.'],
        ['fa-code-branch', 'Branches and users', 'Run several branches. Each staff member only sees the branches and pages they are allowed to.'],
        ['fa-envelope-open-text', 'SMS and app notifications', 'Parents get a message when attendance is marked or a payment is made.'],
        ['fa-clock-rotate-left', 'Activity log', 'Every change is recorded with who did it and when.'],
    ];

    $mobileFeatures = [
        ['fa-qrcode', 'Scan attendance at the door', 'Scan the student card. Attendance is marked for the class running right now, and you see this month\'s payment status straight away.'],
        ['fa-hand-holding-dollar', 'Collect fees', 'Pick the classes and months, take the payment, done. Paid, partly paid and free card students are shown clearly.'],
        ['fa-print', 'Print receipts by Bluetooth', 'Receipts print on a pocket thermal printer the moment a payment is saved.'],
        ['fa-camera', 'Register with a photo', 'Add a new student, take their photo, collect the admission fee and print the receipt.'],
        ['fa-layer-group', 'Add students to classes', 'Scan a card and add the student to any class for their grade in your branch.'],
        ['fa-book-open', 'Issue tutes', 'Mark tutes as given each month so no student is missed or given twice.'],
        ['fa-gauge-high', 'Today at a glance', 'Students, classes running today, today\'s collection and alerts, for the branch you choose.'],
        ['fa-person-chalkboard', 'Teacher view', 'Teachers see their own classes, schedules and attendance.'],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $brand }} | Institute management system</title>
    <meta name="description"
        content="{{ $brand }} runs tuition classes and institutes: student cards, QR attendance, fee collection with printed receipts, teacher salaries and reports, on the web and on a mobile app.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=Figtree:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --ink: #0E1A3A;
            --ink-2: #1B2A57;
            --blue: #2453E6;
            --blue-soft: #DCE5FF;
            --mist: #EEF2FA;
            --paper: #FFFFFF;
            --text: #1A2340;
            --muted: #5A6482;
            --line: #D9E0EE;
            --mint: #12B886;
            --amber: #E8930C;
            --radius-lg: 28px;
            --radius-md: 16px;
            --radius-sm: 10px;
            --display: 'Bricolage Grotesque', 'Figtree', system-ui, sans-serif;
            --body: 'Figtree', system-ui, sans-serif;
            --mono: 'JetBrains Mono', ui-monospace, monospace;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            font-family: var(--body);
            font-size: 17px;
            line-height: 1.6;
            color: var(--text);
            background: var(--paper);
            -webkit-font-smoothing: antialiased;
        }

        a {
            color: inherit;
        }

        :focus-visible {
            outline: 3px solid #8FB0FF;
            outline-offset: 3px;
            border-radius: 8px;
        }

        .wrap {
            width: min(1180px, 100% - 40px);
            margin-inline: auto;
        }

        h1,
        h2,
        h3 {
            font-family: var(--display);
            line-height: 1.08;
            margin: 0;
            letter-spacing: -0.02em;
        }

        /* ---------- buttons ---------- */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 15px 26px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 16px;
            text-decoration: none;
            border: 2px solid transparent;
            transition: transform .15s ease, background-color .15s ease, border-color .15s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-demo {
            background: var(--mint);
            color: #fff;
        }

        .btn-demo:hover {
            background: #0EA676;
        }

        .btn-ghost {
            border-color: rgba(255, 255, 255, .35);
            color: #fff;
        }

        .btn-ghost:hover {
            border-color: #fff;
        }

        .btn-dark {
            background: var(--ink);
            color: #fff;
        }

        /* ---------- header ---------- */
        .site-header {
            position: absolute;
            inset: 0 0 auto 0;
            z-index: 5;
            padding: 22px 0;
        }

        .site-header .wrap {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #fff;
            text-decoration: none;
            font-family: var(--display);
            font-weight: 800;
            font-size: 21px;
        }

        .logo-mark {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: var(--blue);
            font-size: 19px;
        }

        .nav {
            display: flex;
            align-items: center;
            gap: 28px;
        }

        .nav a {
            color: #C9D4F2;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
        }

        .nav a:hover {
            color: #fff;
        }

        .nav .btn {
            padding: 10px 20px;
            font-size: 15px;
            color: #fff;
        }

        /* ---------- hero ---------- */
        .hero {
            position: relative;
            background:
                radial-gradient(900px 500px at 85% 20%, rgba(36, 83, 230, .45), transparent 60%),
                var(--ink);
            color: #fff;
            padding: 150px 0 110px;
            overflow: hidden;
        }

        .hero .wrap {
            display: grid;
            grid-template-columns: 1.05fr .95fr;
            gap: 56px;
            align-items: center;
        }

        .hero h1 {
            font-size: clamp(42px, 6vw, 76px);
            font-weight: 800;
            max-width: 12ch;
        }

        .hero-lead {
            margin: 24px 0 36px;
            font-size: 20px;
            color: #C9D4F2;
            max-width: 34em;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
        }

        .demo-creds {
            margin-top: 22px;
            font-size: 14px;
            color: #9FB0DA;
        }

        .demo-creds code {
            font-family: var(--mono);
            color: #fff;
            background: rgba(255, 255, 255, .08);
            padding: 2px 8px;
            border-radius: 6px;
        }

        /* scene: phone + receipt */
        .scene {
            position: relative;
            height: 560px;
        }

        .phone {
            position: absolute;
            left: 4%;
            top: 0;
            width: 290px;
            height: 560px;
            border-radius: 44px;
            background: #0A1330;
            padding: 12px;
            box-shadow: 0 40px 80px rgba(0, 0, 0, .45), inset 0 0 0 2px #2A3A6E;
        }

        .screen {
            height: 100%;
            border-radius: 34px;
            background: var(--paper);
            color: var(--text);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .screen-cam {
            position: relative;
            height: 44%;
            background: linear-gradient(160deg, #24304F, #11182E);
            display: grid;
            place-items: center;
        }

        .scan-frame {
            position: relative;
            width: 128px;
            height: 128px;
            border-radius: 18px;
            border: 3px solid var(--mint);
            display: grid;
            place-items: center;
            color: #fff;
            font-size: 64px;
            overflow: hidden;
        }

        .scan-frame::after {
            content: '';
            position: absolute;
            left: 8px;
            right: 8px;
            height: 2px;
            background: var(--mint);
            box-shadow: 0 0 14px var(--mint);
            top: 50%;
            animation: scanline 1.2s ease-in-out 2 alternate both;
        }

        .scan-toast {
            position: absolute;
            bottom: 14px;
            padding: 6px 14px;
            border-radius: 999px;
            background: var(--mint);
            color: #fff;
            font-size: 12px;
            font-weight: 700;
        }

        .screen-sheet {
            flex: 1;
            padding: 16px 16px 18px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .stu {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .avatar {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: var(--blue-soft);
            color: var(--blue);
            display: grid;
            place-items: center;
            font-weight: 800;
            font-family: var(--display);
        }

        .stu b {
            display: block;
            font-size: 16px;
        }

        .stu small {
            color: var(--muted);
            font-size: 12px;
        }

        .chips {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .chip {
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 8px 10px;
            font-size: 11px;
            color: var(--muted);
        }

        .chip strong {
            display: block;
            font-size: 15px;
            color: var(--text);
        }

        .chip.ok {
            border-color: #A8E6CF;
            background: #EAFBF4;
        }

        .chip.ok strong {
            color: #0B8A63;
        }

        .phone-btn {
            margin-top: auto;
            text-align: center;
            padding: 11px;
            border-radius: 12px;
            background: var(--blue);
            color: #fff;
            font-weight: 700;
            font-size: 14px;
        }

        .receipt {
            position: absolute;
            right: 0;
            top: 70px;
            width: 250px;
            background: #fff;
            color: #222;
            font-family: var(--mono);
            font-size: 12px;
            line-height: 1.55;
            padding: 22px 20px 30px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, .35);
            transform: rotate(3deg);
            transform-origin: top center;
            clip-path: polygon(0 0, 100% 0, 100% 100%, 95% 97%, 90% 100%, 85% 97%, 80% 100%, 75% 97%, 70% 100%, 65% 97%, 60% 100%, 55% 97%, 50% 100%, 45% 97%, 40% 100%, 35% 97%, 30% 100%, 25% 97%, 20% 100%, 15% 97%, 10% 100%, 5% 97%, 0 100%);
            animation: print 1.4s cubic-bezier(.2, .8, .2, 1) 1.1s both;
        }

        .receipt .c {
            text-align: center;
        }

        .receipt .row {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }

        .receipt hr {
            border: 0;
            border-top: 1px dashed #999;
            margin: 10px 0;
        }

        .receipt .total {
            font-weight: 600;
            font-size: 15px;
        }

        @keyframes scanline {
            from {
                top: 14%;
            }

            to {
                top: 86%;
            }
        }

        @keyframes print {
            from {
                clip-path: inset(0 0 100% 0);
                transform: rotate(3deg) translateY(-30px);
            }

            to {
                transform: rotate(3deg) translateY(0);
            }
        }

        /* ---------- day flow ---------- */
        .flow {
            padding: 96px 0 40px;
        }

        .section-head {
            max-width: 720px;
            margin-bottom: 48px;
        }

        .section-head h2 {
            font-size: clamp(32px, 4vw, 48px);
            font-weight: 800;
            color: var(--ink);
        }

        .section-head p {
            margin: 16px 0 0;
            color: var(--muted);
            font-size: 19px;
        }

        .steps {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            counter-reset: step;
            border-top: 2px solid var(--ink);
        }

        .steps li {
            counter-increment: step;
            padding: 22px 22px 0 0;
        }

        .steps li::before {
            content: counter(step);
            display: grid;
            place-items: center;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: var(--ink);
            color: #fff;
            font-family: var(--display);
            font-weight: 700;
            margin: -40px 0 16px;
        }

        .steps b {
            display: block;
            font-size: 18px;
            color: var(--ink);
            margin-bottom: 6px;
        }

        .steps span {
            color: var(--muted);
            font-size: 15px;
        }

        /* ---------- features ---------- */
        .features {
            padding: 80px 0;
        }

        .split {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 56px;
            padding: 56px 0;
            border-top: 1px solid var(--line);
        }

        .split-intro {
            position: sticky;
            top: 30px;
            align-self: start;
        }

        .platform {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            color: var(--blue);
            margin-bottom: 14px;
        }

        .platform i {
            font-size: 20px;
        }

        .split-intro h3 {
            font-size: 32px;
            color: var(--ink);
        }

        .split-intro p {
            color: var(--muted);
            margin: 12px 0 0;
        }

        .feature-list {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 34px 40px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .feature-list li {
            display: grid;
            grid-template-columns: 46px 1fr;
            gap: 16px;
        }

        .ico {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: var(--mist);
            color: var(--blue);
            font-size: 19px;
        }

        .mobile .ico {
            background: #E3F8F0;
            color: #0B8A63;
        }

        .mobile .platform {
            color: #0B8A63;
        }

        .feature-list b {
            display: block;
            color: var(--ink);
            font-size: 18px;
            margin-bottom: 4px;
        }

        .feature-list p {
            margin: 0;
            color: var(--muted);
            font-size: 15.5px;
        }

        /* ---------- roles ---------- */
        .roles {
            background: var(--mist);
            padding: 96px 0;
        }

        .role-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2px;
            background: var(--line);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }

        .role {
            background: var(--paper);
            padding: 30px 26px;
        }

        .role i {
            font-size: 24px;
            color: var(--blue);
        }

        .role h3 {
            font-size: 22px;
            color: var(--ink);
            margin: 16px 0 8px;
        }

        .role p {
            margin: 0;
            color: var(--muted);
            font-size: 15.5px;
        }

        /* ---------- demo band ---------- */
        .demo-band {
            padding: 96px 0;
        }

        .demo-box {
            background: var(--ink);
            color: #fff;
            border-radius: 36px;
            padding: 64px;
            display: grid;
            grid-template-columns: 1.3fr 1fr;
            gap: 40px;
            align-items: center;
            background:
                radial-gradient(600px 300px at 100% 0%, rgba(18, 184, 134, .35), transparent 60%),
                var(--ink);
        }

        .demo-box h2 {
            font-size: clamp(30px, 4vw, 46px);
            font-weight: 800;
        }

        .demo-box p {
            color: #C9D4F2;
            margin: 16px 0 0;
            font-size: 18px;
        }

        .demo-side {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
        }

        /* ---------- footer ---------- */
        footer {
            border-top: 1px solid var(--line);
            padding: 34px 0 40px;
            color: var(--muted);
            font-size: 15px;
        }

        footer .wrap {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 16px;
        }

        footer a {
            text-decoration: none;
            margin-left: 22px;
        }

        footer a:hover {
            color: var(--blue);
        }

        /* ---------- responsive ---------- */
        @media (max-width: 1020px) {
            .hero .wrap {
                grid-template-columns: 1fr;
            }

            .scene {
                height: 540px;
                max-width: 560px;
            }

            .steps {
                grid-template-columns: 1fr;
                border-top: 0;
                border-left: 2px solid var(--ink);
                gap: 22px;
            }

            .steps li {
                padding: 0 0 0 30px;
                position: relative;
            }

            .steps li::before {
                position: absolute;
                left: -18px;
                top: 0;
                margin: 0;
            }

            .split {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .split-intro {
                position: static;
            }

            .role-grid {
                grid-template-columns: 1fr 1fr;
            }

            .demo-box {
                grid-template-columns: 1fr;
                padding: 44px 32px;
            }
        }

        @media (max-width: 680px) {
            body {
                font-size: 16px;
            }

            .nav a:not(.btn) {
                display: none;
            }

            .hero {
                padding: 120px 0 70px;
            }

            .hero-lead {
                font-size: 18px;
            }

            .scene {
                height: 500px;
            }

            .phone {
                left: 0;
                width: 250px;
                height: 500px;
            }

            .receipt {
                width: 190px;
                font-size: 10.5px;
                top: 250px;
                right: -6px;
                padding: 16px 14px 24px;
            }

            .feature-list,
            .role-grid {
                grid-template-columns: 1fr;
            }

            footer a {
                margin: 0 18px 0 0;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                animation: none !important;
                transition: none !important;
            }

            html {
                scroll-behavior: auto;
            }
        }
    </style>
</head>

<body>

    <header class="site-header">
        <div class="wrap">
            <a href="{{ url('/') }}" class="logo">
                <span class="logo-mark"><i class="fas fa-graduation-cap"></i></span>
                {{ $brand }}
            </a>

            <nav class="nav" aria-label="Main">
                <a href="#features">Features</a>
                <a href="#mobile">Mobile app</a>
                <a href="#roles">Who uses it</a>
                <a href="{{ route('login') }}">Log in</a>
                <a href="{{ $demoUrl }}" class="btn btn-demo">Open demo</a>
            </nav>
        </div>
    </header>

    <main>

        <!-- HERO -->
        <section class="hero">
            <div class="wrap">
                <div>
                    <h1>Run your whole institute from one student card.</h1>

                    <p class="hero-lead">
                        Scan the card at the class door to mark attendance, collect the fee and
                        print the receipt. Students, classes, payments, teacher salaries and reports
                        live in one system, on the office computer and on your phone.
                    </p>

                    <div class="hero-actions">
                        <a href="{{ $demoUrl }}" class="btn btn-demo">
                            <i class="fas fa-play"></i> Open demo
                        </a>
                        <a href="#features" class="btn btn-ghost">See the features</a>
                    </div>

                    @if ($demoEmail && $demoPassword)
                        <p class="demo-creds">
                            Demo login: <code>{{ $demoEmail }}</code> / <code>{{ $demoPassword }}</code>
                        </p>
                    @endif
                </div>

                <div class="scene" aria-hidden="true">
                    <div class="phone">
                        <div class="screen">
                            <div class="screen-cam">
                                <div class="scan-frame"><i class="fas fa-qrcode"></i></div>
                                <span class="scan-toast"><i class="fas fa-check"></i> Attendance marked</span>
                            </div>

                            <div class="screen-sheet">
                                <div class="stu">
                                    <span class="avatar">KP</span>
                                    <div>
                                        <b>K. Perera</b>
                                        <small>STU-0248, Grade 10 Maths</small>
                                    </div>
                                </div>

                                <div class="chips">
                                    <div class="chip ok">This month<strong>Paid</strong></div>
                                    <div class="chip">Attendance<strong>7 / 8</strong></div>
                                    <div class="chip">Fee<strong>Rs. 2,000</strong></div>
                                    <div class="chip">Class time<strong>4.00 pm</strong></div>
                                </div>

                                <div class="phone-btn"><i class="fas fa-wallet"></i> Collect Payment</div>
                            </div>
                        </div>
                    </div>

                    <div class="receipt">
                        <div class="c"><b>{{ $brand }}</b><br>Payment receipt</div>
                        <hr>
                        <div class="row"><span>Receipt</span><span>R-10248</span></div>
                        <div class="row"><span>Student</span><span>K. Perera</span></div>
                        <div class="row"><span>Class</span><span>Maths G10</span></div>
                        <div class="row"><span>Month</span><span>Oct 2026</span></div>
                        <hr>
                        <div class="row total"><span>Total</span><span>2,000.00</span></div>
                        <div class="row"><span>Paid by</span><span>Cash</span></div>
                        <hr>
                        <div class="c">Thank you</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- A CLASS DAY -->
        <section class="flow">
            <div class="wrap">
                <div class="section-head">
                    <h2>What happens when a student walks in</h2>
                    <p>From the gate to the parent's phone in under a minute, with nothing written by hand.</p>
                </div>

                <ol class="steps">
                    <li><b>Scan the card</b><span>The phone camera reads the student's QR card.</span></li>
                    <li><b>Attendance is marked</b><span>For the class running right now, in the right branch.</span></li>
                    <li><b>Fee status shows</b><span>Paid, partly paid, free card or not paid for this month.</span></li>
                    <li><b>Collect and print</b><span>Take the fee and the receipt prints over Bluetooth.</span></li>
                    <li><b>Parent is told</b><span>An SMS confirms attendance and payment.</span></li>
                </ol>
            </div>
        </section>

        <!-- FEATURES -->
        <section class="features" id="features">
            <div class="wrap">
                <div class="split">
                    <div class="split-intro">
                        <span class="platform"><i class="fas fa-display"></i> Web system</span>
                        <h3>Everything the office needs</h3>
                        <p>Open it in any browser. Admins, branch admins and cashiers each get their own pages.</p>
                    </div>

                    <ul class="feature-list">
                        @foreach ($webFeatures as [$icon, $title, $text])
                            <li>
                                <span class="ico"><i class="fas {{ $icon }}"></i></span>
                                <div>
                                    <b>{{ $title }}</b>
                                    <p>{{ $text }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="split mobile" id="mobile">
                    <div class="split-intro">
                        <span class="platform"><i class="fas fa-mobile-screen-button"></i> Mobile app</span>
                        <h3>The front desk in your pocket</h3>
                        <p>An Android app for staff at the class door, connected to the same system in real time.</p>
                    </div>

                    <ul class="feature-list">
                        @foreach ($mobileFeatures as [$icon, $title, $text])
                            <li>
                                <span class="ico"><i class="fas {{ $icon }}"></i></span>
                                <div>
                                    <b>{{ $title }}</b>
                                    <p>{{ $text }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </section>

        <!-- ROLES -->
        <section class="roles" id="roles">
            <div class="wrap">
                <div class="section-head">
                    <h2>Each person sees what they need</h2>
                    <p>Access is set by role and branch, so cash and student data stay with the right people.</p>
                </div>

                <div class="role-grid">
                    <div class="role">
                        <i class="fas fa-user-shield"></i>
                        <h3>Admin</h3>
                        <p>All branches, finance, teacher salaries, SMS balance, users and permissions.</p>
                    </div>
                    <div class="role">
                        <i class="fas fa-building-user"></i>
                        <h3>Branch admin</h3>
                        <p>Their own branch: students, classes, schedules, payments and reports.</p>
                    </div>
                    <div class="role">
                        <i class="fas fa-cash-register"></i>
                        <h3>Cashier</h3>
                        <p>Registers students, scans attendance, collects fees and prints receipts.</p>
                    </div>
                    <div class="role">
                        <i class="fas fa-chalkboard-user"></i>
                        <h3>Teacher</h3>
                        <p>Their classes, timetable and attendance, from the app.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- DEMO -->
        <section class="demo-band">
            <div class="wrap">
                <div class="demo-box">
                    <div>
                        <h2>Try it with sample data</h2>
                        <p>
                            Open the demo and click around: register a student, mark attendance,
                            take a payment and check the reports.
                        </p>
                    </div>

                    <div class="demo-side">
                        <a href="{{ $demoUrl }}" class="btn btn-demo">
                            <i class="fas fa-play"></i> Open demo
                        </a>
                        @if ($demoEmail && $demoPassword)
                            <p class="demo-creds" style="margin:0">
                                Email <code>{{ $demoEmail }}</code><br>
                                Password <code>{{ $demoPassword }}</code>
                            </p>
                        @endif
                        <a href="{{ route('login') }}" class="btn btn-ghost">Log in to your account</a>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <footer>
        <div class="wrap">
            <span>&copy; {{ date('Y') }} {{ $brand }}</span>
            <span>
                @if ($phone)
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}"><i class="fas fa-phone"></i> {{ $phone }}</a>
                @endif
                @if ($email)
                    <a href="mailto:{{ $email }}"><i class="fas fa-envelope"></i> {{ $email }}</a>
                @endif
                <a href="{{ route('login') }}">Log in</a>
            </span>
        </div>
    </footer>

</body>

</html>

@php
    $updated = '30 October 2026';
    $developerEmail = config('services.nexora.support_email');
    $instituteEmail = config('services.institute.contact_email');
    $instituteMobile = config('services.institute.contact_mobile');
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Privacy Policy - Nexora EDU</title>
    <style>
        :root {
            --text: #0f172a;
            --muted: #475569;
            --line: #e2e8f0;
            --accent: #1d4ed8;
            --bg: #f8fafc;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            color: var(--text);
            background: var(--bg);
            line-height: 1.65;
        }

        main {
            max-width: 820px;
            margin: 0 auto;
            padding: 32px 18px 56px;
        }

        .doc {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 32px 28px;
        }

        h1 {
            font-size: 28px;
            margin: 0 0 4px;
        }

        h2 {
            font-size: 19px;
            margin: 30px 0 8px;
            color: var(--accent);
        }

        p,
        li {
            color: var(--muted);
        }

        ul {
            padding-left: 22px;
        }

        li {
            margin: 4px 0;
        }

        strong {
            color: var(--text);
        }

        .meta {
            color: var(--muted);
            font-size: 14px;
            margin-bottom: 18px;
        }

        a {
            color: var(--accent);
        }

        @media (max-width: 560px) {
            .doc {
                padding: 22px 16px;
            }

            h1 {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>
    <main>
        <article class="doc">
            <h1>Privacy Policy</h1>
            <div class="meta">Nexora EDU staff app and web system · Last updated: {{ $updated }}</div>

            <p>
                Nexora EDU is a management system for tuition institutes, made by <strong>Nexora IT</strong>
                (Sri Lanka). It has a web system and an Android app ("the app") for the institute's staff and
                teachers. This policy explains what information the system handles, why, and how it is protected.
            </p>

            <h2>1. Who is responsible for the data</h2>
            <p>
                Each institute runs its own Nexora EDU server with its own database. The <strong>institute</strong>
                decides what student and staff information is entered and how it is used, and is responsible for it.
                <strong>Nexora IT</strong> builds and maintains the software and the servers it runs on, and handles
                the data only to provide that service to the institute.
            </p>

            <h2>2. Who can use the app</h2>
            <p>
                The app is only for staff and teachers of an institute that uses Nexora EDU. It is not for students or
                the general public. To use it, a staff member scans their institute's setup QR code (shown to the
                institute's administrator) and signs in with the account the institute created for them. Students and
                parents do not use this app.
            </p>

            <h2>3. Information the system handles</h2>
            <p><strong>Staff and teacher accounts</strong></p>
            <ul>
                <li>Name, email address, role and branch, and a securely hashed password.</li>
                <li>Sign-in records (login time) used to keep accounts secure.</li>
            </ul>
            <p><strong>Student records entered by institute staff</strong></p>
            <ul>
                <li>Student name, student ID / card number, grade, classes, and guardian name and phone number.</li>
                <li>Student photos taken or uploaded by staff for the student ID card.</li>
                <li>Attendance, class fee payments and receipts, exam marks and tutes issued.</li>
            </ul>
            <p><strong>Information on the phone</strong></p>
            <ul>
                <li>The institute server address and name, and the sign-in session, saved on the phone.</li>
                <li>App settings, such as the receipt printer and printing options.</li>
            </ul>
            <p>
                The app does not collect contacts, messages, call logs, the phone's location history or
                advertising IDs, and it contains no advertising or third-party analytics.
            </p>

            <h2>4. Phone permissions and why they are used</h2>
            <ul>
                <li><strong>Camera:</strong> to scan student QR cards (attendance, payments) and the institute setup
                    QR code, and to take student ID card photos. Photos are uploaded to the institute's server only
                    when staff choose to save them.</li>
                <li><strong>Bluetooth (and location on older Android versions):</strong> only to find and connect to a
                    Bluetooth receipt printer. Android requires location permission for Bluetooth scanning on some
                    versions; the app does not read or store your location.</li>
                <li><strong>Storage (older Android versions):</strong> to pick a photo from the phone.</li>
                <li><strong>Internet:</strong> to communicate with the institute's server.</li>
            </ul>

            <h2>5. How the information is used</h2>
            <ul>
                <li>To run the institute: register students, mark attendance, record payments and print receipts,
                    manage classes, exams, teacher salaries and reports.</li>
                <li>To send the institute's own messages to guardians (for example payment or attendance SMS), when
                    the institute turns this on.</li>
                <li>To keep the system secure and to fix problems.</li>
            </ul>
            <p>The information is <strong>not sold</strong> and is <strong>not used for advertising</strong>.</p>

            <h2>6. Sharing</h2>
            <p>Information is shared only as needed to provide the service:</p>
            <ul>
                <li><strong>Hosting:</strong> the institute's server and database are hosted on servers managed by
                    Nexora IT.</li>
                <li><strong>SMS provider:</strong> guardian phone numbers and message text are sent to the SMS
                    service the institute uses, only to deliver its messages.</li>
                <li><strong>Backups:</strong> encrypted-in-transit database backups are stored in a private cloud
                    storage account controlled by Nexora IT / the institute.</li>
                <li>When required by law.</li>
            </ul>

            <h2>7. Security</h2>
            <ul>
                <li>All app and web traffic uses HTTPS (encrypted in transit).</li>
                <li>Passwords are stored only as secure hashes.</li>
                <li>Each user sees only what their role and branch allow.</li>
                <li>Sign-in attempts are limited, and app sessions end after a period without use.</li>
                <li>The institute setup QR code is checked with the institute's server before the app connects, and
                    the app only connects to Nexora EDU servers.</li>
            </ul>

            <h2>8. How long information is kept</h2>
            <p>
                Records are kept while the institute uses Nexora EDU and as long as the institute needs them for its
                operations and legal or accounting requirements. When an institute stops using the service, its data
                is deleted or handed over to the institute on request.
            </p>

            <h2>9. Your choices and rights</h2>
            <p>
                Students, guardians and staff can ask the <strong>institute</strong> to see, correct or delete their
                information. Staff can sign out of the app at any time, and "Change institute" in the app removes the
                saved institute and session from the phone. Uninstalling the app removes everything it saved on the
                phone.
            </p>

            <h2>10. Children</h2>
            <p>
                The app is used only by adult staff. It handles students' information (some of whom are under 18) only
                as entered by their institute, for the institute's education and administration purposes.
            </p>

            <h2>11. Changes to this policy</h2>
            <p>If this policy changes, the new version will be published on this page with a new date.</p>

            <h2>12. Contact</h2>
            <ul>
                @if ($instituteEmail || $instituteMobile)
                    <li>
                        <strong>{{ \App\Support\InstituteInfo::name() }}:</strong>
                        {{ collect([$instituteEmail, $instituteMobile])->filter()->implode(' · ') }}
                    </li>
                @else
                    <li>For questions about your records, contact your institute.</li>
                @endif
                @if ($developerEmail)
                    <li><strong>Nexora IT (developer):</strong> <a href="mailto:{{ $developerEmail }}">{{ $developerEmail }}</a></li>
                @endif
            </ul>
        </article>
    </main>
</body>

</html>

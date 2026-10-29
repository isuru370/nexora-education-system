@extends('layouts.app')

@section('title', 'Staff App QR')
@section('page-title', 'Staff App QR')

@push('styles')
    <style>
        .qr-sheet {
            max-width: 460px;
            margin: 0 auto;
            text-align: center;
            border: 2px dashed #cbd5e1;
            border-radius: 14px;
            padding: 24px;
            background: #fff;
        }

        .qr-sheet .qr-box svg {
            width: 280px;
            height: 280px;
        }

        .qr-sheet .qr-logo {
            max-height: 56px;
            margin-bottom: 8px;
        }

        .qr-steps {
            text-align: left;
            margin: 16px auto 0;
            max-width: 340px;
            font-size: 14px;
        }

        @media print {
            body * {
                visibility: hidden;
            }

            .qr-sheet,
            .qr-sheet * {
                visibility: visible;
            }

            .qr-sheet {
                position: absolute;
                left: 0;
                right: 0;
                top: 40px;
                border: 2px dashed #94a3b8;
            }
        }
    </style>
@endpush

@section('content')
    <div class="dashboard-page">

        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">Staff App QR</h1>
                    <p class="hero-subtitle">
                        Staff install the Nexora EDU app, scan this QR once to connect it to this institute,
                        then log in with their own email and password.
                    </p>
                </div>
                <div class="hero-right">
                    <small class="hero-updated">{{ now()->format('D, d M Y') }}</small>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="qr-sheet" id="qrSheet">
                    @if ($institute['logo_url'])
                        <img src="{{ $institute['logo_url'] }}" alt="" class="qr-logo">
                    @endif
                    <h4 class="mb-1">{{ $institute['name'] }}</h4>
                    <div class="text-muted mb-3">Nexora EDU staff app</div>

                    <div class="qr-box">
                        {!! QrCode::format('svg')->size(280)->margin(1)->errorCorrection('M')->generate($qrContent) !!}
                    </div>

                    <ol class="qr-steps">
                        <li>Install <strong>Nexora EDU</strong> from the Play Store.</li>
                        <li>Open the app and tap <strong>Scan institute QR</strong>.</li>
                        <li>Scan this code and check the institute name.</li>
                        <li>Log in with your own email and password.</li>
                    </ol>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="nx-card mb-4">
                    <div class="nx-card-header">
                        <div>
                            <h5 class="nx-card-title"><i class="bi bi-printer text-primary"></i> Print / share</h5>
                            <p class="nx-card-subtitle">Print it for the office, or show it on this screen.</p>
                        </div>
                    </div>
                    <div class="p-3">
                        <button type="button" class="nx-btn nx-btn-primary" onclick="window.print()">
                            <i class="bi bi-printer"></i> Print QR
                        </button>
                        <div class="small text-muted mt-3">
                            Server: <code>{{ $baseUrl }}</code>
                        </div>
                    </div>
                </div>

                <div class="nx-card mb-4">
                    <div class="nx-card-header">
                        <div>
                            <h5 class="nx-card-title"><i class="bi bi-shield-lock text-success"></i> Is it safe?</h5>
                        </div>
                    </div>
                    <div class="p-3 small">
                        <ul class="mb-0">
                            <li>The QR only connects the app to this institute. Nobody can see data without logging in.</li>
                            <li>The app checks the code with this server, and only accepts nexorait.lk servers.</li>
                            <li>If a printed QR is lost or shared outside, create a new one below.</li>
                        </ul>
                    </div>
                </div>

                <div class="nx-card">
                    <div class="nx-card-header">
                        <div>
                            <h5 class="nx-card-title"><i class="bi bi-arrow-repeat text-danger"></i> New QR code</h5>
                            <p class="nx-card-subtitle">
                                Old QR codes stop working. Phones already set up keep working.
                            </p>
                        </div>
                    </div>
                    <div class="p-3">
                        <form method="POST" action="{{ route('app-setup.regenerate') }}"
                            onsubmit="return confirm('Create a new QR code? Old printed QR codes will stop working.');">
                            @csrf
                            <button type="submit" class="nx-btn nx-btn-danger">
                                <i class="bi bi-arrow-repeat"></i> Create new QR
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@extends('layouts.app')

@section('title', 'Create Teacher')
@section('page-title', 'Create Teacher')

@push('styles')
    {{-- Select2 CSS (Bank Branch සඳහා පමණි) --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        .teacher-page {
            animation: fadeIn .4s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ============================================
           HERO CARD
        ============================================ */
        .hero-card {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 30%, #2563eb 100%);
            border-radius: 24px;
            padding: 1.75rem 2rem;
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(37, 99, 235, 0.20);
        }

        .hero-card::before {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
        }

        .hero-card::after {
            content: '';
            position: absolute;
            bottom: -80px;
            left: -40px;
            width: 160px;
            height: 160px;
            border-radius: 50%;
            background: rgba(52, 211, 153, 0.06);
        }

        .hero-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            position: relative;
            z-index: 2;
        }

        .hero-card h4 {
            color: #fff;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .hero-card p {
            color: rgba(255, 255, 255, 0.7) !important;
            margin-bottom: 0;
            font-size: 0.9rem;
        }

        .hero-icon {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 2rem;
            flex-shrink: 0;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 0.4rem 1rem;
            border-radius: 30px;
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }

        .hero-card .btn-back {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
            border-radius: 12px;
            padding: 0.6rem 1.25rem;
            font-weight: 600;
            transition: all 0.25s ease;
        }

        .hero-card .btn-back:hover {
            background: rgba(255, 255, 255, 0.22);
            border-color: rgba(255, 255, 255, 0.3);
            color: #fff;
            transform: translateY(-2px);
        }

        /* ============================================
           MAIN CARD
        ============================================ */
        .main-card {
            background: #fff;
            border-radius: 24px;
            padding: 1.75rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border: 1px solid #eef2f7;
        }

        /* ============================================
           FORM INPUTS
        ============================================ */
        .custom-input,
        .form-select,
        .form-control {
            border-radius: 12px;
            min-height: 46px;
            border: 1px solid #e2e8f0;
            box-shadow: none;
            transition: all 0.2s ease;
            font-size: 0.9rem;
        }

        .custom-input:focus,
        .form-select:focus,
        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.10);
        }

        textarea.form-control {
            min-height: 110px;
            padding-top: 0.75rem;
        }

        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.5rem;
        }

        /* ============================================
           BUTTONS
        ============================================ */
        .custom-btn {
            border-radius: 12px;
            padding: 0.7rem 1.3rem;
            font-weight: 600;
            transition: all 0.2s ease;
            font-size: 0.9rem;
        }

        .custom-btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #1d4ed8, #2563eb);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
        }

        /* ============================================
           SELECT2 STYLING (Bank Branch)
        ============================================ */
        .select2-container {
            width: 100% !important;
        }

        .select2-container .select2-selection--single {
            height: 46px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            display: flex;
            align-items: center;
            transition: all 0.2s ease;
        }

        .select2-container--default
        .select2-selection--single
        .select2-selection__rendered {
            line-height: 44px;
            padding-left: 14px;
            padding-right: 30px;
            font-size: 0.9rem;
            color: #334155;
        }

        .select2-container--default
        .select2-selection--single
        .select2-selection__arrow {
            height: 44px;
            right: 10px;
        }

        .select2-container--default.select2-container--focus
        .select2-selection--single,
        .select2-container--default.select2-container--open
        .select2-selection--single {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.10);
        }

        .select2-dropdown {
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .select2-results__option--highlighted {
            background: #2563eb !important;
        }

        /* ============================================
           ALERT
        ============================================ */
        .alert-danger {
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            border: 1px solid #fecaca !important;
            color: #991b1b;
            border-radius: 16px;
        }

        .alert-danger strong {
            color: #7f1d1d;
        }

        /* ============================================
           RESPONSIVE
        ============================================ */
        @media (max-width: 768px) {
            .hero-card {
                padding: 1.25rem 1.5rem;
            }

            .hero-card h4 {
                font-size: 1.25rem;
            }

            .hero-icon {
                width: 55px;
                height: 55px;
                font-size: 1.5rem;
            }

            .hero-content {
                flex-direction: column;
                align-items: stretch;
            }

            .main-card {
                padding: 1.25rem;
            }
        }
    </style>
@endpush


@section('content')
<div class="teacher-page">

    {{-- =====================================================
        HERO SECTION
    ====================================================== --}}
    <div class="hero-card">
        <div class="hero-content">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="hero-icon">
                    <i class="bi bi-person-plus-fill"></i>
                </div>

                <div>
                    <span class="hero-badge">
                        <i class="bi bi-stars"></i>
                        New Teacher
                    </span>

                    <h4>Create Teacher</h4>

                    <p>
                        <i class="bi bi-info-circle me-1"></i>
                        Add a new teacher profile and account details.
                    </p>
                </div>
            </div>

            <div>
                <a href="{{ route('teachers.index') }}" class="btn btn-back">
                    <i class="bi bi-arrow-left me-1"></i>
                    Back to Teachers
                </a>
            </div>
        </div>
    </div>


    {{-- =====================================================
        MAIN CARD
    ====================================================== --}}
    <div class="main-card">

        {{-- VALIDATION ERRORS --}}
        @if($errors->any())
            <div class="alert alert-danger border-0 shadow-sm mb-4">
                <div class="d-flex align-items-start gap-2">
                    <i class="bi bi-exclamation-triangle-fill mt-1" style="font-size: 1.1rem;"></i>
                    <div class="flex-grow-1">
                        <strong>Please fix the following errors:</strong>
                        <ul class="mb-0 mt-2 ps-3">
                            @foreach($errors->all() as $error)
                                <li class="small">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif


        {{-- FORM --}}
        <form method="POST" action="{{ route('teachers.store') }}">
            @csrf

            @include('teachers.partials.form', [
                'teacher' => null,
                'buttonText' => 'Save Teacher',
                'branches' => $branches,
                'selectedBranchIds' => $selectedBranchIds,
                'bankBranches' => $bankBranches,
            ])
        </form>

    </div>

</div>
@endsection


@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function () {
            /* Bank Branch - Select2 (single select) */
            $('.select2-bank-branch').select2({
                placeholder: 'Search Bank or Branch',
                allowClear: true,
                width: '100%'
            });

            /* ✅ Education Branches - Checkbox cards, JS අවශ්‍ය නැත */
        });
    </script>
@endpush
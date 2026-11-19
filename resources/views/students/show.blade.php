@extends('layouts.app')

@section('title', 'Student Details')
@section('page-title', 'Student Details')

@section('content')

    @php
        if (!empty($student->img_url)) {
            if (\Illuminate\Support\Str::startsWith($student->img_url, 'uploads/')) {
                $studentImage = asset('storage/' . $student->img_url);
            } else {
                $studentImage = asset($student->img_url);
            }
        } else {
            $studentImage = $student->gender === 'female'
                ? asset('images/female.png')
                : asset('images/male.png');
        }

        $expireDate = $student->temporary_qr_code_expire_date;
        $daysLeft = $expireDate ? (int) now()->diffInDays($expireDate, false) : null;
    @endphp

    <div class="student-details-page">

        <!-- HERO -->
        <div class="hero-card tw:mb-6!">
            <div class="hero-content">
                <div class="tw:flex! tw:items-center! tw:gap-4!">
                    <div class="profile-avatar">
                        {{ strtoupper(substr($student->full_name, 0, 1)) }}
                    </div>

                    <div>
                        <h4 class="tw:mb-1! tw:font-bold!">{{ $student->full_name }}</h4>
                        <p class="tw:mb-0! tw:text-bs-secondary-color!">{{ $student->custom_id }}</p>
                    </div>
                </div>

                <div class="hero-actions">
                    <a href="{{ route('students.edit', $student) }}" class="ui-btn ui-btn-warning custom-btn">
                        <i class="bi bi-pencil-fill tw:mr-1!"></i>
                        Edit
                    </a>

                    <a href="{{ route('students.index') }}" class="ui-btn ui-btn-outline-secondary custom-btn">
                        <i class="bi bi-arrow-left tw:mr-1!"></i>
                        Back
                    </a>
                </div>
            </div>
        </div>

        <!-- STATS -->
        <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3 bs-row tw:mb-6!">
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">
                <div class="stats-card">
                    <div class="stats-icon blue">
                        <i class="bi bi-person-badge-fill"></i>
                    </div>
                    <div>
                        <h3>{{ $student->custom_id }}</h3>
                        <p>Student ID</p>
                    </div>
                </div>
            </div>

            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">
                <div class="stats-card">
                    <div class="stats-icon green">
                        <i class="bi bi-phone-fill"></i>
                    </div>
                    <div>
                        <h3>{{ $student->mobile }}</h3>
                        <p>Mobile</p>
                    </div>
                </div>
            </div>

            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">
                <div class="stats-card">
                    <div class="stats-icon orange">
                        <i class="bi bi-book-fill"></i>
                    </div>
                    <div>
                        <h3>{{ $student->grade->grade_name ?? 'N/A' }}</h3>
                        <p>Grade</p>
                    </div>
                </div>
            </div>

            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">
                <div class="stats-card">
                    <div class="stats-icon red">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <h3>
                            <span class="ui-badge custom-badge {{ $student->is_active ? 'tw:bg-bs-success! bs-bg-success' : 'tw:bg-bs-secondary!' }}">
                                {{ $student->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </h3>
                        <p>Status</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3 bs-row">

            <!-- LEFT SIDE -->
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">
                <div class="info-card tw:h-full!">
                    <div class="section-header">
                        <h6 class="tw:mb-0! tw:font-bold!">Student Profile</h6>
                        <span class="section-pill">Basic Info</span>
                    </div>

                    <div class="tw:text-center! tw:mb-6!">
                        <img src="{{ $studentImage }}" alt="{{ $student->full_name }}"
                            class="student-profile-image bs-shadow-sm tw:shadow-bs-sm!" onerror="this.src='{{ asset('images/male.png') }}'">

                        <h5 class="tw:font-bold! tw:mt-4! tw:mb-0!">{{ $student->full_name }}</h5>
                        <small class="tw:text-bs-secondary-color!">{{ $student->custom_id }}</small>
                    </div>

                    <div class="info-list">
                        <div class="info-row">
                            <span class="label">Full Name</span>
                            <span class="value">{{ $student->full_name }}</span>
                        </div>

                        <div class="info-row">
                            <span class="label">Initial Name</span>
                            <span class="value">{{ $student->initial_name }}</span>
                        </div>

                        <div class="info-row">
                            <span class="label">NIC</span>
                            <span class="value">{{ $student->nic ?? 'N/A' }}</span>
                        </div>

                        <div class="info-row">
                            <span class="label">Birthday</span>
                            <span class="value">
                                {{ !empty($student->bday) ? \Carbon\Carbon::parse($student->bday)->format('Y-m-d') : 'N/A' }}
                            </span>
                        </div>

                        <div class="info-row">
                            <span class="label">Gender</span>
                            <span class="value">{{ ucfirst($student->gender ?? 'N/A') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT SIDE -->
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">
                <div class="info-card tw:h-full!">
                    <div class="section-header">
                        <h6 class="tw:mb-0! tw:font-bold!">Class & QR Information</h6>
                        <span class="section-pill">Enrollment</span>
                    </div>

                    <div class="info-list">
                        <div class="info-row">
                            <span class="label">Grade</span>
                            <span class="value">{{ $student->grade->grade_name ?? 'N/A' }}</span>
                        </div>

                        <div class="info-row">
                            <span class="label">Class Type</span>
                            <span class="value">
                                <span class="ui-badge bs-bg-info tw:bg-bs-info! tw:text-bs-dark! custom-badge">
                                    {{ ucfirst($student->class_type ?? 'N/A') }}
                                </span>
                            </span>
                        </div>

                        <div class="info-row">
                            <span class="label">School</span>
                            <span class="value">{{ $student->student_school ?? 'N/A' }}</span>
                        </div>

                        <div class="info-row">
                            <span class="label">QR Type</span>
                            <span class="value">
                                @if($student->permanent_qr_active)
                                    <span class="ui-badge bs-bg-success tw:bg-bs-success! custom-badge">Permanent QR</span>
                                @else
                                    <span class="ui-badge bs-bg-warning tw:bg-bs-warning! tw:text-bs-dark! custom-badge">Temporary QR</span>
                                @endif
                            </span>
                        </div>

                        <div class="info-row">
                            <span class="label">QR Code</span>
                            <span class="value">
                                @if($student->permanent_qr_active)
                                    {{ $student->custom_id }}
                                @else
                                    {{ $student->temporary_qr_code }}
                                @endif
                            </span>
                        </div>

                        <div class="info-row">
                            <span class="label">QR Expire</span>
                            <span class="value">
                                @if($student->permanent_qr_active)
                                    <span class="tw:text-bs-secondary-color!">Not applicable</span>
                                @else
                                    @if($daysLeft !== null)
                                        @if($daysLeft < 0)
                                            <span class="tw:text-bs-danger! tw:font-bold!">Expired {{ abs($daysLeft) }} day(s) ago</span>
                                        @elseif($daysLeft == 0)
                                            <span class="tw:text-bs-warning! tw:font-bold!">Expires today</span>
                                        @else
                                            <span class="tw:text-bs-secondary-color!">Expires in {{ $daysLeft }} day(s)</span>
                                        @endif
                                    @else
                                        <span class="tw:text-bs-secondary-color!">N/A</span>
                                    @endif
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CONTACT -->
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">
                <div class="info-card tw:h-full!">
                    <div class="section-header">
                        <h6 class="tw:mb-0! tw:font-bold!">Contact Information</h6>
                        <span class="section-pill">Reach</span>
                    </div>

                    <div class="info-list">
                        <div class="info-row">
                            <span class="label">Mobile</span>
                            <span class="value">{{ $student->mobile }}</span>
                        </div>

                        <div class="info-row">
                            <span class="label">WhatsApp</span>
                            <span class="value">{{ $student->whatsapp_mobile ?? 'N/A' }}</span>
                        </div>

                        <div class="info-row">
                            <span class="label">Email</span>
                            <span class="value">{{ $student->email ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STATUS -->
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">
                <div class="info-card tw:h-full!">
                    <div class="section-header">
                        <h6 class="tw:mb-0! tw:font-bold!">Account Status</h6>
                        <span class="section-pill">Flags</span>
                    </div>

                    <div class="info-list">
                        <div class="info-row">
                            <span class="label">Active</span>
                            <span class="value">
                                <span class="ui-badge custom-badge {{ $student->is_active ? 'tw:bg-bs-success! bs-bg-success' : 'tw:bg-bs-secondary!' }}">
                                    {{ $student->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </span>
                        </div>

                        <div class="info-row">
                            <span class="label">Disabled</span>
                            <span class="value">
                                @if($student->student_disable)
                                    <span class="ui-badge bs-bg-danger tw:bg-bs-danger! custom-badge">Disabled</span>
                                @else
                                    <span class="ui-badge bs-bg-success tw:bg-bs-success! custom-badge">Enabled</span>
                                @endif
                            </span>
                        </div>

                        <div class="info-row">
                            <span class="label">Admission</span>
                            <span class="value">
                                @if($student->admission)
                                    <span class="ui-badge tw:bg-bs-primary! custom-badge">Admission Paid</span>
                                @else
                                    <span class="ui-badge tw:bg-bs-light! tw:text-bs-dark! tw:bs-border! custom-badge">Admission Pending</span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ADDRESS -->
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">
                <div class="info-card">
                    <div class="section-header">
                        <h6 class="tw:mb-0! tw:font-bold!">Address Information</h6>
                        <span class="section-pill">Location</span>
                    </div>

                    <div class="info-list">
                        <div class="info-row align-start">
                            <span class="label">Address</span>
                            <span class="value">
                                {{ $student->address1 ?? 'N/A' }}<br>
                                {{ $student->address2 ?? '' }}<br>
                                {{ $student->address3 ?? '' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- GUARDIAN -->
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">
                <div class="info-card">
                    <div class="section-header">
                        <h6 class="tw:mb-0! tw:font-bold!">Guardian Information</h6>
                        <span class="section-pill">Family</span>
                    </div>

                    <div class="info-list">
                        <div class="info-row">
                            <span class="label">Guardian Name</span>
                            <span class="value">
                                {{ trim(($student->guardian_fname ?? '') . ' ' . ($student->guardian_lname ?? '')) ?: 'N/A' }}
                            </span>
                        </div>

                        <div class="info-row">
                            <span class="label">Guardian NIC</span>
                            <span class="value">{{ $student->guardian_nic ?? 'N/A' }}</span>
                        </div>

                        <div class="info-row">
                            <span class="label">Guardian Mobile</span>
                            <span class="value">{{ $student->guardian_mobile ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

@endsection

@push('styles')
    <style>
        .student-details-page {
            animation: fadeIn .4s ease;
        }

        .hero-card,
        .stats-card,
        .info-card {
            background: #fff;
            border-radius: 28px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .05);
            border: 1px solid #eef2f7;
        }

        .hero-card {
            padding: 1.35rem 1.5rem;
            margin-bottom: 1.5rem;
        }

        .hero-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .hero-actions {
            display: flex;
            gap: .7rem;
            flex-wrap: wrap;
        }

        .custom-btn {
            border-radius: 14px;
            padding: .7rem 1.15rem;
            font-weight: 600;
            border: none;
            transition: .2s ease;
        }

        .custom-btn:hover {
            transform: translateY(-2px);
        }

        .profile-avatar {
            width: 64px;
            height: 64px;
            border-radius: 20px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.6rem;
            box-shadow: 0 10px 20px rgba(79, 70, 229, .22);
            flex-shrink: 0;
        }

        .stats-card {
            padding: 1.5rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            border-radius: 24px;
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .blue {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
        }

        .green {
            background: linear-gradient(135deg, #10b981, #34d399);
        }

        .orange {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
        }

        .red {
            background: linear-gradient(135deg, #ef4444, #f87171);
        }

        .stats-card h3 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            word-break: break-word;
        }

        .stats-card p {
            margin: 0;
            color: #64748b;
            font-size: .92rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid #eef2f7;
        }

        .section-pill {
            display: inline-flex;
            align-items: center;
            padding: .35rem .75rem;
            border-radius: 999px;
            background: #eff6ff;
            color: #2563eb;
            font-weight: 600;
            font-size: .82rem;
        }

        .info-card {
            padding: 1.25rem;
            height: 100%;
        }

        .info-list {
            display: flex;
            flex-direction: column;
            gap: .9rem;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: center;
            background: #f8fafc;
            border: 1px solid #eef2f7;
            border-radius: 18px;
            padding: .9rem 1rem;
        }

        .info-row.align-start {
            align-items: flex-start;
        }

        .label {
            color: #64748b;
            font-weight: 600;
            min-width: 120px;
        }

        .value {
            font-weight: 600;
            color: #0f172a;
            text-align: right;
            word-break: break-word;
        }

        .custom-badge {
            border-radius: 10px;
            padding: .5rem .75rem;
            font-size: .75rem;
        }

        .student-profile-image {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #e5e7eb;
            background: #f8fafc;
        }

        @media (max-width: 768px) {
            .hero-content {
                flex-direction: column;
                align-items: stretch;
            }

            .hero-actions {
                width: 100%;
            }

            .hero-actions .ui-btn {
                flex: 1;
            }

            .info-row {
                flex-direction: column;
                align-items: flex-start;
            }

            .value {
                text-align: left;
            }

            .label {
                min-width: auto;
            }

            .student-profile-image {
                width: 130px;
                height: 130px;
            }
        }
    </style>
@endpush
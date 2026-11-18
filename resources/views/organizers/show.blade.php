@extends('layouts.app')



@section('title', 'Organizer Details')

@section('page-title', 'Organizer Details')



@section('content')



    <div class="organizer-details-page">



        <!-- HERO -->

        <div class="hero-card tw:mb-6!">

            <div class="hero-content">



                <div class="tw:flex! tw:items-center! tw:gap-4!">

                    <div class="profile-avatar">

                        {{ strtoupper(substr($organizer->name, 0, 1)) }}

                    </div>



                    <div>

                        <h4 class="tw:mb-1! tw:font-bold!">{{ $organizer->name }}</h4>

                        <p class="tw:mb-0! tw:text-bs-secondary-color!">

                            Organizer Profile

                        </p>

                    </div>

                </div>



                <div class="hero-actions">



                    <a href="{{ route('organizers.edit', $organizer) }}" class="ui-btn ui-btn-warning custom-btn">

                        <i class="bi bi-pencil-fill tw:mr-1!"></i>

                        Edit

                    </a>



                    <a href="{{ route('organizers.index') }}" class="ui-btn ui-btn-outline-secondary custom-btn">

                        <i class="bi bi-arrow-left tw:mr-1!"></i>

                        Back

                    </a>



                </div>

            </div>

        </div>



        <!-- STATS -->

        <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3 tw:mb-6!">



            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">

                <div class="stats-card">

                    <div class="stats-icon blue">

                        <i class="bi bi-upc-scan"></i>

                    </div>



                    <div>

                        <h3>{{ $organizer->code }}</h3>

                        <p>Organizer Code</p>

                    </div>

                </div>

            </div>



            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">

                <div class="stats-card">

                    <div class="stats-icon green">

                        <i class="bi bi-phone-fill"></i>

                    </div>



                    <div>

                        <h3>{{ $organizer->mobile }}</h3>

                        <p>Mobile</p>

                    </div>

                </div>

            </div>



            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">

                <div class="stats-card">

                    <div class="stats-icon orange">

                        <i class="bi bi-envelope-fill"></i>

                    </div>



                    <div>

                        <h3>{{ $organizer->email ?? 'N/A' }}</h3>

                        <p>Email</p>

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

                            <span class="ui-badge custom-badge {{ $organizer->is_active ? 'tw:bg-bs-success!' : 'tw:bg-bs-secondary!' }}">

                                {{ $organizer->is_active ? 'Active' : 'Inactive' }}

                            </span>

                        </h3>



                        <p>Status</p>

                    </div>

                </div>

            </div>



        </div>



        <!-- CONTENT -->

        <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3">



            <!-- BASIC -->

            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">

                <div class="info-card tw:h-full!">



                    <div class="section-header">

                        <h6 class="tw:mb-0! tw:font-bold!">Basic Information</h6>

                        <span class="section-pill">Profile</span>

                    </div>



                    <div class="info-list">



                        <div class="info-row">

                            <span class="label">Organizer Code</span>

                            <span class="value">{{ $organizer->code }}</span>

                        </div>



                        <div class="info-row">

                            <span class="label">Organizer Name</span>

                            <span class="value">{{ $organizer->name }}</span>

                        </div>



                        <div class="info-row">

                            <span class="label">NIC</span>

                            <span class="value">{{ $organizer->nic ?? 'N/A' }}</span>

                        </div>

                            <div class="info-row">
                                <span class="label">Assigned Branches</span>
                                <span class="value">
                                    @forelse($organizer->branches as $branch)
                                        <span class="ui-badge tw:bg-bs-primary! tw:mr-1! tw:mb-1!">
                                            {{ $branch->name }}
                                        </span>
                                    @empty
                                        <span class="tw:text-bs-secondary-color!">No branches assigned</span>
                                    @endforelse
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

                            <span class="value">{{ $organizer->mobile }}</span>

                        </div>



                        <div class="info-row">

                            <span class="label">Email</span>

                            <span class="value">{{ $organizer->email ?? 'N/A' }}</span>

                        </div>



                    </div>



                </div>

            </div>



            <!-- NOTE -->

            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">

                <div class="info-card">



                    <div class="section-header">

                        <h6 class="tw:mb-0! tw:font-bold!">Organizer Note</h6>

                        <span class="section-pill">Additional</span>

                    </div>



                    <div class="note-box">

                        {{ $organizer->note ?? 'No additional notes available.' }}

                    </div>



                </div>

            </div>



            <!-- STATUS -->

            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">

                <div class="info-card">



                    <div class="section-header">

                        <h6 class="tw:mb-0! tw:font-bold!">Account Status</h6>

                        <span class="section-pill">System</span>

                    </div>



                    <div class="tw:flex! tw:items-center! tw:gap-2! tw:flex-wrap!">



                        <span class="ui-badge custom-badge {{ $organizer->is_active ? 'tw:bg-bs-success!' : 'tw:bg-bs-secondary!' }}">

                            {{ $organizer->is_active ? 'Active' : 'Inactive' }}

                        </span>



                        <span class="tw:text-bs-secondary-color! tw:text-[.875em]">

                            Organizer visibility and payment availability status.

                        </span>



                    </div>



                </div>

            </div>



        </div>



    </div>



@endsection



@push('styles')

    <style>

        .organizer-details-page {

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



        .note-box {

            background: #f8fafc;

            border: 1px solid #eef2f7;

            border-radius: 18px;

            padding: 1rem;

            color: #334155;

            line-height: 1.7;

        }



        .custom-badge {

            border-radius: 10px;

            padding: .5rem .75rem;

            font-size: .75rem;

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

        }

    </style>

@endpush
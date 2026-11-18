@extends('layouts.app')

@section('title', 'Student Card Details')

@section('content')

<div class="ui-container-fluid">

    {{-- ============================================
         PRINTABLE PHYSICAL ID CARD (Hidden on screen)
         Branch design: ID Card Designer > Permanent card stock
    ============================================ --}}
    @if($card->status === 'assigned' && $card->student)
        <div id="printable-student-card">
            @include('id-cards.card', [
                'card' => \App\Support\CardDesign::card(
                    \App\Support\CardDesign::STOCK,
                    ['qr' => $card->qr_code, 'code' => $card->qr_code],
                    false,
                    \App\Support\CardDesign::get(\App\Support\CardDesign::STOCK, (int) $card->branch_id)
                ),
            ])
        </div>
    @endif

    {{-- Header --}}
    <div class="tw:flex! tw:justify-between! tw:items-center! tw:mb-6!">
        <div>
            <h2 class="tw:font-bold!">
                <i class="bi bi-person-vcard-fill tw:text-bs-primary!"></i>
                Student Card Details
            </h2>
            <p class="tw:text-bs-secondary-color! tw:mb-0!">
                View student card information.
            </p>
        </div>
        <div class="tw:flex! tw:gap-2!">
            <a href="{{ route('student-cards.index') }}" class="ui-btn ui-btn-secondary">
                <i class="bi bi-arrow-left"></i>
                Back
            </a>
        </div>
    </div>

    <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3">
        {{-- Student Profile Card --}}
        <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[33.33333333%]">
            <div class="ui-card tw:shadow-bs-sm! tw:[border:0]! tw:rounded-bs-xl! tw:overflow-hidden!">
                {{-- Card Header with Gradient --}}
                <div class="ui-card-header bg-gradient-primary tw:text-bs-white! tw:py-6!" style="background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);">
                    <div class="tw:text-center!">
                        <div class="tw:relative! tw:[display:inline-block]!">
                            {{-- Student Photo --}}
                            @if($card->student && $card->student->img_url)
                                @php
                                    // Handle different image URL formats
                                    $imageUrl = $card->student->img_url;

                                    // Check if it's already a full URL
                                    if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                                        $fullImageUrl = $imageUrl;
                                    } else {
                                        // Remove leading slash if exists
                                        $imageUrl = ltrim($imageUrl, '/');
                                        // Check if it already contains 'storage/'
                                        if (strpos($imageUrl, 'storage/') === 0) {
                                            $fullImageUrl = asset($imageUrl);
                                        } else {
                                            $fullImageUrl = asset('storage/' . $imageUrl);
                                        }
                                    }
                                @endphp
                                <img src="{{ $fullImageUrl }}"
                                     alt="{{ $card->student->initial_name }}"
                                     class="tw:rounded-[50%]! tw:[border:3px_solid_#fff]! tw:shadow-bs-sm!"
                                     style="width: 100px; height: 100px; object-fit: cover;"
                                     onerror="this.onerror=null; this.src='{{ asset('images/default-avatar.png') }}';">
                            @else
                                <div class="tw:rounded-[50%]! tw:[border:3px_solid_#fff]! tw:bg-bs-light! tw:flex! tw:items-center! tw:justify-center! tw:mx-auto!"
                                     style="width: 100px; height: 100px;">
                                    <i class="bi bi-person tw:text-[length:calc(1.375rem_+_1.5vw)]! tw:xl:text-[length:2.5rem]! tw:text-bs-secondary!"></i>
                                </div>
                            @endif

                            {{-- Status Badge on Photo --}}
                            <span class="tw:absolute! tw:bottom-0! tw:right-0!">
                                @if($card->status == 'assigned')
                                    <span class="ui-badge tw:bg-bs-success! tw:rounded-bs-pill! tw:px-4! tw:py-2! tw:[border:1px_solid_#fff]!">
                                        <i class="bi bi-check-circle-fill tw:mr-1!"></i> Active
                                    </span>
                                @elseif($card->status == 'lost')
                                    <span class="ui-badge tw:bg-bs-danger! tw:rounded-bs-pill! tw:px-4! tw:py-2! tw:[border:1px_solid_#fff]!">
                                        <i class="bi bi-x-circle-fill tw:mr-1!"></i> Lost
                                    </span>
                                @elseif($card->status == 'damaged')
                                    <span class="ui-badge tw:bg-bs-warning! tw:rounded-bs-pill! tw:px-4! tw:py-2! tw:[border:1px_solid_#fff]!">
                                        <i class="bi bi-exclamation-triangle-fill tw:mr-1!"></i> Damaged
                                    </span>
                                @else
                                    <span class="ui-badge tw:bg-bs-secondary! tw:rounded-bs-pill! tw:px-4! tw:py-2! tw:[border:1px_solid_#fff]!">
                                        {{ ucfirst($card->status) }}
                                    </span>
                                @endif
                            </span>
                        </div>

                        <h4 class="tw:font-bold! tw:mt-4! tw:mb-1! tw:text-bs-white!">
                            {{ $card->student ? $card->student->initial_name : 'Not Assigned' }}
                        </h4>
                        @if($card->student)
                            <p class="tw:text-[rgba(255,255,255,0.5)]! tw:mb-0!">
                                <i class="bi bi-person-badge tw:mr-1!"></i>
                                {{ $card->student->custom_id ?? 'ID: ' . $card->student->id }}
                            </p>
                        @endif
                    </div>
                </div>

                <div class="ui-card-body tw:p-6!">
                    @if($card->student)
                        {{-- QR Code Preview --}}
                        <div class="tw:text-center! tw:mb-6!">
                            <div class="tw:bg-bs-light! tw:p-4! tw:rounded-bs-lg! tw:[display:inline-block]!">
                                @if($card->qr_code)
                                    <div class="qr-code-preview" style="width: 120px; height: 120px; background: white; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                        @if(class_exists('SimpleSoftwareIO\QrCode\Facades\QrCode'))
                                            {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(120)->generate($card->qr_code) !!}
                                        @else
                                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={{ urlencode($card->qr_code) }}"
                                                 alt="QR Code"
                                                 style="width: 120px; height: 120px;">
                                        @endif
                                    </div>
                                @else
                                    <div class="tw:bg-bs-white! tw:flex! tw:items-center! tw:justify-center!" style="width: 120px; height: 120px;">
                                        <i class="bi bi-qr-code tw:text-[length:calc(1.375rem_+_1.5vw)]! tw:xl:text-[length:2.5rem]! tw:text-bs-secondary-color!"></i>
                                    </div>
                                @endif
                            </div>
                            <p class="tw:text-bs-secondary-color! tw:text-[.875em] tw:mt-2! tw:mb-0!">
                                <i class="bi bi-qr-code tw:mr-1!"></i>
                                {{ $card->qr_code ?? 'No QR Code' }}
                            </p>
                        </div>

                        {{-- Student Information Grid --}}
                        <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2">
                            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[50%]">
                                <div class="info-item">
                                    <label class="tw:text-bs-secondary-color! tw:text-[.875em] tw:uppercase! tw:font-semibold! tw:block!">Student ID</label>
                                    <p class="tw:font-semibold! tw:mb-0!">{{ $card->student->custom_id ?? '#' . $card->student->id }}</p>
                                </div>
                            </div>
                            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[50%]">
                                <div class="info-item">
                                    <label class="tw:text-bs-secondary-color! tw:text-[.875em] tw:uppercase! tw:font-semibold! tw:block!">Grade</label>
                                    <p class="tw:font-semibold! tw:mb-0!">
                                        @if($card->student->grade)
                                            <span class="ui-badge tw:bg-bs-info! tw:text-bs-white!">{{ $card->student->grade->grade_name }}</span>
                                        @else
                                            <span class="tw:text-bs-secondary-color!">-</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[100%]">
                                <div class="info-item">
                                    <label class="tw:text-bs-secondary-color! tw:text-[.875em] tw:uppercase! tw:font-semibold! tw:block!">
                                        <i class="bi bi-telephone tw:mr-1!"></i> Mobile
                                    </label>
                                    <p class="tw:font-semibold! tw:mb-0!">
                                        {{ $card->student->mobile ?? 'Not provided' }}
                                    </p>
                                </div>
                            </div>
                            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[100%]">
                                <div class="info-item">
                                    <label class="tw:text-bs-secondary-color! tw:text-[.875em] tw:uppercase! tw:font-semibold! tw:block!">
                                        <i class="bi bi-envelope tw:mr-1!"></i> Email
                                    </label>
                                    <p class="tw:font-semibold! tw:mb-0!">
                                        {{ $card->student->email ?? 'Not provided' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- Active Classes --}}
                        <div class="tw:mt-6!">
                            <label class="tw:text-bs-secondary-color! tw:text-[.875em] tw:uppercase! tw:font-semibold! tw:block! tw:mb-2!">
                                <i class="bi bi-book tw:mr-1!"></i> Active Classes
                            </label>
                            @php
                                $activeEnrollments = $card->student->enrollments->where('is_active', true);
                            @endphp
                            @if($activeEnrollments->isEmpty())
                                <p class="tw:text-bs-secondary-color! tw:mb-0!">No active classes</p>
                            @else
                                <div class="tw:flex! tw:flex-wrap! tw:gap-2!">
                                    @foreach($activeEnrollments as $enrollment)
                                        <div class="class-badge">
                                            <span class="ui-badge tw:bg-bs-primary! tw:[background-image:linear-gradient(180deg,_rgba(255,255,255,0.15),_rgba(255,255,255,0))]! tw:px-4! tw:py-2! tw:rounded-bs-pill!">
                                                <i class="bi bi-bookmark-fill tw:mr-1!"></i>
                                                {{ $enrollment->studentClass->class_name }}
                                            </span>
                                            @if($enrollment->category)
                                                <span class="ui-badge tw:bg-bs-secondary! tw:[background-image:linear-gradient(180deg,_rgba(255,255,255,0.15),_rgba(255,255,255,0))]! tw:px-4! tw:py-2! tw:rounded-bs-pill! tw:ml-1!">
                                                    {{ $enrollment->category->category_name }}
                                                </span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Guardian Information --}}
                        <div class="tw:mt-6! tw:pt-4! tw:bs-border-t!">
                            <label class="tw:text-bs-secondary-color! tw:text-[.875em] tw:uppercase! tw:font-semibold! tw:block! tw:mb-2!">
                                <i class="bi bi-person-lines-fill tw:mr-1!"></i> Guardian
                            </label>
                            <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3">
                                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:flex-[0_0_auto] tw:w-[100%]">
                                    <p class="tw:mb-1!">
                                        <strong>{{ $card->student->guardian_fname ?? '' }} {{ $card->student->guardian_lname ?? '' }}</strong>
                                    </p>
                                    <p class="tw:text-bs-secondary-color! tw:text-[.875em] tw:mb-0!">
                                        <i class="bi bi-telephone tw:mr-1!"></i>
                                        {{ $card->student->guardian_mobile ?? 'Not provided' }}
                                    </p>
                                    @if($card->student->guardian_nic)
                                        <p class="tw:text-bs-secondary-color! tw:text-[.875em] tw:mb-0!">
                                            <i class="bi bi-card-text tw:mr-1!"></i>
                                            NIC: {{ $card->student->guardian_nic }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="tw:text-center! tw:py-12!">
                            <i class="bi bi-person-x tw:text-[length:calc(1.375rem_+_1.5vw)]! tw:xl:text-[length:2.5rem]! tw:text-bs-secondary-color!"></i>
                            <p class="tw:text-bs-secondary-color! tw:mt-2!">This card has not been assigned to a student.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Card Information & Details --}}
        <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[66.66666667%]">
            <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3">
                {{-- Card Details --}}
                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">
                    <div class="ui-card tw:shadow-bs-sm! tw:[border:0]! tw:rounded-bs-xl!">
                        <div class="ui-card-header tw:bg-bs-white! tw:[border:0]! tw:pt-6!">
                            <h5 class="tw:mb-0! tw:font-bold!">
                                <i class="bi bi-credit-card-2-front tw:text-bs-primary! tw:mr-2!"></i>
                                Card Information
                            </h5>
                        </div>
                        <div class="ui-card-body tw:pt-0!">
                            <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3">
                                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                                    <div class="info-card tw:p-4! tw:bg-bs-light! tw:rounded-bs-lg!">
                                        <label class="tw:text-bs-secondary-color! tw:text-[.875em] tw:uppercase! tw:font-semibold! tw:block!">Card Number</label>
                                        <p class="tw:text-[length:1.25rem]! tw:font-bold! tw:mb-0! tw:text-bs-primary!">
                                            <i class="bi bi-hash tw:mr-1!"></i>
                                            {{ $card->card_number }}
                                        </p>
                                    </div>
                                </div>
                                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                                    <div class="info-card tw:p-4! tw:bg-bs-light! tw:rounded-bs-lg!">
                                        <label class="tw:text-bs-secondary-color! tw:text-[.875em] tw:uppercase! tw:font-semibold! tw:block!">Status</label>
                                        @if ($card->status == 'available')
                                            <span class="ui-badge tw:bg-bs-success! tw:[background-image:linear-gradient(180deg,_rgba(255,255,255,0.15),_rgba(255,255,255,0))]! tw:text-[length:1rem]! tw:px-6! tw:py-2!">
                                                <i class="bi bi-check-circle tw:mr-1!"></i> Available
                                            </span>
                                        @elseif($card->status == 'assigned')
                                            <span class="ui-badge tw:bg-bs-primary! tw:[background-image:linear-gradient(180deg,_rgba(255,255,255,0.15),_rgba(255,255,255,0))]! tw:text-[length:1rem]! tw:px-6! tw:py-2!">
                                                <i class="bi bi-person-check tw:mr-1!"></i> Assigned
                                            </span>
                                        @elseif($card->status == 'lost')
                                            <span class="ui-badge tw:bg-bs-danger! tw:[background-image:linear-gradient(180deg,_rgba(255,255,255,0.15),_rgba(255,255,255,0))]! tw:text-[length:1rem]! tw:px-6! tw:py-2!">
                                                <i class="bi bi-x-circle tw:mr-1!"></i> Lost
                                            </span>
                                        @elseif($card->status == 'damaged')
                                            <span class="ui-badge tw:bg-bs-warning! tw:[background-image:linear-gradient(180deg,_rgba(255,255,255,0.15),_rgba(255,255,255,0))]! tw:text-[length:1rem]! tw:px-6! tw:py-2!">
                                                <i class="bi bi-exclamation-triangle tw:mr-1!"></i> Damaged
                                            </span>
                                        @else
                                            <span class="ui-badge tw:bg-bs-secondary! tw:[background-image:linear-gradient(180deg,_rgba(255,255,255,0.15),_rgba(255,255,255,0))]! tw:text-[length:1rem]! tw:px-6! tw:py-2!">
                                                {{ ucfirst($card->status) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                                    <div class="info-card tw:p-4! tw:bg-bs-light! tw:rounded-bs-lg!">
                                        <label class="tw:text-bs-secondary-color! tw:text-[.875em] tw:uppercase! tw:font-semibold! tw:block!">Current Card</label>
                                        @if ($card->is_current)
                                            <span class="ui-badge tw:bg-bs-success! tw:[background-image:linear-gradient(180deg,_rgba(255,255,255,0.15),_rgba(255,255,255,0))]! tw:text-[length:1rem]! tw:px-6! tw:py-2!">
                                                <i class="bi bi-star-fill tw:mr-1!"></i> Active
                                            </span>
                                        @else
                                            <span class="ui-badge tw:bg-bs-secondary! tw:[background-image:linear-gradient(180deg,_rgba(255,255,255,0.15),_rgba(255,255,255,0))]! tw:text-[length:1rem]! tw:px-6! tw:py-2!">
                                                <i class="bi bi-clock tw:mr-1!"></i> Inactive
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                                    <div class="info-card tw:p-4! tw:bg-bs-light! tw:rounded-bs-lg!">
                                        <label class="tw:text-bs-secondary-color! tw:text-[.875em] tw:uppercase! tw:font-semibold! tw:block!">Issued Date</label>
                                        <p class="tw:font-semibold! tw:mb-0!">
                                            <i class="bi bi-calendar-event tw:mr-1!"></i>
                                            {{ optional($card->issued_at)->format('d M Y') }}
                                            <span class="tw:text-bs-secondary-color! tw:text-[.875em] tw:block!">
                                                {{ optional($card->issued_at)->format('h:i A') }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                                    <div class="info-card tw:p-4! tw:bg-bs-light! tw:rounded-bs-lg!">
                                        <label class="tw:text-bs-secondary-color! tw:text-[.875em] tw:uppercase! tw:font-semibold! tw:block!">QR Code</label>
                                        <p class="tw:font-semibold! tw:mb-0!">
                                            <code class="tw:bg-bs-white! tw:p-1! tw:rounded-bs!">{{ $card->qr_code ?? 'N/A' }}</code>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Enrollment Summary --}}
                @if($card->student)
                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">
                    <div class="ui-card tw:shadow-bs-sm! tw:[border:0]! tw:rounded-bs-xl!">
                        <div class="ui-card-header tw:bg-bs-white! tw:[border:0]! tw:pt-6!">
                            <h5 class="tw:mb-0! tw:font-bold!">
                                <i class="bi bi-clock-history tw:text-bs-primary! tw:mr-2!"></i>
                                Enrollment Summary
                            </h5>
                        </div>
                        <div class="ui-card-body tw:pt-0!">
                            <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2">
                                @php
                                    $enrollments = $card->student->enrollments;
                                    $activeCount = $enrollments->where('is_active', true)->count();
                                    $totalEnrollments = $enrollments->count();
                                @endphp
                                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                                    <div class="stat-card tw:text-center! tw:p-4! tw:bg-[rgba(13,110,253,0.1)]! tw:rounded-bs-lg!">
                                        <h3 class="tw:mb-0! tw:text-bs-primary!">{{ $activeCount }}</h3>
                                        <p class="tw:text-bs-secondary-color! tw:text-[.875em] tw:mb-0!">Active Classes</p>
                                    </div>
                                </div>
                                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                                    <div class="stat-card tw:text-center! tw:p-4! tw:bg-[rgba(25,135,84,0.1)]! tw:rounded-bs-lg!">
                                        <h3 class="tw:mb-0! tw:text-bs-success!">{{ $totalEnrollments }}</h3>
                                        <p class="tw:text-bs-secondary-color! tw:text-[.875em] tw:mb-0!">Total Enrollments</p>
                                    </div>
                                </div>
                                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                                    <div class="stat-card tw:text-center! tw:p-4! tw:bg-[rgba(13,202,240,0.1)]! tw:rounded-bs-lg!">
                                        <h3 class="tw:mb-0! tw:text-bs-info!">{{ $enrollments->where('is_active', false)->count() }}</h3>
                                        <p class="tw:text-bs-secondary-color! tw:text-[.875em] tw:mb-0!">Completed/Inactive</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Recent Enrollments List --}}
                            @if($enrollments->isNotEmpty())
                            <div class="tw:mt-4!">
                                <label class="tw:text-bs-secondary-color! tw:text-[.875em] tw:uppercase! tw:font-semibold! tw:block! tw:mb-2!">Recent Enrollments</label>
                                <div class="ui-table-responsive">
                                    <table class="ui-table ui-table-sm ui-table-hover tw:mb-0!">
                                        <thead>
                                            <tr>
                                                <th>Class</th>
                                                <th>Category</th>
                                                <th>Status</th>
                                                <th>Enrolled Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($enrollments->take(5) as $enrollment)
                                                <tr>
                                                    <td>{{ $enrollment->studentClass->class_name ?? 'N/A' }}</td>
                                                    <td>
                                                        @if($enrollment->category)
                                                            <span class="ui-badge tw:bg-bs-secondary!">{{ $enrollment->category->category_name }}</span>
                                                        @else
                                                            <span class="tw:text-bs-secondary-color!">-</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($enrollment->is_active)
                                                            <span class="ui-badge tw:bg-bs-success!">Active</span>
                                                        @else
                                                            <span class="ui-badge tw:bg-bs-secondary!">Inactive</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ optional($enrollment->enrolled_at)->format('d M Y') ?? '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    }

    .info-item {
        padding: 6px 0;
    }

    .info-item label {
        font-size: 0.7rem;
        letter-spacing: 0.5px;
        color: #6c757d;
    }

    .info-item p {
        font-size: 0.95rem;
        margin-bottom: 2px;
    }

    .class-badge {
        display: inline-block;
        margin-bottom: 4px;
    }

    .stat-card {
        transition: transform 0.2s;
    }

    .stat-card:hover {
        transform: translateY(-2px);
    }

    .info-card {
        transition: transform 0.2s, box-shadow 0.2s;
        height: 100%;
    }

    .info-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }

    .qr-code-preview {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .qr-code-preview svg {
        max-width: 100%;
        height: auto;
    }

    /* Image error fallback */
    img[onerror] {
        background: #f8f9fa;
    }

    /* ==========================================
       PRINTABLE PHYSICAL ID CARD
       (Hidden on screen, visible only in print)
    ========================================== */

    #printable-student-card {
        position: relative;
        width: 85.6mm;
        height: 54mm;
        overflow: hidden;
        background: #fff;
        border-radius: 0;
    }

    #printable-student-card .card-bg {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: fill;
    }

    /* QR Image - Default (Branch 1 / Fallback) */
    #printable-student-card .qr-image {
        position: absolute;
        top: 33%;
        right: 7.3%;
        width: 24%;
        height: 45%;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2;
        padding: 0;
    }

    #printable-student-card .qr-image svg {
        width: 85%;
        height: 85%;
        display: block;
    }

    /* QR Text - Default (Branch 1 / Fallback) */
    #printable-student-card .qr-text {
        position: absolute;
        top: 44%;
        right: 44%;
        width: 24%;
        height: 8%;
        display: flex;
        justify-content: center;
        align-items: center;
        text-align: center;
        font-size: 13px;
        font-weight: 800;
        color: #111;
        letter-spacing: .3px;
        white-space: nowrap;
        overflow: hidden;
        padding: 0;
        z-index: 2;
    }

    /* ==========================================
       BRANCH 2 TEMPLATE
    ========================================== */

    #printable-student-card.branch-2 .qr-image {
        top: 22%;
        right: 4.5%;
        width: 23%;
        height: 42%;
        padding: 0;
    }

    #printable-student-card.branch-2 .qr-image svg {
        width: 92%;
        height: 92%;
    }

    #printable-student-card.branch-2 .qr-text {
        top: 66%;
        right: 4%;
        width: 24%;
        height: 7%;
        font-size: 10px;
    }

    /* ==========================================
       BRANCH 3 TEMPLATE
    ========================================== */

    #printable-student-card.branch-3 .qr-image {
        top: 30%;
        right: 6%;
        width: 23%;
        height: 44%;
        padding: 0;
    }

    #printable-student-card.branch-3 .qr-image svg {
        width: 90%;
        height: 90%;
    }

    #printable-student-card.branch-3 .qr-text {
        top: 76%;
        right: 6%;
        width: 23%;
        height: 7%;
        font-size: 11px;
    }

    /* ==========================================
       BRANCH 4 TEMPLATE
    ========================================== */

    #printable-student-card.branch-4 .qr-image {
        top: 26%;
        right: 5%;
        width: 24%;
        height: 44%;
        padding: 0;
    }

    #printable-student-card.branch-4 .qr-image svg {
        width: 92%;
        height: 92%;
    }

    #printable-student-card.branch-4 .qr-text {
        top: 72%;
        right: 5%;
        width: 24%;
        height: 7%;
        font-size: 10px;
    }

    /* ==========================================
       HIDE PHYSICAL CARD ON SCREEN
    ========================================== */

    #printable-student-card {
        display: none;
    }

    /* ==========================================
       PRINT - ONLY PHYSICAL ID CARD
    ========================================== */

    @media print {
        @page {
            size: 3.375in 2.125in;
            margin: 0;
        }

        body {
            margin: 0 !important;
            padding: 0 !important;
            background: #fff !important;
        }

        body * {
            visibility: hidden !important;
        }

        #printable-student-card,
        #printable-student-card * {
            visibility: visible !important;
        }

        #printable-student-card {
            display: block !important;
            position: fixed;
            left: 0;
            top: 0;
            width: 3.375in;
            height: 2.125in;
            margin: 0;
            padding: 0;
            border: none;
            border-radius: 0;
            box-shadow: none;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    // Optional: Add image loading handling
    document.addEventListener('DOMContentLoaded', function() {
        const studentImages = document.querySelectorAll('img[alt]');
        studentImages.forEach(img => {
            img.addEventListener('error', function() {
                this.onerror = null;
                this.src = '{{ asset("images/default-avatar.png") }}';
            });
        });
    });
</script>
@endpush
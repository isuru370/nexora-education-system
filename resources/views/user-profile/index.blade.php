@extends('layouts.app')

@section('title', 'User Profile')
@section('page-title', 'User Profile')

@section('content')
    <div class="dashboard-page">

        {{-- HERO --}}
        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">User Profile</h1>
                    <p class="hero-subtitle">Manage your personal information, contact details and password.</p>
                </div>
                <div class="hero-right">
                    <div class="hero-date-time">
                        <div class="date-box">
                            <i class="bi bi-person-badge"></i>
                            <span>{{ $user->userType?->name ?? 'User' }}</span>
                        </div>
                    </div>
                    @if ($user->is_active)
                        <span class="nx-badge nx-badge-green">Active</span>
                    @else
                        <span class="nx-badge nx-badge-gray">Inactive</span>
                    @endif
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

        @if (session('error'))
            <div class="nx-alert nx-alert-red mb-4">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <div class="row g-4">
            {{-- PROFILE INFORMATION --}}
            <div class="col-lg-8">
                <div class="nx-card">
                    <div class="nx-card-header">
                        <div>
                            <h5 class="nx-card-title"><i class="bi bi-person-badge text-primary"></i> Profile Information</h5>
                            <p class="nx-card-subtitle">Update your personal information and contact details</p>
                        </div>
                    </div>
                    <div class="p-3 p-md-4">
                        <form action="{{ route('admin.profile.update') }}" method="POST" id="profileForm">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="nx-form-group">
                                        <label class="nx-label"><i class="bi bi-person-circle me-1"></i> Login Name</label>
                                        <input type="text" name="name" class="form-control nx-input @error('name') is-invalid @enderror"
                                            value="{{ old('name', $user->name) }}" placeholder="Enter login name">
                                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="nx-form-group">
                                        <label class="nx-label"><i class="bi bi-envelope me-1"></i> Email Address</label>
                                        <input type="email" name="email" class="form-control nx-input @error('email') is-invalid @enderror"
                                            value="{{ old('email', $user->email) }}" placeholder="Enter email address">
                                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="nx-form-group">
                                        <label class="nx-label"><i class="bi bi-person me-1"></i> Full Name</label>
                                        <input type="text" name="full_name" class="form-control nx-input @error('full_name') is-invalid @enderror"
                                            value="{{ old('full_name', $user->systemUser?->full_name) }}" placeholder="Enter full name">
                                        @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="nx-form-group">
                                        <label class="nx-label"><i class="bi bi-phone me-1"></i> Mobile Number</label>
                                        <input type="text" name="mobile" class="form-control nx-input"
                                            value="{{ old('mobile', $user->systemUser?->mobile) }}" placeholder="Enter mobile number">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="nx-form-group">
                                        <label class="nx-label"><i class="bi bi-card-text me-1"></i> NIC</label>
                                        <input type="text" name="nic" class="form-control nx-input"
                                            value="{{ old('nic', $user->systemUser?->nic) }}" placeholder="Enter NIC number">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="nx-form-group">
                                        <label class="nx-label"><i class="bi bi-calendar-heart me-1"></i> Birthday</label>
                                        <input type="date" name="bday" class="form-control nx-input"
                                            value="{{ old('bday', optional($user->systemUser?->bday)->format('Y-m-d')) }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="nx-form-group">
                                        <label class="nx-label"><i class="bi bi-gender-ambiguous me-1"></i> Gender</label>
                                        <select name="gender" class="form-select nx-input">
                                            <option value="">Select Gender</option>
                                            <option value="male" {{ old('gender', $user->systemUser?->gender) == 'male' ? 'selected' : '' }}>Male</option>
                                            <option value="female" {{ old('gender', $user->systemUser?->gender) == 'female' ? 'selected' : '' }}>Female</option>
                                            <option value="other" {{ old('gender', $user->systemUser?->gender) == 'other' ? 'selected' : '' }}>Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="nx-form-group">
                                        <label class="nx-label"><i class="bi bi-house-door me-1"></i> Address Line 1</label>
                                        <input type="text" name="address1" class="form-control nx-input"
                                            value="{{ old('address1', $user->systemUser?->address1) }}" placeholder="Street address">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="nx-form-group">
                                        <label class="nx-label"><i class="bi bi-building me-1"></i> Address Line 2</label>
                                        <input type="text" name="address2" class="form-control nx-input"
                                            value="{{ old('address2', $user->systemUser?->address2) }}" placeholder="City">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="nx-form-group">
                                        <label class="nx-label"><i class="bi bi-geo-alt me-1"></i> Address Line 3</label>
                                        <input type="text" name="address3" class="form-control nx-input"
                                            value="{{ old('address3', $user->systemUser?->address3) }}" placeholder="Postal code / District">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="nx-form-group">
                                        <label class="nx-label"><i class="bi bi-pencil-square me-1"></i> Note</label>
                                        <textarea name="note" rows="3" class="form-control nx-input"
                                            placeholder="Additional notes...">{{ old('note', $user->systemUser?->note) }}</textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="nx-form-actions">
                                <button type="submit" class="nx-btn nx-btn-primary">
                                    <i class="bi bi-save"></i> Update Profile
                                </button>
                                <button type="button" class="nx-btn nx-btn-light btn-reset">
                                    <i class="bi bi-arrow-repeat"></i> Reset
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- SIDEBAR --}}
            <div class="col-lg-4">
                <div class="nx-card mb-4">
                    <div class="nx-card-header">
                        <div>
                            <h5 class="nx-card-title"><i class="bi bi-shield-lock text-primary"></i> Change Password</h5>
                            <p class="nx-card-subtitle">Update your account password</p>
                        </div>
                    </div>
                    <div class="p-3">
                        <form action="{{ route('admin.profile.password') }}" method="POST" id="passwordForm">
                            @csrf
                            <div class="nx-form-group mb-3">
                                <label class="nx-label"><i class="bi bi-lock me-1"></i> Current Password</label>
                                <div class="password-input-wrapper">
                                    <input type="password" name="current_password" id="current_password"
                                        class="form-control nx-input @error('current_password') is-invalid @enderror"
                                        placeholder="Enter current password">
                                    <i class="bi bi-eye-slash toggle-password" data-target="current_password"></i>
                                </div>
                                @error('current_password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="nx-form-group mb-3">
                                <label class="nx-label"><i class="bi bi-key me-1"></i> New Password</label>
                                <div class="password-input-wrapper">
                                    <input type="password" name="password" id="new_password"
                                        class="form-control nx-input @error('password') is-invalid @enderror"
                                        placeholder="Enter new password">
                                    <i class="bi bi-eye-slash toggle-password" data-target="new_password"></i>
                                </div>
                                <div class="password-strength mt-2">
                                    <div class="strength-bar"></div>
                                    <small class="strength-text"></small>
                                </div>
                                @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="nx-form-group mb-4">
                                <label class="nx-label"><i class="bi bi-check-circle me-1"></i> Confirm Password</label>
                                <div class="password-input-wrapper">
                                    <input type="password" name="password_confirmation" id="confirm_password"
                                        class="form-control nx-input" placeholder="Confirm new password">
                                    <i class="bi bi-eye-slash toggle-password" data-target="confirm_password"></i>
                                </div>
                            </div>
                            <button type="submit" class="nx-btn nx-btn-primary w-100">
                                <i class="bi bi-arrow-repeat"></i> Change Password
                            </button>
                        </form>
                    </div>
                </div>

                <div class="nx-card">
                    <div class="nx-card-header">
                        <div>
                            <h5 class="nx-card-title"><i class="bi bi-info-circle text-primary"></i> User Details</h5>
                            <p class="nx-card-subtitle">Account information summary</p>
                        </div>
                    </div>
                    <div class="p-3">
                        <div class="details-list">
                            <div class="detail-item">
                                <div class="detail-icon"><i class="bi bi-person-badge"></i></div>
                                <div class="detail-content">
                                    <span class="detail-label">User Type</span>
                                    <span class="detail-value">{{ $user->userType?->name ?? 'N/A' }}</span>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-icon"><i class="bi bi-toggle-on"></i></div>
                                <div class="detail-content">
                                    <span class="detail-label">Status</span>
                                    <span class="detail-value">
                                        @if ($user->is_active)
                                            <span class="nx-badge nx-badge-green"><i class="bi bi-check-circle-fill"></i> Active</span>
                                        @else
                                            <span class="nx-badge nx-badge-red"><i class="bi bi-x-circle-fill"></i> Inactive</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-icon"><i class="bi bi-calendar-check"></i></div>
                                <div class="detail-content">
                                    <span class="detail-label">Member Since</span>
                                    <span class="detail-value">{{ $user->created_at?->format('d M Y') ?? 'N/A' }}</span>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-icon"><i class="bi bi-clock-history"></i></div>
                                <div class="detail-content">
                                    <span class="detail-label">Last Updated</span>
                                    <span class="detail-value">{{ $user->updated_at?->format('d M Y') ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection


@push('styles')
<style>
    /* ============================================================
       User Profile — page-specific styles only.
       ============================================================ */

    .dashboard-page .nx-form-group { margin-bottom: 0; }

    .dashboard-page textarea.nx-input { min-height: 80px; padding-top: .5rem; }

    .dashboard-page .nx-form-actions {
        display: flex;
        gap: .5rem;
        margin-top: 1.5rem;
        padding-top: 1rem;
        border-top: 1px solid var(--nx-border);
        flex-wrap: wrap;
    }

    .dashboard-page .password-input-wrapper { position: relative; }
    .dashboard-page .password-input-wrapper input { padding-right: 45px; }
    .dashboard-page .toggle-password {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: var(--nx-muted);
        font-size: 1rem;
        transition: color .15s ease;
    }
    .dashboard-page .toggle-password:hover { color: var(--nx-blue); }

    .dashboard-page .password-strength { margin-top: 8px; }
    .dashboard-page .strength-bar {
        height: 4px;
        background: #eef1f6;
        border-radius: 4px;
        transition: all .3s ease;
        width: 0%;
    }
    .dashboard-page .strength-text {
        font-size: .7rem;
        color: var(--nx-muted);
        margin-top: 4px;
        display: block;
    }

    .dashboard-page .details-list {
        display: flex;
        flex-direction: column;
        gap: 0;
    }
    .dashboard-page .detail-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: .75rem 0;
        border-bottom: 1px solid var(--nx-border);
    }
    .dashboard-page .detail-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    .dashboard-page .detail-icon {
        width: 38px; height: 38px;
        border-radius: 10px;
        background: var(--nx-blue-soft);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--nx-blue);
        font-size: 1rem;
        flex-shrink: 0;
    }
    .dashboard-page .detail-content { flex: 1; }
    .dashboard-page .detail-label {
        display: block;
        font-size: .7rem;
        color: var(--nx-muted);
        text-transform: uppercase;
        letter-spacing: .4px;
        margin-bottom: 4px;
    }
    .dashboard-page .detail-value {
        display: block;
        font-size: .88rem;
        font-weight: 600;
        color: var(--nx-text);
    }

    .dashboard-page .nx-badge-red { background: var(--nx-red-soft); color: var(--nx-red); }

    .dashboard-page .form-control.is-invalid,
    .dashboard-page .form-select.is-invalid { border-color: var(--nx-red); }
    .dashboard-page .form-control.is-invalid:focus,
    .dashboard-page .form-select.is-invalid:focus { box-shadow: 0 0 0 3px var(--nx-red-soft); }

    @media (max-width: 768px) {
        .dashboard-page .nx-form-actions { flex-direction: column; }
        .dashboard-page .nx-form-actions .nx-btn { width: 100%; }
    }
</style>
@endpush
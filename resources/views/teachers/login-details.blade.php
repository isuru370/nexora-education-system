@extends('layouts.app')

@section('title', 'Teacher Login Details')
@section('page-title', 'Login Management')

@section('content')

<div class="login-details-page">

    <!-- MAIN CARD -->
    <div class="main-card">

        <!-- HEADER -->
        <div class="main-card-header">

            <div>
                <h4>
                    <i class="bi bi-key-fill tw:text-bs-primary! tw:mr-2!"></i>
                    Teacher Login Details
                </h4>
                <p class="tw:text-bs-secondary-color! tw:mb-0!">
                    Manage login credentials for {{ $teacher->full_name ?? $teacher->name }}
                </p>
            </div>

            <!-- ACTION BUTTONS -->
            <div class="header-buttons">
                <a href="{{ route('teachers.show', $teacher) }}" class="ui-btn ui-btn-info custom-btn">
                    <i class="bi bi-eye-fill"></i>
                    View Profile
                </a>
                <a href="{{ route('teachers.edit', $teacher) }}" class="ui-btn ui-btn-warning custom-btn">
                    <i class="bi bi-pencil-fill"></i>
                    Edit Teacher
                </a>
                <a href="{{ route('teachers.index') }}" class="ui-btn ui-btn-light tw:bs-border! custom-btn">
                    <i class="bi bi-arrow-left"></i>
                    Back to List
                </a>
            </div>

        </div>

        <!-- ALERTS -->
        @if(session('success'))
            <div class="ui-alert ui-alert-success ui-alert-dismissible ui-fade show custom-alert" role="alert">
                <i class="bi bi-check-circle-fill tw:mr-2!"></i>
                {{ session('success') }}
                <button type="button" class="ui-btn-close" data-nx-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="ui-alert ui-alert-danger ui-alert-dismissible ui-fade show custom-alert" role="alert">
                <i class="bi bi-exclamation-circle-fill tw:mr-2!"></i>
                {{ session('error') }}
                <button type="button" class="ui-btn-close" data-nx-dismiss="alert"></button>
            </div>
        @endif

        <!-- TEACHER INFO CARD -->
        <div class="teacher-info-card">

            <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3 bs-row tw:items-center!">

                <!-- AVATAR & NAME -->
                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[41.66666667%]">

                    <div class="bs-d-flex tw:flex! tw:items-center! tw:gap-6!">

                        <div class="teacher-avatar-large">
                            {{ strtoupper(substr($teacher->full_name ?? $teacher->name, 0, 1)) }}
                        </div>

                        <div>
                            <h3 class="tw:mb-1!">{{ $teacher->full_name ?? $teacher->name }}</h3>
                            <span class="ui-badge custom-badge tw:bg-bs-light! tw:text-bs-dark! tw:bs-border!">
                                {{ $teacher->custom_id ?? 'No ID' }}
                            </span>
                            @if($teacher->is_active)
                                <span class="ui-badge custom-badge tw:bg-bs-success! tw:ml-2!">
                                    <i class="bi bi-check-circle-fill tw:mr-1!"></i> Active
                                </span>
                            @else
                                <span class="ui-badge custom-badge tw:bg-bs-secondary! tw:ml-2!">
                                    <i class="bi bi-pause-circle-fill tw:mr-1!"></i> Inactive
                                </span>
                            @endif
                        </div>

                    </div>

                </div>

                <!-- CONTACT INFO -->
                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[58.33333333%]">

                    <div class="tw:flex tw:flex-wrap tw:-mt-2 tw:-mx-1 bs-row">

                        <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:w-full tw:sm:flex-[0_0_auto] tw:sm:w-[50%]">

                            <div class="info-item">
                                <i class="bi bi-envelope-fill tw:text-bs-primary!"></i>
                                <div>
                                    <small class="tw:text-bs-secondary-color! tw:block!">Email</small>
                                    <span class="tw:font-semibold!">{{ $teacher->email ?? 'N/A' }}</span>
                                </div>
                            </div>

                        </div>

                        <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:w-full tw:sm:flex-[0_0_auto] tw:sm:w-[50%]">

                            <div class="info-item">
                                <i class="bi bi-phone-fill tw:text-bs-success!"></i>
                                <div>
                                    <small class="tw:text-bs-secondary-color! tw:block!">Mobile</small>
                                    <span class="tw:font-semibold!">{{ $teacher->mobile ?? 'N/A' }}</span>
                                </div>
                            </div>

                        </div>

                        <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:w-full tw:sm:flex-[0_0_auto] tw:sm:w-[50%]">

                            <div class="info-item">
                                <i class="bi bi-person-fill tw:text-bs-info!"></i>
                                <div>
                                    <small class="tw:text-bs-secondary-color! tw:block!">NIC</small>
                                    <span class="tw:font-semibold!">{{ $teacher->nic ?? 'N/A' }}</span>
                                </div>
                            </div>

                        </div>

                        <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:w-full tw:sm:flex-[0_0_auto] tw:sm:w-[50%]">

                            <div class="info-item">
                                <i class="bi bi-calendar-fill tw:text-bs-warning!"></i>
                                <div>
                                    <small class="tw:text-bs-secondary-color! tw:block!">Birthday</small>
                                    <span class="tw:font-semibold!">{{ $teacher->bday ? $teacher->bday->format('Y-m-d') : 'N/A' }}</span>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <!-- LOGIN STATUS -->
        <div class="login-status-card">

            <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3 bs-row tw:items-center!">

                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">

                    <div class="bs-d-flex tw:flex! tw:items-center! tw:gap-4!">

                        <div class="status-icon {{ $teacher->user_id ? 'status-success' : 'status-warning' }}">
                            <i class="bi {{ $teacher->user_id ? 'bi-check-lg' : 'bi-clock' }}"></i>
                        </div>

                        <div>
                            <h5 class="tw:mb-0!">
                                Login Account Status
                            </h5>
                            <p class="tw:mb-0! tw:text-bs-secondary-color!">
                                @if($teacher->user_id)
                                    <span class="tw:text-bs-success! tw:font-semibold!">
                                        <i class="bi bi-check-circle-fill tw:mr-1!"></i> Created
                                    </span>
                                    <span class="tw:mx-2!">|</span>
                                    <span class="tw:text-bs-secondary-color!">User ID: #{{ $teacher->user_id }}</span>
                                @else
                                    <span class="tw:text-bs-warning! tw:font-semibold!">
                                        <i class="bi bi-exclamation-triangle-fill tw:mr-1!"></i> Not Created
                                    </span>
                                    <span class="tw:text-bs-secondary-color! tw:ml-2!">(No login account exists)</span>
                                @endif
                            </p>
                        </div>

                    </div>

                </div>

                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] bs-text-md-end tw:md:text-right!">

                    @if($teacher->user_id)
                        <div class="bs-d-flex tw:flex! bs-justify-content-md-end tw:md:justify-end! tw:gap-2! tw:flex-wrap!">
                            <button class="ui-btn ui-btn-outline-info custom-btn" disabled>
                                <i class="bi bi-envelope-fill"></i>
                                Send Credentials
                            </button>
                        </div>
                    @endif

                </div>

            </div>

        </div>

        <!-- CREATE LOGIN FORM (When no user_id exists) -->
        @if(!$teacher->user_id)

            <div class="create-login-card">

                <div class="create-login-header">
                    <h5>
                        <i class="bi bi-person-plus-fill tw:text-bs-primary! tw:mr-2!"></i>
                        Create Login Account
                    </h5>
                    <p class="tw:text-bs-secondary-color! tw:mb-0!">
                        Set a password for this teacher to access the system
                    </p>
                </div>

                <form action="{{ route('teachers.create-login', $teacher) }}" method="POST">

                    @csrf

                    <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3 bs-row">

                        <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">

                            <div class="form-group">
                                <label for="password" class="ui-form-label tw:font-semibold!">
                                    <i class="bi bi-lock-fill tw:mr-1!"></i>
                                    Password
                                    <span class="tw:text-bs-danger!">*</span>
                                </label>
                                <div class="password-input-wrapper">
                                    <i class="bi bi-key-fill"></i>
                                    <input
                                        type="password"
                                        id="password"
                                        name="password"
                                        class="ui-form-control custom-input @error('password') ui-is-invalid @enderror"
                                        placeholder="Enter a strong password"
                                        required
                                        minlength="8">
                                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                        <i class="bi bi-eye-slash-fill" id="password-icon"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <div class="ui-invalid-feedback tw:block!">
                                        <i class="bi bi-exclamation-circle-fill tw:mr-1!"></i>
                                        {{ $message }}
                                    </div>
                                @enderror
                                <small class="tw:text-bs-secondary-color!">
                                    <i class="bi bi-info-circle-fill tw:mr-1!"></i>
                                    Minimum 8 characters, include letters and numbers
                                </small>
                            </div>

                        </div>

                        <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">

                            <div class="form-group">
                                <label for="password_confirmation" class="ui-form-label tw:font-semibold!">
                                    <i class="bi bi-check-circle-fill tw:mr-1!"></i>
                                    Confirm Password
                                    <span class="tw:text-bs-danger!">*</span>
                                </label>
                                <div class="password-input-wrapper">
                                    <i class="bi bi-shield-lock-fill"></i>
                                    <input
                                        type="password"
                                        id="password_confirmation"
                                        name="password_confirmation"
                                        class="ui-form-control custom-input"
                                        placeholder="Confirm your password"
                                        required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('password_confirmation')">
                                        <i class="bi bi-eye-slash-fill" id="confirm-password-icon"></i>
                                    </button>
                                </div>
                                <div id="password-match-message" class="tw:mt-1!"></div>
                            </div>

                        </div>

                        <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">

                            <div class="password-requirements">
                                <h6 class="tw:mb-2!">
                                    <i class="bi bi-shield-check tw:mr-1!"></i>
                                    Password Requirements:
                                </h6>
                                <div class="tw:flex tw:flex-wrap tw:-mt-2 tw:-mx-1 bs-row">
                                    <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:flex-[0_0_auto] tw:w-[50%] tw:md:flex-[0_0_auto] tw:md:w-[25%]">
                                        <span class="requirement" id="req-length">
                                            <i class="bi bi-x-circle-fill tw:text-bs-danger!"></i>
                                            8+ characters
                                        </span>
                                    </div>
                                    <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:flex-[0_0_auto] tw:w-[50%] tw:md:flex-[0_0_auto] tw:md:w-[25%]">
                                        <span class="requirement" id="req-uppercase">
                                            <i class="bi bi-x-circle-fill tw:text-bs-danger!"></i>
                                            Uppercase letter
                                        </span>
                                    </div>
                                    <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:flex-[0_0_auto] tw:w-[50%] tw:md:flex-[0_0_auto] tw:md:w-[25%]">
                                        <span class="requirement" id="req-lowercase">
                                            <i class="bi bi-x-circle-fill tw:text-bs-danger!"></i>
                                            Lowercase letter
                                        </span>
                                    </div>
                                    <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:flex-[0_0_auto] tw:w-[50%] tw:md:flex-[0_0_auto] tw:md:w-[25%]">
                                        <span class="requirement" id="req-number">
                                            <i class="bi bi-x-circle-fill tw:text-bs-danger!"></i>
                                            Number
                                        </span>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">

                            <hr>

                            <div class="tw:mb-4!">
                                @include('partials.send-login-sms', ['id' => 'sendSmsCreateLogin'])
                            </div>

                            <div class="bs-d-flex tw:flex! tw:gap-4! tw:flex-wrap!">
                                <button type="submit" class="ui-btn ui-btn-primary custom-btn ui-btn-lg">
                                    <i class="bi bi-person-plus-fill tw:mr-2!"></i>
                                    Create Login Account
                                </button>
                                <button type="reset" class="ui-btn ui-btn-light tw:bs-border! custom-btn">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                    Clear Form
                                </button>
                                <a href="{{ route('teachers.index') }}" class="ui-btn ui-btn-outline-secondary custom-btn">
                                    <i class="bi bi-x-circle"></i>
                                    Cancel
                                </a>
                            </div>

                        </div>

                    </div>

                </form>

            </div>

        @else

            <!-- ACCOUNT CREATED - SHOW RESET PASSWORD -->
            <div class="account-created-card">

                <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3 bs-row">

                    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">

                        <div class="bs-d-flex tw:flex! tw:items-start! tw:gap-4!">

                            <div class="success-icon">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>

                            <div>
                                <h5 class="tw:text-bs-success!">
                                    <i class="bi bi-check-circle-fill tw:mr-2!"></i>
                                    Login Account Created
                                </h5>
                                <p class="tw:mb-1!">
                                    This teacher already has a login account.
                                    <span class="tw:font-semibold!">User ID: #{{ $teacher->user_id }}</span>
                                </p>
                                <ul class="ui-list-unstyled tw:mt-2!">
                                    <li>
                                        <i class="bi bi-check-circle tw:text-bs-success! tw:mr-2!"></i>
                                        Account active and ready to use
                                    </li>
                                    <li>
                                        <i class="bi bi-envelope tw:text-bs-primary! tw:mr-2!"></i>
                                        Login email: <span class="tw:font-semibold!">{{ $teacher->email }}</span>
                                    </li>
                                    <li>
                                        <i class="bi bi-phone tw:text-bs-success! tw:mr-2!"></i>
                                        Account linked to mobile: <span class="tw:font-semibold!">{{ $teacher->mobile }}</span>
                                    </li>
                                </ul>
                            </div>

                        </div>

                    </div>

                    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">

                        <!-- RESET PASSWORD FORM -->
                        <div class="reset-password-card">

                            <h6 class="tw:mb-4!">
                                <i class="bi bi-arrow-clockwise tw:text-bs-warning! tw:mr-2!"></i>
                                Reset Password
                            </h6>

                            <form action="{{ route('teachers.reset-password', $teacher) }}" method="POST">

                                @csrf

                                <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2 bs-row">

                                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[100%]">

                                        <div class="form-group">
                                            <label for="reset_password" class="ui-form-label tw:font-semibold!">
                                                <i class="bi bi-lock-fill tw:mr-1!"></i>
                                                New Password
                                                <span class="tw:text-bs-danger!">*</span>
                                            </label>
                                            <div class="password-input-wrapper">
                                                <i class="bi bi-key-fill"></i>
                                                <input
                                                    type="password"
                                                    id="reset_password"
                                                    name="password"
                                                    class="ui-form-control custom-input @error('password') ui-is-invalid @enderror"
                                                    placeholder="Enter new password"
                                                    required
                                                    minlength="8">
                                                <button type="button" class="password-toggle" onclick="togglePassword('reset_password')">
                                                    <i class="bi bi-eye-slash-fill" id="reset-password-icon"></i>
                                                </button>
                                            </div>
                                            @error('password')
                                                <div class="ui-invalid-feedback tw:block!">
                                                    <i class="bi bi-exclamation-circle-fill tw:mr-1!"></i>
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>

                                    </div>

                                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[100%]">

                                        <div class="form-group">
                                            <label for="reset_password_confirmation" class="ui-form-label tw:font-semibold!">
                                                <i class="bi bi-check-circle-fill tw:mr-1!"></i>
                                                Confirm New Password
                                                <span class="tw:text-bs-danger!">*</span>
                                            </label>
                                            <div class="password-input-wrapper">
                                                <i class="bi bi-shield-lock-fill"></i>
                                                <input
                                                    type="password"
                                                    id="reset_password_confirmation"
                                                    name="password_confirmation"
                                                    class="ui-form-control custom-input"
                                                    placeholder="Confirm new password"
                                                    required>
                                                <button type="button" class="password-toggle" onclick="togglePassword('reset_password_confirmation')">
                                                    <i class="bi bi-eye-slash-fill" id="reset-confirm-icon"></i>
                                                </button>
                                            </div>
                                            <div id="reset-password-match" class="tw:mt-1!"></div>
                                        </div>

                                    </div>

                                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[100%]">

                                        <div class="tw:mb-4!">
                                            @include('partials.send-login-sms', ['id' => 'sendSmsResetPassword'])
                                        </div>

                                        <div class="bs-d-flex tw:flex! tw:gap-2! tw:flex-wrap!">
                                            <button type="submit" class="ui-btn ui-btn-warning custom-btn">
                                                <i class="bi bi-arrow-clockwise tw:mr-2!"></i>
                                                Reset Password
                                            </button>
                                            <button type="reset" class="ui-btn ui-btn-light tw:bs-border! custom-btn">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                                Clear
                                            </button>
                                        </div>

                                        <small class="tw:text-bs-secondary-color! tw:block! tw:mt-2!">
                                            <i class="bi bi-info-circle-fill tw:mr-1!"></i>
                                            The teacher will receive a notification with their new credentials
                                        </small>

                                    </div>

                                </div>

                            </form>

                        </div>

                    </div>

                </div>

                <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3 bs-row tw:mt-4!">
                    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:flex-[0_0_auto] tw:w-[100%]">
                        <a href="{{ route('teachers.index') }}" class="ui-btn ui-btn-primary custom-btn">
                            <i class="bi bi-arrow-left"></i>
                            Back to Teachers
                        </a>
                    </div>
                </div>

            </div>

        @endif

    </div>

</div>

@endsection

@push('styles')
<style>
    .login-details-page {
        animation: fadeIn 0.4s ease;
    }

    /* MAIN CARD */
    .main-card {
        background: white;
        border-radius: 28px;
        padding: 1.5rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    }

    .main-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .main-card-header h4 {
        margin: 0;
        font-weight: 700;
    }

    .header-buttons {
        display: flex;
        gap: 0.7rem;
        flex-wrap: wrap;
    }

    .custom-btn {
        border-radius: 14px;
        padding: 0.7rem 1.2rem;
        font-weight: 600;
        border: none;
        transition: all 0.3s ease;
        white-space: nowrap;
    }

    .custom-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }

    /* ALERTS */
    .custom-alert {
        border-radius: 16px;
        border: none;
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
        font-weight: 500;
    }

    /* TEACHER INFO CARD */
    .teacher-info-card {
        background: #f8fafc;
        border-radius: 20px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        border: 1px solid #eef2f7;
    }

    .teacher-avatar-large {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 2rem;
        flex-shrink: 0;
    }

    .info-item {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        padding: 0.6rem 0.8rem;
        background: white;
        border-radius: 12px;
        border: 1px solid #eef2f7;
        height: 100%;
    }

    .info-item i {
        font-size: 1.2rem;
        width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8fafc;
        border-radius: 8px;
        flex-shrink: 0;
    }

    .custom-badge {
        border-radius: 10px;
        padding: 0.5rem 0.7rem;
        font-size: 0.75rem;
    }

    /* LOGIN STATUS CARD */
    .login-status-card {
        background: #f8fafc;
        border-radius: 20px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        border: 1px solid #eef2f7;
    }

    .status-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        flex-shrink: 0;
    }

    .status-success {
        background: #ecfdf5;
        color: #10b981;
    }

    .status-warning {
        background: #fffbeb;
        color: #f59e0b;
    }

    /* CREATE LOGIN CARD */
    .create-login-card {
        background: #f8fafc;
        border-radius: 20px;
        padding: 1.5rem;
        border: 1px solid #eef2f7;
        margin-bottom: 1.5rem;
    }

    .create-login-header {
        margin-bottom: 1.5rem;
    }

    .create-login-header h5 {
        font-weight: 700;
        margin-bottom: 0.2rem;
    }

    .form-group label {
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .password-input-wrapper {
        position: relative;
    }

    .password-input-wrapper i:first-child {
        position: absolute;
        top: 50%;
        left: 15px;
        transform: translateY(-50%);
        color: #64748b;
        z-index: 2;
    }

    .password-input-wrapper .custom-input {
        border-radius: 14px !important;
        border: 1px solid #e2e8f0;
        min-height: 48px;
        padding-left: 42px;
        padding-right: 48px;
    }

    .password-toggle {
        position: absolute;
        top: 50%;
        right: 12px;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #64748b;
        padding: 5px;
        cursor: pointer;
        z-index: 2;
    }

    .password-toggle:hover {
        color: #1e293b;
    }

    .password-requirements {
        background: white;
        border-radius: 14px;
        padding: 1rem;
        border: 1px solid #eef2f7;
    }

    .requirement {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.85rem;
        padding: 0.3rem 0.7rem;
        background: #f8fafc;
        border-radius: 8px;
        color: #64748b;
    }

    .requirement.valid {
        color: #10b981;
        background: #ecfdf5;
    }

    .requirement.valid i {
        color: #10b981;
    }

    /* ACCOUNT CREATED CARD */
    .account-created-card {
        background: #f0fdf4;
        border-radius: 20px;
        padding: 1.5rem;
        border: 1px solid #d1fae5;
        margin-bottom: 1.5rem;
    }

    .success-icon {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        background: #d1fae5;
        color: #10b981;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        flex-shrink: 0;
    }

    /* RESET PASSWORD CARD */
    .reset-password-card {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        border: 1px solid #eef2f7;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
    }

    .reset-password-card h6 {
        font-weight: 700;
        color: #1e293b;
    }

    /* BREADCRUMB */
    .ui-breadcrumb {
        background: transparent;
        padding: 0;
        margin: 0;
    }

    .ui-breadcrumb-item a {
        color: #4f46e5;
        font-weight: 500;
    }

    .ui-breadcrumb-item.active {
        color: #64748b;
        font-weight: 600;
    }

    /* MOBILE RESPONSIVE */
    @media(max-width: 768px) {
        .main-card-header {
            flex-direction: column;
            align-items: stretch;
        }

        .header-buttons {
            width: 100%;
        }

        .header-buttons a,
        .header-buttons button {
            flex: 1;
            text-align: center;
            justify-content: center;
        }

        .teacher-info-card .bs-row {
            gap: 1rem;
        }

        .teacher-avatar-large {
            width: 60px;
            height: 60px;
            font-size: 1.5rem;
        }

        .info-item {
            padding: 0.5rem;
        }

        .info-item i {
            width: 30px;
            height: 30px;
            font-size: 1rem;
        }

        .status-icon {
            width: 50px;
            height: 50px;
            font-size: 1.5rem;
        }

        .login-status-card .bs-text-md-end {
            text-align: left !important;
            margin-top: 1rem;
        }

        .login-status-card .bs-d-flex.bs-justify-content-md-end {
            justify-content: flex-start !important;
        }

        .account-created-card .bs-row > div:first-child {
            margin-bottom: 1.5rem;
        }

        .account-created-card .bs-d-flex {
            flex-direction: column;
            align-items: flex-start !important;
        }

        .success-icon {
            width: 50px;
            height: 50px;
            font-size: 2rem;
        }

        .reset-password-card {
            padding: 1rem;
        }

        .password-requirements .bs-row {
            gap: 0.5rem;
        }

        .requirement {
            font-size: 0.75rem;
            padding: 0.2rem 0.5rem;
        }
    }

    @media(max-width: 576px) {
        .main-card {
            padding: 1rem;
        }

        .teacher-info-card {
            padding: 1rem;
        }

        .teacher-avatar-large {
            width: 50px;
            height: 50px;
            font-size: 1.2rem;
        }

        .header-buttons a,
        .header-buttons button {
            font-size: 0.85rem;
            padding: 0.5rem 0.8rem;
        }

        .custom-btn.ui-btn-lg {
            font-size: 1rem;
            padding: 0.6rem 1rem;
        }

        .info-item {
            font-size: 0.9rem;
        }
    }

    /* ANIMATION */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
@endpush

@push('scripts')
<script>
    // Toggle password visibility
    function togglePassword(fieldId) {
        const input = document.getElementById(fieldId);
        if (!input) return;

        // Find the icon associated with this field
        let iconId = fieldId + '-icon';
        let icon = document.getElementById(iconId);

        // If not found, try alternative icon IDs
        if (!icon) {
            if (fieldId === 'password_confirmation') {
                icon = document.getElementById('confirm-password-icon');
            } else if (fieldId === 'reset_password') {
                icon = document.getElementById('reset-password-icon');
            } else if (fieldId === 'reset_password_confirmation') {
                icon = document.getElementById('reset-confirm-icon');
            }
        }

        if (input.type === 'password') {
            input.type = 'text';
            if (icon) icon.className = 'bi bi-eye-fill';
        } else {
            input.type = 'password';
            if (icon) icon.className = 'bi bi-eye-slash-fill';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Password match checking for create login
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('password_confirmation');
        const matchMessage = document.getElementById('password-match-message');

        if (password && confirmPassword && matchMessage) {
            confirmPassword.addEventListener('input', function() {
                if (this.value.length === 0) {
                    matchMessage.innerHTML = '';
                    return;
                }

                if (password.value === this.value) {
                    matchMessage.innerHTML = '<i class="bi bi-check-circle-fill tw:text-bs-success! tw:mr-1!"></i> <span class="tw:text-bs-success! tw:font-semibold!">Passwords match!</span>';
                } else {
                    matchMessage.innerHTML = '<i class="bi bi-exclamation-circle-fill tw:text-bs-danger! tw:mr-1!"></i> <span class="tw:text-bs-danger! tw:font-semibold!">Passwords do not match</span>';
                }
            });
        }

        // Password match checking for reset password
        const resetPassword = document.getElementById('reset_password');
        const resetConfirm = document.getElementById('reset_password_confirmation');
        const resetMatch = document.getElementById('reset-password-match');

        if (resetPassword && resetConfirm && resetMatch) {
            resetConfirm.addEventListener('input', function() {
                if (this.value.length === 0) {
                    resetMatch.innerHTML = '';
                    return;
                }

                if (resetPassword.value === this.value) {
                    resetMatch.innerHTML = '<i class="bi bi-check-circle-fill tw:text-bs-success! tw:mr-1!"></i> <span class="tw:text-bs-success! tw:font-semibold!">Passwords match!</span>';
                } else {
                    resetMatch.innerHTML = '<i class="bi bi-exclamation-circle-fill tw:text-bs-danger! tw:mr-1!"></i> <span class="tw:text-bs-danger! tw:font-semibold!">Passwords do not match</span>';
                }
            });
        }

        // Password strength validation for create login
        function validatePassword() {
            const pass = document.getElementById('password');
            if (!pass) return;

            const value = pass.value;

            const requirements = [
                { id: 'req-length', test: value.length >= 8, label: '8+ characters' },
                { id: 'req-uppercase', test: /[A-Z]/.test(value), label: 'Uppercase letter' },
                { id: 'req-lowercase', test: /[a-z]/.test(value), label: 'Lowercase letter' },
                { id: 'req-number', test: /[0-9]/.test(value), label: 'Number' }
            ];

            requirements.forEach(req => {
                const element = document.getElementById(req.id);
                if (element) {
                    if (req.test) {
                        element.className = 'requirement valid';
                        element.innerHTML = '<i class="bi bi-check-circle-fill"></i> ' + req.label;
                    } else {
                        element.className = 'requirement';
                        element.innerHTML = '<i class="bi bi-x-circle-fill tw:text-bs-danger!"></i> ' + req.label;
                    }
                }
            });
        }

        const passwordField = document.getElementById('password');
        if (passwordField) {
            passwordField.addEventListener('input', validatePassword);
        }

        // Auto-dismiss alerts after 5 seconds
        const alerts = document.querySelectorAll('.custom-alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                const closeBtn = alert.querySelector('.ui-btn-close');
                if (closeBtn) {
                    closeBtn.click();
                }
            }, 5000);
        });
    });
</script>
@endpush
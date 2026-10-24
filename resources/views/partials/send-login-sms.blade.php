{{--
    "Send login details by SMS" (ticked). The hidden 0 is sent when it is
    unticked. Read with $request->boolean('send_sms', true).
    @include('partials.send-login-sms', ['id' => 'sendSmsCreate'])
--}}
@php
    $smsBoxId = $id ?? 'sendLoginSms';
@endphp
<input type="hidden" name="send_sms" value="0">
<div class="form-check">
    <input class="form-check-input" type="checkbox" name="send_sms" value="1" id="{{ $smsBoxId }}"
        {{ old('send_sms', '1') ? 'checked' : '' }}>
    <label class="form-check-label fw-semibold" for="{{ $smsBoxId }}">
        <i class="bi bi-chat-dots"></i> Send login details by SMS
    </label>
    <div class="form-text">Username, password, institute name and the login link go to their mobile.</div>
</div>

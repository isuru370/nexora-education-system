@php
    $student = $student ?? null;
    $isEdit = $isEdit ?? false;

    $latestAdmissionPayment = $latestAdmissionPayment ?? null;

    $showAdmissionFields = old('admission', !empty($latestAdmissionPayment) ? 1 : 0) == 1;
    $selectedAdmissionId = old('admission_id', $latestAdmissionPayment?->admission_id);
    $selectedAmount = old('amount', $latestAdmissionPayment?->amount);

    if (!empty($student?->img_url)) {
        $studentImage = \Illuminate\Support\Str::startsWith($student->img_url, 'http')
            ? $student->img_url
            : asset('storage/' . ltrim($student->img_url, '/'));
    } else {
        $studentImage = asset('images/default-student.png');
    }
@endphp

<div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3">

    {{-- =========================================================
        STUDENT CARD QR CODE
    ========================================================== --}}
    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">
        <div class="section-card tw:p-6!">
            <h6 class="section-title">
                <i class="bi bi-credit-card tw:mr-2!"></i>
                Student Card Information
            </h6>


            <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2">
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                    <label class="ui-form-label tw:font-semibold!">
                        Student Card QR Code <span class="tw:text-bs-danger!">*</span>
                    </label>

                    <input type="text" name="card_qr_code" id="card_qr_code"
                        class="ui-form-control custom-input @error('card_qr_code') ui-is-invalid @enderror"
                        value="{{ old('card_qr_code', $isEdit ? $student->custom_id : '') }}"
                        placeholder="Scan or Enter Student Card QR Code" {{ $isEdit ? 'readonly' : '' }} required>

                    <small class="tw:text-bs-secondary-color!">
                        @if ($isEdit)
                            Student Card QR Code cannot be changed while editing.
                        @else
                            Scan or enter the QR code printed on the physical student card.
                        @endif
                    </small>

                    @error('card_qr_code')
                        <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>


    {{-- =========================================================
        STUDENT IMAGE
    ========================================================== --}}
    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">
        <div class="section-card tw:p-6!">
            <h6 class="section-title">
                <i class="bi bi-image tw:mr-2!"></i> Student Image
            </h6>

            <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2 tw:items-start!">
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[25%]">
                    <div class="image-preview-wrapper">
                        <img id="imagePreview" src="{{ $studentImage }}" alt="Student Image" class="image-preview"
                            onerror="this.src='{{ asset('storage/uploads/male.png') }}'">
                    </div>
                </div>

                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[75%]">
                    <label class="ui-form-label tw:font-semibold!">
                        {{ $isEdit ? 'Update Student Image' : 'Upload Student Image' }}
                        <small class="tw:text-bs-secondary-color! tw:font-normal!">(Optional)</small>
                    </label>

                    <input type="file" name="image" id="imageInput"
                        class="ui-form-control custom-input @error('image') ui-is-invalid @enderror"
                        accept="image/jpeg,image/jpg,image/png,image/webp" capture="environment">

                    <small class="tw:text-bs-secondary-color! tw:block! tw:mt-2!">
                        <i class="bi bi-info-circle"></i> Allowed: JPG, JPEG, PNG, WEBP. Max: 5MB.
                    </small>

                    @error('image')
                        <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                    @enderror

                    <button type="button" class="ui-btn ui-btn-outline-danger ui-btn-sm tw:mt-4!" id="removeImageBtn">
                        <i class="bi bi-trash tw:mr-1!"></i>
                        {{ $isEdit ? 'Reset to Default' : 'Remove' }}
                    </button>
                </div>
            </div>
        </div>
    </div>


    {{-- =========================================================
        STUDENT INFORMATION
    ========================================================== --}}
    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">
        <div class="section-card tw:p-6!">
            <h6 class="section-title">
                <i class="bi bi-person-badge tw:mr-2!"></i> Student Information
            </h6>

            <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2">

                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                    <label class="ui-form-label tw:font-semibold!">Full Name <span class="tw:text-bs-danger!">*</span></label>
                    <input type="text" name="full_name"
                        class="ui-form-control custom-input @error('full_name') ui-is-invalid @enderror"
                        value="{{ old('full_name', $student->full_name ?? '') }}" required
                        placeholder="Enter full name">
                    @error('full_name')
                        <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                    @enderror
                </div>

                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                    <label class="ui-form-label tw:font-semibold!">Initial Name <span class="tw:text-bs-danger!">*</span></label>
                    <input type="text" name="initial_name"
                        class="ui-form-control custom-input @error('initial_name') ui-is-invalid @enderror"
                        value="{{ old('initial_name', $student->initial_name ?? '') }}" required
                        placeholder="e.g., J.D. Smith">
                    @error('initial_name')
                        <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                    @enderror
                </div>

                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                    <label class="ui-form-label tw:font-semibold!">Mobile <span class="tw:text-bs-danger!">*</span></label>
                    <input type="tel" name="mobile"
                        class="ui-form-control custom-input @error('mobile') ui-is-invalid @enderror"
                        value="{{ old('mobile', $student->mobile ?? '') }}" pattern="[0-9]{10,15}" required
                        placeholder="07XXXXXXXX">
                    @error('mobile')
                        <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                    @enderror
                </div>

                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                    <label class="ui-form-label tw:font-semibold!">
                        WhatsApp Mobile <small class="tw:text-bs-secondary-color! tw:font-normal!">(Optional)</small>
                    </label>
                    <input type="tel" name="whatsapp_mobile"
                        class="ui-form-control custom-input @error('whatsapp_mobile') ui-is-invalid @enderror"
                        value="{{ old('whatsapp_mobile', $student->whatsapp_mobile ?? '') }}" pattern="[0-9]{10,15}"
                        placeholder="07XXXXXXXX">
                    @error('whatsapp_mobile')
                        <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                    @enderror
                </div>

                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                    <label class="ui-form-label tw:font-semibold!">
                        Email <small class="tw:text-bs-secondary-color! tw:font-normal!">(Optional)</small>
                    </label>
                    <input type="email" name="email"
                        class="ui-form-control custom-input @error('email') ui-is-invalid @enderror"
                        value="{{ old('email', $student->email ?? '') }}" placeholder="student@example.com">
                    @error('email')
                        <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                    @enderror
                </div>

                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                    <label class="ui-form-label tw:font-semibold!">
                        NIC <small class="tw:text-bs-secondary-color! tw:font-normal!">(Optional)</small>
                    </label>
                    <input type="text" name="nic"
                        class="ui-form-control custom-input @error('nic') ui-is-invalid @enderror"
                        value="{{ old('nic', $student->nic ?? '') }}" placeholder="National ID">
                    @error('nic')
                        <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                    @enderror
                </div>

                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                    <label class="ui-form-label tw:font-semibold!">
                        Birthday <small class="tw:text-bs-secondary-color! tw:font-normal!">(Optional)</small>
                    </label>
                    <input type="date" name="bday"
                        class="ui-form-control custom-input @error('bday') ui-is-invalid @enderror"
                        value="{{ old('bday', !empty($student?->bday) ? \Carbon\Carbon::parse($student->bday)->format('Y-m-d') : '') }}">
                    @error('bday')
                        <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                    @enderror
                </div>

                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                    <label class="ui-form-label tw:font-semibold!">
                        Gender <span class="tw:text-bs-danger!">*</span>
                    </label>

                    <select name="gender" class="ui-form-select custom-input @error('gender') ui-is-invalid @enderror"
                        required>
                        <option value="">Select Gender</option>
                        <option value="male"
                            {{ old('gender', $student->gender ?? '') === 'male' ? 'selected="selected"' : '' }}>Male
                        </option>
                        <option value="female"
                            {{ old('gender', $student->gender ?? '') === 'female' ? 'selected="selected"' : '' }}>
                            Female</option>
                        <option value="other"
                            {{ old('gender', $student->gender ?? '') === 'other' ? 'selected="selected"' : '' }}>Other
                        </option>
                    </select>

                    <small class="tw:text-bs-secondary-color! tw:block! tw:mt-1!">Used for profile and reports.</small>

                    @error('gender')
                        <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>


    {{-- =========================================================
        ADDRESS INFORMATION
    ========================================================== --}}
    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">
        <div class="section-card tw:p-6!">
            <h6 class="section-title">
                <i class="bi bi-house-door tw:mr-2!"></i> Address Information
            </h6>

            <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2">
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[100%]">
                    <label class="ui-form-label tw:font-semibold!">Address Line 1 <span class="tw:text-bs-danger!">*</span></label>
                    <input type="text" name="address1"
                        class="ui-form-control custom-input @error('address1') ui-is-invalid @enderror"
                        value="{{ old('address1', $student->address1 ?? '') }}" required
                        placeholder="Street address">
                    @error('address1')
                        <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                    @enderror
                </div>

                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                    <label class="ui-form-label tw:font-semibold!">
                        Address Line 2 <small class="tw:text-bs-secondary-color! tw:font-normal!">(Optional)</small>
                    </label>
                    <input type="text" name="address2"
                        class="ui-form-control custom-input @error('address2') ui-is-invalid @enderror"
                        value="{{ old('address2', $student->address2 ?? '') }}" placeholder="City / Town">
                    @error('address2')
                        <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                    @enderror
                </div>

                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                    <label class="ui-form-label tw:font-semibold!">
                        Address Line 3 <small class="tw:text-bs-secondary-color! tw:font-normal!">(Optional)</small>
                    </label>
                    <input type="text" name="address3"
                        class="ui-form-control custom-input @error('address3') ui-is-invalid @enderror"
                        value="{{ old('address3', $student->address3 ?? '') }}" placeholder="Postal code / District">
                    @error('address3')
                        <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>


    {{-- =========================================================
        GUARDIAN INFORMATION
    ========================================================== --}}
    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">
        <div class="section-card tw:p-6!">
            <h6 class="section-title">
                <i class="bi bi-shield-lock tw:mr-2!"></i> Guardian Information
            </h6>

            <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2">
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                    <label class="ui-form-label tw:font-semibold!">
                        Guardian First Name <small class="tw:text-bs-secondary-color! tw:font-normal!">(Optional)</small>
                    </label>
                    <input type="text" name="guardian_fname"
                        class="ui-form-control custom-input @error('guardian_fname') ui-is-invalid @enderror"
                        value="{{ old('guardian_fname', $student->guardian_fname ?? '') }}" placeholder="First name">
                    @error('guardian_fname')
                        <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                    @enderror
                </div>

                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                    <label class="ui-form-label tw:font-semibold!">
                        Guardian Last Name <small class="tw:text-bs-secondary-color! tw:font-normal!">(Optional)</small>
                    </label>
                    <input type="text" name="guardian_lname"
                        class="ui-form-control custom-input @error('guardian_lname') ui-is-invalid @enderror"
                        value="{{ old('guardian_lname', $student->guardian_lname ?? '') }}" placeholder="Last name">
                    @error('guardian_lname')
                        <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                    @enderror
                </div>

                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                    <label class="ui-form-label tw:font-semibold!">
                        Guardian NIC <small class="tw:text-bs-secondary-color! tw:font-normal!">(Optional)</small>
                    </label>
                    <input type="text" name="guardian_nic"
                        class="ui-form-control custom-input @error('guardian_nic') ui-is-invalid @enderror"
                        value="{{ old('guardian_nic', $student->guardian_nic ?? '') }}" placeholder="Guardian NIC">
                    @error('guardian_nic')
                        <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                    @enderror
                </div>

                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                    <label class="ui-form-label tw:font-semibold!">Guardian Mobile <span class="tw:text-bs-danger!">*</span></label>
                    <input type="tel" name="guardian_mobile"
                        class="ui-form-control custom-input @error('guardian_mobile') ui-is-invalid @enderror"
                        value="{{ old('guardian_mobile', $student->guardian_mobile ?? '') }}" pattern="[0-9]{10,15}"
                        required placeholder="Guardian contact">
                    @error('guardian_mobile')
                        <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>


    {{-- =========================================================
        CLASS INFORMATION (Branch + Grade + Class Type)
    ========================================================== --}}
    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">
        <div class="section-card tw:p-6!">
            <h6 class="section-title">
                <i class="bi bi-book tw:mr-2!"></i> Class Information
            </h6>

            <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2">

                {{-- ✅ BRANCH DROPDOWN (Before Grade) --}}
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                    <label class="ui-form-label tw:font-semibold!">
                        Main Branch <span class="tw:text-bs-danger!">*</span>
                    </label>

                    <select name="branch_id" id="branch_id"
                        class="ui-form-select custom-input @error('branch_id') ui-is-invalid @enderror" required>
                        <option value="">Select Branch</option>

                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}"
                                {{ (string) old('branch_id', $student->branch_id ?? '') === (string) $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                                @if ($branch->code)
                                    ({{ $branch->code }})
                                @endif
                            </option>
                        @endforeach
                    </select>

                    <small class="tw:text-bs-secondary-color! tw:block! tw:mt-1!">
                        Select the student's main education branch.
                    </small>

                    @error('branch_id')
                        <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                    @enderror
                </div>


                {{-- GRADE --}}
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                    <label class="ui-form-label tw:font-semibold!">
                        Grade <span class="tw:text-bs-danger!">*</span>
                    </label>

                    <select name="grade_id" class="ui-form-select custom-input @error('grade_id') ui-is-invalid @enderror"
                        required>
                        <option value="">Select Grade</option>

                        @foreach ($grades as $grade)
                            <option value="{{ $grade->id }}" @if (old('grade_id', $student->grade_id ?? '') == $grade->id) selected @endif>
                                {{ $grade->grade_name }}
                            </option>
                        @endforeach
                    </select>

                    <small class="tw:text-bs-secondary-color! tw:block! tw:mt-1!">
                        Select the student's class grade.
                    </small>

                    @error('grade_id')
                        <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                    @enderror
                </div>


                {{-- CLASS TYPE --}}
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                    <label class="ui-form-label tw:font-semibold!">
                        Class Type <span class="tw:text-bs-danger!">*</span>
                    </label>

                    <select name="class_type"
                        class="ui-form-select custom-input @error('class_type') ui-is-invalid @enderror" required>
                        <option value="offline" @if (old('class_type', $student->class_type ?? 'offline') === 'offline') selected @endif>Offline</option>
                        <option value="online" @if (old('class_type', $student->class_type ?? '') === 'online') selected @endif>Online</option>
                        <option value="hybrid" @if (old('class_type', $student->class_type ?? '') === 'hybrid') selected @endif>Hybrid</option>
                    </select>

                    <small class="tw:text-bs-secondary-color! tw:block! tw:mt-1!">
                        Choose how this student attends classes.
                    </small>

                    @error('class_type')
                        <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                    @enderror
                </div>


                {{-- STUDENT SCHOOL --}}
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                    <label class="ui-form-label tw:font-semibold!">
                        Student School <small class="tw:text-bs-secondary-color! tw:font-normal!">(Optional)</small>
                    </label>
                    <input type="text" name="student_school"
                        class="ui-form-control custom-input @error('student_school') ui-is-invalid @enderror"
                        value="{{ old('student_school', $student->student_school ?? '') }}"
                        placeholder="School name">
                    @error('student_school')
                        <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                    @enderror
                </div>


                {{-- ADMISSION SWITCH --}}
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[100%]">
                    <div class="ui-form-check ui-form-switch tw:mt-2!">
                        <input class="ui-form-check-input" type="checkbox" name="admission" value="1"
                            id="admission" {{ $showAdmissionFields ? 'checked' : '' }}>
                        <label class="ui-form-check-label tw:font-semibold! tw:ml-2!" for="admission">
                            Admission Paid
                        </label>
                    </div>
                </div>


                {{-- ADMISSION SELECT --}}
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:mt-4! {{ $showAdmissionFields ? '' : 'tw:hidden!' }}" id="admissionBox">
                    <label class="ui-form-label tw:font-semibold!">Select Admission</label>
                    <select name="admission_id" id="admissionSelect" class="ui-form-select custom-input">
                        <option value="">Select Admission</option>
                        @foreach ($admissions as $admission)
                            <option value="{{ $admission->id }}" data-amount="{{ $admission->amount }}"
                                {{ (int) $selectedAdmissionId === (int) $admission->id ? 'selected' : '' }}>
                                {{ $admission->name }} - Rs. {{ number_format($admission->amount, 2) }}
                            </option>
                        @endforeach
                    </select>
                </div>


                {{-- PAYMENT AMOUNT --}}
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:mt-4! {{ $showAdmissionFields ? '' : 'tw:hidden!' }}" id="paymentAmountBox">
                    <label class="ui-form-label tw:font-semibold!">Admission Payment Amount</label>
                    <input type="number" name="amount" id="amountInput" class="ui-form-control custom-input" readonly
                        value="{{ $selectedAmount }}">
                </div>

            </div>
        </div>
    </div>


    {{-- =========================================================
        FORM ACTIONS
    ========================================================== --}}
    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%] tw:flex! tw:justify-end! tw:gap-2! tw:pt-2!">
        <a href="{{ route('students.index') }}" class="ui-btn ui-btn-outline-secondary tw:px-6! custom-btn">
            <i class="bi bi-x-lg tw:mr-1!"></i> Cancel
        </a>
        <button type="submit" class="ui-btn ui-btn-primary tw:px-6! custom-btn">
            <i class="bi bi-save tw:mr-1!"></i> {{ $buttonText ?? 'Save Student' }}
        </button>
    </div>

</div>


@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // ============================================
            // ADMISSION FIELDS TOGGLE
            // ============================================
            const admissionCheckbox = document.getElementById('admission');
            const admissionBox = document.getElementById('admissionBox');
            const paymentAmountBox = document.getElementById('paymentAmountBox');
            const admissionSelect = document.getElementById('admissionSelect');
            const amountInput = document.getElementById('amountInput');

            function toggleAdmissionFields() {
                if (!admissionCheckbox) return;
                const show = admissionCheckbox.checked;
                if (admissionBox) admissionBox.classList.toggle('tw:hidden!', !show);
                if (paymentAmountBox) paymentAmountBox.classList.toggle('tw:hidden!', !show);
            }

            if (admissionCheckbox) {
                admissionCheckbox.addEventListener('change', toggleAdmissionFields);
                toggleAdmissionFields();
            }

            // Auto-fill amount when admission is selected
            if (admissionSelect) {
                admissionSelect.addEventListener('change', function() {
                    const selected = this.options[this.selectedIndex];
                    const amount = selected.getAttribute('data-amount');
                    if (amountInput) {
                        amountInput.value = amount || '';
                    }
                });
            }

        });
    </script>
@endpush

{{-- =============================================================
     Student Information Card
============================================================= --}}
<div class="ui-card tw:shadow-bs-sm! tw:[border:0]! tw:mb-6!">

    <div class="ui-card-body">

        <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2 tw:items-center!">

            {{-- =========================================================
                 Student Avatar
            ========================================================== --}}
            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-auto">

                <img id="student-image"
                     src="{{ asset('images/default-student.png') }}"
                     class="tw:rounded-[50%]! tw:bs-border! student-avatar"
                     width="90"
                     height="90"
                     alt="Student Photo">

            </div>


            {{-- =========================================================
                 Student Details
            ========================================================== --}}
            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[1_0_0%] tw:w-full">

                {{-- Student Name --}}
                <h5 id="student-name" class="tw:font-bold! tw:mb-1!">
                    -
                </h5>


                {{-- Student ID --}}
                <div class="tw:text-bs-secondary-color! tw:mb-2!">
                    Student ID:
                    <strong id="student-custom-id">-</strong>
                </div>


                {{-- Contact Info --}}
                <div class="tw:text-[.875em] tw:text-bs-secondary-color! student-contact-row">

                    <span class="contact-item">
                        <i class="bi bi-telephone-fill tw:mr-1!"></i>
                        <span id="student-mobile">-</span>
                    </span>

                    <span class="contact-item">
                        <i class="bi bi-person-fill tw:mr-1!"></i>
                        Guardian:
                        <span id="student-guardian">-</span>
                    </span>

                </div>

            </div>

        </div>

    </div>

</div>
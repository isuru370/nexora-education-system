{{-- =============================================================
     Student Information Card
============================================================= --}}
<div class="card shadow-sm border-0 mb-4">

    <div class="card-body">

        <div class="row align-items-center g-3">

            {{-- =========================================================
                 Student Avatar
            ========================================================== --}}
            <div class="col-auto">

                <img id="student-image"
                     src="{{ asset('images/default-student.png') }}"
                     class="rounded-circle border student-avatar"
                     width="90"
                     height="90"
                     alt="Student Photo">

            </div>


            {{-- =========================================================
                 Student Details
            ========================================================== --}}
            <div class="col">

                {{-- Student Name --}}
                <h5 id="student-name" class="fw-bold mb-1">
                    -
                </h5>


                {{-- Student ID --}}
                <div class="text-muted mb-2">
                    Student ID:
                    <strong id="student-custom-id">-</strong>
                </div>


                {{-- Contact Info --}}
                <div class="small text-muted student-contact-row">

                    <span class="contact-item">
                        <i class="bi bi-telephone-fill me-1"></i>
                        <span id="student-mobile">-</span>
                    </span>

                    <span class="contact-item">
                        <i class="bi bi-person-fill me-1"></i>
                        Guardian:
                        <span id="student-guardian">-</span>
                    </span>

                </div>

            </div>

        </div>

    </div>

</div>
<!-- Create Grade Modal -->
<div class="ui-modal ui-fade"
     id="createGradeModal"
     tabindex="-1"
     aria-labelledby="createGradeModalLabel"
     aria-hidden="true">

    <div class="ui-modal-dialog">

        <form action="{{ route('admin.grades.store') }}" method="POST">

            @csrf

            <div class="ui-modal-content">

                <div class="ui-modal-header tw:bg-bs-primary! tw:text-bs-white!">

                    <h5 class="ui-modal-title" id="createGradeModalLabel">
                        <i class="fas fa-plus-circle"></i>
                        Add New Grade
                    </h5>

                    <button type="button"
                            class="ui-btn-close ui-btn-close-white"
                            data-nx-dismiss="modal">
                    </button>

                </div>

                <div class="ui-modal-body">

                    {{-- Grade Name --}}
                    <div class="tw:mb-4!">

                        <label class="ui-form-label">
                            Grade Name
                            <span class="tw:text-bs-danger!">*</span>
                        </label>

                        <input type="text"
                               name="grade_name"
                               class="ui-form-control @error('grade_name') ui-is-invalid @enderror"
                               value="{{ old('grade_name') }}"
                               placeholder="Enter Grade Name"
                               required>

                        @error('grade_name')
                            <div class="ui-invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                    {{-- Status --}}
                    <div class="ui-form-check ui-form-switch">

                        <input
                            class="ui-form-check-input"
                            type="checkbox"
                            name="is_active"
                            id="createStatus"
                            value="1"
                            checked>

                        <label class="ui-form-check-label" for="createStatus">
                            Active
                        </label>

                    </div>

                </div>

                <div class="ui-modal-footer">

                    <button type="button"
                            class="ui-btn ui-btn-secondary"
                            data-nx-dismiss="modal">

                        <i class="fas fa-times"></i>
                        Close

                    </button>

                    <button type="submit"
                            class="ui-btn ui-btn-primary">

                        <i class="fas fa-save"></i>
                        Save Grade

                    </button>

                </div>

            </div>

        </form>

    </div>

</div>

{{-- Reopen Modal if Validation Error --}}
@if($errors->any())
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modal = new NxUi.Modal(document.getElementById('createGradeModal'));
        modal.show();
    });
</script>
@endif
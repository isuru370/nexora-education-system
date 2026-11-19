<!-- Edit Grade Modal -->
<div class="ui-modal ui-fade"
     id="editGradeModal"
     tabindex="-1"
     aria-labelledby="editGradeModalLabel"
     aria-hidden="true">

    <div class="ui-modal-dialog">

        <form id="editGradeForm" method="POST">

            @csrf
            @method('PUT')

            <div class="ui-modal-content">

                <div class="ui-modal-header tw:bg-bs-warning!">

                    <h5 class="ui-modal-title" id="editGradeModalLabel">
                        <i class="fas fa-edit"></i>
                        Edit Grade
                    </h5>

                    <button type="button"
                            class="ui-btn-close"
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

                        <input
                            type="text"
                            name="grade_name"
                            id="edit_grade_name"
                            class="ui-form-control"
                            required>

                    </div>

                    {{-- Status --}}
                    <div class="ui-form-check ui-form-switch">

                        <input
                            class="ui-form-check-input"
                            type="checkbox"
                            name="is_active"
                            id="edit_is_active"
                            value="1">

                        <label class="ui-form-check-label">
                            Active
                        </label>

                    </div>

                </div>

                <div class="ui-modal-footer">

                    <button
                        type="button"
                        class="ui-btn ui-btn-secondary"
                        data-nx-dismiss="modal">

                        Close

                    </button>

                    <button
                        type="submit"
                        class="ui-btn ui-btn-warning">

                        <i class="fas fa-save"></i>

                        Update Grade

                    </button>

                </div>

            </div>

        </form>

    </div>

</div>


<script>

document.addEventListener('DOMContentLoaded', function () {

    const editButtons = document.querySelectorAll('.editBtn');

    editButtons.forEach(button => {

        button.addEventListener('click', function () {

            let id = this.dataset.id;
            let name = this.dataset.name;
            let status = this.dataset.status;

            document.getElementById('edit_grade_name').value = name;

            document.getElementById('edit_is_active').checked =
                status == 1 ? true : false;

            document.getElementById('editGradeForm').action =
                "{{ url('admin/grades') }}/" + id;

            let modal = new NxUi.Modal(
                document.getElementById('editGradeModal')
            );

            modal.show();

        });

    });

});

</script>
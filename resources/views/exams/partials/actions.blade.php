{{-- ============================================================
     Exam Actions — nx-* action buttons
     Expected variables: $exam, $showResults, $showMarkEntry, $showEdit, $showCancel, $showDelete
     ============================================================ --}}

<div class="action-buttons">

    {{-- VIEW --}}
    <a href="{{ route('admin.exams.show', $exam->id) }}"
        class="action-btn"
        title="View" aria-label="View">
        <i class="bi bi-eye-fill"></i>
    </a>

    {{-- RESULTS --}}
    @if ($showResults)
        <a href="{{ route('admin.exams.results', $exam->id) }}"
            class="action-btn action-btn-success"
            title="Results" aria-label="Results">
            <i class="bi bi-bar-chart-fill"></i>
        </a>
    @endif

    {{-- MARK ENTRY --}}
    @if ($showMarkEntry)
        <a href="{{ route('admin.exams.mark-entry', $exam->id) }}"
            class="action-btn"
            style="color: var(--nx-violet);"
            title="Marks" aria-label="Marks">
            <i class="bi bi-journal-check"></i>
        </a>
    @endif

    {{-- EDIT --}}
    @if ($showEdit)
        <a href="{{ route('admin.exams.edit', $exam->id) }}"
            class="action-btn action-btn-warning"
            title="Edit" aria-label="Edit">
            <i class="bi bi-pencil-fill"></i>
        </a>
    @endif

    {{-- CANCEL --}}
    @if ($showCancel)
        <button type="button"
            class="action-btn action-btn-danger"
            data-bs-toggle="modal"
            data-bs-target="#cancelModal"
            data-exam-id="{{ $exam->id }}"
            data-exam-title="{{ $exam->title }}"
            data-exam-route="{{ route('admin.exams.cancel', $exam->id) }}"
            title="Cancel" aria-label="Cancel">
            <i class="bi bi-x-circle-fill"></i>
        </button>
    @endif

    {{-- DELETE --}}
    @if ($showDelete)
        <form method="POST" action="{{ route('admin.exams.destroy', $exam->id) }}">
            @csrf
            @method('DELETE')

            <button type="submit"
                class="action-btn action-btn-danger"
                title="Delete" aria-label="Delete">
                <i class="bi bi-trash-fill"></i>
            </button>
        </form>
    @endif

</div>
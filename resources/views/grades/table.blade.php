<div class="ui-table-responsive">

    <table class="ui-table ui-table-bordered ui-table-hover tw:align-middle!" id="gradeTable">

        <thead class="ui-table-dark">
            <tr>
                <th width="70">#</th>
                <th>Grade Name</th>
                <th class="tw:text-center!">Students</th>
                <th class="tw:text-center!">Status</th>
                <th class="tw:text-center!">Created</th>
                <th class="tw:text-center!" width="150">Action</th>
            </tr>
        </thead>

        <tbody>

            @forelse($grades as $key => $grade)

                <tr>

                    <td>{{ $key + 1 }}</td>

                    <td>
                        <strong>{{ $grade->grade_name }}</strong>
                    </td>

                    <td class="tw:text-center!">
                        <span class="ui-badge tw:bg-bs-info!">
                            {{ $grade->students_count }}
                        </span>
                    </td>

                    <td class="tw:text-center!">

                        @if($grade->is_active)

                            <span class="ui-badge tw:bg-bs-success!">
                                Active
                            </span>

                        @else

                            <span class="ui-badge tw:bg-bs-danger!">
                                Inactive
                            </span>

                        @endif

                    </td>

                    <td class="tw:text-center!">
                        {{ $grade->created_at->format('d M Y') }}
                    </td>

                    <td class="tw:text-center!">

                        {{-- Edit --}}
                        <button
                            class="ui-btn ui-btn-sm ui-btn-warning editBtn"

                            data-id="{{ $grade->id }}"
                            data-name="{{ $grade->grade_name }}"
                            data-status="{{ $grade->is_active }}">

                            <i class="fas fa-edit"></i>

                        </button>

                        {{-- Delete --}}
                        <form
                            action="{{ route('admin.grades.destroy',$grade->id) }}"
                            method="POST"
                            class="tw:[display:inline]!">

                            @csrf
                            @method('DELETE')

                            <button
                                class="ui-btn ui-btn-sm ui-btn-danger"
                                onclick="return confirm('Delete this grade?')">

                                <i class="fas fa-trash"></i>

                            </button>

                        </form>

                    </td>

                </tr>

            @empty

                <tr>

                    <td colspan="6" class="tw:text-center! tw:text-bs-secondary-color!">

                        No Grades Found.

                    </td>

                </tr>

            @endforelse

        </tbody>

    </table>

</div>
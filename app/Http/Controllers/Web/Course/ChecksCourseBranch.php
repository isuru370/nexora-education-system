<?php

namespace App\Http\Controllers\Web\Course;

use App\Models\Course;
use App\Services\Branch\BranchAccessService;

/** Users only see courses of their own branches (others get 404). */
trait ChecksCourseBranch
{
    protected function branchIds(): array
    {
        return app(BranchAccessService::class)->getBranchIds()->map(fn ($id) => (int) $id)->all();
    }

    protected function checkBranch(int $branchId): void
    {
        abort_unless(in_array($branchId, $this->branchIds(), true), 404);
    }

    /** The child record must belong to this course and the user's branch. */
    protected function checkCourse(Course $course, ?int $childCourseId = null): void
    {
        $this->checkBranch((int) $course->branch_id);

        if ($childCourseId !== null) {
            abort_unless($childCourseId === (int) $course->id, 404);
        }
    }
}

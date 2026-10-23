<?php

namespace App\Services\Course;

use App\Models\Branch;
use App\Models\Course;
use Carbon\Carbon;

/**
 * Course codes: branch code + "C" + start year + number, e.g. CMB-C26001.
 * Numbers start at 001 for each branch and year.
 */
class CourseCodeService
{
    /** Call inside a transaction (the branch row is locked so two courses cannot get one code). */
    public function next(int $branchId, Carbon|string $startDate): string
    {
        $branch = Branch::query()->whereKey($branchId)->lockForUpdate()->first();

        $branchCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) optional($branch)->code)) ?: 'BR' . $branchId;
        $prefix = $branchCode . '-C' . Carbon::parse($startDate)->format('y');

        $last = Course::withTrashed()
            ->where('code', 'like', $prefix . '%')
            ->pluck('code')
            ->map(fn ($code) => (int) substr($code, strlen($prefix)))
            ->max() ?? 0;

        return $prefix . str_pad((string) ($last + 1), 3, '0', STR_PAD_LEFT);
    }
}

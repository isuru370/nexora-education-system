<?php

namespace App\Http\Requests\Web\TeacherSalary;

use Illuminate\Foundation\Http\FormRequest;

/**
 * GET filters for teacher salary pages: ?year=&month=&branch_id=
 * All are optional. Defaults: current year / month, first allowed branch.
 */
class TeacherSalaryFilterRequest extends FormRequest
{
    /**
     * Route middleware (auth + role) handles access.
     * Branch access is checked in TeacherSalaryService.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'year' => ['nullable', 'integer', 'min:2020', 'max:' . (now()->year + 1)],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'branch_id' => ['nullable', 'integer'],
        ];
    }

    public function year(?int $default = null): int
    {
        return (int) ($this->validated()['year'] ?? $default ?? now()->year);
    }

    public function month(?int $default = null): int
    {
        return (int) ($this->validated()['month'] ?? $default ?? now()->month);
    }

    public function branchId(): ?int
    {
        $branchId = $this->validated()['branch_id'] ?? null;

        return $branchId !== null ? (int) $branchId : null;
    }
}
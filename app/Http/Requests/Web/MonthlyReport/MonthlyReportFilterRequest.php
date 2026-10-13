<?php

namespace App\Http\Requests\Web\MonthlyReport;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Filters for the monthly report page and the teacher salary report:
 * ?year=&month=&branch_id=&teacher_id=   (all optional)
 */
class MonthlyReportFilterRequest extends FormRequest
{
    /**
     * Route middleware (auth + role) handles access.
     * Branch access is checked in MonthlyReportService.
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
            'teacher_id' => ['nullable', 'integer'],
        ];
    }

    public function year(): int
    {
        return (int) ($this->validated()['year'] ?? now()->year);
    }

    public function month(): int
    {
        return (int) ($this->validated()['month'] ?? now()->month);
    }

    public function branchId(): ?int
    {
        $branchId = $this->validated()['branch_id'] ?? null;

        return $branchId !== null ? (int) $branchId : null;
    }

    public function teacherId(): ?int
    {
        $teacherId = $this->validated()['teacher_id'] ?? null;

        return $teacherId !== null ? (int) $teacherId : null;
    }
}
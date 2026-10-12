<?php

namespace App\Exports\Web\StudentClassEnrollment;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClassEnrollmentStudent implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    /**
     * @var Collection<int, \App\Models\StudentClassEnrollment>
     */
    protected Collection $enrollments;

    /**
     * @var \App\Models\StudentClass
     */
    protected $studentClass;

    /**
     * @var \App\Models\ClassCategory
     */
    protected $classCategory;

    /**
     * Constructor.
     *
     * @param Collection<int, \App\Models\StudentClassEnrollment> $enrollments
     * @param \App\Models\StudentClass $studentClass
     * @param \App\Models\ClassCategory $classCategory
     */
    public function __construct(
        Collection $enrollments,
        $studentClass,
        $classCategory
    ) {
        $this->enrollments = $enrollments;
        $this->studentClass = $studentClass;
        $this->classCategory = $classCategory;
    }

    /**
     * Column headings.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            '#',
            'Branch',
            'Student ID / QR',
            'Initial Name',
            'Full Name',
            'Mobile',
            'Fee Option',
            'Fee Option Fee',
            'Final Fee',
            'Status',
            'Enrolled Date',
        ];
    }

    /**
     * Data rows.
     *
     * @return Collection<int, array<int, mixed>>
     */
    public function collection(): Collection
    {
        $rows = collect();

        /*
        |--------------------------------------------------------------------------
        | Header Info (Class / Grade / Subject / Teacher / Category)
        |--------------------------------------------------------------------------
        */

        $rows->push([
            'Class',
            $this->studentClass->class_name ?? '-',
        ]);

        $rows->push([
            'Grade',
            optional($this->studentClass->grade)->grade_name ?: '-',
        ]);

        $rows->push([
            'Subject',
            optional($this->studentClass->subject)->subject_name ?: '-',
        ]);

        $rows->push([
            'Teacher',
            optional($this->studentClass->teacher)->initials
                ?: optional($this->studentClass->teacher)->full_name
                ?: '-',
        ]);

        $rows->push([
            'Category',
            $this->classCategory->category_name ?? '-',
        ]);

        $rows->push([]);

        /*
        |--------------------------------------------------------------------------
        | Column Headings Row (duplicate for visual separation)
        |--------------------------------------------------------------------------
        */

        $rows->push($this->headings());

        /*
        |--------------------------------------------------------------------------
        | Enrollment Rows
        |--------------------------------------------------------------------------
        */

        foreach ($this->enrollments as $index => $enrollment) {
            $student = $enrollment->student;

            /*
            |--------------------------------------------------------------------------
            | Student Code (Permanent QR or Temporary QR)
            |--------------------------------------------------------------------------
            */

            $studentCode = '-';

            if (
                $student &&
                $student->permanent_qr_active &&
                !empty($student->custom_id) &&
                $student->custom_id != '0'
            ) {
                $studentCode = $student->custom_id;
            } else {
                $studentCode = $student
                    ? ($student->temporary_qr_code ?: '-')
                    : '-';
            }

            /*
            |--------------------------------------------------------------------------
            | Fee Option
            |--------------------------------------------------------------------------
            */

            $feeOption = $enrollment->classCategoryFeeOption;

            if ($enrollment->is_free_card) {
                $feeType = 'Free Card';
                $feeOptionFee = 0;
                $finalFee = 0;
            } else {
                $feeType = $feeOption
                    ? $feeOption->label
                    : 'N/A';

                $feeOptionFee = $feeOption
                    ? (float) $feeOption->fee
                    : 0;

                $finalFee = (float) $enrollment->final_fee;
            }

            /*
            |--------------------------------------------------------------------------
            | Push Row
            |--------------------------------------------------------------------------
            */

            $rows->push([
                $index + 1,
                $enrollment->branch?->name ?? '-',
                $studentCode,
                $student ? ($student->initial_name ?: '-') : '-',
                $student ? ($student->full_name ?: '-') : '-',
                $student ? ($student->mobile ?: '-') : '-',
                $feeType,
                $feeOptionFee,
                $finalFee,
                $enrollment->is_active ? 'Active' : 'Inactive',
                $enrollment->enrolled_at
                    ? $enrollment->enrolled_at->format('Y-m-d')
                    : '-',
            ]);
        }

        return $rows;
    }

    /**
     * Apply styles to the worksheet.
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            // Header info — bold
            1 => ['font' => ['bold' => true]],
            2 => ['font' => ['bold' => true]],
            3 => ['font' => ['bold' => true]],
            4 => ['font' => ['bold' => true]],
            5 => ['font' => ['bold' => true]],

            // Column headings row — bold
            7 => ['font' => ['bold' => true]],
        ];
    }
}
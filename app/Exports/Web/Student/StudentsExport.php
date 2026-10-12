<?php

namespace App\Exports\Web\Student;

use App\Models\Student;
use App\Services\Branch\BranchAccessService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentsExport implements FromCollection, WithHeadings
{
    public function __construct(
        private BranchAccessService $branchAccessService
    ) {
    }

    public function collection(): Collection
    {
        $branchIds = $this->branchAccessService->getBranchIdArray();

        return Student::query()
            ->with([
                'grade',
                'branch',
            ])
            ->whereIn('branch_id', $branchIds)
            ->orderBy('branch_id')
            ->orderBy('id')
            ->get()
            ->map(function (Student $student) {
                return [
                    'Student ID' => $student->id,
                    'Branch Code' => $student->branch?->code ?? 'N/A',
                    'Branch' => $student->branch?->name ?? 'N/A',

                    'Custom ID' => $student->custom_id,
                    'QR Code' => $student->permanent_qr_active
                        ? $student->custom_id
                        : $student->temporary_qr_code,

                    'Full Name' => $student->full_name,
                    'Initial Name' => $student->initial_name,

                    'Mobile' => $student->mobile,
                    'WhatsApp' => $student->whatsapp_mobile,

                    'Guardian Name' => trim(
                        ($student->guardian_fname ?? '') . ' ' .
                        ($student->guardian_lname ?? '')
                    ),
                    'Guardian Mobile' => $student->guardian_mobile,

                    'NIC' => $student->nic,
                    'Date of Birth' => $student->bday?->format('Y-m-d'),
                    'Gender' => ucfirst($student->gender),

                    'Grade' => $student->grade?->grade_name ?? 'N/A',
                    'Class Type' => $student->class_type,
                    'School' => $student->student_school,

                    'Admission' => $student->admission
                        ? 'Paid'
                        : 'Pending',

                    'Permanent QR' => $student->permanent_qr_active
                        ? 'Active'
                        : 'Inactive',

                    'Student Disabled' => $student->student_disable
                        ? 'Yes'
                        : 'No',

                    'Status' => $student->is_active
                        ? 'Active'
                        : 'Inactive',

                    'Temporary QR Expiry' =>
                        $student->temporary_qr_code_expire_date?->format('Y-m-d'),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Student ID',
            'Branch Code',
            'Branch',

            'Custom ID',
            'QR Code',

            'Full Name',
            'Initial Name',

            'Mobile',
            'WhatsApp',

            'Guardian Name',
            'Guardian Mobile',

            'NIC',
            'Date of Birth',
            'Gender',

            'Grade',
            'Class Type',
            'School',

            'Admission',
            'Permanent QR',
            'Student Disabled',
            'Status',

            'Temporary QR Expiry',
        ];
    }
}
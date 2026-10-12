<?php

namespace App\Exports\Web\Teacher;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TeachersExport implements FromCollection, WithHeadings
{
    protected Collection $teachers;

    public function __construct(Collection $teachers)
    {
        $this->teachers = $teachers;
    }

    public function collection(): Collection
    {
        return $this->teachers->map(function ($teacher) {
            return [
                'Custom ID' => $teacher->custom_id,
                'Full Name' => $teacher->full_name,
                'Initials' => $teacher->initials,
                'Email' => $teacher->email,
                'Mobile' => $teacher->mobile,
                'NIC' => $teacher->nic,
                'Birthday' => optional($teacher->bday)->format('Y-m-d'),
                'Gender' => ucfirst($teacher->gender),

                // Bank
                'Bank' => $teacher->bankBranch->bank->bank_name ?? 'N/A',

                // Bank Branch
                'Bank Branch' => $teacher->bankBranch->branch_name ?? 'N/A',

                'Account Number' => $teacher->account_number ?? 'N/A',

                // Education Institute Branches
                'Institute Branch' => $teacher->branches
                    ->pluck('name')
                    ->implode(', ') ?: 'N/A',

                'Graduation' => $teacher->graduation_details ?? '-',
                'Experience' => $teacher->experience ?? '-',

                'Status' => $teacher->is_active
                    ? 'Active'
                    : 'Inactive',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Custom ID',
            'Full Name',
            'Initials',
            'Email',
            'Mobile',
            'NIC',
            'Birthday',
            'Gender',
            'Bank',
            'Bank Branch',
            'Account Number',
            'Institute Branch',
            'Graduation Details',
            'Experience',
            'Status',
        ];
    }
}

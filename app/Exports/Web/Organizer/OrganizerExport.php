<?php

namespace App\Exports\Web\Organizer;

use App\Models\Organizer;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class OrganizerExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected Builder $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public function collection()
    {
        return $this->query
            ->with(['branches', 'createdBy'])
            ->latest('organizers.id')
            ->get()
            ->map(function (Organizer $organizer) {
                return [
                    'Code'       => $organizer->code,
                    'Name'       => $organizer->name,
                    'Branches'   => $organizer->branches
                        ->pluck('name')
                        ->implode(', '),
                    'Mobile'     => $organizer->mobile,
                    'Email'      => $organizer->email,
                    'NIC'        => $organizer->nic,
                    'Status'     => $organizer->is_active
                        ? 'Active'
                        : 'Inactive',
                    'Note'       => $organizer->note,
                    'Created By' => optional($organizer->createdBy)->name,
                    'Created At' => optional($organizer->created_at)
                        ->format('Y-m-d H:i:s'),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Code',
            'Name',
            'Branches',
            'Mobile',
            'Email',
            'NIC',
            'Status',
            'Note',
            'Created By',
            'Created At',
        ];
    }
}

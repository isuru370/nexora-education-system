<?php

namespace App\Exports\Web\StudentClass;

use App\Models\StudentClass;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentClassesExport implements FromCollection, WithHeadings
{
    protected $query;

    public function __construct(?Builder $query = null)
    {
        $this->query = $query;
    }

    public function collection()
    {
        $query = $this->query ?? StudentClass::query();

        return $query
            ->with([
                'branch',
                'teacher',
                'subject',
                'grade',
                'paymentConfig.organizer',
            ])
            ->latest('student_classes.id')
            ->get()
            ->map(function ($class) {
                return [
                    'Class Name' => $class->class_name,
                    'Class Type' => ucfirst($class->class_type),
                    'Medium' => $class->medium,

                    // Branch Information
                    'Branch' => $class->branch->name ?? 'N/A',

                    // Teacher Information
                    'Teacher' => $class->teacher->full_name ?? 'N/A',
                    'Teacher ID' => $class->teacher->custom_id ?? 'N/A',

                    // Grade and Subject
                    'Grade' => $class->grade->grade_name ?? 'N/A',
                    'Subject' => $class->subject->subject_name ?? 'N/A',

                    // Payment Configuration
                    'Teacher Percentage' =>
                        $class->paymentConfig->teacher_percentage ?? '0.00',

                    'Organizer Percentage' =>
                        $class->paymentConfig->organizer_percentage ?? '0.00',

                    'Institution Percentage' =>
                        $class->paymentConfig->institution_percentage ?? '0.00',

                    'Organizer' =>
                        $class->paymentConfig->organizer->name ?? 'None',

                    'Effective From' =>
                        $class->paymentConfig?->effective_from
                            ? \Carbon\Carbon::parse(
                                $class->paymentConfig->effective_from
                            )->format('Y-m-d')
                            : 'N/A',

                    'Effective To' =>
                        $class->paymentConfig?->effective_to
                            ? \Carbon\Carbon::parse(
                                $class->paymentConfig->effective_to
                            )->format('Y-m-d')
                            : 'N/A',

                    // Status
                    'Active Status' =>
                        $class->is_active ? 'Active' : 'Inactive',

                    'Ongoing Status' =>
                        $class->is_ongoing ? 'Ongoing' : 'Not Ongoing',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Class Name',
            'Class Type',
            'Medium',
            'Branch',
            'Teacher',
            'Teacher ID',
            'Grade',
            'Subject',
            'Teacher Percentage',
            'Organizer Percentage',
            'Institution Percentage',
            'Organizer',
            'Effective From',
            'Effective To',
            'Active Status',
            'Ongoing Status',
        ];
    }
}

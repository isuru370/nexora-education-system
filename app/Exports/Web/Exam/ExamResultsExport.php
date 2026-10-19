<?php

namespace App\Exports\Web\Exam;

use App\Models\Exam;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExamResultsExport implements FromCollection, WithHeadings
{
    protected $exam;

    public function __construct(Exam $exam)
    {
        $this->exam = $exam;
    }

    public function collection()
    {
        $rankColumn = \Illuminate\Support\Facades\DB::getQueryGrammar()->wrap('rank');

        return $this->exam->results()
            ->with('student')
            ->orderByRaw("CASE WHEN {$rankColumn} IS NULL THEN 1 ELSE 0 END")
            ->orderBy('rank')
            ->orderByDesc('marks')
            ->get()
            ->map(function ($result) {
                $student = $result->student;

                return [
                    'rank' => $result->rank,
                    'student_id' => $student
                        ? ($student->permanent_qr_active ? $student->custom_id : ($student->temporary_qr_code ?: $student->custom_id))
                        : 'N/A',
                    'student' => $student
                        ? ($student->initial_name ?: $student->full_name)
                        : 'N/A',
                    'marks' => $result->is_absent ? 'Absent' : $result->marks,
                    'max_marks' => $result->max_marks,
                    'percentage' => $result->percentage,
                    'grade' => $result->grade,
                    'status' => ucfirst((string) $result->status),
                    'remark' => $result->remark,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Rank',
            'Student ID',
            'Student',
            'Marks',
            'Max Marks',
            'Percentage',
            'Grade',
            'Status',
            'Remark',
        ];
    }
}
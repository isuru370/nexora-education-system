<?php

namespace App\Services\Web\Report;

use App\Models\Teacher;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class fees collected for one teacher's classes in a date range.
 * (Replaces the old Admin\TeacherReportController "Teacher Daily Collection Report".)
 */
class TeacherCollectionReportService
{
    public function __construct(
        protected PaymentReportQuery $query
    ) {}

    /**
     * Active teachers in the report's branches.
     */
    public function teacherOptions(ReportFilter $filter): Collection
    {
        return Teacher::query()
            ->select('id', 'custom_id', 'initials', 'full_name')
            ->where('is_active', true)
            ->whereHas('branches', fn ($query) => $query->whereIn('branches.id', $filter->branchIds))
            ->orderBy('custom_id')
            ->get();
    }

    /**
     * @throws DomainException
     */
    public function assertTeacherInBranches(int $teacherId, ReportFilter $filter): Teacher
    {
        $teacher = Teacher::withTrashed()->find($teacherId);

        $inBranch = $teacher && DB::table('teacher_branches')
            ->where('teacher_id', $teacherId)
            ->whereIn('branch_id', $filter->branchIds)
            ->exists();

        if (!$inBranch) {
            throw new DomainException('You do not have access to this teacher.');
        }

        return $teacher;
    }

    /**
     * @return array{teacher: Teacher, rows: Collection, classes: Collection, summary: array}
     *
     * @throws DomainException
     */
    public function generate(int $teacherId, ReportFilter $filter): array
    {
        $teacher = $this->assertTeacherInBranches($teacherId, $filter);

        $payments = $this->query->teacherStudentPayments($filter, $teacherId)
            ->with([
                'student' => fn ($query) => $query->withTrashed()
                    ->select('id', 'custom_id', 'permanent_qr_active', 'temporary_qr_code', 'initial_name', 'guardian_mobile'),
                'collectedBy:id,name',
                'splitSnapshot:id,payment_id,teacher_amount',
                'enrollment' => fn ($query) => $query->withTrashed()
                    ->select('id', 'student_class_id', 'class_category_fee_id', 'class_category_fee_option_id'),
                'enrollment.studentClass' => fn ($query) => $query->withTrashed()->select('id', 'class_name', 'grade_id'),
                'enrollment.studentClass.grade:id,grade_name',
                'enrollment.classCategoryFee' => fn ($query) => $query->withTrashed()->select('id', 'class_category_id'),
                'enrollment.classCategoryFee.category:id,category_name',
                'enrollment.classCategoryFeeOption:id,label,fee',
            ])
            ->orderBy('paid_at')
            ->get();

        $rows = $payments->map(function ($payment) {
            $enrollment = $payment->enrollment;
            $class = optional($enrollment)->studentClass;
            $option = optional($enrollment)->classCategoryFeeOption;

            return [
                'class_name' => optional($class)->class_name ?: 'Unknown class',
                'grade_name' => optional(optional($class)->grade)->grade_name,
                'category_name' => optional(optional(optional($enrollment)->classCategoryFee)->category)->category_name,
                'fee_option' => $option ? $option->label . ' (' . number_format((float) $option->fee, 2) . ')' : null,
                'student_code' => PaymentReportQuery::studentCode($payment->student),
                'student_name' => optional($payment->student)->initial_name,
                'guardian_mobile' => optional($payment->student)->guardian_mobile,
                'receipt_number' => $payment->receipt_number,
                'paid_at' => optional($payment->paid_at)->format('Y-m-d H:i'),
                'fee_month' => $payment->payment_month ? Carbon::parse($payment->payment_month)->format('M Y') : null,
                'payment_method' => ucwords(str_replace('_', ' ', (string) $payment->payment_method)),
                'collected_by' => optional($payment->collectedBy)->name,
                'amount' => (float) $payment->amount,
                'teacher_amount' => (float) optional($payment->splitSnapshot)->teacher_amount,
            ];
        });

        $classes = $rows
            ->groupBy(fn ($row) => $row['class_name'] . '|' . $row['category_name'])
            ->map(fn (Collection $group) => [
                'class_name' => $group->first()['class_name'],
                'grade_name' => $group->first()['grade_name'],
                'category_name' => $group->first()['category_name'],
                'payments' => $group->count(),
                'students' => $group->pluck('student_code')->unique()->count(),
                'amount' => round($group->sum('amount'), 2),
                'teacher_amount' => round($group->sum('teacher_amount'), 2),
            ])
            ->sortBy('class_name')
            ->values();

        return [
            'teacher' => $teacher,
            'rows' => $rows->sortBy([['class_name', 'asc'], ['category_name', 'asc'], ['paid_at', 'asc']])->values(),
            'classes' => $classes,
            'summary' => [
                'payments' => $rows->count(),
                'students' => $rows->pluck('student_code')->unique()->count(),
                'classes' => $classes->pluck('class_name')->unique()->count(),
                'amount' => round($rows->sum('amount'), 2),
                'teacher_amount' => round($rows->sum('teacher_amount'), 2),
            ],
        ];
    }

    public function table(): array
    {
        return [
            'headings' => ['Class', 'Grade', 'Category', 'Fee Option', 'Student Code', 'Student', 'Guardian Mobile', 'Receipt No', 'Paid At', 'Fee Month', 'Method', 'Collected By', 'Amount (Rs.)', 'Teacher Share (Rs.)'],
            'columns' => ['class_name', 'grade_name', 'category_name', 'fee_option', 'student_code', 'student_name', 'guardian_mobile', 'receipt_number', 'paid_at', 'fee_month', 'payment_method', 'collected_by', 'amount', 'teacher_amount'],
            'money' => ['amount', 'teacher_amount'],
            'total' => 'amount',
        ];
    }
}

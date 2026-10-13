<?php

namespace App\Services\Web\Report;

use App\Models\Payment;
use App\Models\PaymentSplitSnapshot;
use App\Models\StudentClassEnrollment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Monthly collection report: expected vs collected class fees for one fee month,
 * grouped by Class + Category.
 * (Replaces the old Admin\PaymentCollectionReportController + PaymentCollectionReportService.)
 *
 * Expected  = final fee of every enrollment that was active in that month (free card = 0)
 * Collected = completed payments whose payment_month is that month
 * Split     = teacher / organizer / institute share of the collected payments
 */
class MonthlyCollectionReportService
{
    public function generate(ReportFilter $filter, Carbon $month): array
    {
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();

        /*
        |----------------------------------------------------------------------
        | Collected per enrollment (one grouped query)
        |----------------------------------------------------------------------
        */
        $paymentsQuery = Payment::query()
            ->where('status', 'completed')
            ->whereIn('branch_id', $filter->branchIds)
            ->whereBetween('payment_month', [$monthStart->toDateString(), $monthEnd->toDateString()]);

        $collectedByEnrollment = (clone $paymentsQuery)
            ->groupBy('student_class_enrollment_id')
            ->selectRaw('student_class_enrollment_id, SUM(amount) as total')
            ->pluck('total', 'student_class_enrollment_id');

        $splitByEnrollment = PaymentSplitSnapshot::query()
            ->whereIn('payment_id', (clone $paymentsQuery)->select('id'))
            ->groupBy('student_class_enrollment_id')
            ->selectRaw('
                student_class_enrollment_id,
                SUM(teacher_amount) as teacher,
                SUM(organizer_amount) as organizer,
                SUM(institution_amount) as institute
            ')
            ->get()
            ->keyBy('student_class_enrollment_id');

        /*
        |----------------------------------------------------------------------
        | Enrollments for the month
        |----------------------------------------------------------------------
        | Active during the month (enrolled before month end, not left before
        | month start) OR paid for this month (so no collected money is lost
        | when a student left early).
        */
        $enrollments = StudentClassEnrollment::withTrashed()
            ->whereIn('branch_id', $filter->branchIds)
            ->where(function ($query) use ($monthStart, $monthEnd, $collectedByEnrollment) {
                $query->where(function ($active) use ($monthStart, $monthEnd) {
                    $active->whereNull('deleted_at')
                        ->where(function ($enrolled) use ($monthEnd) {
                            $enrolled->whereNull('enrolled_at')
                                ->orWhereDate('enrolled_at', '<=', $monthEnd->toDateString());
                        })
                        ->where(function ($left) use ($monthStart) {
                            $left->whereNull('left_at')
                                ->orWhereDate('left_at', '>=', $monthStart->toDateString());
                        });
                })
                    ->orWhereIn('id', $collectedByEnrollment->keys());
            })
            ->with([
                'studentClass' => fn ($query) => $query->withTrashed()->select('id', 'class_name', 'grade_id'),
                'studentClass.grade:id,grade_name',
                'classCategoryFee' => fn ($query) => $query->withTrashed()->select('id', 'class_category_id'),
                'classCategoryFee.category:id,category_name',
                'classCategoryFeeOption:id,class_category_fee_id,label,fee',
            ])
            ->get();

        /*
        |----------------------------------------------------------------------
        | Rows: Class + Category
        |----------------------------------------------------------------------
        */
        $rows = $enrollments
            ->groupBy(fn ($enrollment) => $enrollment->student_class_id . '_' . $enrollment->class_category_fee_id)
            ->map(function (Collection $group) use ($collectedByEnrollment, $splitByEnrollment) {
                $first = $group->first();
                $class = $first->studentClass;
                $ids = $group->pluck('id');

                $expected = round($group->sum(fn ($enrollment) => (float) $enrollment->final_fee), 2);
                $collected = round($ids->sum(fn ($id) => (float) $collectedByEnrollment->get($id, 0)), 2);
                $due = round(max($expected - $collected, 0), 2);

                $split = fn (string $key) => round(
                    $ids->sum(fn ($id) => (float) optional($splitByEnrollment->get($id))->{$key}),
                    2
                );

                return [
                    'class_name' => optional($class)->class_name ?: 'Unknown class',
                    'grade_name' => optional(optional($class)->grade)->grade_name ?: '-',
                    'category_name' => optional(optional($first->classCategoryFee)->category)->category_name ?: 'Unknown category',
                    'fee_options' => $group
                        ->map(fn ($enrollment) => $enrollment->classCategoryFeeOption)
                        ->filter()
                        ->unique('id')
                        ->map(fn ($option) => $option->label . ' (' . number_format((float) $option->fee, 2) . ')')
                        ->implode(', '),
                    'student_count' => $group->count(),
                    'free_cards' => $group->where('is_free_card', true)->count(),
                    'paid_students' => $ids->filter(fn ($id) => (float) $collectedByEnrollment->get($id, 0) > 0)->count(),
                    'expected' => $expected,
                    'collected' => $collected,
                    'due' => $due,
                    'collection_percentage' => $this->percent($collected, $expected),
                    'teacher_amount' => $split('teacher'),
                    'organizer_amount' => $split('organizer'),
                    'institution_amount' => $split('institute'),
                ];
            })
            ->sortBy([['class_name', 'asc'], ['category_name', 'asc']])
            ->values();

        $expected = round($rows->sum('expected'), 2);
        $collected = round($rows->sum('collected'), 2);
        $due = round(max($expected - $collected, 0), 2);

        return [
            'month_label' => $monthStart->format('F Y'),
            'month' => $monthStart->format('Y-m'),
            'summary' => [
                'student_count' => $enrollments->count(),
                'paid_students' => $rows->sum('paid_students'),
                'expected' => $expected,
                'collected' => $collected,
                'due' => $due,
                'collection_percentage' => $this->percent($collected, $expected),
                'due_percentage' => $this->percent($due, $expected),
                'teacher_amount' => round($rows->sum('teacher_amount'), 2),
                'organizer_amount' => round($rows->sum('organizer_amount'), 2),
                'institution_amount' => round($rows->sum('institution_amount'), 2),
            ],
            'rows' => $rows,
        ];
    }

    public function table(): array
    {
        return [
            'headings' => ['Class', 'Grade', 'Category', 'Fee Options', 'Students', 'Paid', 'Expected (Rs.)', 'Collected (Rs.)', 'Due (Rs.)', 'Collected %', 'Teacher (Rs.)', 'Organizer (Rs.)', 'Institute (Rs.)'],
            'columns' => ['class_name', 'grade_name', 'category_name', 'fee_options', 'student_count', 'paid_students', 'expected', 'collected', 'due', 'collection_percentage', 'teacher_amount', 'organizer_amount', 'institution_amount'],
            'money' => ['expected', 'collected', 'due', 'teacher_amount', 'organizer_amount', 'institution_amount'],
            'total' => 'collected',
        ];
    }

    protected function percent(float $part, float $whole): float
    {
        return $whole > 0 ? round($part / $whole * 100, 1) : 0.0;
    }
}

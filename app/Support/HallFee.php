<?php

namespace App\Support;

use App\Models\ClassHall;
use App\Models\ClassSchedulePattern;
use App\Models\StudentClassEnrollment;

/**
 * Hall fee of a class (one fee category): the highest hall price of the
 * class's active schedule patterns.
 *
 * A class held in two halls (e.g. Saturday in Hall A = Rs. 50, Sunday in
 * Hall B = Rs. 0) is charged Rs. 50. Only active patterns count: to stop a
 * hall fee, make that pattern inactive (Class Schedules > Bulk Edit > Edit)
 * or change its hall.
 *
 * Used for the payment amount, the fee split, the receipt and the SMS, so
 * they always agree.
 */
class HallFee
{
    public static function forEnrollment(?StudentClassEnrollment $enrollment): float
    {
        if (!$enrollment || !$enrollment->student_class_id) {
            return 0.0;
        }

        return self::forClass((int) $enrollment->student_class_id, $enrollment->class_category_fee_id)['fee'];
    }

    /**
     * @return array{fee: float, hall: ClassHall|null}
     */
    public static function forClass(int $studentClassId, ?int $classCategoryFeeId): array
    {
        $pattern = ClassSchedulePattern::query()
            ->with('hall:id,hall_name,hall_price')
            ->where('student_class_id', $studentClassId)
            ->where('class_category_fee_id', $classCategoryFeeId)
            ->where('is_active', true)
            ->whereNotNull('class_hall_id')
            ->whereHas('hall')
            ->get()
            ->sortByDesc(fn (ClassSchedulePattern $p) => sprintf(
                '%012.2f|%s|%010d',
                (float) ($p->hall->hall_price ?? 0),
                optional($p->start_date)->format('Y-m-d') ?? (string) $p->start_date,
                $p->id
            ))
            ->first();

        $fee = $pattern ? round(max(0, (float) ($pattern->hall->hall_price ?? 0)), 2) : 0.0;

        return ['fee' => $fee, 'hall' => $pattern?->hall];
    }
}

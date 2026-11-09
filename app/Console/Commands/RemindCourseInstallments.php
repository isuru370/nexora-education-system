<?php

namespace App\Console\Commands;

use App\Models\CourseEnrollment;
use App\Services\Course\CourseFeeService;
use App\Services\Course\CourseNotificationService;
use App\Support\Features;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Tells parents about an unpaid course installment 3 days before its due
 * date and on the due date. Runs daily at 9 AM; each reminder is sent once.
 */
class RemindCourseInstallments extends Command
{
    public const DAYS_BEFORE = [3, 0];

    protected $signature = 'course-installments:remind';

    protected $description = 'Remind parents of course installments due in 3 days or today';

    public function handle(CourseFeeService $fees, CourseNotificationService $notices): int
    {
        if (!Features::enabled('courses')) {
            return self::SUCCESS;
        }

        $today = now()->startOfDay();
        $dueDates = collect(self::DAYS_BEFORE)
            ->mapWithKeys(fn (int $days) => [$today->copy()->addDays($days)->toDateString() => $days]);

        $sent = 0;

        CourseEnrollment::query()
            ->where('status', 'active')
            ->where('payment_plan', 'installment')
            ->whereHas('course', fn ($q) => $q->where('is_active', true))
            ->whereHas('installments', fn ($q) => $q->whereBetween('due_date', [
                $today->toDateString(),
                $today->copy()->addDays(max(self::DAYS_BEFORE))->endOfDay()->toDateTimeString(),
            ]))
            ->with(['course', 'student', 'installments'])
            ->chunkById(200, function ($enrollments) use ($fees, $notices, $dueDates, &$sent) {
                foreach ($enrollments as $enrollment) {
                    foreach ($fees->summary($enrollment)['installments'] as $installment) {
                        if ($installment['remaining'] <= 0 || !$dueDates->has($installment['due_date'])) {
                            continue;
                        }

                        $daysLeft = $dueDates[$installment['due_date']];
                        $key = "course_installment_reminder:{$installment['id']}:{$daysLeft}";

                        if (!Cache::add($key, true, now()->addDays(2))) {
                            continue; // already sent
                        }

                        $sent += $notices->installmentReminder($enrollment, $installment, $daysLeft) ? 1 : 0;
                    }
                }
            });

        $this->info("{$sent} course installment reminder(s) sent.");

        return self::SUCCESS;
    }
}

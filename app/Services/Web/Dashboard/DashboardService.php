<?php

namespace App\Services\Web\Dashboard;

use App\Models\Branch;
use App\Models\ClassSchedule;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentCard;
use App\Models\StudentClass;
use App\Models\StudentClassEnrollment;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Admin dashboard data (SUPER_ADMIN / ADMIN).
 *
 * Admins see all branches. Branch-wise numbers are in `branchSummary`.
 *
 * Income rules:
 * - "Income" = completed payments by paid_at (cash collected on that day/month).
 * - "Unpaid" = active, non-free-card enrollments with no completed payment
 *              for the current payment_month.
 */
class DashboardService
{
    /**
     * Show a warning when available student cards drop below this.
     */
    private const LOW_CARD_THRESHOLD = 10;

    /**
     * Temporary QR codes expiring within this many days.
     */
    private const QR_EXPIRY_DAYS = 10;

    public function getDashboardData(): array
    {
        try {
            $today = Carbon::today();
            $now = Carbon::now();
            $monthStart = $now->copy()->startOfMonth();

            $availableStudentCardCount = $this->getAvailableStudentCardCount();
            $expiringStudents = $this->getExpiringStudents($today);
            $todayClasses = $this->getTodayClasses($today);

            $todayIncome = $this->getTodayIncome($today);
            $monthlyIncome = $this->getMonthlyIncome($now->month, $now->year);

            return [

                /*
                |--------------------------------------------------------------------------
                | Existing keys (kept for the current dashboard view)
                |--------------------------------------------------------------------------
                */

                'studentsCount' => $this->getStudentsCount(),
                'teachersCount' => $this->getTeachersCount(),
                'classesCount' => $this->getClassesCount(),

                'todayIncome' => $todayIncome,
                'monthlyIncome' => $monthlyIncome,

                'availableStudentCardCount' => $availableStudentCardCount,
                'showStudentCardWarning' => $availableStudentCardCount < self::LOW_CARD_THRESHOLD,

                'incompleteRegistrationCount' => $this->getIncompleteRegistrationCount(),
                'incompleteRegistrations' => $this->getIncompleteRegistrations(),

                'latestStudents' => $this->getLatestStudents(),

                'expiringStudents' => $expiringStudents,
                'expiringStudentsCount' => $expiringStudents->count(),

                /*
                |--------------------------------------------------------------------------
                | New dashboard data
                |--------------------------------------------------------------------------
                */

                'stats' => $this->getStats($today, $now, $todayIncome, $monthlyIncome),

                'todayClasses' => $todayClasses,
                'todayClassSummary' => $this->summarizeTodayClasses($todayClasses),

                'unpaid' => $this->getUnpaidSummary($monthStart),

                'incomeTrend' => $this->getIncomeTrend($now, 6),
                'dailyIncome' => $this->getDailyIncome($today, 14),

                'paymentMethods' => $this->getPaymentMethodBreakdown($now),

                'branchSummary' => $this->getBranchSummary($today, $now),

                'recentPayments' => $this->getRecentPayments(8),

                'generatedAt' => $now->format('Y-m-d h:i A'),
            ];
        } catch (Throwable $e) {

            Log::error('Dashboard data loading failed.', [
                'user_id' => auth()->id(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | KPI Stats
    |--------------------------------------------------------------------------
    */

    protected function getStats(
        Carbon $today,
        Carbon $now,
        float $todayIncome,
        float $monthlyIncome
    ): array {
        /*
         * Month-to-date vs the same days of last month
         * (e.g. 1-13 Oct vs 1-13 Sep), so the % is fair mid-month.
         */
        $lastMonthStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $lastMonthSameDay = $lastMonthStart->copy()->day(
            min($now->day, $lastMonthStart->daysInMonth)
        )->endOfDay();

        $lastMonthToDate = $this->sumCompletedPayments(
            $lastMonthStart,
            $lastMonthSameDay
        );

        $yesterdayIncome = $this->sumCompletedPayments(
            $today->copy()->subDay()->startOfDay(),
            $today->copy()->subDay()->endOfDay()
        );

        return [
            'totalStudents' => $this->getStudentsCount(),
            'activeStudents' => $this->activeStudentQuery()->count(),
            'newStudentsThisMonth' => Student::query()
                ->where('created_at', '>=', $now->copy()->startOfMonth())
                ->count(),

            'activeEnrollments' => StudentClassEnrollment::query()
                ->where('is_active', true)
                ->count(),

            'activeTeachers' => Teacher::query()->where('is_active', true)->count(),
            'activeClasses' => StudentClass::query()->where('is_active', true)->count(),

            'todayIncome' => $todayIncome,
            'yesterdayIncome' => $yesterdayIncome,
            'todayChangePercent' => $this->percentChange($todayIncome, $yesterdayIncome),

            'monthlyIncome' => $monthlyIncome,
            'lastMonthToDateIncome' => $lastMonthToDate,
            'monthlyChangePercent' => $this->percentChange($monthlyIncome, $lastMonthToDate),

            'monthlyDiscount' => (float) Payment::query()
                ->where('status', 'completed')
                ->whereBetween('paid_at', [$now->copy()->startOfMonth(), $now])
                ->sum('discount_amount'),

            'todayPaymentsCount' => Payment::query()
                ->where('status', 'completed')
                ->whereDate('paid_at', $today)
                ->count(),

            'todayAttendance' => StudentAttendance::query()
                ->whereDate('attended_at', $today)
                ->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Today's Classes
    |--------------------------------------------------------------------------
    */

    protected function getTodayClasses(Carbon $today): Collection
    {
        $schedules = ClassSchedule::query()
            ->select([
                'id',
                'branch_id',
                'student_class_id',
                'class_category_fee_id',
                'class_hall_id',
                'class_date',
                'start_time',
                'end_time',
                'status',
            ])
            ->with([
                'branch:id,name',
                'studentClass:id,class_name,teacher_id,grade_id',
                'studentClass.teacher:id,full_name',
                'studentClass.grade:id,grade_name',
                'classCategoryFee:id,class_category_id',
                'classCategoryFee.category:id,category_name',
                'hall:id,hall_name',
            ])
            ->withCount('studentAttendances')
            ->whereDate('class_date', $today)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get();

        if ($schedules->isEmpty()) {
            return collect();
        }

        /*
         * Enrolled students per class + category, in one query.
         */
        $enrolledCounts = StudentClassEnrollment::query()
            ->select([
                'student_class_id',
                'class_category_fee_id',
                DB::raw('COUNT(*) as total'),
            ])
            ->where('is_active', true)
            ->whereIn('student_class_id', $schedules->pluck('student_class_id')->unique())
            ->groupBy('student_class_id', 'class_category_fee_id')
            ->get()
            ->mapWithKeys(function ($row) {
                return [
                    $row->student_class_id . '-' . $row->class_category_fee_id => (int) $row->total,
                ];
            });

        $now = Carbon::now();

        return $schedules->map(function (ClassSchedule $schedule) use ($enrolledCounts, $now) {
            $enrolled = $enrolledCounts->get(
                $schedule->student_class_id . '-' . $schedule->class_category_fee_id,
                0
            );

            $present = (int) $schedule->student_attendances_count;

            $start = $schedule->class_date->copy()->setTimeFromTimeString($schedule->start_time);
            $end = $schedule->class_date->copy()->setTimeFromTimeString($schedule->end_time);

            return [
                'id' => $schedule->id,
                'class_name' => optional($schedule->studentClass)->class_name ?? '-',
                'grade' => optional(optional($schedule->studentClass)->grade)->grade_name ?? '-',
                'category' => optional(optional($schedule->classCategoryFee)->category)->category_name ?? '-',
                'teacher' => optional(optional($schedule->studentClass)->teacher)->full_name ?? '-',
                'hall' => optional($schedule->hall)->hall_name ?? '-',
                'branch' => optional($schedule->branch)->name ?? '-',

                'start_time' => $start->format('h:i A'),
                'end_time' => $end->format('h:i A'),
                'status' => $schedule->status,

                // live = within class time now (for a "Now" badge)
                'is_live' => $schedule->status !== 'cancelled' && $now->between($start, $end),

                'enrolled' => $enrolled,
                'present' => $present,
                'attendance_percent' => $enrolled > 0 ? (int) round(($present / $enrolled) * 100) : 0,
            ];
        })->values();
    }

    protected function summarizeTodayClasses(Collection $todayClasses): array
    {
        $byStatus = $todayClasses->countBy('status');

        return [
            'total' => $todayClasses->count(),
            'scheduled' => (int) $byStatus->get('scheduled', 0),
            'ongoing' => (int) $byStatus->get('ongoing', 0),
            'completed' => (int) $byStatus->get('completed', 0),
            'cancelled' => (int) $byStatus->get('cancelled', 0),
            'expected_students' => (int) $todayClasses->where('status', '!=', 'cancelled')->sum('enrolled'),
            'present_students' => (int) $todayClasses->sum('present'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Unpaid (current month)
    |--------------------------------------------------------------------------
    */

    protected function getUnpaidSummary(Carbon $monthStart): array
    {
        $unpaidQuery = StudentClassEnrollment::query()
            ->where('is_active', true)
            ->where('is_free_card', false)
            ->whereHas('student', function ($query) {
                $query->where('is_active', true);
            })
            ->whereDoesntHave('payments', function ($query) use ($monthStart) {
                $query->where('status', 'completed')
                    ->whereDate('payment_month', $monthStart->toDateString());
            });

        $unpaidCount = (clone $unpaidQuery)->count();

        $payableCount = StudentClassEnrollment::query()
            ->where('is_active', true)
            ->where('is_free_card', false)
            ->whereHas('student', function ($query) {
                $query->where('is_active', true);
            })
            ->count();

        /*
         * Classes with the most unpaid students this month.
         */
        $topClasses = (clone $unpaidQuery)
            ->select([
                'student_class_id',
                DB::raw('COUNT(*) as unpaid_count'),
            ])
            ->with('studentClass:id,class_name')
            ->groupBy('student_class_id')
            ->orderByDesc('unpaid_count')
            ->limit(5)
            ->get()
            ->map(function ($row) {
                return [
                    'student_class_id' => $row->student_class_id,
                    'class_name' => optional($row->studentClass)->class_name ?? '-',
                    'unpaid_count' => (int) $row->unpaid_count,
                ];
            })
            ->values();

        return [
            'month' => $monthStart->format('F Y'),
            'unpaid_count' => $unpaidCount,
            'paid_count' => max($payableCount - $unpaidCount, 0),
            'payable_count' => $payableCount,
            'paid_percent' => $payableCount > 0
                ? (int) round((($payableCount - $unpaidCount) / $payableCount) * 100)
                : 0,
            'top_classes' => $topClasses,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Income Charts
    |--------------------------------------------------------------------------
    */

    /**
     * Income for the last N months (oldest first), for a chart.
     */
    protected function getIncomeTrend(Carbon $now, int $months): array
    {
        $labels = [];
        $values = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = $now->copy()->subMonthsNoOverflow($i);

            $labels[] = $month->format('M Y');
            $values[] = $this->getMonthlyIncome($month->month, $month->year);
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * Income per day for the last N days (oldest first), for a chart.
     */
    protected function getDailyIncome(Carbon $today, int $days): array
    {
        $from = $today->copy()->subDays($days - 1)->startOfDay();

        $totals = Payment::query()
            ->select([
                DB::raw('DATE(paid_at) as day'),
                DB::raw('SUM(amount) as total'),
            ])
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$from, $today->copy()->endOfDay()])
            ->groupBy(DB::raw('DATE(paid_at)'))
            ->pluck('total', 'day');

        $labels = [];
        $values = [];

        for ($date = $from->copy(); $date->lte($today); $date->addDay()) {
            $labels[] = $date->format('d M');
            $values[] = (float) ($totals[$date->toDateString()] ?? 0);
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * This month's income split by payment method.
     */
    protected function getPaymentMethodBreakdown(Carbon $now): Collection
    {
        return Payment::query()
            ->select([
                'payment_method',
                DB::raw('COUNT(*) as payments'),
                DB::raw('SUM(amount) as total'),
            ])
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$now->copy()->startOfMonth(), $now])
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) {
                return [
                    'method' => $row->payment_method ?: 'cash',
                    'label' => ucwords(str_replace('_', ' ', $row->payment_method ?: 'cash')),
                    'payments' => (int) $row->payments,
                    'total' => (float) $row->total,
                ];
            })
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | Branch Summary
    |--------------------------------------------------------------------------
    */

    protected function getBranchSummary(Carbon $today, Carbon $now): Collection
    {
        $branches = Branch::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($branches->count() <= 1) {
            // Single-branch institute: the page can hide this section.
            return collect();
        }

        $todayIncome = Payment::query()
            ->where('status', 'completed')
            ->whereDate('paid_at', $today)
            ->groupBy('branch_id')
            ->select('branch_id', DB::raw('SUM(amount) as total'))
            ->pluck('total', 'branch_id');

        $monthIncome = Payment::query()
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$now->copy()->startOfMonth(), $now])
            ->groupBy('branch_id')
            ->select('branch_id', DB::raw('SUM(amount) as total'))
            ->pluck('total', 'branch_id');

        $activeStudents = $this->activeStudentQuery()
            ->groupBy('branch_id')
            ->select('branch_id', DB::raw('COUNT(*) as total'))
            ->pluck('total', 'branch_id');

        $todayClasses = ClassSchedule::query()
            ->whereDate('class_date', $today)
            ->where('is_active', true)
            ->where('status', '!=', 'cancelled')
            ->groupBy('branch_id')
            ->select('branch_id', DB::raw('COUNT(*) as total'))
            ->pluck('total', 'branch_id');

        return $branches->map(function (Branch $branch) use ($todayIncome, $monthIncome, $activeStudents, $todayClasses) {
            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'today_income' => (float) ($todayIncome[$branch->id] ?? 0),
                'month_income' => (float) ($monthIncome[$branch->id] ?? 0),
                'active_students' => (int) ($activeStudents[$branch->id] ?? 0),
                'today_classes' => (int) ($todayClasses[$branch->id] ?? 0),
            ];
        })->values();
    }

    /*
    |--------------------------------------------------------------------------
    | Recent Payments
    |--------------------------------------------------------------------------
    */

    protected function getRecentPayments(int $limit): Collection
    {
        return Payment::query()
            ->select([
                'id',
                'student_id',
                'student_class_enrollment_id',
                'user_id',
                'amount',
                'payment_method',
                'payment_month',
                'receipt_number',
                'paid_at',
            ])
            ->with([
                'student:id,custom_id,initial_name',
                'enrollment:id,student_class_id',
                'enrollment.studentClass:id,class_name',
                'collectedBy:id,name',
            ])
            ->where('status', 'completed')
            ->latest('paid_at')
            ->limit($limit)
            ->get()
            ->map(function (Payment $payment) {
                return [
                    'id' => $payment->id,
                    'student_code' => optional($payment->student)->custom_id ?? '-',
                    'student_name' => optional($payment->student)->initial_name ?? '-',
                    'class_name' => optional(optional($payment->enrollment)->studentClass)->class_name ?? '-',
                    'amount' => (float) $payment->amount,
                    'payment_method' => ucwords(str_replace('_', ' ', $payment->payment_method ?: 'cash')),
                    'payment_month' => optional($payment->payment_month)->format('M Y'),
                    'receipt_number' => $payment->receipt_number ?? '-',
                    'collected_by' => optional($payment->collectedBy)->name ?? '-',
                    'paid_at' => optional($payment->paid_at)->format('d M, h:i A'),
                    'paid_at_human' => optional($payment->paid_at)->diffForHumans(),
                ];
            })
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | Existing Counts
    |--------------------------------------------------------------------------
    */

    protected function getStudentsCount(): int
    {
        return Student::query()->count();
    }

    protected function getTeachersCount(): int
    {
        return Teacher::query()->count();
    }

    protected function getClassesCount(): int
    {
        return StudentClass::query()->count();
    }

    protected function getTodayIncome(Carbon $today): float
    {
        return $this->sumCompletedPayments(
            $today->copy()->startOfDay(),
            $today->copy()->endOfDay()
        );
    }

    protected function getMonthlyIncome(int $month, int $year): float
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();

        return $this->sumCompletedPayments($start, $start->copy()->endOfMonth());
    }

    protected function getAvailableStudentCardCount(): int
    {
        return StudentCard::query()
            ->where('status', 'available')
            ->count();
    }

    /*
    |--------------------------------------------------------------------------
    | Registrations / Alerts
    |--------------------------------------------------------------------------
    */

    protected function incompleteRegistrationQuery()
    {
        return Student::query()
            ->where(function ($query) {
                $query->whereNull('full_name')
                    ->orWhere('full_name', '')
                    ->orWhereNull('mobile')
                    ->orWhere('mobile', '')
                    ->orWhereNull('address1')
                    ->orWhere('address1', '');
            });
    }

    protected function getIncompleteRegistrationCount(): int
    {
        return $this->incompleteRegistrationQuery()->count();
    }

    protected function getIncompleteRegistrations(): Collection
    {
        return $this->incompleteRegistrationQuery()
            ->select([
                'id',
                'custom_id',
                'initial_name',
                'guardian_mobile',
            ])
            ->latest()
            ->take(5)
            ->get();
    }

    protected function getLatestStudents(): Collection
    {
        return Student::query()
            ->latest()
            ->take(5)
            ->get([
                'id',
                'custom_id',
                'initial_name',
                'guardian_mobile',
                'created_at',
            ]);
    }

    protected function getExpiringStudents(Carbon $today): Collection
    {
        return Student::query()
            ->select([
                'id',
                'temporary_qr_code',
                'initial_name',
                'guardian_mobile',
                'temporary_qr_code_expire_date',
            ])
            ->where('is_active', true)
            ->where('permanent_qr_active', false)
            ->whereNotNull('temporary_qr_code_expire_date')
            ->whereBetween('temporary_qr_code_expire_date', [
                $today->toDateString(),
                $today->copy()->addDays(self::QR_EXPIRY_DAYS)->toDateString(),
            ])
            ->orderBy('temporary_qr_code_expire_date')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Active = is_active and not disabled.
     */
    protected function activeStudentQuery()
    {
        return Student::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('student_disable')
                    ->orWhere('student_disable', false);
            });
    }

    /**
     * Sum of completed payments collected between two times.
     *
     * whereBetween on paid_at (instead of whereMonth/whereYear)
     * lets MySQL use the (status, paid_at) index.
     */
    protected function sumCompletedPayments(Carbon $from, Carbon $to): float
    {
        return (float) Payment::query()
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$from, $to])
            ->sum('amount');
    }

    /**
     * Percentage change, rounded to 1 decimal. Null when there is no base.
     */
    protected function percentChange(float $current, float $previous): ?float
    {
        if ($previous <= 0) {
            return null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
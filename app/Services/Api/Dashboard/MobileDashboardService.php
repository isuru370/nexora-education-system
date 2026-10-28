<?php

namespace App\Services\Api\Dashboard;

use App\Models\AdmissionPayment;
use App\Models\Branch;
use App\Models\ClassSchedule;
use App\Models\ExtraIncome;
use App\Models\InstitutePayment;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentCard;
use App\Models\StudentClass;
use App\Models\StudentClassEnrollment;
use App\Models\StudentTute;
use App\Models\Teacher;
use App\Models\TeacherPayment;
use App\Models\TemporaryIdCard;
use App\Models\User;
use App\Services\Branch\BranchAccessService;
use App\Services\SmsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Throwable;
use App\Support\CardAccess;
use App\Support\CardMode;

/**
 * Mobile app staff dashboard (GET /api/mobile-dashboard?branch_id=).
 *
 * - Every number is limited to the branches the user can access.
 * - branch_id (optional) narrows it to one branch.
 * - "features" tells the app which menu items / quick actions to show.
 * - Money = completed class fees + paid admissions, counted by paid_at
 *   (same rule as the web reports).
 */
class MobileDashboardService
{
    /** Roles that see every branch and the SMS balance */
    public const MANAGER_ROLES = ['SUPER_ADMIN', 'ADMIN'];

    /** Low stock warning levels (cards) */
    public const LOW_CARD_LIMIT = 10;
    public const CRITICAL_CARD_LIMIT = 3;

    /** How many rows the lists return */
    public const LIST_LIMIT = 5;
    public const TODAY_CLASS_LIMIT = 30;

    /**
     * Menu items / sections each role can see in the app.
     * Change only this array to give / take a feature from a role.
     */
    public const FEATURES = [
        // Student management
        'register_student' => ['SUPER_ADMIN', 'ADMIN', 'BRANCH_ADMIN', 'USER'],
        'reassign_card' => ['SUPER_ADMIN', 'ADMIN', 'BRANCH_ADMIN', 'USER'],
        'capture_image' => ['SUPER_ADMIN', 'ADMIN', 'BRANCH_ADMIN', 'USER'],
        'students' => ['SUPER_ADMIN', 'ADMIN', 'BRANCH_ADMIN', 'USER'],
        'student_images' => ['SUPER_ADMIN', 'ADMIN', 'BRANCH_ADMIN', 'USER'],
        'search_student' => ['SUPER_ADMIN', 'ADMIN', 'BRANCH_ADMIN', 'USER'],

        // Class & attendance
        'today_classes' => ['SUPER_ADMIN', 'ADMIN', 'BRANCH_ADMIN', 'USER'],
        'add_class' => ['SUPER_ADMIN', 'ADMIN', 'BRANCH_ADMIN', 'USER'],
        'class_schedule' => ['SUPER_ADMIN', 'ADMIN', 'BRANCH_ADMIN'], // update / cancel classes
        'attendance' => ['SUPER_ADMIN', 'ADMIN', 'BRANCH_ADMIN', 'USER'],

        // Financial
        'payment' => ['SUPER_ADMIN', 'ADMIN', 'BRANCH_ADMIN', 'USER'],
        'today_payments' => ['SUPER_ADMIN', 'ADMIN', 'BRANCH_ADMIN', 'USER'],
        'admission' => ['SUPER_ADMIN', 'ADMIN', 'BRANCH_ADMIN', 'USER'],
        'finance_summary' => ['SUPER_ADMIN', 'ADMIN', 'BRANCH_ADMIN'], // income / expenses today
        'teacher_advance' => ['SUPER_ADMIN', 'ADMIN', 'BRANCH_ADMIN'], // pay a teacher advance (same roles as the web)

        // Other
        'student_tute' => ['SUPER_ADMIN', 'ADMIN', 'BRANCH_ADMIN', 'USER'],
        'sms_balance' => ['SUPER_ADMIN', 'ADMIN'],
    ];

    public function __construct(
        protected BranchAccessService $branchAccess,
        protected SmsService $smsService
    ) {}

    public function role(User $user): string
    {
        return strtoupper((string) optional($user->userType)->code);
    }

    /**
     * Branch IDs to use for the numbers.
     *
     * @return Collection|null  null = the user cannot access $branchId
     */
    public function resolveBranchIds(?int $branchId): ?Collection
    {
        $allowed = $this->branchAccess->getBranchIds()->map(fn ($id) => (int) $id)->values();

        if ($branchId === null) {
            return $allowed;
        }

        return $allowed->contains($branchId) ? collect([$branchId]) : null;
    }

    /**
     * Feature keys for a role.
     */
    public function features(string $role): array
    {
        $features = collect(self::FEATURES)
            ->filter(fn (array $roles) => in_array($role, $roles, true))
            ->keys();

        // ID cards: one way to register per institute (nexora:card-mode)
        $cards = CardAccess::forRole($role);

        if (CardMode::permanent()) {
            // students get the permanent card at registration: no Re Assign
            $features = $features->reject(fn ($key) => $key === 'reassign_card');
        }

        if ($features->contains('register_student')) {
            $features->push(CardMode::temporary() ? 'register_temporary' : 'register_permanent');
        }

        if ($cards['manage']) {
            $features->push('replace_card'); // lost / damaged -> new card
        }

        return $features->values()->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    public function build(User $user, Collection $branchIds, ?int $selectedBranchId): array
    {
        $user->loadMissing('userType');

        $role = $this->role($user);
        $features = $this->features($role);
        $ids = $branchIds->all();

        $now = now();
        $day = [$now->copy()->startOfDay(), $now->copy()->endOfDay()];
        $month = [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];

        $students = $this->studentStats($ids, $day, $month);
        $classes = $this->classStats($ids, $now);
        $todayClasses = $this->todayClasses($ids, $now);
        $collection = $this->collectionStats($ids, $day, $month, $user->id);
        $cards = $this->cardStats($ids);

        $smsBalance = in_array('sms_balance', $features, true) ? $this->smsBalance() : null;
        $finance = in_array('finance_summary', $features, true)
            ? $this->financeSummary($ids, $now, $collection['today_total'])
            : null;

        $pendingAdmissions = AdmissionPayment::query()
            ->whereIn('branch_id', $ids)
            ->where('status', AdmissionPayment::STATUS_PENDING)
            ->count();

        $todayAttendance = StudentAttendance::query()
            ->whereIn('branch_id', $ids)
            ->whereBetween('attended_at', $day)
            ->count();

        $tutesIssuedToday = StudentTute::query()
            ->whereIn('branch_id', $ids)
            ->where('is_issued', true)
            ->whereBetween('issued_at', $day)
            ->count();

        return [
            /*
             * Old keys - the current app reads these, keep them.
             */
            'sms_balance' => $smsBalance,
            'total_student' => $students['total'],
            'total_teacher' => $classes['teachers'],
            'total_classes' => $classes['active_classes'],
            'today_class_count' => $classes['today_running'],
            'today_payment_collection' => $collection['today_class_fees'],
            'current_month_admission_count' => $students['new_this_month'],
            'current_month_admissions' => $students['latest'],
            'temporary_student_count' => $students['temporary'],
            'permanent_student_count' => $students['permanent'],
            'available_id_card' => $cards['permanent_available'],

            // Flat shortcuts used by the new app
            'today_cancelled_class_count' => $classes['today_cancelled'],
            'today_payment_count' => $collection['today_class_fee_count'],
            'today_admission_collection' => $collection['today_admissions'],
            'today_total_collection' => $collection['today_total'],
            'my_today_collection' => $collection['my_today'],

            /*
             * Full details (grouped)
             */
            'students' => collect($students)->except('latest')->all(),
            'classes' => $classes,
            'today_classes' => $todayClasses,
            'attendance' => [
                'today_count' => $todayAttendance,
            ],
            'collection' => $collection,
            'recent_payments' => $this->recentPayments($ids, $day),
            'admissions' => [
                'pending_count' => $pendingAdmissions,
            ],
            'tutes' => [
                'issued_today' => $tutesIssuedToday,
            ],
            'cards' => $cards,
            'finance' => $finance,
            'alerts' => $this->alerts($cards, $classes, $pendingAdmissions, $smsBalance),

            /*
             * Who / where
             */
            'role' => $role,
            'all_branches' => in_array($role, self::MANAGER_ROLES, true),
            'branch' => $this->branchInfo($selectedBranchId),
            'branches' => $this->branchAccess->getAccessibleBranches()
                ->map(fn (Branch $b) => ['id' => $b->id, 'name' => $b->name, 'code' => $b->code])
                ->values(),
            'features' => $features,
            'card_mode' => CardMode::get(),
            'date' => $now->toDateString(),
            'generated_at' => $now->toDateTimeString(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Students
    |--------------------------------------------------------------------------
    */

    protected function studentStats(array $ids, array $day, array $month): array
    {
        $students = fn () => Student::query()->whereIn('branch_id', $ids);

        $total = $students()->count();
        $permanent = $students()->where('permanent_qr_active', true)->count();

        $active = $students()
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('student_disable', false)->orWhereNull('student_disable'))
            ->count();

        $newThisMonth = $students()->whereBetween('created_at', $month);

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active,
            'permanent' => $permanent,
            'temporary' => $total - $permanent,
            'new_today' => $students()->whereBetween('created_at', $day)->count(),
            'new_this_month' => (clone $newThisMonth)->count(),
            'latest' => $newThisMonth
                ->latest()
                ->take(self::LIST_LIMIT)
                ->get([
                    'id',
                    'custom_id',
                    'temporary_qr_code',
                    'initial_name',
                    'gender',
                    'guardian_mobile',
                ]),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Classes / teachers
    |--------------------------------------------------------------------------
    */

    protected function classStats(array $ids, Carbon $now): array
    {
        $byStatus = ClassSchedule::query()
            ->whereIn('branch_id', $ids)
            ->whereDate('class_date', $now->toDateString())
            ->where('is_active', true)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $scheduled = (int) ($byStatus['scheduled'] ?? 0);
        $ongoing = (int) ($byStatus['ongoing'] ?? 0);
        $completed = (int) ($byStatus['completed'] ?? 0);
        $cancelled = (int) ($byStatus['cancelled'] ?? 0);

        return [
            'teachers' => Teacher::query()
                ->where('is_active', true)
                ->whereHas('branches', fn ($q) => $q->whereIn('branches.id', $ids))
                ->count(),
            'active_classes' => StudentClass::query()
                ->whereIn('branch_id', $ids)
                ->where('is_active', true)
                ->count(),
            'active_enrollments' => StudentClassEnrollment::query()
                ->whereIn('branch_id', $ids)
                ->where('is_active', true)
                ->count(),
            'today_total' => $scheduled + $ongoing + $completed + $cancelled,
            'today_running' => $scheduled + $ongoing + $completed, // not cancelled
            'today_scheduled' => $scheduled,
            'today_ongoing' => $ongoing,
            'today_completed' => $completed,
            'today_cancelled' => $cancelled,
        ];
    }

    /**
     * Today's sessions with teacher, hall and attendance (marked / enrolled).
     */
    protected function todayClasses(array $ids, Carbon $now): Collection
    {
        $schedules = ClassSchedule::query()
            ->with([
                'studentClass:id,class_name,teacher_id,branch_id',
                'studentClass.teacher:id,full_name,initials',
                'hall:id,hall_name',
                'branch:id,name,code',
            ])
            ->whereIn('branch_id', $ids)
            ->whereDate('class_date', $now->toDateString())
            ->where('is_active', true)
            ->orderBy('start_time')
            ->take(self::TODAY_CLASS_LIMIT)
            ->get();

        if ($schedules->isEmpty()) {
            return collect();
        }

        // Attendance marked per session
        $attended = StudentAttendance::query()
            ->whereIn('class_schedule_id', $schedules->pluck('id'))
            ->selectRaw('class_schedule_id, COUNT(*) as total')
            ->groupBy('class_schedule_id')
            ->pluck('total', 'class_schedule_id');

        // Active students per class
        $enrolled = StudentClassEnrollment::query()
            ->whereIn('student_class_id', $schedules->pluck('student_class_id')->unique())
            ->where('is_active', true)
            ->selectRaw('student_class_id, COUNT(*) as total')
            ->groupBy('student_class_id')
            ->pluck('total', 'student_class_id');

        return $schedules->map(function (ClassSchedule $s) use ($attended, $enrolled) {
            $teacher = optional($s->studentClass)->teacher;

            return [
                'id' => $s->id,
                'student_class_id' => $s->student_class_id,
                'class_name' => optional($s->studentClass)->class_name,
                'teacher_name' => $teacher->full_name ?? $teacher->initials ?? null,
                'hall_name' => optional($s->hall)->hall_name,
                'branch_name' => optional($s->branch)->name,
                'start_time' => $this->time($s->start_time),
                'end_time' => $this->time($s->end_time),
                'status' => $s->status,
                'enrolled_count' => (int) ($enrolled[$s->student_class_id] ?? 0),
                'attended_count' => (int) ($attended[$s->id] ?? 0),
                'cancel_reason' => $s->status === 'cancelled' ? $s->cancel_reason : null,
            ];
        })->values();
    }

    /*
    |--------------------------------------------------------------------------
    | Money
    |--------------------------------------------------------------------------
    */

    protected function collectionStats(array $ids, array $day, array $month, int $userId): array
    {
        $fees = fn (array $range) => Payment::query()
            ->whereIn('branch_id', $ids)
            ->where('status', 'completed')
            ->whereBetween('paid_at', $range);

        $admissions = fn (array $range) => AdmissionPayment::query()
            ->whereIn('branch_id', $ids)
            ->where('status', AdmissionPayment::STATUS_PAID)
            ->whereBetween('paid_at', $range);

        $todayFees = (float) $fees($day)->sum('amount');
        $todayAdmissions = (float) $admissions($day)->sum('amount');
        $monthFees = (float) $fees($month)->sum('amount');
        $monthAdmissions = (float) $admissions($month)->sum('amount');

        // Money this user collected today (cashier's drawer)
        $myFees = (float) $fees($day)->where('user_id', $userId)->sum('amount');
        $myAdmissions = (float) $admissions($day)->where('user_id', $userId)->sum('amount');

        // Today by payment method (class fees + admissions)
        $byMethod = collect();

        foreach ([$fees($day), $admissions($day)] as $query) {
            $query->selectRaw('payment_method, SUM(amount) as amount, COUNT(*) as total')
                ->groupBy('payment_method')
                ->get()
                ->each(function ($row) use ($byMethod) {
                    $key = $row->payment_method ?: 'other';
                    $old = $byMethod->get($key, ['amount' => 0, 'count' => 0]);

                    $byMethod->put($key, [
                        'amount' => $old['amount'] + (float) $row->amount,
                        'count' => $old['count'] + (int) $row->total,
                    ]);
                });
        }

        return [
            'today_class_fees' => $this->money($todayFees),
            'today_class_fee_count' => $fees($day)->count(),
            'today_admissions' => $this->money($todayAdmissions),
            'today_admission_count' => $admissions($day)->count(),
            'today_total' => $this->money($todayFees + $todayAdmissions),
            'today_discount' => $this->money((float) $fees($day)->sum('discount_amount')),

            'my_today' => $this->money($myFees + $myAdmissions),
            'my_today_count' => $fees($day)->where('user_id', $userId)->count()
                + $admissions($day)->where('user_id', $userId)->count(),

            'month_class_fees' => $this->money($monthFees),
            'month_admissions' => $this->money($monthAdmissions),
            'month_total' => $this->money($monthFees + $monthAdmissions),

            'by_method' => $byMethod
                ->map(fn (array $v, string $method) => [
                    'method' => $method,
                    'amount' => $this->money($v['amount']),
                    'count' => $v['count'],
                ])
                ->sortByDesc('amount')
                ->values(),
        ];
    }

    /**
     * Last class-fee payments today.
     */
    protected function recentPayments(array $ids, array $day): Collection
    {
        return Payment::query()
            ->with([
                'student:id,custom_id,initial_name',
                'enrollment:id,student_class_id',
                'enrollment.studentClass:id,class_name',
                'collectedBy:id,name',
            ])
            ->whereIn('branch_id', $ids)
            ->where('status', 'completed')
            ->whereBetween('paid_at', $day)
            ->latest('paid_at')
            ->latest('id')
            ->take(self::LIST_LIMIT)
            ->get()
            ->map(fn (Payment $p) => [
                'id' => $p->id,
                'receipt_number' => $p->receipt_number,
                'student_id' => $p->student_id,
                'student_custom_id' => optional($p->student)->custom_id,
                'student_name' => optional($p->student)->initial_name,
                'class_name' => optional(optional($p->enrollment)->studentClass)->class_name,
                'payment_month' => $p->payment_month ? Carbon::parse($p->payment_month)->format('Y-m') : null,
                'amount' => $this->money((float) $p->amount),
                'payment_method' => $p->payment_method,
                'paid_at' => $p->paid_at ? Carbon::parse($p->paid_at)->format('Y-m-d H:i:s') : null,
                'collected_by' => optional($p->collectedBy)->name,
            ])
            ->values();
    }

    /**
     * Income and expenses of today (Admin / Branch Admin).
     * Records without a branch are not counted here.
     */
    protected function financeSummary(array $ids, Carbon $now, float $collected): array
    {
        $today = $now->toDateString();

        $extraIncome = (float) ExtraIncome::query()
            ->whereIn('branch_id', $ids)
            ->where('status', 'received')
            ->whereDate('income_date', $today)
            ->sum('amount');

        $expenses = (float) InstitutePayment::query()
            ->whereIn('branch_id', $ids)
            ->where('status', 'paid')
            ->whereDate('payment_date', $today)
            ->sum('amount');

        $teacherAdvances = (float) TeacherPayment::query()
            ->whereIn('branch_id', $ids)
            ->where('status', 'paid')
            ->where('payment_type', 'advance')
            ->whereDate('payment_date', $today)
            ->sum('amount');

        $income = $collected + $extraIncome;
        $outgoing = $expenses + $teacherAdvances;

        return [
            'student_collection' => $this->money($collected),
            'extra_income' => $this->money($extraIncome),
            'total_income' => $this->money($income),
            'expenses' => $this->money($expenses),
            'teacher_advances' => $this->money($teacherAdvances),
            'total_outgoing' => $this->money($outgoing),
            'net' => $this->money($income - $outgoing),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Cards
    |--------------------------------------------------------------------------
    */

    protected function cardStats(array $ids): array
    {
        $permanentAvailable = StudentCard::query()
            ->whereIn('branch_id', $ids)
            ->where('status', 'available')
            ->count();

        // Temporary cards are shared by all branches (no branch_id).
        // Printed (downloaded / issued) and not given to a student yet.
        $temporaryAvailable = TemporaryIdCard::query()
            ->whereIn('status', ['downloaded', 'issued'])
            ->whereNull('student_id')
            ->count();

        return [
            'permanent_available' => $permanentAvailable,
            'permanent_level' => $this->stockLevel($permanentAvailable),
            'temporary_available' => $temporaryAvailable,
            'temporary_level' => $this->stockLevel($temporaryAvailable),
        ];
    }

    protected function stockLevel(int $count): string
    {
        if ($count <= self::CRITICAL_CARD_LIMIT) {
            return 'critical';
        }

        return $count <= self::LOW_CARD_LIMIT ? 'low' : 'ok';
    }

    /*
    |--------------------------------------------------------------------------
    | Alerts (shown at the top of the dashboard)
    |--------------------------------------------------------------------------
    */

    protected function alerts(array $cards, array $classes, int $pendingAdmissions, ?array $sms): array
    {
        $alerts = [];

        if ($cards['permanent_level'] !== 'ok') {
            $alerts[] = [
                'type' => 'permanent_cards',
                'level' => $cards['permanent_level'] === 'critical' ? 'danger' : 'warning',
                'message' => "Only {$cards['permanent_available']} permanent ID card(s) left.",
            ];
        }

        if (CardMode::temporary() && $cards['temporary_level'] !== 'ok') {
            $alerts[] = [
                'type' => 'temporary_cards',
                'level' => $cards['temporary_level'] === 'critical' ? 'danger' : 'warning',
                'message' => "Only {$cards['temporary_available']} temporary card(s) left.",
            ];
        }

        if ($classes['today_cancelled'] > 0) {
            $alerts[] = [
                'type' => 'cancelled_classes',
                'level' => 'info',
                'message' => "{$classes['today_cancelled']} class(es) cancelled today.",
            ];
        }

        if ($pendingAdmissions > 0) {
            $alerts[] = [
                'type' => 'pending_admissions',
                'level' => 'info',
                'message' => "{$pendingAdmissions} admission payment(s) pending.",
            ];
        }

        if ($sms !== null && !$sms['success']) {
            $alerts[] = [
                'type' => 'sms_balance',
                'level' => 'warning',
                'message' => 'Could not read the SMS balance.',
            ];
        }

        return $alerts;
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function branchInfo(?int $branchId): ?array
    {
        if (!$branchId) {
            return null;
        }

        $branch = Branch::query()->find($branchId, ['id', 'name', 'code']);

        return $branch ? ['id' => $branch->id, 'name' => $branch->name, 'code' => $branch->code] : null;
    }

    protected function money(float $value): float
    {
        return round($value, 2);
    }

    /** "08:00:00" / Carbon -> "08:00" */
    protected function time($value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('H:i');
        } catch (Throwable $e) {
            return (string) $value;
        }
    }

    /**
     * SMS balance in the shape the app reads (SmsBalanceModel):
     * { success, http_status, provider_status_code, current_balance, data: { status_code, current_balance } }
     *
     * Cached for 5 minutes - the provider call can take up to 15 seconds,
     * and it must never break the dashboard.
     */
    protected function smsBalance(): array
    {
        return Cache::remember('mobile_dashboard_sms_balance', now()->addMinutes(5), function () {
            try {
                $raw = $this->smsService->getBalance();
            } catch (Throwable $e) {
                report($e);
                $raw = [];
            }

            $json = is_array($raw['json'] ?? null) ? $raw['json'] : [];

            $balance = $json['current_balance'] ?? $json['balance'] ?? $raw['current_balance'] ?? null;
            $statusCode = (int) ($json['status_code'] ?? $raw['provider_status_code'] ?? 0);

            return [
                'success' => $balance !== null,
                'http_status' => (int) ($raw['http_status'] ?? 0),
                'provider_status_code' => $statusCode,
                'current_balance' => $balance !== null ? (string) $balance : null,
                'data' => [
                    'status_code' => $statusCode,
                    'current_balance' => $balance !== null ? (string) $balance : '0',
                ],
            ];
        });
    }
}

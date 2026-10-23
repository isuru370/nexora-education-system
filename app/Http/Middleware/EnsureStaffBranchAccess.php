<?php

namespace App\Http\Middleware;

use App\Services\Branch\BranchAccessService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Staff API (mobile app): a branch admin / cashier may only read or change
 * records of their own branches.
 *
 * Every request is checked for the records it points at - students,
 * enrollments, schedules, classes, attendance, payments, notifications and
 * student QR codes - in the body, the query string or the URL. If any of
 * them belongs to a branch the user cannot access, the request is refused
 * with 403 before it reaches the controller.
 *
 * ADMIN / SUPER_ADMIN can access every branch and are not checked.
 * Records that do not exist are left to the endpoint's own validation.
 */
class EnsureStaffBranchAccess
{
    /** request field / route parameter => [table, column] */
    protected const RECORDS = [
        'student_id' => ['students', 'id'],
        'studentId' => ['students', 'id'],
        'enrollment_id' => ['student_class_enrollments', 'id'],
        'student_class_enrollment_id' => ['student_class_enrollments', 'id'],
        'enrollmentId' => ['student_class_enrollments', 'id'],
        'enrolledId' => ['student_class_enrollments', 'id'],
        'enrollment' => ['student_class_enrollments', 'id'],
        'schedule_id' => ['class_schedules', 'id'],
        'class_schedule_id' => ['class_schedules', 'id'],
        'student_class_id' => ['student_classes', 'id'],
        'class_id' => ['student_classes', 'id'],
        'attendanceId' => ['student_attendances', 'id'],
        'paymentId' => ['payments', 'id'],
        'branch_id' => ['branches', 'id'],
    ];

    /** Endpoints where "code" / "qr_code" is a student's QR code or student ID. */
    protected const STUDENT_CODE_ROUTES = [
        'api/students/filter',
        'api/attendance/read',
        'api/payments/read',
        'api/student-class-enrollments/read-student-class',
        'api/student-tutes/read',
        'api/new-attendance/scan',
        'api/new-payment/read',
        'api/new-payment/bulk-pay',
        'api/student-card-assignment/search-student',
    ];

    public function __construct(private BranchAccessService $branchAccess)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $role = strtoupper((string) optional(optional($user)->userType)->code);

        if (!$user || in_array($role, ['ADMIN', 'SUPER_ADMIN'], true)) {
            return $next($request);
        }

        $allowed = $this->branchAccess->getBranchIds()->map(fn ($id) => (int) $id)->all();

        foreach ($this->branchesOf($request) as $branchId) {
            if ($branchId !== null && !in_array((int) $branchId, $allowed, true)) {
                return $this->forbidden();
            }
        }

        return $next($request);
    }

    /**
     * Branch ids of every record the request points at.
     *
     * @return array<int|null>
     */
    protected function branchesOf(Request $request): array
    {
        $input = $request->all();
        $route = $request->route() ? $request->route()->parameters() : [];
        $branches = [];

        foreach (self::RECORDS as $field => [$table, $column]) {
            $values = $this->ids(array_merge(
                Arr::wrap($input[$field] ?? []),
                Arr::wrap($route[$field] ?? []),
                Arr::pluck($input['payments'] ?? [], $field)
            ));

            if (!$values) {
                continue;
            }

            if ($table === 'branches') {
                // A branch_id in the request is itself the branch.
                array_push($branches, ...$values);
                continue;
            }

            array_push($branches, ...DB::table($table)->whereIn($column, $values)->pluck('branch_id')->all());
        }

        // Class fee -> its class's branch
        $fees = $this->ids(Arr::wrap($input['class_category_fee_id'] ?? []));
        if ($fees) {
            array_push($branches, ...DB::table('class_category_fees')
                ->join('student_classes', 'student_classes.id', '=', 'class_category_fees.student_class_id')
                ->whereIn('class_category_fees.id', $fees)
                ->pluck('student_classes.branch_id')->all());
        }

        // /api/notifications/{id}/...
        if ($request->is('api/notifications/*') && isset($route['id'])) {
            array_push($branches, ...DB::table('notifications')->whereIn('id', $this->ids([$route['id']]))->pluck('branch_id')->all());
        }

        // Student QR code / student ID
        if ($request->is(...self::STUDENT_CODE_ROUTES)) {
            $codes = array_values(array_filter(array_map(
                fn ($code) => is_scalar($code) ? trim((string) $code) : '',
                array_merge(
                    Arr::wrap($input['code'] ?? []),
                    Arr::wrap($input['qr_code'] ?? []),
                    Arr::pluck($input['payments'] ?? [], 'code')
                )
            )));

            if ($codes) {
                array_push($branches, ...DB::table('students')
                    ->where(fn ($q) => $q->whereIn('custom_id', $codes)->orWhereIn('temporary_qr_code', $codes))
                    ->pluck('branch_id')->all());
            }
        }

        return $branches;
    }

    /** Positive integer ids only (models bound to the route give their key). */
    protected function ids(array $values): array
    {
        $ids = [];

        foreach ($values as $value) {
            if (is_object($value) && method_exists($value, 'getKey')) {
                $value = $value->getKey();
            }

            if (is_numeric($value) && (int) $value > 0) {
                $ids[] = (int) $value;
            }
        }

        return array_values(array_unique($ids));
    }

    protected function forbidden(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'status' => false,
            'message' => 'You do not have access to this branch.',
        ], 403);
    }
}

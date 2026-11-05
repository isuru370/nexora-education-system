<?php

namespace App\Http\Controllers\Web\Report;

use App\Http\Controllers\Controller;
use App\Models\StudentAttendance;
use App\Models\StudentClass;
use App\Services\Branch\BranchAccessService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Reports > Card Scan vs Manual Attendance: per class, how many were marked
 * by scanning the ID card and how many by typing the Student ID, and the
 * students marked by typing (they did not bring the card).
 */
class ManualAttendanceReportController extends Controller
{
    public function __construct(protected BranchAccessService $branchAccess) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'class_id' => ['nullable', 'integer'],
        ]);

        $from = Carbon::parse($validated['from'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $to = Carbon::parse($validated['to'] ?? now()->toDateString())->endOfDay();
        if ($to->lt($from)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }
        $classId = isset($validated['class_id']) ? (int) $validated['class_id'] : null;

        $branchIds = $this->branchAccess->getBranchIds()->map(fn ($id) => (int) $id)->all();

        $base = StudentAttendance::query()
            ->whereIn('student_attendances.branch_id', $branchIds)
            ->whereBetween('student_attendances.attended_at', [$from, $to])
            ->join('class_schedules', 'class_schedules.id', '=', 'student_attendances.class_schedule_id')
            ->when($classId, fn ($q) => $q->where('class_schedules.student_class_id', $classId));

        $manual = "CASE WHEN student_attendances.mark_method LIKE 'manual%' THEN 1 ELSE 0 END";

        // Per class: card scans / typed
        $classes = (clone $base)
            ->groupBy('class_schedules.student_class_id')
            ->selectRaw("class_schedules.student_class_id as class_id,
                COUNT(*) as total,
                SUM({$manual}) as manual")
            ->get();

        $classNames = StudentClass::withTrashed()
            ->whereIn('id', $classes->pluck('class_id')->merge($classId ? [$classId] : [])->unique())
            ->pluck('class_name', 'id');

        $classRows = $classes->map(fn ($row) => [
            'class_id' => (int) $row->class_id,
            'class' => $classNames[$row->class_id] ?? ('#' . $row->class_id),
            'total' => (int) $row->total,
            'manual' => (int) $row->manual,
            'qr' => (int) $row->total - (int) $row->manual,
        ])->sortByDesc('manual')->values();

        // Students marked by typing the Student ID, most often first
        $students = (clone $base)
            ->join('students', 'students.id', '=', 'student_attendances.student_id')
            ->groupBy('student_attendances.student_id', 'class_schedules.student_class_id', 'students.custom_id', 'students.initial_name', 'students.guardian_mobile')
            ->havingRaw("SUM({$manual}) > 0")
            ->selectRaw("student_attendances.student_id,
                class_schedules.student_class_id as class_id,
                students.custom_id, students.initial_name, students.guardian_mobile,
                COUNT(*) as total,
                SUM({$manual}) as manual,
                MAX(CASE WHEN student_attendances.mark_method LIKE 'manual%' THEN student_attendances.attended_at END) as last_manual")
            ->orderByDesc(DB::raw("SUM({$manual})"))
            ->orderBy('students.initial_name')
            ->limit(500)
            ->get()
            ->map(fn ($row) => [
                'custom_id' => $row->custom_id,
                'name' => $row->initial_name,
                'guardian_mobile' => $row->guardian_mobile,
                'class' => $classNames[$row->class_id] ?? ('#' . $row->class_id),
                'manual' => (int) $row->manual,
                'total' => (int) $row->total,
                'last_manual' => $row->last_manual ? Carbon::parse($row->last_manual) : null,
            ]);

        $allClasses = StudentClass::query()
            ->whereIn('branch_id', $branchIds)
            ->orderBy('class_name')
            ->pluck('class_name', 'id');

        return view('reports.manual-attendance', [
            'from' => $from,
            'to' => $to,
            'classId' => $classId,
            'allClasses' => $allClasses,
            'classRows' => $classRows,
            'students' => $students,
            'totals' => [
                'total' => $classRows->sum('total'),
                'manual' => $classRows->sum('manual'),
                'qr' => $classRows->sum('qr'),
            ],
        ]);
    }
}

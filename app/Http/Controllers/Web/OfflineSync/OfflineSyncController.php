<?php

namespace App\Http\Controllers\Web\OfflineSync;

use App\Http\Controllers\Controller;
use App\Models\OfflineSyncItem;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\StudentClassEnrollment;
use App\Services\Branch\BranchAccessService;
use Illuminate\Http\Request;

/**
 * What the staff app sent after working offline. Conflicts (e.g. a month
 * paid on two phones) and refused items wait here for a person.
 */
class OfflineSyncController extends Controller
{
    public function __construct(
        protected BranchAccessService $branchAccess
    ) {}

    public function index(Request $request)
    {
        $show = in_array($request->get('show'), ['review', 'all'], true) ? $request->get('show') : 'review';
        $branchIds = $this->branchAccess->getBranchIds();

        $items = OfflineSyncItem::query()
            ->with(['user:id,name', 'branch:id,name', 'payment:id,receipt_number,amount,status,deleted_at'])
            // an item refused before its branch was known: show it to everyone who can see this page
            ->where(fn ($q) => $q->whereIn('branch_id', $branchIds)->orWhereNull('branch_id'))
            ->when($show === 'review', fn ($q) => $q->whereIn('status', OfflineSyncItem::NEEDS_REVIEW)->whereNull('reviewed_at'))
            ->orderByDesc('happened_at')
            ->paginate(50)
            ->withQueryString();

        $rows = collect($items->items());
        $payloads = $rows->map->payload;
        $students = Student::query()
            ->whereIn('id', $payloads->pluck('student_id')->merge($rows->pluck('student_id'))->filter()->unique())
            ->get(['id', 'custom_id', 'initial_name', 'full_name'])->keyBy('id');
        $enrollments = StudentClassEnrollment::withTrashed()->with('studentClass:id,class_name')
            ->whereIn('id', $payloads->pluck('enrollment_id')->merge($rows->pluck('student_class_enrollment_id'))->filter()->unique())
            ->get(['id', 'student_class_id'])->keyBy('id');
        // enrollment items refused: the class they asked for
        $classes = StudentClass::query()->whereIn('id', $payloads->pluck('student_class_id')->filter()->unique())
            ->pluck('class_name', 'id');

        $waiting = OfflineSyncItem::query()
            ->where(fn ($q) => $q->whereIn('branch_id', $branchIds)->orWhereNull('branch_id'))
            ->whereIn('status', OfflineSyncItem::NEEDS_REVIEW)
            ->whereNull('reviewed_at')
            ->count();

        return view('offline-sync.index', compact('items', 'students', 'enrollments', 'classes', 'show', 'waiting'));
    }

    public function reviewed(OfflineSyncItem $item)
    {
        if ($item->branch_id && !$this->branchAccess->canAccessBranch((int) $item->branch_id)) {
            abort(403);
        }

        $item->update(['reviewed_at' => now(), 'reviewed_by' => auth()->id()]);

        return back()->with('success', 'Marked as checked.');
    }
}

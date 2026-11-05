<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\StudentAttendance;
use App\Services\Web\Student\StudentAutoDisableService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as ConsoleCommand;

class DisableExpiredStudents extends Command
{
    protected $signature = 'disable:expired-students';

    protected $description = 'Disable students with no attendance for the last 2 months';

    public function handle(StudentAutoDisableService $service)
    {
        $twoMonthsAgo = Carbon::now()
            ->subMonths(2)
            ->startOfDay();

        /*
        |--------------------------------------------------------------------------
        | Branches that have used this system for 2 months
        |--------------------------------------------------------------------------
        |
        | "No attendance for 2 months" only means something when attendance has
        | been marked in this system for 2 months. A branch that started (or
        | imported its students) less than 2 months ago has no attendance
        | history yet, so nobody there is disabled. (Imported students and
        | enrollments keep their old dates, so their dates cannot tell this.)
        |
        */

        $readyBranchIds = StudentAttendance::query()
            ->whereNotNull('branch_id')
            ->groupBy('branch_id')
            ->havingRaw('MIN(attended_at) <= ?', [$twoMonthsAgo->toDateTimeString()])
            ->pluck('branch_id');

        if ($readyBranchIds->isEmpty()) {
            $this->info('No branch has 2 months of attendance in this system yet. Nobody was disabled.');

            return ConsoleCommand::SUCCESS;
        }

        /*
        |--------------------------------------------------------------------------
        | Find students eligible for deactivation
        |--------------------------------------------------------------------------
        */

        $students = Student::query()
            ->where('student_disable', false)
            ->where('is_active', true)
            ->whereIn('branch_id', $readyBranchIds)

            // At least one active enrollment older than 2 months - in THIS system.
            // An enrollment imported from an old system keeps its old enrolled_at,
            // but its attendance was not imported: count from when the row was
            // made here (created_at), or every imported student is disabled the
            // first night.
            ->whereHas('enrollments', function ($query) use ($twoMonthsAgo) {
                $query
                    ->where('is_active', true)
                    ->whereNotNull('enrolled_at')
                    ->whereDate(
                        'enrolled_at',
                        '<=',
                        $twoMonthsAgo->toDateString()
                    )
                    ->where(fn ($q) => $q->whereNull('created_at')->orWhere('created_at', '<=', $twoMonthsAgo));
            })

            // No attendance during the last 2 months
            ->whereDoesntHave('attendances', function ($query) use ($twoMonthsAgo) {
                $query->where(
                    'attended_at',
                    '>=',
                    $twoMonthsAgo
                );
            })

            ->get();

        /*
        |--------------------------------------------------------------------------
        | Disable students + Leave active classes
        |--------------------------------------------------------------------------
        */

        $updated = 0;
        $now = Carbon::now();

        foreach ($students as $student) {
            // Disable + leave the active classes, and keep a record (classes,
            // last attendance, last payment) for the "Auto-disabled students" page
            $service->disable($student, $now);
            $updated++;
        }

        $this->info(
            "Disabled {$updated} students and left their active classes."
        );

        return ConsoleCommand::SUCCESS;
    }
}
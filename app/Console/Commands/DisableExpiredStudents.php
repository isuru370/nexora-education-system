<?php

namespace App\Console\Commands;

use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as ConsoleCommand;

class DisableExpiredStudents extends Command
{
    protected $signature = 'disable:expired-students';

    protected $description = 'Disable students with no attendance for the last 2 months';

    public function handle()
    {
        $twoMonthsAgo = Carbon::now()
            ->subMonths(2)
            ->startOfDay();

        /*
        |--------------------------------------------------------------------------
        | Find students eligible for deactivation
        |--------------------------------------------------------------------------
        */

        $students = Student::query()
            ->where('student_disable', false)
            ->where('is_active', true)

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

        foreach ($students as $student) {

            // Disable student
            $student->update([
                'student_disable' => true,
                'is_active' => false,
            ]);

            // Leave all currently active classes
            $student->enrollments()
                ->where('is_active', true)
                ->whereNull('left_at')
                ->update([
                    'is_active' => false,
                    'left_at' => now()->toDateString(),
                ]);

            $updated++;
        }

        $this->info(
            "Disabled {$updated} students and left their active classes."
        );

        return ConsoleCommand::SUCCESS;
    }
}
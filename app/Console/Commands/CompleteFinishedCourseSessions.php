<?php

namespace App\Console\Commands;

use App\Services\Course\CourseSessionService;
use App\Support\Features;
use Illuminate\Console\Command;

/**
 * Course class days after their end time: ongoing -> completed,
 * scheduled (nobody marked) -> cancelled. Runs every minute.
 */
class CompleteFinishedCourseSessions extends Command
{
    protected $signature = 'course-sessions:complete-finished';

    protected $description = 'Complete finished course class days and cancel the ones nobody attended';

    public function handle(CourseSessionService $sessions): int
    {
        if (!Features::enabled('courses')) {
            return self::SUCCESS;
        }

        $result = $sessions->closeFinished();

        $this->info("{$result['completed']} course class day(s) completed, {$result['cancelled']} cancelled (no attendance).");

        return self::SUCCESS;
    }
}

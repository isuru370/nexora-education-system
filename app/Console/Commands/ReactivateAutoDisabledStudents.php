<?php

namespace App\Console\Commands;

use App\Models\StudentAutoDisable;
use App\Services\Web\Student\StudentAutoDisableService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as ConsoleCommand;

/**
 * Re-activate every student the nightly disable:expired-students job
 * disabled on one day (default: its last run), and put them back in the
 * same classes - the "Re-activate" button of the Auto-disabled Students
 * page, for all of them at once.
 *
 *   php artisan nexora:reactivate-auto-disabled --dry-run
 *   php artisan nexora:reactivate-auto-disabled --date=2026-11-05
 */
class ReactivateAutoDisabledStudents extends Command
{
    protected $signature = 'nexora:reactivate-auto-disabled
        {--date= : the day the job disabled them (Y-m-d), default the last run}
        {--dry-run : only count, change nothing}';

    protected $description = 'Re-activate the students the expired-students job disabled on one day';

    public function handle(StudentAutoDisableService $service): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->toDateString()
            : optional(StudentAutoDisable::query()->max('disabled_at'), fn ($d) => Carbon::parse($d)->toDateString());

        if (!$date) {
            $this->info('Nobody was disabled by the job.');

            return ConsoleCommand::SUCCESS;
        }

        $ids = StudentAutoDisable::query()
            ->whereDate('disabled_at', $date)
            ->whereNull('reactivated_at')
            ->orderBy('id')
            ->pluck('id');

        $this->info("Disabled on {$date} and still inactive: {$ids->count()}");

        if ($this->option('dry-run') || $ids->isEmpty()) {
            if ($this->option('dry-run')) {
                $this->line('DRY RUN - nothing was changed');
            }

            return ConsoleCommand::SUCCESS;
        }

        $students = 0;
        $classes = 0;
        $failed = [];

        foreach ($ids as $id) {
            try {
                $result = $service->reactivate((int) $id, false);
                $students++;
                $classes += $result['classes'];
            } catch (DomainException $e) {
                $failed[] = "#{$id}: " . $e->getMessage();
            }
        }

        $this->info("Re-activated {$students} students, back in {$classes} classes.");

        foreach (array_slice($failed, 0, 20) as $line) {
            $this->warn($line);
        }

        return ConsoleCommand::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\GradeClosure;
use App\Services\Web\GradeClosure\CloseGradeService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

/**
 * End of a batch (e.g. after the A/L exam). Same as the web page
 * "Close Batch" (/close-grade), see CloseGradeService.
 *
 *   php artisan nexora:close-grade "2025 A/L" "2026 A/L" --dry-run
 *   php artisan nexora:close-grade "2025 A/L" "2026 A/L"
 *   php artisan nexora:close-grade --undo            (the last close)
 *   php artisan nexora:close-grade --undo=3          (close #3)
 */
class CloseGradeCommand extends Command
{
    protected $signature = 'nexora:close-grade
        {grades?* : Grade names, e.g. "2025 A/L" "2026 A/L"}
        {--dry-run : Only show what would change}
        {--undo= : Put back a close ("last" or its number in the history)}';

    protected $description = 'Close all classes of the given grades and deactivate their students';

    public function handle(CloseGradeService $service): int
    {
        $imported = $service->importOldFiles();
        if ($imported) {
            $this->line("Added $imported earlier run(s) from storage/app/close-grade to the history.");
        }

        try {
            if ($this->input->hasParameterOption('--undo')) {
                return $this->undo($service, $this->option('undo') ?: 'last');
            }

            $names = (array) $this->argument('grades');
            if (! array_filter($names)) {
                $this->error('Give the grade names, e.g. php artisan nexora:close-grade "2025 A/L" "2026 A/L" --dry-run');

                return self::FAILURE;
            }

            $gradeIds = $service->gradeIdsFromNames($names);
            $dry = (bool) $this->option('dry-run');
            $p = $service->preview($gradeIds);
        } catch (ValidationException $e) {
            $this->error(collect($e->errors())->flatten()->first());

            return self::FAILURE;
        }

        $this->info(($dry ? '[DRY RUN] ' : '') . 'Closing grades: ' . implode(', ', $p['grades']));
        $this->table(['What', 'Count'], [
            ['Classes made inactive', $p['classes']->count()],
            ['Enrollments ended (left today)', $p['enrollments']->count()],
            ['Schedule patterns made inactive', $p['patterns']->count()],
            ['Future sessions cancelled', $p['schedules']->count()],
            ['Students deactivated', $p['close']->count()],
            ['Students kept active (in another class)', $p['keep']->count()],
        ]);

        if ($p['classes']->isNotEmpty()) {
            $this->line('Classes: ' . $p['classes']->pluck('class_name')->implode(', '));
        }

        if ($p['keep']->isNotEmpty()) {
            $this->warn('Kept active, still in an active class of another grade:');
            $this->table(['Student ID', 'Name', 'Other classes'], $p['keep']->map(fn ($s) => [
                $s['custom_id'], $s['name'], $s['other_classes'],
            ])->all());
        }

        if ($dry) {
            $this->line('Nothing changed. Run again without --dry-run to apply.');

            return self::SUCCESS;
        }

        $closure = $service->close($gradeIds, null, 'command');

        $this->info("Done. Saved as close #{$closure->id} (web: Close Batch page).");
        $this->line("To put it back: php artisan nexora:close-grade --undo={$closure->id}");

        return self::SUCCESS;
    }

    private function undo(CloseGradeService $service, string $which): int
    {
        $closure = $which === 'last'
            ? GradeClosure::whereNull('undone_at')->latest('id')->first()
            : GradeClosure::find((int) $which);

        if (! $closure) {
            $this->error($which === 'last' ? 'No close to undo.' : "Close #$which not found.");

            return self::FAILURE;
        }

        $closure = $service->undo($closure);

        $this->info("Put back close #{$closure->id}: " . implode(', ', $closure->grade_names));
        $this->line(sprintf(
            '%d classes, %d enrollments, %d patterns, %d sessions, %d students.',
            $closure->total('classes'), $closure->total('enrollments'), $closure->total('patterns'),
            $closure->total('sessions'), $closure->total('students')
        ));

        return self::SUCCESS;
    }
}

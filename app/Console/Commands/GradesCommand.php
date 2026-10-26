<?php

namespace App\Console\Commands;

use App\Models\Grade;
use Illuminate\Console\Command;

/**
 * List grades, or add the ones an institute needs (e.g. A/L batches):
 *
 *   php artisan nexora:grades
 *   php artisan nexora:grades add "2025 A/L" "2026 A/L" "2027 A/L" "2028 A/L" ALL
 *
 * Existing grades are left as they are (a deleted one is restored).
 */
class GradesCommand extends Command
{
    protected $signature = 'nexora:grades
        {action? : add (leave out to list the grades)}
        {names?* : grade names, e.g. "2027 A/L"}';

    protected $description = 'List grades or add missing ones';

    public function handle(): int
    {
        $action = $this->argument('action');

        if ($action === null || $action === 'list') {
            $this->table(['ID', 'Grade', 'Active'], Grade::withTrashed()->orderBy('id')->get()
                ->map(fn ($g) => [$g->id, $g->grade_name, $g->trashed() ? 'deleted' : ($g->is_active ? 'yes' : 'no')])
                ->all());

            return self::SUCCESS;
        }

        if ($action !== 'add') {
            $this->error('Use: php artisan nexora:grades add "2027 A/L" ...');

            return self::FAILURE;
        }

        $names = collect($this->argument('names'))
            ->map(fn ($n) => preg_replace('/\s+/', ' ', trim((string) $n)))
            ->filter()
            ->unique(fn ($n) => mb_strtolower($n))
            ->values();

        if ($names->isEmpty()) {
            $this->error('Give at least one grade name.');

            return self::FAILURE;
        }

        foreach ($names as $name) {
            $grade = Grade::withTrashed()->whereRaw('LOWER(grade_name) = ?', [mb_strtolower($name)])->first();

            if (!$grade) {
                Grade::create(['grade_name' => $name, 'is_active' => true]);
                $this->info("Added: {$name}");
            } elseif ($grade->trashed()) {
                $grade->restore();
                $this->info("Restored: {$grade->grade_name}");
            } else {
                $this->line("Already there: {$grade->grade_name}");
            }
        }

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * End of a batch (e.g. after the A/L exam): close every class of the given
 * grades, take the students out of those classes and deactivate them.
 *
 *   php artisan nexora:close-grade "2025 A/L" "2026 A/L" --dry-run
 *   php artisan nexora:close-grade "2025 A/L" "2026 A/L"
 *   php artisan nexora:close-grade --undo            (the last run)
 *
 * A student of these grades who is still in an active class of another
 * grade (e.g. an "ALL" class) is left active, with that class.
 *
 * Nothing is deleted (payments, attendance stay). What was changed is saved in
 * storage/app/close-grade/<time>.json, so --undo puts it back.
 */
class CloseGradeCommand extends Command
{
    protected $signature = 'nexora:close-grade
        {grades?* : Grade names, e.g. "2025 A/L" "2026 A/L"}
        {--dry-run : Only show what would change}
        {--undo= : Put back a run (file name in storage/app/close-grade, or "last")}';

    protected $description = 'Close all classes of the given grades and deactivate their students';

    private const DIR = 'close-grade';
    private const REASON = 'Grade closed';

    public function handle(): int
    {
        if ($this->input->hasParameterOption('--undo')) {
            return $this->undo($this->option('undo') ?: 'last');
        }

        $names = array_values(array_unique(array_filter(array_map('trim', (array) $this->argument('grades')))));
        if (! $names) {
            $this->error('Give the grade names, e.g. php artisan nexora:close-grade "2025 A/L" "2026 A/L" --dry-run');

            return self::FAILURE;
        }

        $grades = DB::table('grades')->whereIn('grade_name', $names)->pluck('id', 'grade_name');
        $missing = array_diff($names, $grades->keys()->all());
        if ($missing) {
            $this->error('Grade not found: ' . implode(', ', $missing));
            $this->line('Grades: ' . DB::table('grades')->orderBy('grade_name')->pluck('grade_name')->implode(', '));

            return self::FAILURE;
        }

        $gradeIds = $grades->values()->all();
        $today = now()->toDateString();
        $dry = (bool) $this->option('dry-run');

        // ---- what will change -------------------------------------------
        $classIds = DB::table('student_classes')->whereNull('deleted_at')
            ->whereIn('grade_id', $gradeIds)->pluck('id')->all();

        $classes = DB::table('student_classes')->whereIn('id', $classIds)
            ->where('is_active', true)->get(['id', 'class_name', 'is_active']);

        $enrollments = DB::table('student_class_enrollments')->whereNull('deleted_at')
            ->whereIn('student_class_id', $classIds)->where('is_active', true)
            ->get(['id', 'is_active', 'left_at']);

        $patterns = DB::table('class_schedule_patterns')->whereNull('deleted_at')
            ->whereIn('student_class_id', $classIds)->where('is_active', true)
            ->get(['id', 'is_active']);

        $schedules = DB::table('class_schedules')->whereNull('deleted_at')
            ->whereIn('student_class_id', $classIds)
            ->where('status', 'scheduled')->where('class_date', '>=', $today)
            ->get(['id', 'status', 'cancel_reason', 'cancelled_at']);

        $students = DB::table('students')->whereNull('deleted_at')
            ->whereIn('grade_id', $gradeIds)
            ->where(fn ($q) => $q->where('is_active', true)->orWhere('student_disable', false)->orWhereNull('student_disable'))
            ->get(['id', 'custom_id', 'initial_name', 'is_active', 'student_disable']);

        // still in an active class of another grade -> stays active
        $otherClasses = DB::table('student_class_enrollments as e')
            ->join('student_classes as c', 'c.id', '=', 'e.student_class_id')
            ->whereNull('e.deleted_at')->whereNull('c.deleted_at')
            ->where('e.is_active', true)->where('c.is_active', true)
            ->whereNotIn('c.id', $classIds)
            ->whereIn('e.student_id', $students->pluck('id'))
            ->get(['e.student_id', 'c.class_name'])
            ->groupBy('student_id');

        $keep = $students->filter(fn ($s) => $otherClasses->has($s->id));
        $close = $students->reject(fn ($s) => $otherClasses->has($s->id));

        // ---- report ------------------------------------------------------
        $this->info(($dry ? '[DRY RUN] ' : '') . 'Closing grades: ' . implode(', ', $names));
        $this->table(['What', 'Count'], [
            ['Classes made inactive', $classes->count()],
            ['Enrollments ended (left today)', $enrollments->count()],
            ['Schedule patterns made inactive', $patterns->count()],
            ['Future sessions cancelled', $schedules->count()],
            ['Students deactivated', $close->count()],
            ['Students kept active (in another class)', $keep->count()],
        ]);

        if ($classes->isNotEmpty()) {
            $this->line('Classes: ' . $classes->pluck('class_name')->implode(', '));
        }

        if ($keep->isNotEmpty()) {
            $this->warn('Kept active, still in an active class of another grade:');
            $this->table(['Student ID', 'Name', 'Other classes'], $keep->map(fn ($s) => [
                $s->custom_id,
                $s->initial_name,
                $otherClasses[$s->id]->pluck('class_name')->unique()->implode(', '),
            ])->values()->all());
        }

        if ($dry) {
            $this->line('Nothing changed. Run again without --dry-run to apply.');

            return self::SUCCESS;
        }

        // ---- apply -------------------------------------------------------
        $now = now();

        DB::transaction(function () use ($classes, $enrollments, $patterns, $schedules, $close, $today, $now) {
            DB::table('student_classes')->whereIn('id', $classes->pluck('id'))
                ->update(['is_active' => false, 'updated_at' => $now]);

            foreach ($enrollments->chunk(500) as $chunk) {
                DB::table('student_class_enrollments')->whereIn('id', $chunk->pluck('id'))
                    ->update(['is_active' => false, 'updated_at' => $now]);
                DB::table('student_class_enrollments')->whereIn('id', $chunk->pluck('id'))
                    ->whereNull('left_at')->update(['left_at' => $today]);
            }

            DB::table('class_schedule_patterns')->whereIn('id', $patterns->pluck('id'))
                ->update(['is_active' => false, 'updated_at' => $now]);

            foreach ($schedules->chunk(500) as $chunk) {
                DB::table('class_schedules')->whereIn('id', $chunk->pluck('id'))->update([
                    'status' => 'cancelled', 'cancel_reason' => self::REASON,
                    'cancelled_at' => $now, 'updated_at' => $now,
                ]);
            }

            foreach ($close->chunk(500) as $chunk) {
                DB::table('students')->whereIn('id', $chunk->pluck('id'))
                    ->update(['is_active' => false, 'student_disable' => true, 'updated_at' => $now]);
            }
        });

        $file = self::DIR . '/' . $now->format('Ymd_His') . '.json';
        Storage::put($file, json_encode([
            'grades' => $names,
            'at' => $now->toDateTimeString(),
            'classes' => $classes->map(fn ($r) => (array) $r)->values(),
            'enrollments' => $enrollments->map(fn ($r) => (array) $r)->values(),
            'patterns' => $patterns->map(fn ($r) => (array) $r)->values(),
            'schedules' => $schedules->map(fn ($r) => (array) $r)->values(),
            'students' => $close->map(fn ($r) => ['id' => $r->id, 'is_active' => $r->is_active, 'student_disable' => $r->student_disable])->values(),
        ], JSON_PRETTY_PRINT));

        $this->info('Done. Saved for undo: storage/app/' . $file);
        $this->line('To put it back: php artisan nexora:close-grade --undo=' . basename($file));

        return self::SUCCESS;
    }

    private function undo(string $name): int
    {
        if ($name === 'last') {
            $files = collect(Storage::files(self::DIR))->filter(fn ($f) => str_ends_with($f, '.json'))->sort()->values();
            if ($files->isEmpty()) {
                $this->error('No close-grade run to undo.');

                return self::FAILURE;
            }
            $file = $files->last();
        } else {
            $file = self::DIR . '/' . basename($name);
        }

        if (! Storage::exists($file)) {
            $this->error("Not found: storage/app/$file");

            return self::FAILURE;
        }

        $run = json_decode(Storage::get($file), true);
        $now = now();

        DB::transaction(function () use ($run, $now) {
            foreach ($run['classes'] as $r) {
                DB::table('student_classes')->where('id', $r['id'])->update(['is_active' => (bool) $r['is_active'], 'updated_at' => $now]);
            }
            foreach ($run['enrollments'] as $r) {
                DB::table('student_class_enrollments')->where('id', $r['id'])
                    ->update(['is_active' => (bool) $r['is_active'], 'left_at' => $r['left_at'], 'updated_at' => $now]);
            }
            foreach ($run['patterns'] as $r) {
                DB::table('class_schedule_patterns')->where('id', $r['id'])->update(['is_active' => (bool) $r['is_active'], 'updated_at' => $now]);
            }
            foreach ($run['schedules'] as $r) {
                DB::table('class_schedules')->where('id', $r['id'])->where('cancel_reason', self::REASON)->update([
                    'status' => $r['status'], 'cancel_reason' => $r['cancel_reason'],
                    'cancelled_at' => $r['cancelled_at'], 'updated_at' => $now,
                ]);
            }
            foreach ($run['students'] as $r) {
                DB::table('students')->where('id', $r['id'])->update([
                    'is_active' => (bool) $r['is_active'], 'student_disable' => (bool) $r['student_disable'], 'updated_at' => $now,
                ]);
            }
        });

        Storage::move($file, $file . '.undone');

        $this->info('Put back: ' . implode(', ', $run['grades']) . ' (run of ' . $run['at'] . ')');
        $this->line(sprintf(
            '%d classes, %d enrollments, %d patterns, %d sessions, %d students.',
            count($run['classes']), count($run['enrollments']), count($run['patterns']),
            count($run['schedules']), count($run['students'])
        ));

        return self::SUCCESS;
    }
}

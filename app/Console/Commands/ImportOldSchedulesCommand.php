<?php

namespace App\Console\Commands;

use App\Models\ClassCategoryFeeOption;
use App\Models\ClassHall;
use App\Models\ClassSchedule;
use App\Models\ClassSchedulePattern;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\StudentClassEnrollment;
use App\Services\Import\OldValue;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Bring the old system's class sessions (timetable) and student attendance
 * into class_schedule_patterns, class_schedules and student_attendances.
 *
 *   php artisan nexora:import-schedules sessions.csv --attendance=attendance.csv --dry-run
 *   php artisan nexora:import-schedules sessions.csv --attendance=attendance.csv
 *
 * Run after nexora:import-classes (with --enrollments).
 *
 * sessions.csv: old_id, old_fee_id, class_date, start_time, end_time (HH:MM),
 * hall_code, held (1 = held - had attendance, or left "ongoing" in the old
 * system - comes in as completed).
 *  - Weekly patterns: one per class + category + day + time + hall, from
 *    the first to the last session date.
 *  - Sessions up to --until (default today): held -> "completed", not held
 *    -> "cancelled" (Not held in the old system). The same class + category
 *    + date + time twice is one schedule.
 *  - After --until: made from the patterns (active classes only), as the
 *    system does for a new pattern.
 *
 * attendance.csv: old_id, custom_id, old_enrollment_id, old_fee_id,
 * old_session_id, attended_at. Each to the session's schedule and the
 * student's enrollment; one per student and schedule. No SMS.
 *
 * Imported rows are marked in their note, so it is safe to run again.
 */
class ImportOldSchedulesCommand extends Command
{
    protected $signature = 'nexora:import-schedules
        {file : sessions CSV - old_id, old_fee_id, class_date, start_time, end_time, hall_code, held}
        {--attendance= : attendance CSV - old_id, custom_id, old_enrollment_id, old_fee_id, old_session_id, attended_at}
        {--until= : last date imported from the old sessions (default today); later dates come from the patterns}
        {--dry-run : only report, change nothing}';

    protected $description = 'Import the old system class sessions (schedules) and student attendance';

    public const SESSION_COLUMNS = ['old_id', 'old_fee_id', 'class_date', 'start_time', 'end_time'];
    public const ATTENDANCE_COLUMNS = ['old_id', 'custom_id', 'old_session_id', 'attended_at'];
    public const NOTE = 'Imported from old system';
    public const ATTENDANCE_NOTE = 'Imported from old system #';
    public const NOT_HELD = 'Not held (old system)';

    private bool $dry;
    private array $problems = [];
    private int $fake = -1;

    public function handle(): int
    {
        $this->dry = (bool) $this->option('dry-run');
        $until = $this->option('until') ? Carbon::parse((string) $this->option('until'))->toDateString() : now()->toDateString();

        try {
            $rows = OldValue::csv((string) $this->argument('file'), self::SESSION_COLUMNS);
            $attendance = $this->option('attendance')
                ? OldValue::csv((string) $this->option('attendance'), self::ATTENDANCE_COLUMNS)
                : [];
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $fees = $this->importedFees();
        if (!$fees) {
            $this->error('No imported classes found. Run nexora:import-classes first.');

            return self::FAILURE;
        }

        $classes = StudentClass::query()->get(['id', 'branch_id', 'is_active'])->keyBy('id');

        // ---- Sessions: check, then group into weekly patterns ----
        $sessions = [];
        $skipped = 0;
        foreach ($rows as $row) {
            $fee = $fees[(int) $row['old_fee_id']] ?? null;
            $date = $this->day($row['class_date']);
            $start = $this->time($row['start_time']);
            $end = $this->time($row['end_time']);

            if (!$fee || !$date || !$start || !$end) {
                $skipped++;
                $this->problems[] = "session #{$row['old_id']}: NOT imported - " . (!$fee ? 'class fee #' . $row['old_fee_id'] . ' not imported' : 'no date / time');
                continue;
            }

            $class = $classes[$fee['class']];
            $sessions[] = [
                'old_id' => (int) $row['old_id'],
                'branch' => (int) $class->branch_id,
                'class' => $fee['class'],
                'fee' => $fee['fee'],
                'date' => $date,
                'day' => strtolower(Carbon::parse($date)->format('l')),
                'start' => $start,
                'end' => $end,
                'hall' => OldValue::text($row['hall_code'] ?? null),
                'held' => OldValue::bool($row['held'] ?? null),
            ];
        }

        $halls = ClassHall::query()->get(['id', 'branch_id', 'code'])
            ->mapWithKeys(fn ($h) => [$h->branch_id . '|' . strtoupper($h->code) => (int) $h->id])->all();

        $count = ['sessions' => count($rows), 'skipped' => $skipped, 'patterns_new' => 0, 'patterns_exist' => 0,
            'completed' => 0, 'cancelled' => 0, 'merged' => 0, 'exists' => 0, 'future' => 0, 'failed' => 0];

        // existing schedules: class|fee|date|start|end => [id, status, imported]
        $schedules = ClassSchedule::query()->get(['id', 'student_class_id', 'class_category_fee_id', 'class_date', 'start_time', 'end_time', 'status', 'note'])
            ->mapWithKeys(fn ($s) => [$this->key($s->student_class_id, $s->class_category_fee_id, $s->class_date->toDateString(), substr($s->start_time, 0, 5), substr($s->end_time, 0, 5))
                => ['id' => (int) $s->id, 'status' => $s->status, 'imported' => str_starts_with((string) $s->note, self::NOTE)]])
            ->all();

        $existingPatterns = ClassSchedulePattern::query()->where('note', self::NOTE)->get()
            ->mapWithKeys(fn ($p) => [$this->patternKey($p->student_class_id, $p->class_category_fee_id, $p->class_day, substr($p->start_time, 0, 5), substr($p->end_time, 0, 5), $p->class_hall_id) => $p])
            ->all();

        $sessionSchedule = []; // old session id => schedule id

        $groups = collect($sessions)->groupBy(fn ($s) => $this->patternKey($s['class'], $s['fee'], $s['day'], $s['start'], $s['end'], $s['hall'] ? ($halls[$s['branch'] . '|' . strtoupper($s['hall'])] ?? null) : null));

        foreach ($groups as $pKey => $group) {
            $first = $group->first();
            $hallId = $first['hall'] ? ($halls[$first['branch'] . '|' . strtoupper($first['hall'])] ?? null) : null;
            if ($first['hall'] && !$hallId) {
                $this->problems[] = "hall {$first['hall']} not found - patterns left without a hall (add the halls first)";
            }
            $from = $group->min('date');
            $to = $group->max('date');
            $classActive = (bool) $classes[$first['class']]->is_active;

            try {
                DB::beginTransaction();

                // the pattern
                $pattern = $existingPatterns[$pKey] ?? null;
                if ($pattern) {
                    $count['patterns_exist']++;
                    $patternId = $pattern->id;
                } else {
                    $patternId = $this->dry ? $this->fake-- : ClassSchedulePattern::create([
                        'branch_id' => $first['branch'],
                        'student_class_id' => $first['class'],
                        'class_category_fee_id' => $first['fee'],
                        'class_hall_id' => $hallId,
                        'start_date' => $from,
                        'end_date' => $to,
                        'start_time' => $first['start'] . ':00',
                        'end_time' => $first['end'] . ':00',
                        'class_day' => $first['day'],
                        'is_active' => $classActive && $to >= now()->toDateString(),
                        'note' => self::NOTE,
                    ])->id;
                    $count['patterns_new']++;
                }

                // past sessions from the old system
                foreach ($group->where('date', '<=', $until) as $s) {
                    $key = $this->key($s['class'], $s['fee'], $s['date'], $s['start'], $s['end']);
                    $existing = $schedules[$key] ?? null;

                    if ($existing) {
                        // a double session in the old system, or a run again
                        if ($s['held'] && $existing['status'] !== 'completed' && $existing['imported']) {
                            if (!$this->dry) {
                                ClassSchedule::whereKey($existing['id'])->update(['status' => 'completed', 'cancel_reason' => null, 'cancelled_at' => null]);
                            }
                            $schedules[$key]['status'] = 'completed';
                        }
                        $sessionSchedule[$s['old_id']] = $existing['id'];
                        $existing['imported'] ? $count['merged']++ : $count['exists']++;
                        continue;
                    }

                    $status = $s['held'] ? 'completed' : 'cancelled';
                    $id = $this->dry ? $this->fake-- : $this->createSchedule($s, $patternId, $hallId, $status);

                    $schedules[$key] = ['id' => $id, 'status' => $status, 'imported' => true];
                    $sessionSchedule[$s['old_id']] = $id;
                    $count[$status]++;
                }

                // future: from the pattern (active classes only)
                if ($classActive && $to > $until) {
                    $date = Carbon::parse(max($from, Carbon::parse($until)->addDay()->toDateString()));
                    while ($date->toDateString() <= $to) {
                        if (strtolower($date->format('l')) === $first['day']) {
                            $key = $this->key($first['class'], $first['fee'], $date->toDateString(), $first['start'], $first['end']);
                            if (!isset($schedules[$key])) {
                                $id = $this->dry ? $this->fake-- : $this->createSchedule(
                                    ['date' => $date->toDateString(), 'day' => $first['day'], 'start' => $first['start'], 'end' => $first['end'],
                                        'branch' => $first['branch'], 'class' => $first['class'], 'fee' => $first['fee'], 'old_id' => null],
                                    $patternId, $hallId, 'scheduled'
                                );
                                $schedules[$key] = ['id' => $id, 'status' => 'scheduled', 'imported' => true];
                                $count['future']++;
                            }
                        }
                        $date->addDay();
                    }
                }

                DB::commit();
            } catch (Throwable $e) {
                DB::rollBack();
                $count['failed']++;
                $this->problems[] = "pattern {$pKey}: NOT imported - " . strtok($e->getMessage(), "\n");
            }
        }

        $att = $attendance ? $this->attendance($attendance, $sessionSchedule, $fees) : null;

        // ---- Report ----
        $this->newLine();
        $this->line($this->dry ? '<fg=yellow>DRY RUN - nothing was changed</>' : '<fg=green>Done</>');
        $this->line("Old sessions up to {$until}; later dates from the patterns");
        $table = [
            ['Sessions in the file', $count['sessions']],
            ['Weekly patterns', $count['patterns_new'] . ' new' . ($count['patterns_exist'] ? ", {$count['patterns_exist']} already there" : '')],
            ['Held -> completed', $count['completed']],
            ['Not held -> cancelled', $count['cancelled']],
            ['Same class + date + time twice (one schedule)', $count['merged']],
            ['Already a schedule there', $count['exists']],
            ['Future schedules from the patterns', $count['future']],
            ['Skipped (class not imported / no date)', $count['skipped']],
        ];
        if ($att) {
            $table[] = ['Attendance in the file', $att['rows']];
            $table[] = [$this->dry ? '  would be imported' : '  imported', $att['created']];
            $table[] = ['  already imported', $att['exists']];
            $table[] = ['  skipped: same student + schedule twice', $att['duplicate']];
            $table[] = ['  skipped: student / enrollment / session not found', $att['missing']];
            $table[] = ['  linked to the session\'s category (old system had the other one)', $att['other_category']];
            $table[] = ['  not enrolled in the session\'s class (listed)', $att['other_class']];
            $count['failed'] += $att['failed'];
        }
        $table[] = ['Failed', $count['failed']];
        $this->table(['', ''], $table);

        if ($att && !$this->dry) {
            $this->check($att['file_months']);
        }

        $log = OldValue::writeProblems('import-schedules', $this->problems, fn ($l) => $this->line($l));
        if ($log) {
            $this->warn(count($this->problems) . ' lines - full list: ' . $log);
        }

        return $count['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function createSchedule(array $s, int $patternId, ?int $hallId, string $status): int
    {
        $schedule = new ClassSchedule([
            'branch_id' => $s['branch'],
            'class_schedule_pattern_id' => $patternId,
            'student_class_id' => $s['class'],
            'class_category_fee_id' => $s['fee'],
            'class_hall_id' => $hallId,
            'class_date' => $s['date'],
            'start_time' => $s['start'] . ':00',
            'end_time' => $s['end'] . ':00',
            'day_of_week' => $s['day'],
            'status' => $status,
            'is_active' => true,
            'cancel_reason' => $status === 'cancelled' ? self::NOT_HELD : null,
            'cancelled_at' => $status === 'cancelled' ? Carbon::parse($s['date'] . ' ' . $s['end']) : null,
            'note' => $s['old_id'] ? self::NOTE . ' #' . $s['old_id'] : self::NOTE,
        ]);

        if ($s['old_id']) {
            $at = Carbon::parse($s['date'] . ' ' . $s['start']);
            $schedule->created_at = $at;
            $schedule->updated_at = $at;
        }
        $schedule->save();

        return (int) $schedule->id;
    }

    /** @return array<string, mixed> */
    protected function attendance(array $rows, array $sessionSchedule, array $fees): array
    {
        $count = ['rows' => count($rows), 'created' => 0, 'exists' => 0, 'duplicate' => 0, 'missing' => 0, 'failed' => 0, 'file_months' => []];

        $students = Student::query()->pluck('id', 'custom_id')->mapWithKeys(fn ($id, $c) => [strtoupper($c) => (int) $id])->all();

        $enrollmentByOld = [];
        StudentClassEnrollment::query()->where('note', 'like', ImportOldClassesCommand::ENROLLMENT_NOTE . '%')
            ->get(['id', 'note', 'branch_id', 'student_id', 'student_class_id'])
            ->each(function ($e) use (&$enrollmentByOld) {
                $enrollmentByOld[(int) substr($e->note, strlen(ImportOldClassesCommand::ENROLLMENT_NOTE))] = $e;
            });

        $byStudentOption = [];
        $byStudentClass = [];
        $byStudentClassFee = [];
        StudentClassEnrollment::query()->orderByDesc('is_active')->orderBy('id')
            ->get(['id', 'branch_id', 'student_id', 'student_class_id', 'class_category_fee_id', 'class_category_fee_option_id'])
            ->each(function ($e) use (&$byStudentOption, &$byStudentClass, &$byStudentClassFee) {
                $byStudentOption[$e->student_id . '|' . $e->class_category_fee_option_id] ??= $e;
                $byStudentClass[$e->student_id . '|' . $e->student_class_id] ??= $e;
                $byStudentClassFee[$e->student_id . '|' . $e->student_class_id . '|' . $e->class_category_fee_id] ??= $e;
            });

        // schedule id => [class, category fee]
        $scheduleInfo = ClassSchedule::query()->whereIn('id', array_filter($sessionSchedule, fn ($id) => $id > 0))
            ->get(['id', 'student_class_id', 'class_category_fee_id'])
            ->mapWithKeys(fn ($s) => [$s->id => [(int) $s->student_class_id, (int) $s->class_category_fee_id]])->all();
        $count['other_category'] = 0;
        $count['other_class'] = 0;

        $imported = DB::table('student_attendances')->where('note', 'like', self::ATTENDANCE_NOTE . '%')->pluck('note')
            ->map(fn ($n) => (int) substr($n, strlen(self::ATTENDANCE_NOTE)))->flip()->all();
        $taken = DB::table('student_attendances')->whereNull('deleted_at')->get(['student_id', 'class_schedule_id'])
            ->mapWithKeys(fn ($a) => [$a->student_id . '|' . $a->class_schedule_id => true])->all();

        $insert = [];
        $flush = function () use (&$insert, &$count) {
            if (!$insert) {
                return;
            }
            try {
                $this->dry || DB::table('student_attendances')->insert($insert);
            } catch (Throwable $e) {
                $count['failed'] += count($insert);
                $count['created'] -= count($insert);
                $this->problems[] = 'attendance batch NOT imported - ' . strtok($e->getMessage(), "\n");
            }
            $insert = [];
        };

        foreach ($rows as $row) {
            $oldId = (int) $row['old_id'];
            $id = strtoupper((string) OldValue::text($row['custom_id']));
            $at = OldValue::text($row['attended_at']) ? OldValue::time($row['attended_at']) : null;
            if ($at) {
                $count['file_months'][$at->format('Y-m')] = ($count['file_months'][$at->format('Y-m')] ?? 0) + 1;
            }

            if (isset($imported[$oldId])) {
                $count['exists']++;
                continue;
            }

            $scheduleId = $sessionSchedule[(int) $row['old_session_id']] ?? null;
            $studentId = $students[$id] ?? null;

            // The student's enrollment in the session's class + category; the old system
            // sometimes linked the student's other enrollment.
            $old = $enrollmentByOld[(int) ($row['old_enrollment_id'] ?? 0)] ?? null;
            if (!$old && $studentId && isset($fees[(int) ($row['old_fee_id'] ?? 0)])) {
                $old = $byStudentOption[$studentId . '|' . $fees[(int) $row['old_fee_id']]['option']] ?? null;
            }
            [$sClass, $sFee] = $scheduleInfo[$scheduleId] ?? [null, null];

            $enrollment = null;
            if ($studentId && $sClass) {
                $enrollment = $byStudentClassFee[$studentId . '|' . $sClass . '|' . $sFee]
                    ?? (($old && (int) $old->student_class_id === $sClass) ? $old : null)
                    ?? $byStudentClass[$studentId . '|' . $sClass]
                    ?? null;
            }
            if ($enrollment && $old && $enrollment->id !== $old->id && (int) $old->student_class_id === $sClass) {
                $count['other_category']++;
            }
            if (!$enrollment && $old) {
                // not enrolled in the session's class: keep the old system's link (listed)
                $enrollment = $old;
                $count['other_class']++;
                $this->problems[] = "attendance #{$oldId} {$id}: not enrolled in this session's class - linked to the student's other class enrollment (as in the old system)";
            }

            if (!$scheduleId || !$studentId || !$enrollment || !$at || (int) $enrollment->student_id !== $studentId) {
                $count['missing']++;
                $this->problems[] = "attendance #{$oldId} {$id}: NOT imported - " . (!$scheduleId ? 'session #' . $row['old_session_id'] . ' not imported' : 'student / enrollment not found');
                continue;
            }

            $key = $studentId . '|' . $scheduleId;
            if (isset($taken[$key])) {
                $count['duplicate']++;
                $this->problems[] = "attendance #{$oldId} {$id}: NOT imported - already marked for this class session";
                continue;
            }

            $insert[] = [
                'branch_id' => $enrollment->branch_id,
                'student_id' => $studentId,
                'class_schedule_id' => $scheduleId,
                'student_class_enrollment_id' => $enrollment->id,
                'attended_at' => $at->format('Y-m-d H:i:s'),
                'mark_method' => 'manual_web',
                'marked_by' => null,
                'is_synced' => true,
                'note' => self::ATTENDANCE_NOTE . $oldId,
                'created_at' => $at->format('Y-m-d H:i:s'),
                'updated_at' => $at->format('Y-m-d H:i:s'),
            ];
            $taken[$key] = true;
            $count['created']++;

            if (count($insert) >= 500) {
                $flush();
            }
        }
        $flush();

        return $count;
    }

    /** File vs database: attendance per month. */
    protected function check(array $fileMonths): void
    {
        $db = DB::table('student_attendances')->where('note', 'like', self::ATTENDANCE_NOTE . '%')->whereNull('deleted_at')
            ->pluck('attended_at')->countBy(fn ($d) => substr((string) $d, 0, 7))->all();

        ksort($fileMonths);
        $rows = [];
        foreach ($fileMonths as $m => $n) {
            $in = $db[$m] ?? 0;
            $rows[] = [$m, $n, $in, $n - $in ?: '', $n === $in ? 'OK' : 'listed'];
        }
        $this->newLine();
        $this->line('Check - attendance per month (old system vs new database):');
        $this->table(['Month', 'Old system', 'Imported', 'Not imported', ''], $rows);
    }

    /** old fee id => [class, fee, option] from the classes import */
    protected function importedFees(): array
    {
        $fees = [];
        $options = ClassCategoryFeeOption::withTrashed()->where('note', 'like', 'Imported from old system - class #%')
            ->with(['classCategoryFee' => fn ($q) => $q->withTrashed()])->get();

        foreach ($options as $option) {
            if ($option->classCategoryFee && preg_match('/fee #([\d,]+)/', (string) $option->note, $m)) {
                foreach (explode(',', $m[1]) as $oldFee) {
                    $fees[(int) $oldFee] = [
                        'class' => (int) $option->classCategoryFee->student_class_id,
                        'fee' => (int) $option->class_category_fee_id,
                        'option' => (int) $option->id,
                    ];
                }
            }
        }

        return $fees;
    }

    protected function key($class, $fee, string $date, string $start, string $end): string
    {
        return "{$class}|{$fee}|{$date}|{$start}|{$end}";
    }

    protected function patternKey($class, $fee, string $day, string $start, string $end, $hallId): string
    {
        return "{$class}|{$fee}|{$day}|{$start}|{$end}|" . ($hallId ?: '-');
    }

    /** "2027-01-24" / "2027-01-24 00:00:00" -> "2027-01-24" (future dates too) */
    protected function day(?string $value): ?string
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})/', trim((string) $value), $m) || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return null;
        }

        return "{$m[1]}-{$m[2]}-{$m[3]}";
    }

    /** "3:30 PM", "15:30", "15:30:00" -> "15:30" */
    protected function time(?string $value): ?string
    {
        $value = strtoupper(trim((string) $value));
        foreach (['g:i A', 'h:i A', 'g:iA', 'H:i', 'H:i:s'] as $format) {
            try {
                $t = Carbon::createFromFormat($format, $value);
                if ($t && $t->format($format) === $value) {
                    return $t->format('H:i');
                }
            } catch (Throwable) {
            }
        }

        return null;
    }
}

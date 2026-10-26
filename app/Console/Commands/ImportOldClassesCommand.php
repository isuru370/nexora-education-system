<?php

namespace App\Console\Commands;

use App\Models\ClassCategory;
use App\Models\ClassCategoryFee;
use App\Models\ClassCategoryFeeOption;
use App\Models\ClassPaymentConfig;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\StudentClassEnrollment;
use App\Models\Subject;
use App\Models\Teacher;
use App\Services\Import\OldValue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Bring the old system's classes (with their fees and teacher %) and the
 * students in them into the new tables.
 *
 *   php artisan nexora:import-classes classes.csv --enrollments=enrollments.csv --dry-run
 *   php artisan nexora:import-classes classes.csv --enrollments=enrollments.csv
 *
 * classes.csv has one row per class + category: old_class_id, old_fee_id,
 * class_name, teacher_custom_id, teacher_percentage, subject, grade,
 * is_active, is_ongoing, category, fee, created_at (+ optional option_label).
 * For each class: student_classes, class_payment_configs (teacher %,
 * institute = 100 - teacher %) and a class_category_fees row per category.
 * Fee options: "<Category> Only" (e.g. "Theory Only"); another fee in the
 * same category is "Special"; option_label overrides (the old category
 * "Theory+Revision" comes in as the Theory option "Theory + Revision").
 * Subjects and categories are made when missing; grades must be there
 * (nexora:grades add).
 *
 * enrollments.csv: old_id, custom_id, old_fee_id, is_active, is_free_card,
 * enrolled_at, updated_at. An inactive enrollment gets left_at = updated_at
 * (as when deactivated from the web). A student in the same class +
 * category twice is enrolled once (the active row).
 *
 * Imported rows are marked in their note, so it is safe to run again.
 */
class ImportOldClassesCommand extends Command
{
    protected $signature = 'nexora:import-classes
        {file : classes CSV (one row per class + category)}
        {--enrollments= : enrollments CSV (students in the classes)}
        {--branch= : branch id / code / name (default: the only branch, or MAIN)}
        {--dry-run : only report, change nothing}';

    protected $description = 'Import the old system classes, fees, teacher % and enrollments';

    public const CLASS_COLUMNS = ['old_class_id', 'old_fee_id', 'class_name', 'teacher_custom_id', 'teacher_percentage', 'subject', 'grade', 'category', 'fee'];
    public const ENROLLMENT_COLUMNS = ['old_id', 'custom_id', 'old_fee_id'];

    public const FEE_NOTE = 'Imported from old system - class #%d fee #%s';
    public const ENROLLMENT_NOTE = 'Imported from old system #';
    
    private bool $dry;
    private array $problems = [];

    public function handle(): int
    {
        $this->dry = (bool) $this->option('dry-run');

        try {
            $rows = OldValue::csv((string) $this->argument('file'), self::CLASS_COLUMNS);
            $enrollments = $this->option('enrollments')
                ? OldValue::csv((string) $this->option('enrollments'), self::ENROLLMENT_COLUMNS)
                : [];
            $branch = OldValue::branch($this->option('branch'));
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        // Grades must be there first
        $grades = OldValue::gradeMap();
        $missingGrades = collect($rows)->pluck('grade')->map(fn ($g) => trim((string) $g))
            ->unique(fn ($g) => OldValue::gradeKey($g))
            ->reject(fn ($g) => isset($grades[OldValue::gradeKey($g)]))->values();

        if ($missingGrades->isNotEmpty()) {
            $this->error('These grades are not in the database: ' . $missingGrades->map(fn ($g) => "\"{$g}\"")->implode(', '));
            $this->line('Add them first:  php artisan nexora:grades add ' . $missingGrades->map(fn ($g) => '"' . preg_replace('/\s+grade$/i', '', $g) . '"')->implode(' '));

            return self::FAILURE;
        }

        $teachers = Teacher::query()->get(['id', 'custom_id'])->keyBy(fn ($t) => strtoupper($t->custom_id));
        $subjects = Subject::query()->get()->keyBy(fn ($s) => mb_strtolower(trim($s->subject_name)))->all();
        $categories = ClassCategory::query()->get()->keyBy(fn ($c) => mb_strtolower(trim($c->category_name)))->all();

        // old fee id => [class id, fee id, option id] of what is already imported
        // (the option note: "Imported from old system - class #12 fee #25,26")
        $importedFees = [];
        $options = ClassCategoryFeeOption::withTrashed()
            ->where('note', 'like', 'Imported from old system - class #%')
            ->with(['classCategoryFee' => fn ($q) => $q->withTrashed()])
            ->get();
        foreach ($options as $option) {
            if ($option->classCategoryFee && preg_match('/class #\d+ fee #([\d,]+)/', (string) $option->note, $m)) {
                foreach (explode(',', $m[1]) as $oldFeeId) {
                    $importedFees[(int) $oldFeeId] = [
                        'class' => (int) $option->classCategoryFee->student_class_id,
                        'fee' => (int) $option->class_category_fee_id,
                        'option' => (int) $option->id,
                    ];
                }
            }
        }

        $count = ['classes' => 0, 'classes_new' => 0, 'classes_exist' => 0, 'fees_new' => 0, 'fees_exist' => 0,
            'skipped' => 0, 'subjects_new' => [], 'categories_new' => [], 'failed' => 0];

        // ---- Classes (rows grouped by old class) ----
        $byClass = collect($rows)->groupBy(fn ($r) => (int) $r['old_class_id']);
        $count['classes'] = $byClass->count();
        $fake = -1; // ids for the dry run

        foreach ($byClass as $oldClassId => $classRows) {
            $first = $classRows->first();
            $label = "class #{$oldClassId} " . OldValue::text($first['class_name']);

            $teacher = $teachers[strtoupper((string) OldValue::text($first['teacher_custom_id']))] ?? null;
            $percentage = is_numeric(trim($first['teacher_percentage'])) ? (float) $first['teacher_percentage'] : null;
            $subjectName = OldValue::text($first['subject'], 150);
            $className = OldValue::text($first['class_name'], 150);

            if (!$teacher || $percentage === null || $percentage < 0 || $percentage > 100 || !$subjectName || !$className) {
                $count['skipped']++;
                $this->problems[] = "{$label}: NOT imported - " . (!$teacher ? 'teacher ' . $first['teacher_custom_id'] . ' not found' : 'no name / subject / valid teacher %');
                continue;
            }

            $isActive = OldValue::bool($first['is_active'] ?? null, true);
            $isOngoing = OldValue::bool($first['is_ongoing'] ?? null, false);
            $created = OldValue::time($first['created_at'] ?? null);
            $gradeId = $grades[OldValue::gradeKey($first['grade'])];

            $newSubjects = [];
            $newCategories = [];
            $newFees = [];   // old fee id => ids, kept only when the class commits
            $made = ['class' => 0, 'fees' => 0, 'fees_exist' => 0];

            try {
                DB::beginTransaction();

                // Subject
                $subject = $subjects[mb_strtolower($subjectName)] ?? null;
                if (!$subject) {
                    if ($this->dry) {
                        $subject = new Subject(['subject_name' => $subjectName]);
                        $subject->id = $fake--;
                    } else {
                        $subject = Subject::create(['subject_name' => $subjectName, 'is_active' => true]);
                    }
                    $newSubjects[mb_strtolower($subjectName)] = $subject;
                }

                // Class: already imported = same name, teacher, subject, grade and date
                $class = $subject->id > 0 ? StudentClass::withTrashed()
                    ->where('branch_id', $branch->id)
                    ->where('class_name', $className)
                    ->where('teacher_id', $teacher->id)
                    ->where('subject_id', $subject->id)
                    ->where('grade_id', $gradeId)
                    ->where('created_at', $created->format('Y-m-d H:i:s'))
                    ->first() : null;

                if ($class) {
                    $classId = $class->id;
                } elseif ($this->dry) {
                    $classId = $fake--;
                    $made['class'] = 1;
                } else {
                    $class = new StudentClass([
                        'branch_id' => $branch->id,
                        'class_name' => $className,
                        'class_type' => 'offline',
                        'medium' => 'Sinhala',
                        'teacher_id' => $teacher->id,
                        'subject_id' => $subject->id,
                        'grade_id' => $gradeId,
                        'is_active' => $isActive,
                        'is_ongoing' => $isOngoing,
                    ]);
                    $class->created_at = $created;
                    $class->updated_at = $created;
                    $class->save();
                    $classId = $class->id;
                    $made['class'] = 1;

                    ClassPaymentConfig::create([
                        'branch_id' => $branch->id,
                        'student_class_id' => $classId,
                        'teacher_id' => $teacher->id,
                        'organizer_id' => null,
                        'teacher_percentage' => $percentage,
                        'organizer_percentage' => 0,
                        'institution_percentage' => round(100 - $percentage, 2),
                        'effective_from' => $created->toDateString(),
                        'is_active' => true,
                    ]);
                }

                // Fees: one per category; the same category twice = more options
                $feeByCategory = [];   // category id => fee id
                $optionByAmount = [];  // fee id|label|amount => [option id, old fee ids]
                $labelsByFee = [];     // fee id => option labels used

                // rows without their own option label first: they make the default option
                $classRows = $classRows->sortBy(fn ($r) => [OldValue::text($r['option_label'] ?? null) === null ? 0 : 1, (int) ($r['old_fee_id'] ?? 0)])->values();

                foreach ($classRows as $row) {
                    $oldFeeId = (int) ($row['old_fee_id'] ?? 0);
                    $categoryName = OldValue::text($row['category'] ?? null, 100);

                    if ($oldFeeId <= 0 || $categoryName === null) {
                        continue; // a class without fees
                    }

                    if (isset($importedFees[$oldFeeId])) {
                        $made['fees_exist']++;
                        continue;
                    }

                    $key = mb_strtolower($categoryName);
                    $category = $categories[$key] ?? $newCategories[$key] ?? null;
                    if (!$category) {
                        if ($this->dry) {
                            $category = new ClassCategory(['category_name' => $categoryName]);
                            $category->id = $fake--;
                        } else {
                            $category = ClassCategory::create([
                                'category_name' => $categoryName,
                                'code' => $this->categoryCode($categoryName),
                                'is_schedulable' => true,
                                'is_active' => true,
                            ]);
                        }
                        $newCategories[$key] = $category;
                    }

                    $amount = is_numeric(trim($row['fee'])) ? round((float) $row['fee'], 2) : 0.0;

                    // the class + category fee
                    $feeId = $feeByCategory[$category->id] ?? null;
                    if ($feeId === null && !$this->dry && $category->id > 0) {
                        $feeId = ClassCategoryFee::withTrashed()->where('student_class_id', $classId)
                            ->where('class_category_id', $category->id)->value('id');
                    }
                    $isNewFee = $feeId === null;
                    if ($isNewFee) {
                        $feeId = $this->dry ? $fake-- : ClassCategoryFee::create([
                            'student_class_id' => $classId,
                            'class_category_id' => $category->id,
                            'is_active' => $isActive,
                            'note' => 'Imported from old system',
                        ])->id;
                        $made['fees']++;
                    }
                    $feeByCategory[$category->id] = $feeId;

                    // the fee option (same label + amount = the same option)
                    $givenLabel = OldValue::text($row['option_label'] ?? null, 150);
                    $optionKey = $feeId . '|' . mb_strtolower((string) $givenLabel) . '|' . $amount;
                    if (isset($optionByAmount[$optionKey])) {
                        $optionByAmount[$optionKey]['old'][] = $oldFeeId;
                        $optionId = $optionByAmount[$optionKey]['id'];
                        if (!$this->dry) {
                            ClassCategoryFeeOption::whereKey($optionId)->update([
                                'note' => sprintf(self::FEE_NOTE, $oldClassId, implode(',', $optionByAmount[$optionKey]['old'])),
                            ]);
                        }
                    } else {
                        $isDefault = $isNewFee;
                        // "Theory Only" / "Revision Only"; another fee in the same category = "Special"
                        $optionLabel = $givenLabel ?? ($isDefault ? $category->category_name . ' Only' : 'Special');
                        $used = $labelsByFee[$feeId] ?? [];
                        for ($n = 2, $base = $optionLabel; in_array(mb_strtolower($optionLabel), $used, true); $n++) {
                            $optionLabel = "{$base} {$n}";
                        }
                        $labelsByFee[$feeId][] = mb_strtolower($optionLabel);
                        $optionId = $this->dry ? $fake-- : ClassCategoryFeeOption::create([
                            'class_category_fee_id' => $feeId,
                            'label' => $optionLabel,
                            'fee' => $amount,
                            'is_default' => $isDefault,
                            'is_active' => true,
                            'note' => sprintf(self::FEE_NOTE, $oldClassId, $oldFeeId),
                        ])->id;
                        $optionByAmount[$optionKey] = ['id' => $optionId, 'old' => [$oldFeeId]];
                    }

                    $newFees[$oldFeeId] = ['class' => $classId, 'fee' => $feeId, 'option' => $optionId];
                }

                DB::commit();
            } catch (Throwable $e) {
                DB::rollBack();
                $count['failed']++;
                $this->problems[] = "{$label}: NOT imported - " . strtok($e->getMessage(), "\n");
                continue;
            }

            // keep what this class made
            $importedFees += $newFees;
            $subjects = array_merge($subjects, $newSubjects);
            $categories = array_merge($categories, $newCategories);
            foreach (array_keys($newSubjects) as $name) {
                $count['subjects_new'][] = $newSubjects[$name]->subject_name;
            }
            foreach (array_keys($newCategories) as $name) {
                $count['categories_new'][] = $newCategories[$name]->category_name;
            }
            $made['class'] ? $count['classes_new']++ : $count['classes_exist']++;
            $count['fees_new'] += $made['fees'];
            $count['fees_exist'] += $made['fees_exist'];
        }

        $enrolled = $enrollments ? $this->enrollments($enrollments, $importedFees, $branch->id) : null;

        // ---- Report ----
        $this->newLine();
        $this->line($this->dry ? '<fg=yellow>DRY RUN - nothing was changed</>' : '<fg=green>Done</>');
        $this->line("Branch: {$branch->name}");
        if ($count['subjects_new']) {
            $this->line(($this->dry ? 'Subjects to add: ' : 'Subjects added: ') . implode(', ', $count['subjects_new']));
        }
        if ($count['categories_new']) {
            $this->line(($this->dry ? 'Categories to add: ' : 'Categories added: ') . implode(', ', $count['categories_new']));
        }

        $table = [
            ['Classes in the file', $count['classes']],
            [$this->dry ? '  would be imported' : '  imported', $count['classes_new']],
            ['  already imported', $count['classes_exist']],
            ['  skipped (teacher / data)', $count['skipped']],
            ['Class fees (class + category)', $count['fees_new'] . ($count['fees_exist'] ? " new, {$count['fees_exist']} already there" : '')],
        ];
        if ($enrolled !== null) {
            $table[] = ['Enrollments in the file', $enrolled['rows']];
            $table[] = [$this->dry ? '  would be imported' : '  imported', $enrolled['created'] . " (active {$enrolled['active']}, free card {$enrolled['free']})"];
            $table[] = ['  already imported', $enrolled['exists']];
            $table[] = ['  skipped: same student + class + category twice', $enrolled['duplicate']];
            $table[] = ['  skipped: student / class not found', $enrolled['missing']];
            $count['failed'] += $enrolled['failed'];
        }
        $table[] = ['Failed', $count['failed']];
        $this->table(['', ''], $table);

        $log = OldValue::writeProblems('import-classes', $this->problems, fn ($l) => $this->line($l));
        if ($log) {
            $this->warn(count($this->problems) . ' lines - full list: ' . $log);
        }

        return $count['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /** @return array<string, int> */
    protected function enrollments(array $rows, array $fees, int $branchId): array
    {
        $count = ['rows' => count($rows), 'created' => 0, 'exists' => 0, 'duplicate' => 0, 'missing' => 0, 'failed' => 0, 'active' => 0, 'free' => 0];

        // Active rows first, so a student entered twice keeps the active row
        usort($rows, fn ($a, $b) => OldValue::bool($b['is_active'] ?? null, true) <=> OldValue::bool($a['is_active'] ?? null, true));

        $students = Student::query()->get(['id', 'custom_id'])->keyBy(fn ($s) => strtoupper($s->custom_id));

        $imported = StudentClassEnrollment::withTrashed()->where('note', 'like', self::ENROLLMENT_NOTE . '%')->pluck('note')
            ->map(fn ($n) => (int) substr($n, strlen(self::ENROLLMENT_NOTE)))->flip()->all();

        $taken = StudentClassEnrollment::withTrashed()
            ->get(['student_id', 'student_class_id', 'class_category_fee_id', 'class_category_fee_option_id'])
            ->mapWithKeys(fn ($e) => ["{$e->student_id}|{$e->student_class_id}|{$e->class_category_fee_id}|{$e->class_category_fee_option_id}" => true])
            ->all();

        foreach ($rows as $row) {
            $oldId = (int) $row['old_id'];
            $id = strtoupper((string) OldValue::text($row['custom_id']));

            if (isset($imported[$oldId])) {
                $count['exists']++;
                continue;
            }

            $student = $students[$id] ?? null;
            $fee = $fees[(int) $row['old_fee_id']] ?? null;

            if (!$student || !$fee) {
                $count['missing']++;
                $this->problems[] = "enrollment #{$oldId} {$id}: NOT imported - " . (!$student ? 'student not found' : 'class fee #' . $row['old_fee_id'] . ' not imported');
                continue;
            }

            $key = "{$student->id}|{$fee['class']}|{$fee['fee']}|{$fee['option']}";
            if (isset($taken[$key])) {
                $count['duplicate']++;
                $this->problems[] = "enrollment #{$oldId} {$id}: NOT imported - already in this class + category";
                continue;
            }

            $active = OldValue::bool($row['is_active'] ?? null, true);
            $free = OldValue::bool($row['is_free_card'] ?? null);
            $date = OldValue::text($row['enrolled_at'] ?? null) ? OldValue::time($row['enrolled_at']) : null;

            // like deactivating from the web: an inactive enrollment has a left date
            // (the old row's last update), so reports stop counting it after that
            $leftAt = null;
            if (!$active) {
                $leftAt = OldValue::text($row['updated_at'] ?? null) ? OldValue::time($row['updated_at']) : ($date ?? now());
                if ($date && $leftAt->lt($date)) {
                    $leftAt = $date->copy();
                }
            }

            if (!$this->dry) {
                try {
                    $enrollment = new StudentClassEnrollment([
                        'branch_id' => $branchId,
                        'student_id' => $student->id,
                        'student_class_id' => $fee['class'],
                        'class_category_fee_id' => $fee['fee'],
                        'class_category_fee_option_id' => $fee['option'],
                        'is_active' => $active,
                        'is_free_card' => $free,
                        'enrolled_at' => $date?->toDateString(),
                        'left_at' => $leftAt?->toDateString(),
                        'note' => self::ENROLLMENT_NOTE . $oldId,
                    ]);
                    if ($date) {
                        $enrollment->created_at = $date;
                        $enrollment->updated_at = $date;
                    }
                    $enrollment->save();
                } catch (Throwable $e) {
                    $count['failed']++;
                    $this->problems[] = "enrollment #{$oldId} {$id}: NOT imported - " . strtok($e->getMessage(), "\n");
                    continue;
                }
            }

            $taken[$key] = true;
            $imported[$oldId] = true;
            $count['created']++;
            $active && $count['active']++;
            $free && $count['free']++;
        }

        return $count;
    }

    protected function categoryCode(string $name): string
    {
        $base = Str::slug($name) ?: 'category';
        $code = $base;
        $i = 2;
        while (ClassCategory::withTrashed()->where('code', $code)->exists()) {
            $code = $base . '-' . $i++;
        }

        return $code;
    }
}

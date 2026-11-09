<?php

namespace App\Services\Web\StudentClass;

use App\Models\Branch;
use App\Models\ClassCategory;
use App\Models\ClassCategoryFee;
use App\Models\ClassCategoryFeeOption;
use App\Models\ClassPaymentConfig;
use App\Models\Grade;
use App\Models\Organizer;
use App\Models\StudentClass;
use App\Models\StudentClassEnrollment;
use App\Models\Subject;
use App\Models\Teacher;
use App\Services\Branch\BranchAccessService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Classes -> categories -> fee options from one CSV (one row = one fee
 * option). New rows are created, existing ones updated; nothing is deleted.
 *
 * run($rows, commit: false) is the preview: it does the real work inside a
 * transaction and rolls it back, so the preview always matches the import.
 */
class ClassCsvImportService
{
    public const COLUMNS = [
        'branch', 'class_name', 'teacher_id', 'teacher_name', 'subject', 'grade', 'medium', 'class_type', 'class_active',
        'category', 'option_label', 'fee', 'is_default', 'option_active',
        'teacher_percentage', 'organizer_code', 'organizer_percentage', 'institution_percentage',
    ];

    public const MAX_ROWS = 2000;

    /** Values that must be the same on every row of one class */
    protected const CLASS_FIELDS = [
        'teacher_id', 'subject', 'grade', 'medium', 'class_type', 'class_active',
        'teacher_percentage', 'organizer_code', 'organizer_percentage', 'institution_percentage',
    ];

    public function __construct(
        private BranchAccessService $branchAccess,
        private ClassPaymentConfigService $paymentConfigs,
    ) {}

    /* =====================================================================
     | CSV out
     * ===================================================================== */

    /** Every class the user can see, one row per fee option. */
    public function exportRows(): array
    {
        $classes = StudentClass::query()
            ->whereIn('branch_id', $this->branchIds())
            ->with([
                'branch:id,name',
                'teacher:id,custom_id,full_name',
                'subject:id,subject_name',
                'grade:id,grade_name',
                'paymentConfig.organizer:id,code',
                'categoryFees' => fn ($q) => $q->with(['category:id,category_name', 'feeOptions' => fn ($o) => $o->orderBy('id')])->orderBy('id'),
            ])
            ->orderBy('branch_id')
            ->orderBy('class_name')
            ->get();

        $rows = [];

        foreach ($classes as $class) {
            $config = $class->paymentConfig;
            $base = [
                'branch' => $class->branch->name ?? '',
                'class_name' => $class->class_name,
                'teacher_id' => $class->teacher->custom_id ?? '',
                'teacher_name' => $class->teacher->full_name ?? '',
                'subject' => $class->subject->subject_name ?? '',
                'grade' => $class->grade->grade_name ?? '',
                'medium' => $class->medium,
                'class_type' => $class->class_type,
                'class_active' => $class->is_active ? 'yes' : 'no',
                'teacher_percentage' => $config ? $this->num($config->teacher_percentage) : '',
                'organizer_code' => $config->organizer->code ?? '',
                'organizer_percentage' => $config ? $this->num($config->organizer_percentage) : '',
                'institution_percentage' => $config ? $this->num($config->institution_percentage) : '',
            ];

            $wrote = false;

            foreach ($class->categoryFees as $categoryFee) {
                foreach ($categoryFee->feeOptions as $option) {
                    $rows[] = $base + [
                        'category' => $categoryFee->category->category_name ?? '',
                        'option_label' => $option->label,
                        'fee' => $this->num($option->fee),
                        'is_default' => $option->is_default ? 'yes' : 'no',
                        'option_active' => $option->is_active && $categoryFee->is_active ? 'yes' : 'no',
                    ];
                    $wrote = true;
                }
            }

            if (!$wrote) {
                $rows[] = $base + ['category' => '', 'option_label' => '', 'fee' => '', 'is_default' => '', 'option_active' => ''];
            }
        }

        return array_map(fn ($row) => array_map(fn ($c) => $row[$c] ?? '', self::COLUMNS), $rows);
    }

    /** Header + two example rows (the first branch / teacher the user has). */
    public function templateRows(): array
    {
        $branch = Branch::query()
            ->whereIn('id', $this->branchIds())
            ->orderBy('id')
            ->value('name') ?? 'Main';
        $teacher = Teacher::query()->orderBy('id')->first(['custom_id', 'full_name']);
        $tid = $teacher->custom_id ?? 'T001';
        $tname = $teacher->full_name ?? 'Teacher name';

        return [
            [$branch, 'Maths 10 2027', $tid, $tname, 'Maths', '10', 'Sinhala', 'offline', 'yes', 'Theory', 'Normal', '2000', 'yes', 'yes', '70', '', '0', '30'],
            [$branch, 'Maths 10 2027', $tid, $tname, 'Maths', '10', 'Sinhala', 'offline', 'yes', 'Theory', 'Half card', '1000', 'no', 'yes', '70', '', '0', '30'],
        ];
    }

    /** CSV text (UTF-8 with BOM so Excel shows Sinhala correctly). */
    public function toCsv(array $rows, bool $withHeader = true): string
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");

        if ($withHeader) {
            fputcsv($handle, self::COLUMNS);
        }

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /* =====================================================================
     | CSV in
     * ===================================================================== */

    /**
     * @return array{rows: array<int, array>, error: ?string}  rows keyed by CSV line number
     */
    public function parse(string $text): array
    {
        $text = preg_replace('/^\xEF\xBB\xBF/', '', $text);
        $lines = preg_split('/\r\n|\n|\r/', trim($text));

        if (!$lines || trim($lines[0]) === '') {
            return ['rows' => [], 'error' => 'The file is empty.'];
        }

        $delimiter = substr_count($lines[0], ';') > substr_count($lines[0], ',') ? ';' : ',';
        $header = array_map(fn ($h) => Str::snake(trim(strtolower(str_replace(['%', ' '], ['percentage', '_'], trim($h))))), str_getcsv($lines[0], $delimiter));
        $header = array_map(fn ($h) => str_replace('__', '_', $h), $header);

        $missing = array_diff(['branch', 'class_name', 'teacher_id'], $header);
        if ($missing) {
            return ['rows' => [], 'error' => 'These columns are missing: ' . implode(', ', $missing) . '. Download the template and use its first row.'];
        }

        $rows = [];

        foreach (array_slice($lines, 1, null, true) as $index => $line) {
            if (trim(str_replace([',', ';', '"'], '', $line)) === '') {
                continue;
            }

            $values = str_getcsv($line, $delimiter);
            $row = [];

            foreach ($header as $i => $column) {
                if (in_array($column, self::COLUMNS, true)) {
                    $row[$column] = trim((string) ($values[$i] ?? ''));
                }
            }

            $rows[$index + 1] = $row + array_fill_keys(self::COLUMNS, '');
        }

        if ($rows === []) {
            return ['rows' => [], 'error' => 'The file has no rows under the header.'];
        }

        if (count($rows) > self::MAX_ROWS) {
            return ['rows' => [], 'error' => 'Too many rows (' . count($rows) . '). Upload at most ' . self::MAX_ROWS . ' rows at a time.'];
        }

        return ['rows' => $rows, 'error' => null];
    }

    /**
     * Check and (when $commit and there are no errors) save every row.
     *
     * @return array{rows: array, summary: array, errors: int, saved: bool}
     */
    public function run(array $rows, bool $commit): array
    {
        $result = [];
        $summary = ['new_classes' => 0, 'updated_classes' => 0, 'new_categories' => 0, 'new_options' => 0, 'updated_options' => 0, 'unchanged' => 0, 'errors' => 0];

        // 1. Check every row on its own, and that one class has one set of values
        $checked = [];
        $firstRowOfClass = [];

        foreach ($rows as $line => $row) {
            [$data, $errors] = $this->check($row);

            if ($data) {
                $key = $data['branch_id'] . '|' . mb_strtolower($data['class_name']);

                if (isset($firstRowOfClass[$key])) {
                    foreach (self::CLASS_FIELDS as $field) {
                        if (mb_strtolower((string) $row[$field]) !== mb_strtolower((string) $rows[$firstRowOfClass[$key]][$field])) {
                            $errors[] = "\"{$field}\" is different from row {$firstRowOfClass[$key]} of the same class.";
                        }
                    }
                } else {
                    $firstRowOfClass[$key] = $line;
                }
            }

            $checked[$line] = ['data' => $data, 'errors' => $errors];
        }

        // The same option twice in one class
        $seen = [];
        foreach ($checked as $line => $c) {
            if (!$c['data'] || $c['data']['category'] === null) {
                continue;
            }
            $k = $c['data']['branch_id'] . '|' . mb_strtolower($c['data']['class_name']) . '|' . mb_strtolower($c['data']['category']) . '|' . mb_strtolower((string) $c['data']['option_label']);
            if (isset($seen[$k])) {
                $checked[$line]['errors'][] = "The same class, category and option is also on row {$seen[$k]}.";
            }
            $seen[$k] ??= $line;
        }

        $errorCount = collect($checked)->filter(fn ($c) => $c['errors'] !== [])->count();

        // 2. Do the work in a transaction (rolled back for the preview)
        DB::beginTransaction();

        try {
            $classesDone = [];

            foreach ($checked as $line => $c) {
                if ($c['errors'] !== []) {
                    $result[$line] = ['row' => $rows[$line], 'status' => 'error', 'changes' => $c['errors']];
                    continue;
                }

                $changes = $this->apply($c['data'], $classesDone, $summary);
                $status = $changes['status'];
                unset($changes['status']);

                $result[$line] = ['row' => $rows[$line], 'status' => $status, 'changes' => array_values($changes)];
            }

            if ($commit && $errorCount === 0) {
                DB::commit();
                $saved = true;
            } else {
                DB::rollBack();
                $saved = false;
            }
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $summary['errors'] = $errorCount;
        ksort($result);

        return ['rows' => $result, 'summary' => $summary, 'errors' => $errorCount, 'saved' => $saved];
    }

    /* =====================================================================
     | One row
     * ===================================================================== */

    /** @return array{0: ?array, 1: array<string>} */
    protected function check(array $row): array
    {
        $errors = [];

        foreach (['branch', 'class_name', 'teacher_id', 'subject', 'grade', 'class_type', 'teacher_percentage', 'institution_percentage'] as $required) {
            if ($row[$required] === '') {
                $errors[] = "\"{$required}\" is empty.";
            }
        }

        if ($errors) {
            return [null, $errors];
        }

        // Branch: name or code, and one the user may change
        $branch = Branch::query()
            ->where(fn ($q) => $q->whereRaw('LOWER(name) = ?', [mb_strtolower($row['branch'])])
                ->orWhereRaw('LOWER(code) = ?', [mb_strtolower($row['branch'])]))
            ->first();

        if (!$branch) {
            $errors[] = "Branch \"{$row['branch']}\" not found.";
        } elseif (!in_array((int) $branch->id, $this->branchIds(), true)) {
            $errors[] = "You can't change classes of the {$branch->name} branch.";
        }

        // Teacher: by ID, the name must match
        $teacher = Teacher::query()->whereRaw('LOWER(custom_id) = ?', [mb_strtolower($row['teacher_id'])])->first();

        if (!$teacher) {
            $errors[] = "Teacher ID \"{$row['teacher_id']}\" not found.";
        } elseif ($row['teacher_name'] !== '' && !$this->sameTeacher($row['teacher_name'], $teacher)) {
            $errors[] = "{$teacher->custom_id} is {$teacher->full_name}, not {$row['teacher_name']}.";
        }

        $classType = strtolower($row['class_type']);
        if (!in_array($classType, ['online', 'offline', 'hybrid'], true)) {
            $errors[] = 'class_type must be offline, online or hybrid.';
        }

        // Shares
        $teacherPct = $this->number($row['teacher_percentage']);
        $organizerPct = $row['organizer_percentage'] === '' ? 0.0 : $this->number($row['organizer_percentage']);
        $institutionPct = $this->number($row['institution_percentage']);

        foreach (['teacher_percentage' => $teacherPct, 'organizer_percentage' => $organizerPct, 'institution_percentage' => $institutionPct] as $field => $value) {
            if ($value === null || $value < 0 || $value > 100) {
                $errors[] = "{$field} must be a number from 0 to 100.";
            }
        }

        if ($teacherPct !== null && $organizerPct !== null && $institutionPct !== null
            && round($teacherPct + $organizerPct + $institutionPct, 2) !== 100.00) {
            $errors[] = 'Teacher + organizer + institute % must add up to 100 (now ' . $this->num($teacherPct + $organizerPct + $institutionPct) . ').';
        }

        $organizer = null;
        if ($row['organizer_code'] !== '') {
            $organizer = Organizer::query()->whereRaw('LOWER(code) = ?', [mb_strtolower($row['organizer_code'])])->first();
            if (!$organizer) {
                $errors[] = "Organizer \"{$row['organizer_code']}\" not found.";
            }
        } elseif (($organizerPct ?? 0) > 0) {
            $errors[] = 'organizer_code is needed when organizer_percentage is more than 0.';
        }

        // Yes / no columns (empty = keep / default)
        $flags = [];
        foreach (['class_active', 'is_default', 'option_active'] as $flag) {
            $flags[$flag] = $this->bool($row[$flag]);
            if ($row[$flag] !== '' && $flags[$flag] === null) {
                $errors[] = "{$flag} must be yes or no.";
            }
        }

        // Category / option: all or nothing
        $category = $row['category'] !== '' ? $row['category'] : null;
        $fee = null;

        if ($category !== null) {
            if ($row['option_label'] === '') {
                $errors[] = 'option_label is empty (needed with a category).';
            }
            $fee = $this->number($row['fee']);
            if ($fee === null || $fee < 0) {
                $errors[] = 'fee must be a number (0 or more).';
            }
        } elseif ($row['option_label'] !== '' || $row['fee'] !== '') {
            $errors[] = 'category is empty but an option / fee is given.';
        }

        if (mb_strlen($row['class_name']) > 150) {
            $errors[] = 'class_name is longer than 150 letters.';
        }

        if ($errors) {
            return [null, $errors];
        }

        return [[
            'branch_id' => (int) $branch->id,
            'class_name' => preg_replace('/\s+/', ' ', $row['class_name']),
            'teacher' => $teacher,
            'subject' => preg_replace('/\s+/', ' ', $row['subject']),
            'grade' => preg_replace('/\s+/', ' ', $row['grade']),
            'medium' => $row['medium'] !== '' ? $row['medium'] : 'Sinhala',
            'class_type' => $classType,
            'class_active' => $flags['class_active'],
            'category' => $category !== null ? preg_replace('/\s+/', ' ', $category) : null,
            'option_label' => $category !== null ? preg_replace('/\s+/', ' ', $row['option_label']) : null,
            'fee' => $fee,
            'is_default' => $flags['is_default'],
            'option_active' => $flags['option_active'],
            'organizer_id' => $organizer?->id,
            'teacher_percentage' => $teacherPct,
            'organizer_percentage' => $organizerPct,
            'institution_percentage' => $institutionPct,
        ], []];
    }

    /** Create / update the class, category and option of one row. */
    protected function apply(array $d, array &$classesDone, array &$summary): array
    {
        $changes = [];
        $status = 'unchanged';
        $classKey = $d['branch_id'] . '|' . mb_strtolower($d['class_name']);

        $gradeId = $this->lookup(Grade::class, 'grade_name', $d['grade'], $changes, 'grade');
        $subjectId = $this->lookup(Subject::class, 'subject_name', $d['subject'], $changes, 'subject');

        $class = StudentClass::query()
            ->where('branch_id', $d['branch_id'])
            ->whereRaw('LOWER(class_name) = ?', [mb_strtolower($d['class_name'])])
            ->first();

        $paymentData = [
            'organizer_id' => $d['organizer_id'],
            'teacher_percentage' => $d['teacher_percentage'],
            'organizer_percentage' => $d['organizer_percentage'],
            'institution_percentage' => $d['institution_percentage'],
        ];

        if (!$class) {
            $class = StudentClass::create([
                'branch_id' => $d['branch_id'],
                'class_name' => $d['class_name'],
                'class_type' => $d['class_type'],
                'medium' => $d['medium'],
                'teacher_id' => $d['teacher']->id,
                'subject_id' => $subjectId,
                'grade_id' => $gradeId,
                'is_active' => $d['class_active'] ?? true,
                'is_ongoing' => false,
            ]);
            $this->paymentConfigs->createForClass($class, $paymentData + ['effective_from' => null, 'effective_to' => null]);

            $changes[] = 'New class';
            $status = 'new';
            $summary['new_classes']++;
            $classesDone[$classKey] = 'new';
        } elseif (($classesDone[$classKey] ?? null) === 'new') {
            $status = 'new'; // created by an earlier row of this file
        } elseif (!isset($classesDone[$classKey])) {
            $classesDone[$classKey] = 'seen';
            $classChanges = [];

            $wanted = [
                'class_type' => $d['class_type'],
                'medium' => $d['medium'],
                'teacher_id' => $d['teacher']->id,
                'subject_id' => $subjectId,
                'grade_id' => $gradeId,
            ];
            if ($d['class_active'] !== null) {
                $wanted['is_active'] = $d['class_active'];
            }

            foreach ($wanted as $field => $value) {
                if ((string) $class->{$field} !== (string) $value && !($field === 'is_active' && (bool) $class->is_active === (bool) $value)) {
                    $classChanges[] = $this->describe($field, $class->{$field}, $value, $d);
                }
            }

            if ($classChanges) {
                $class->update($wanted);
            }

            $config = ClassPaymentConfig::query()->where('student_class_id', $class->id)->first();
            $configChanged = !$config
                || (int) $config->organizer_id !== (int) $d['organizer_id']
                || round((float) $config->teacher_percentage, 2) !== round($d['teacher_percentage'], 2)
                || round((float) $config->organizer_percentage, 2) !== round($d['organizer_percentage'], 2)
                || round((float) $config->institution_percentage, 2) !== round($d['institution_percentage'], 2);

            if ($configChanged) {
                $this->paymentConfigs->updateForClass($class, $paymentData + [
                    'effective_from' => $config?->effective_from,
                    'effective_to' => $config?->effective_to,
                ]);
                $classChanges[] = $config
                    ? 'Shares ' . $this->num($config->teacher_percentage) . '/' . $this->num($config->organizer_percentage) . '/' . $this->num($config->institution_percentage)
                        . ' → ' . $this->num($d['teacher_percentage']) . '/' . $this->num($d['organizer_percentage']) . '/' . $this->num($d['institution_percentage'])
                    : 'Shares set';
            }

            if ($classChanges) {
                $changes = array_merge($changes, $classChanges);
                $status = 'updated';
                $summary['updated_classes']++;
            }
        }

        if ($d['category'] === null) {
            if ($status === 'unchanged') {
                $summary['unchanged']++;
            }

            return $changes + ['status' => $status];
        }

        // Category (shared list) -> this class's category fee
        $category = ClassCategory::query()->whereRaw('LOWER(category_name) = ?', [mb_strtolower($d['category'])])->first();

        if (!$category) {
            $category = ClassCategory::create([
                'category_name' => $d['category'],
                'code' => $this->categoryCode($d['category']),
                'is_schedulable' => true,
                'is_active' => true,
            ]);
            $changes[] = "New category \"{$d['category']}\"";
        }

        $categoryFee = ClassCategoryFee::withTrashed()
            ->where('student_class_id', $class->id)
            ->where('class_category_id', $category->id)
            ->first();

        if (!$categoryFee) {
            $categoryFee = ClassCategoryFee::create([
                'student_class_id' => $class->id,
                'class_category_id' => $category->id,
                'is_active' => true,
            ]);
            if ($status !== 'new') {
                $changes[] = "Category {$category->category_name} added to the class";
                $summary['new_categories']++;
            }
            $status = $status === 'new' ? 'new' : 'updated';
        } elseif ($categoryFee->trashed()) {
            $categoryFee->restore();
            $categoryFee->update(['is_active' => true]);
            $changes[] = "Category {$category->category_name} restored";
            $status = $status === 'new' ? 'new' : 'updated';
        }

        // Fee option
        $option = ClassCategoryFeeOption::query()
            ->where('class_category_fee_id', $categoryFee->id)
            ->whereRaw('LOWER(label) = ?', [mb_strtolower($d['option_label'])])
            ->first();

        $active = $d['option_active'] ?? true;

        if (!$option) {
            $hasOptions = ClassCategoryFeeOption::query()->where('class_category_fee_id', $categoryFee->id)->where('is_active', true)->exists();
            $default = $active && (!$hasOptions || $d['is_default'] === true);

            if ($default) {
                $this->clearDefault($categoryFee->id);
            }

            ClassCategoryFeeOption::create([
                'class_category_fee_id' => $categoryFee->id,
                'label' => $d['option_label'],
                'fee' => $d['fee'],
                'is_default' => $default,
                'is_active' => $active,
            ]);

            $changes[] = "New option {$d['option_label']} Rs. " . number_format($d['fee'], 2) . ($default ? ' (default)' : '');
            $summary['new_options']++;

            return $changes + ['status' => $status === 'new' ? 'new' : 'updated'];
        }

        $optionChanges = [];
        $update = [];

        if (round((float) $option->fee, 2) !== round($d['fee'], 2)) {
            $students = StudentClassEnrollment::query()->where('class_category_fee_option_id', $option->id)->where('is_active', true)->count();
            $optionChanges[] = "{$option->label}: fee Rs. " . number_format((float) $option->fee, 2) . ' → Rs. ' . number_format($d['fee'], 2)
                . ($students ? " ({$students} student" . ($students === 1 ? '' : 's') . ' on this option)' : '');
            $update['fee'] = $d['fee'];
        }

        if ($d['option_active'] !== null && (bool) $option->is_active !== $d['option_active']) {
            $optionChanges[] = "{$option->label}: " . ($d['option_active'] ? 'activated' : 'deactivated');
            $update['is_active'] = $d['option_active'];
            if (!$d['option_active']) {
                $update['is_default'] = false;
            }
        }

        $willBeActive = $update['is_active'] ?? (bool) $option->is_active;

        if ($d['is_default'] === true && !$option->is_default && $willBeActive) {
            $this->clearDefault($categoryFee->id);
            $update['is_default'] = true;
            $optionChanges[] = "{$option->label}: now the default";
        } elseif ($d['is_default'] === false && $option->is_default) {
            $update['is_default'] = false;
            $optionChanges[] = "{$option->label}: no longer the default";
        }

        if ($update) {
            $option->update($update);
            $summary['updated_options']++;
            $status = $status === 'new' ? 'new' : 'updated';
        }

        $changes = array_merge($changes, $optionChanges);

        if ($status === 'unchanged') {
            $summary['unchanged']++;
        }

        return $changes + ['status' => $status];
    }

    /* =====================================================================
     | Helpers
     * ===================================================================== */

    private ?array $branchIdCache = null;

    /** Branches this user may export / change. */
    protected function branchIds(): array
    {
        return $this->branchIdCache ??= array_map('intval', $this->branchAccess->getBranchIdArray());
    }

    /** Existing grade / subject by name (case does not matter), or a new one. */
    protected function lookup(string $model, string $column, string $name, array &$changes, string $what): int
    {
        $found = $model::query()->whereRaw("LOWER({$column}) = ?", [mb_strtolower($name)])->first();

        if ($found) {
            return (int) $found->id;
        }

        $changes[] = "New {$what} \"{$name}\"";

        return (int) $model::create([$column => $name, 'is_active' => true])->id;
    }

    protected function clearDefault(int $categoryFeeId): void
    {
        ClassCategoryFeeOption::query()->where('class_category_fee_id', $categoryFeeId)->update(['is_default' => false]);
    }

    protected function categoryCode(string $name): string
    {
        $base = strtoupper(Str::limit(preg_replace('/[^A-Za-z0-9]/', '', Str::ascii($name)) ?: 'CAT', 10, ''));
        $code = $base;
        $i = 2;

        while (ClassCategory::withTrashed()->where('code', $code)->exists()) {
            $code = $base . $i++;
        }

        return $code;
    }

    protected function describe(string $field, mixed $old, mixed $new, array $d): string
    {
        return match ($field) {
            'teacher_id' => 'Teacher ' . (Teacher::find($old)?->custom_id ?? '-') . ' → ' . $d['teacher']->custom_id,
            'subject_id' => 'Subject ' . (Subject::find($old)?->subject_name ?? '-') . ' → ' . $d['subject'],
            'grade_id' => 'Grade ' . (Grade::find($old)?->grade_name ?? '-') . ' → ' . $d['grade'],
            'is_active' => $new ? 'Class activated' : 'Class deactivated',
            default => ucfirst(str_replace('_', ' ', $field)) . " {$old} → {$new}",
        };
    }

    /** "Nimal Perera" or "N. Perera" (initials + surname) both match. */
    protected function sameTeacher(string $given, Teacher $teacher): bool
    {
        $full = (string) $teacher->full_name;
        $parts = preg_split('/\s+/', trim($full)) ?: [];
        $surname = end($parts) ?: '';

        return in_array($this->squash($given), array_filter([
            $this->squash($full),
            $this->squash(trim((string) $teacher->initials . ' ' . $surname)),
        ]), true);
    }

    protected function squash(string $text): string
    {
        return mb_strtolower(preg_replace('/[\s.]+/', '', $text));
    }

    protected function number(string|float|int|null $value): ?float
    {
        $value = str_replace([',', 'Rs.', 'Rs', '%', ' '], '', (string) $value);

        return is_numeric($value) ? round((float) $value, 2) : null;
    }

    protected function bool(string $value): ?bool
    {
        $value = mb_strtolower(trim($value));

        return match (true) {
            $value === '' => null,
            in_array($value, ['yes', 'y', '1', 'true', 'active', 'on'], true) => true,
            in_array($value, ['no', 'n', '0', 'false', 'inactive', 'off'], true) => false,
            default => null,
        };
    }

    protected function num(mixed $value): string
    {
        $n = round((float) $value, 2);

        return floor($n) == $n ? (string) (int) $n : rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    }

    /** Lists shown on the import page so people can type exact values. */
    public function referenceLists(): array
    {
        return [
            'branches' => Branch::query()->whereIn('id', $this->branchIds())->orderBy('name')->get(['name', 'code']),
            'teachers' => Teacher::query()->orderBy('custom_id')->get(['custom_id', 'full_name']),
            'subjects' => Subject::query()->orderBy('subject_name')->pluck('subject_name'),
            'grades' => Grade::query()->orderBy('grade_name')->pluck('grade_name'),
            'categories' => ClassCategory::query()->orderBy('category_name')->pluck('category_name'),
            'organizers' => Organizer::query()->orderBy('code')->get(['code', 'name']),
        ];
    }
}

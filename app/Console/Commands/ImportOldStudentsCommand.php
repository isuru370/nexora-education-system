<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Services\Import\OldValue;
use App\Services\Web\Student\StudentPhotoService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Bring the old system's students into students.
 *
 *   php artisan nexora:import-students /path/students.csv --images=/path/old/uploads --dry-run
 *   php artisan nexora:import-students /path/students.csv --images=/path/old/uploads
 *
 * The CSV has the old `students` columns, with the grade NAME in `grade`.
 * The old Custom ID is kept (it is the QR on the old cards), so cards
 * keep working: permanent_qr_active is set. Students already in the
 * database are left as they are, so it is safe to run again.
 */
class ImportOldStudentsCommand extends Command
{
    protected $signature = 'nexora:import-students
        {file : CSV - custom_id, fname, lname, mobile, whatsapp_mobile, email, nic, bday, gender, address1-3, guardian_fname, guardian_lname, guardian_nic, guardian_mobile, is_active, img_url, grade, admission, student_school, created_at, updated_at}
        {--images= : folder with the old image files (e.g. the old uploads folder)}
        {--branch= : branch id / code / name (default: the only branch, or MAIN)}
        {--dry-run : only report, change nothing}';

    protected $description = 'Import the old system students (Custom ID / QR kept)';

    public const REQUIRED = ['custom_id', 'fname', 'lname', 'grade'];

    public function handle(StudentPhotoService $photos): int
    {
        $dry = (bool) $this->option('dry-run');
        $imagesDir = $this->option('images') ? rtrim((string) $this->option('images'), '/\\') : null;

        if ($imagesDir !== null && !is_dir($imagesDir)) {
            $this->error("Images folder not found: {$imagesDir}");

            return self::FAILURE;
        }

        try {
            $rows = OldValue::csv((string) $this->argument('file'), self::REQUIRED);
            $branch = OldValue::branch($this->option('branch'));
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        // Every grade must be there first (nexora:grades add ...)
        $grades = OldValue::gradeMap();
        $missingGrades = collect($rows)->pluck('grade')
            ->map(fn ($g) => trim((string) $g))
            ->unique(fn ($g) => OldValue::gradeKey($g))
            ->reject(fn ($g) => isset($grades[OldValue::gradeKey($g)]))
            ->values();

        if ($missingGrades->isNotEmpty()) {
            $this->error('These grades are not in the database: ' . $missingGrades->map(fn ($g) => "\"{$g}\"")->implode(', '));
            $this->line('Add them first:  php artisan nexora:grades add ' . $missingGrades->map(fn ($g) => '"' . preg_replace('/\s+grade$/i', '', $g) . '"')->implode(' '));

            return self::FAILURE;
        }

        $count = ['rows' => count($rows), 'created' => 0, 'exists' => 0, 'failed' => 0, 'bad' => 0,
            'in_storage' => 0, 'copied' => 0, 'no_photo' => 0, 'warnings' => 0];
        $problems = [];
        $warn = function (string $id, string $text) use (&$problems, &$count) {
            $problems[] = "{$id}: {$text}";
            $count['warnings']++;
        };

        $existing = Student::withTrashed()->pluck('custom_id')->map(fn ($id) => strtoupper($id))->flip()->all();
        $nics = Student::withTrashed()->whereNotNull('nic')->pluck('custom_id', 'nic')->all();

        foreach ($rows as $row) {
            $id = (string) OldValue::text($row['custom_id'] ?? '', 50);

            if ($id === '') {
                $count['bad']++;
                $problems[] = '(no custom_id): skipped - ' . (OldValue::text($row['lname'] ?? '') ?? 'no name');
                continue;
            }

            if (isset($existing[strtoupper($id)])) {
                $count['exists']++;
                continue;
            }

            $initialName = OldValue::text($row['lname'] ?? '', 100) ?? OldValue::text($row['fname'] ?? '', 100);

            if ($initialName === null) {
                $count['bad']++;
                $problems[] = "{$id}: skipped - no name";
                continue;
            }

            // Phones
            $mobile = OldValue::phone($row['mobile'] ?? null);
            $whatsapp = OldValue::phone($row['whatsapp_mobile'] ?? null) ?? $mobile;
            $guardianMobile = OldValue::phone($row['guardian_mobile'] ?? null) ?? $mobile ?? $whatsapp;

            foreach (['mobile' => $mobile, 'guardian_mobile' => $guardianMobile] as $field => $phone) {
                if (OldValue::phoneLooksWrong($phone)) {
                    $warn($id, "{$field} {$phone} is not 10 digits - check it");
                }
            }
            if ($guardianMobile === null) {
                $warn($id, 'no guardian mobile');
            }

            // NIC is unique
            $nic = OldValue::nic($row['nic'] ?? null);
            if ($nic !== null && isset($nics[$nic])) {
                $warn($id, "NIC {$nic} is also on {$nics[$nic]} - left empty");
                $nic = null;
            }

            $bday = OldValue::date($row['bday'] ?? null);
            if ($bday === null && OldValue::text($row['bday'] ?? null) !== null) {
                $warn($id, 'birthday "' . trim($row['bday']) . '" not understood - left empty');
            }

            $gender = OldValue::gender($row['gender'] ?? null);
            if ($gender === null) {
                $warn($id, 'no gender - set to male, check it');
                $gender = 'male';
            }

            // Photo
            [$imagePath, $imageState] = OldValue::image($row['img_url'] ?? null, $imagesDir, $dry);
            if ($imagePath === null) {
                $count['no_photo']++;
                $warn($id, $imageState === 'missing'
                    ? 'photo ' . basename((string) parse_url((string) $row['img_url'], PHP_URL_PATH)) . ' not found - default photo'
                    : 'no photo - default photo');
                // same default picture as a new registration without a photo
                $imagePath = $photos->getDefaultImageUrl($gender);
            } else {
                $count[$imageState]++;
            }

            $data = [
                'custom_id' => $id,
                'branch_id' => $branch->id,
                'full_name' => OldValue::text($row['fname'] ?? '', 150),
                'initial_name' => $initialName,
                'mobile' => $mobile,
                'whatsapp_mobile' => $whatsapp,
                'email' => OldValue::email($row['email'] ?? null),
                'nic' => $nic,
                'bday' => $bday,
                'gender' => $gender,
                'address1' => OldValue::text($row['address1'] ?? null, 150),
                'address2' => OldValue::text($row['address2'] ?? null, 150),
                'address3' => OldValue::text($row['address3'] ?? null, 150),
                'guardian_fname' => OldValue::text($row['guardian_fname'] ?? null, 100),
                'guardian_lname' => OldValue::text($row['guardian_lname'] ?? null, 100),
                'guardian_nic' => OldValue::nic($row['guardian_nic'] ?? null),
                'guardian_mobile' => $guardianMobile ?? '',
                'grade_id' => $grades[OldValue::gradeKey($row['grade'])],
                'admission' => OldValue::bool($row['admission'] ?? null),
                'student_school' => OldValue::text($row['student_school'] ?? null, 150),
                'img_url' => $imagePath,
                'is_active' => OldValue::bool($row['is_active'] ?? null, true),
                // the old card QR is the Custom ID
                'permanent_qr_active' => true,
            ];

            if (!$dry) {
                try {
                    Student::withoutEvents(function () use ($data, $row) {
                        $student = new Student($data);
                        $student->created_at = OldValue::time($row['created_at'] ?? null);
                        $student->updated_at = OldValue::time(($row['updated_at'] ?? null) ?: ($row['created_at'] ?? null));
                        $student->save();
                    });
                } catch (Throwable $e) {
                    $count['failed']++;
                    $problems[] = "{$id}: NOT imported - " . strtok($e->getMessage(), "\n");
                    continue;
                }
            }

            $existing[strtoupper($id)] = true;
            if ($nic !== null) {
                $nics[$nic] = $id;
            }
            $count['created']++;
        }

        $this->newLine();
        $this->line($dry ? '<fg=yellow>DRY RUN - nothing was changed</>' : '<fg=green>Done</>');
        $this->line("Branch: {$branch->name}");
        $this->table(['', 'Students'], [
            ['In the file', $count['rows']],
            [$dry ? 'Would be imported' : 'Imported', $count['created']],
            ['  photo already in storage/app/public/uploads', $count['in_storage']],
            ['  photo ' . ($dry ? 'to copy' : 'copied') . ' from --images', $count['copied']],
            ['  no photo: default male.png / female.png', $count['no_photo']],
            ['Already imported (left as they are)', $count['exists']],
            ['Skipped: no custom_id / name', $count['bad']],
            ['Failed', $count['failed']],
            ['Notes to check', $count['warnings']],
        ]);

        $log = OldValue::writeProblems('import-students', $problems, fn ($l) => $this->line($l));
        if ($log) {
            $this->warn(count($problems) . ' lines - full list: ' . $log);
        }

        return $count['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}

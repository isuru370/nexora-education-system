<?php

namespace App\Console\Commands;

use App\Models\QuickPhoto;
use App\Services\Import\OldQuickPhotoReader;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Bring the old system's quick photos (CQS00001 ...) into quick_photos.
 *
 *   php artisan nexora:import-photos /path/quick_image.sql --images=/path/old/uploads --dry-run
 *   php artisan nexora:import-photos /path/quick_image.sql --images=/path/old/uploads
 *
 * The picture is storage/app/public/uploads/<file name from the old URL>:
 * it is copied from --images when it is not there yet. Rows whose picture
 * cannot be found are skipped (and listed). Photos already imported are
 * left as they are, so it is safe to run again.
 */
class ImportOldPhotosCommand extends Command
{
    protected $signature = 'nexora:import-photos
        {file : quick_photo_details .sql dump (or .csv: custom_id, quick_img, is_active)}
        {--images= : folder with the old image files (e.g. the old uploads folder)}
        {--dry-run : only report, change nothing}';

    protected $description = 'Import the old system quick photos (custom id + picture)';

    public const FOLDER = 'uploads';
    public const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public function handle(OldQuickPhotoReader $reader): int
    {
        $dry = (bool) $this->option('dry-run');
        $imagesDir = $this->option('images') ? rtrim((string) $this->option('images'), '/\\') : null;

        if ($imagesDir !== null && !is_dir($imagesDir)) {
            $this->error("Images folder not found: {$imagesDir}");

            return self::FAILURE;
        }

        try {
            $rows = $reader->read((string) $this->argument('file'));
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $disk = Storage::disk('public');
        $count = ['rows' => count($rows), 'created' => 0, 'exists' => 0, 'copied' => 0, 'in_storage' => 0, 'missing' => 0, 'bad' => 0];
        $problems = [];

        $existing = QuickPhoto::withTrashed()->pluck('custom_id')->flip();

        foreach ($rows as $row) {
            $id = $row['custom_id'];
            $file = basename((string) parse_url($row['image_url'], PHP_URL_PATH));
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            if ($id === '' || $file === '' || !in_array($ext, self::EXTENSIONS, true)) {
                $count['bad']++;
                $problems[] = "{$id}: not an image ({$row['image_url']})";
                continue;
            }

            if (isset($existing[$id])) {
                $count['exists']++;
                continue;
            }

            $path = self::FOLDER . '/' . $file;

            if ($disk->exists($path)) {
                $count['in_storage']++;
            } elseif ($imagesDir !== null && is_file($imagesDir . DIRECTORY_SEPARATOR . $file)) {
                if (!$dry) {
                    $disk->put($path, file_get_contents($imagesDir . DIRECTORY_SEPARATOR . $file));
                }
                $count['copied']++;
            } else {
                $count['missing']++;
                $problems[] = "{$id}: picture {$file} not found";
                continue;
            }

            if (!$dry) {
                $created = $this->time($row['created_at']);
                $photo = new QuickPhoto([
                    'custom_id' => $id,
                    'image_path' => $path,
                    'is_active' => $row['is_active'],
                ]);
                $photo->created_at = $created;
                $photo->updated_at = $created;
                $photo->save();
            }

            $existing[$id] = true;
            $count['created']++;
        }

        $this->newLine();
        $this->line($dry ? '<fg=yellow>DRY RUN - nothing was changed</>' : '<fg=green>Done</>');
        $this->table(['', 'Photos'], [
            ['In the file', $count['rows']],
            [$dry ? 'Would be imported' : 'Imported', $count['created']],
            ['  picture already in storage/app/public/uploads', $count['in_storage']],
            ['  picture ' . ($dry ? 'to copy' : 'copied') . ' from --images', $count['copied']],
            ['Already imported (left as they are)', $count['exists']],
            ['Skipped: picture not found', $count['missing']],
            ['Skipped: not an image', $count['bad']],
        ]);

        if ($problems) {
            $log = storage_path('logs/import-photos-problems.txt');
            file_put_contents($log, implode(PHP_EOL, $problems) . PHP_EOL);
            $this->warn(count($problems) . ' skipped - list: ' . $log);
            foreach (array_slice($problems, 0, 10) as $p) {
                $this->line('  ' . $p);
            }
        }

        return self::SUCCESS;
    }

    protected function time(?string $value): Carbon
    {
        try {
            return $value ? Carbon::parse($value) : now();
        } catch (Throwable) {
            return now();
        }
    }
}

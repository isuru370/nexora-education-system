<?php

namespace App\Services\FileUpload;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Throwable;

class ImageUploadService
{
    /** Longest side of a saved photo (a student card / profile photo needs no more). */
    public const MAX_SIDE = 800;

    public const JPEG_QUALITY = 80;

    public const DEFAULT_IMAGES = [
        'uploads/male.png',
        'uploads/female.png',
        'uploads/other.png',
    ];

    /**
     * Save a photo on the public disk, made smaller first (most phone photos are
     * 2-5 MB; this keeps them around 100-200 KB). If resizing fails, the
     * original file is saved as before.
     */
    public function upload(UploadedFile $file, string $folder): string
    {
        $folder = trim($folder, '/');

        try {
            $manager = new ImageManager(['driver' => 'gd']);
            $image = $manager->make($file->getRealPath());

            if (function_exists('exif_read_data')) {
                $image->orientate();   // phone photos taken sideways
            }

            $image->resize(self::MAX_SIDE, self::MAX_SIDE, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            $path = $folder . '/' . Str::uuid() . '.jpg';
            Storage::disk('public')->put($path, (string) $image->encode('jpg', self::JPEG_QUALITY));

            return $path;
        } catch (Throwable $e) {
            Log::warning('Image resize failed, saving the original', ['message' => $e->getMessage()]);

            $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');

            return $file->storeAs($folder, Str::uuid() . '.' . $extension, 'public');
        }
    }

    /**
     * Is this file still used by a student or a quick photo?
     * Quick photo files are shared with the student they are given to.
     */
    public function isInUse(string $path): bool
    {
        return DB::table('students')->where('img_url', $path)->exists()
            || DB::table('quick_photos')->where('image_path', $path)->exists();
    }

    /**
     * Delete an uploaded photo. Default images, files outside the photo
     * folders and files still in use are never deleted.
     *
     * @return bool true when the file was deleted
     */
    public function delete(?string $path): bool
    {
        if (!$this->isDeletable($path)) {
            return false;
        }

        if (!Storage::disk('public')->exists($path)) {
            return false;
        }

        return Storage::disk('public')->delete($path);
    }

    protected function isDeletable(?string $path): bool
    {
        if (!$path || in_array($path, self::DEFAULT_IMAGES, true)) {
            return false;
        }

        // Only photo folders, and no way out of them
        if (Str::contains($path, ['..', '\\']) || !Str::startsWith($path, ['uploads/', 'students/'])) {
            return false;
        }

        return !$this->isInUse($path);
    }
}

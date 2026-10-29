<?php

namespace App\Services\Web\Student;

use App\Models\QuickPhoto;
use App\Services\FileUpload\ImageUploadService;

class StudentPhotoService
{
    public function __construct(
        protected ImageUploadService $imageUploadService
    ) {}

    /**
     * Get default image URL based on gender.
     */
    public function getDefaultImageUrl(
        string $gender
    ): string {

        return $gender === 'female'
            ? 'uploads/female.png'
            : 'uploads/male.png';
    }

    /**
     * Handle student image upload.
     */
    public function handleStudentImage(
        $file,
        ?string $gender = null
    ): string {

        if ($file) {
            // Saved smaller (see ImageUploadService::upload)
            return $this->imageUploadService->upload($file, 'uploads');
        }

        return $gender
            ? $this->getDefaultImageUrl($gender)
            : 'uploads/male.png';
    }

    /**
     * Handle quick photo assignment.
     */
    public function assignQuickPhoto(
        string $quickImageId
    ): ?string {

        $quickPhoto = QuickPhoto::query()
            ->where(
                'custom_id',
                $quickImageId
            )
            ->where('is_active', true)
            ->lockForUpdate()
            ->first();

        if (
            $quickPhoto &&
            !empty($quickPhoto->image_path)
        ) {
            $quickPhoto->update([
                'is_active' => false,
            ]);

            return $quickPhoto->image_path;
        }

        return null;
    }

    /**
     * Validate quick photo.
     */
    public function validateQuickPhoto(
        string $quickImageId
    ): bool {

        return QuickPhoto::query()
            ->where(
                'custom_id',
                $quickImageId
            )
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Remove a student's previous photo after it was replaced,
     * unless it is a default image or still used elsewhere.
     */
    public function deleteReplacedImage(?string $oldPath, ?string $newPath): void
    {
        if ($oldPath && $oldPath !== $newPath) {
            $this->imageUploadService->delete($oldPath);
        }
    }
}

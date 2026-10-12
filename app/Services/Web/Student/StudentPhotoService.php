<?php

namespace App\Services\Web\Student;

use App\Models\QuickPhoto;

class StudentPhotoService
{
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
            return $file->store(
                'uploads/',
                'public'
            );
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
}
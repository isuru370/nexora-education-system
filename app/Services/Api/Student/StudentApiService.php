<?php

namespace App\Services\Api\Student;

use App\Models\QuickPhoto;
use App\Models\Student;
use App\Services\Branch\BranchAccessService;
use App\Services\Api\Student\StudentQRService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StudentApiService
{
    public function __construct(
        private BranchAccessService $branchAccessService
    ) {
    }

    /**
     * Fetch all students accessible to the authenticated user.
     */
    public function fetchAllStudents(): Collection
    {
        $query = Student::query()
            ->with([
                'grade',
                'branch',
            ])
            ->orderByDesc('id');

        $this->applyBranchAccess($query);

        return $query->get();
    }

    /**
     * Search students.
     */
    public function searchStudents(string $search): Collection
    {
        $search = trim($search);

        if ($search === '') {
            throw new RuntimeException('Search value is required.');
        }

        $query = Student::query()
            ->with([
                'grade',
                'branch',
            ])
            ->where(function (Builder $query) use ($search) {
                $query
                    ->where('full_name', 'like', "%{$search}%")
                    ->orWhere('initial_name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('custom_id', 'like', "%{$search}%")
                    ->orWhere('temporary_qr_code', 'like', "%{$search}%")
                    ->orWhere('guardian_mobile', 'like', "%{$search}%");
            })
            ->orderByDesc('id');

        $this->applyBranchAccess($query);

        return $query->get();
    }

    /**
     * Find student using QR code.
     *
     * Existing StudentQRService business rules are preserved.
     */
    public function findStudentByQr(string $qrCode): Student
    {
        $qrCode = trim($qrCode);

        if ($qrCode === '') {
            throw new RuntimeException('QR code is required.');
        }

        $qrResult = StudentQRService::read($qrCode);

        if (! ($qrResult['success'] ?? false)) {
            throw new RuntimeException(
                $qrResult['message'] ?? 'Failed to read QR code.'
            );
        }

        $studentId = $qrResult['student_id'] ?? null;

        if (! $studentId) {
            throw new RuntimeException('Student not found.');
        }

        $query = Student::query()
            ->with([
                'grade',
                'branch',
            ])
            ->whereKey($studentId);

        $this->applyBranchAccess($query);

        $student = $query->first();

        if (! $student) {
            throw new RuntimeException('Student not found.');
        }

        return $student;
    }

    /**
     * Update student image using a QuickPhoto.
     *
     * The QuickPhoto is locked to prevent the same photo
     * from being assigned to multiple students concurrently.
     */
    public function updateStudentImage(
        int $studentId,
        string $quickPhotoCustomId
    ): array {
        return DB::transaction(function () use (
            $studentId,
            $quickPhotoCustomId
        ) {
            $quickPhoto = QuickPhoto::query()
                ->where('custom_id', $quickPhotoCustomId)
                ->lockForUpdate()
                ->first();

            if (! $quickPhoto) {
                throw new ModelNotFoundException(
                    'Quick photo not found.'
                );
            }

            if (! $quickPhoto->is_active) {
                throw new RuntimeException(
                    'This quick photo is already used.'
                );
            }

            $studentQuery = Student::query()
                ->whereKey($studentId);

            $this->applyBranchAccess($studentQuery);

            $student = $studentQuery
                ->lockForUpdate()
                ->first();

            if (! $student) {
                throw new ModelNotFoundException(
                    'Student not found.'
                );
            }

            $student->update([
                'img_url' => $quickPhoto->image_path,
                'last_image_update_at' => now(),
            ]);

            $quickPhoto->update([
                'is_active' => false,
            ]);

            return [
                'student_id' => $student->id,
                'student_custom_id' => $student->custom_id,
                'quick_photo_custom_id' => $quickPhoto->custom_id,
                'img_url' => $student->img_url,
                'image_url' => asset(
                    'storage/' . $student->img_url
                ),
            ];
        });
    }

    /**
     * Apply authenticated user's branch access.
     */
    protected function applyBranchAccess(Builder $query): void
    {
        $branchIds = $this->branchAccessService->getBranchIdArray();

        if (empty($branchIds)) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn('branch_id', $branchIds);
    }
}
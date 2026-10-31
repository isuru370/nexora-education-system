<?php

namespace App\Services\Web\GenerateStudentId;

use App\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateStudentIdService
{
    /**
     * Get paginated students for the ID card generation page: active
     * students who do not have a permanent card yet (temporary card mode).
     *
     * @throws \RuntimeException
     */
    public function getIndexData(?string $search = null): LengthAwarePaginator
    {
        try {
            $search = $search !== null ? trim($search) : '';

            return Student::query()
                ->with(['branch:id,name,code', 'grade'])
                ->select([
                    'id',
                    'custom_id',
                    'initial_name',
                    'full_name',
                    'address1',
                    'address2',
                    'address3',
                    'branch_id',
                    'img_url',
                    'grade_id',
                    'mobile',
                    'is_active',
                    'created_at',
                ])
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($mainQuery) use ($search) {
                        $mainQuery->where('custom_id', 'like', "%{$search}%")
                            ->orWhere('initial_name', 'like', "%{$search}%")
                            ->orWhere('full_name', 'like', "%{$search}%");
                    });
                })
                ->where('is_active', true)
                // only students still on a temporary card need an ID printed
                ->where('permanent_qr_active', false)
                ->latest('created_at')
                ->paginate(12);

        } catch (Throwable $e) {
            Log::error('Failed to fetch students for ID card page.', [
                'service' => self::class,
                'method'  => __FUNCTION__,
                'search'  => $search,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'user_id' => auth()->id(),
            ]);

            throw new \RuntimeException(
                'Unable to retrieve students. Please try again later.',
                0,
                $e
            );
        }
    }

    /**
     * Find a student for printing ID card.
     */
    public function findStudentForPrint(int $studentId): ?Student
    {
        try {
            return Student::with([
                'branch:id,name,code',
                'grade:id,grade_name',
            ])->find($studentId);

        } catch (Throwable $e) {
            Log::error('Failed to find student for print.', [
                'student_id' => $studentId,
                'message'    => $e->getMessage(),
            ]);

            return null;
        }
    }
}
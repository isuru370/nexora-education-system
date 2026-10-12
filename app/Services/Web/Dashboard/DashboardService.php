<?php

namespace App\Services\Web\Dashboard;

use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentCard;
use App\Models\StudentClass;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class DashboardService
{
    public function getDashboardData(): array
    {
        try {
            $today = Carbon::today();
            $now = Carbon::now();

            $availableStudentCardCount = $this->getAvailableStudentCardCount();
            $expiringStudents = $this->getExpiringStudents();

            return [
                'studentsCount' => $this->getStudentsCount(),
                'teachersCount' => $this->getTeachersCount(),
                'classesCount' => $this->getClassesCount(),

                'todayIncome' => $this->getTodayIncome($today),

                'monthlyIncome' => $this->getMonthlyIncome(
                    $now->month,
                    $now->year
                ),

                'availableStudentCardCount' => $availableStudentCardCount,

                'showStudentCardWarning' =>
                    $availableStudentCardCount < 10,

                'incompleteRegistrationCount' =>
                    $this->getIncompleteRegistrationCount(),

                'incompleteRegistrations' =>
                    $this->getIncompleteRegistrations(),

                'latestStudents' =>
                    $this->getLatestStudents(),

                'expiringStudents' =>
                    $expiringStudents,

                'expiringStudentsCount' =>
                    $expiringStudents->count(),
            ];
        } catch (Throwable $e) {

            Log::error('Dashboard data loading failed.', [
                'user_id' => auth()->id(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    protected function getStudentsCount(): int
    {
        return Student::query()->count();
    }

    protected function getTeachersCount(): int
    {
        return Teacher::query()->count();
    }

    protected function getClassesCount(): int
    {
        return StudentClass::query()->count();
    }

    protected function getTodayIncome(Carbon $today): float
    {
        return (float) Payment::query()
            ->whereDate('paid_at', $today)
            ->where('status', 'completed')
            ->sum('amount');
    }

    protected function getMonthlyIncome(int $month, int $year): float
    {
        return (float) Payment::query()
            ->whereMonth('paid_at', $month)
            ->whereYear('paid_at', $year)
            ->where('status', 'completed')
            ->sum('amount');
    }

    protected function getAvailableStudentCardCount(): int
    {
        return StudentCard::query()
            ->where('status', 'available')
            ->count();
    }

    protected function incompleteRegistrationQuery()
    {
        return Student::query()
            ->where(function ($query) {
                $query->whereNull('full_name')
                    ->orWhere('full_name', '')
                    ->orWhereNull('mobile')
                    ->orWhere('mobile', '')
                    ->orWhereNull('address1')
                    ->orWhere('address1', '');
            });
    }

    protected function getIncompleteRegistrationCount(): int
    {
        return $this->incompleteRegistrationQuery()->count();
    }

    protected function getIncompleteRegistrations(): Collection
    {
        return $this->incompleteRegistrationQuery()
            ->select([
                'id',
                'custom_id',
                'initial_name',
                'guardian_mobile',
            ])
            ->latest()
            ->take(5)
            ->get();
    }

    protected function getLatestStudents(): Collection
    {
        return Student::query()
            ->latest()
            ->take(5)
            ->get([
                'id',
                'custom_id',
                'initial_name',
                'guardian_mobile',
                'created_at',
            ]);
    }

    protected function getExpiringStudents(): Collection
    {
        return Student::query()
            ->select([
                'id',
                'temporary_qr_code',
                'initial_name',
                'guardian_mobile',
                'temporary_qr_code_expire_date',
            ])
            ->where('permanent_qr_active', 0)
            ->whereNotNull('temporary_qr_code_expire_date')
            ->whereBetween('temporary_qr_code_expire_date', [
                Carbon::today(),
                Carbon::today()->addDays(10),
            ])
            ->orderBy('temporary_qr_code_expire_date')
            ->get();
    }
}
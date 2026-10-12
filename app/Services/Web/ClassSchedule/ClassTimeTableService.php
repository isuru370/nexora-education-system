<?php

namespace App\Services\Web\ClassSchedule;

use App\Models\ClassSchedule;
use App\Services\Branch\BranchAccessService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ClassTimeTableService
{
    public function __construct(
        protected BranchAccessService $branchAccessService,
    ) {}


    /*
    |--------------------------------------------------------------------------
    | Week Date Resolution
    |--------------------------------------------------------------------------
    */

    /**
     * Resolve week date info from a given date string.
     *
     * @param string|null $selectedDate
     * @return array{
     *     selectedDate: string,
     *     date: Carbon,
     *     startOfWeek: Carbon,
     *     endOfWeek: Carbon,
     *     weekNumber: int,
     *     year: int
     * }
     */
    public function resolveWeekDate(?string $selectedDate): array
    {
        $selectedDate = $selectedDate ?: now()->toDateString();

        $date = Carbon::parse($selectedDate);

        $startOfWeek = $date->copy()
            ->startOfWeek(Carbon::MONDAY)
            ->startOfDay();

        $endOfWeek = $date->copy()
            ->endOfWeek(Carbon::SUNDAY)
            ->endOfDay();

        return [
            'selectedDate' => $selectedDate,
            'date' => $date,
            'startOfWeek' => $startOfWeek,
            'endOfWeek' => $endOfWeek,
            'weekNumber' => $date->isoWeek,
            'year' => $date->isoWeekYear,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Weekly Schedules
    |--------------------------------------------------------------------------
    */

    /**
     * Get weekly schedules (branch-scoped).
     *
     * @param Carbon $startOfWeek
     * @param Carbon $endOfWeek
     * @return Collection<int, ClassSchedule>
     */
    public function getWeeklySchedules(
        Carbon $startOfWeek,
        Carbon $endOfWeek
    ): Collection {
        /** @var Collection<int, int> $accessibleBranchIds */
        $accessibleBranchIds = $this->branchAccessService->getBranchIds();

        return ClassSchedule::query()
            ->select([
                'id',
                'branch_id',
                'student_class_id',
                'class_category_fee_id',
                'class_date',
                'start_time',
                'end_time',
                'status',
                'class_hall_id',
                'day_of_week',
            ])
            ->with([
                'branch:id,name,code',

                'hall:id,hall_name',

                'studentClass:id,class_name,grade_id',
                'studentClass.grade:id,grade_name',

                'classCategoryFee:id,student_class_id,class_category_id,is_active',
                'classCategoryFee.category:id,category_name',

                'classCategoryFee.activeFeeOptions' => function ($query) {
                    $query
                        ->select([
                            'id',
                            'class_category_fee_id',
                            'label',
                            'fee',
                            'is_default',
                            'is_active',
                        ])
                        ->where('is_active', true)
                        ->orderByDesc('is_default')
                        ->orderBy('id');
                },
            ])
            ->whereIn('branch_id', $accessibleBranchIds)
            ->whereBetween('class_date', [
                $startOfWeek->toDateString(),
                $endOfWeek->toDateString(),
            ])
            ->orderBy('class_date')
            ->orderBy('start_time')
            ->get();
    }


    /*
    |--------------------------------------------------------------------------
    | Export Dataset
    |--------------------------------------------------------------------------
    */

    /**
     * Get column headings.
     *
     * @return array<int, string>
     */
    public function getHeadings(): array
    {
        return [
            '#',
            'Branch',
            'Date',
            'Day',
            'Start Time',
            'End Time',
            'Class',
            'Grade',
            'Category',
            'Hall',
            'Status',
        ];
    }


    /**
     * Get header info rows.
     *
     * @param Carbon $startOfWeek
     * @param Carbon $endOfWeek
     * @param Carbon|null $generatedAt
     * @return Collection<int, array<int, mixed>>
     */
    public function getHeaderRows(
        Carbon $startOfWeek,
        Carbon $endOfWeek,
        ?Carbon $generatedAt = null
    ): Collection {
        $generatedAt = $generatedAt ?? now();

        return collect([
            ['Weekly Timetable'],
            [
                'Week',
                $startOfWeek->format('Y-m-d')
                    . ' to '
                    . $endOfWeek->format('Y-m-d'),
            ],
            ['Generated At', $generatedAt->format('Y-m-d H:i')],
            [],
        ]);
    }


    /**
     * Get data rows for the given schedules.
     *
     * @param Collection<int, ClassSchedule> $schedules
     * @return Collection<int, array<int, mixed>>
     */
    public function getDataRows(Collection $schedules): Collection
    {
        return $schedules
            ->values()
            ->map(fn (ClassSchedule $schedule, int $index) => $this->mapSchedule($schedule, $index + 1));
    }


    /**
     * Build the complete export dataset.
     *
     * @param Collection<int, ClassSchedule> $schedules
     * @param Carbon $startOfWeek
     * @param Carbon $endOfWeek
     * @param Carbon|null $generatedAt
     * @return Collection<int, array<int, mixed>>
     */
    public function buildDataset(
        Collection $schedules,
        Carbon $startOfWeek,
        Carbon $endOfWeek,
        ?Carbon $generatedAt = null
    ): Collection {
        return $this->getHeaderRows($startOfWeek, $endOfWeek, $generatedAt)
            ->merge([$this->getHeadings()])
            ->merge($this->getDataRows($schedules))
            ->values();
    }


    /**
     * Alias for buildDataset — used by controller.
     *
     * @param Collection<int, ClassSchedule> $schedules
     * @param Carbon $startOfWeek
     * @param Carbon $endOfWeek
     * @param Carbon|null $generatedAt
     * @return Collection<int, array<int, mixed>>
     */
    public function buildExportDataset(
        Collection $schedules,
        Carbon $startOfWeek,
        Carbon $endOfWeek,
        ?Carbon $generatedAt = null
    ): Collection {
        return $this->buildDataset($schedules, $startOfWeek, $endOfWeek, $generatedAt);
    }


    /*
    |--------------------------------------------------------------------------
    | Protected Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Map a single schedule to an array row.
     *
     * @param ClassSchedule $schedule
     * @param int $index
     * @return array<int, mixed>
     */
    protected function mapSchedule(ClassSchedule $schedule, int $index): array
    {
        return [
            $index,
            $schedule->branch?->name ?? '-',
            $this->formatDate($schedule->class_date),
            $this->formatDayName($schedule->class_date),
            $schedule->start_time ?? '-',
            $schedule->end_time ?? '-',
            $schedule->studentClass?->class_name ?? '-',
            $schedule->studentClass?->grade?->grade_name ?? '-',
            $schedule->classCategoryFee?->category?->category_name ?? '-',
            $schedule->hall?->hall_name ?? '-',
            $this->formatStatus($schedule->status),
        ];
    }


    /**
     * Format date column.
     */
    protected function formatDate(?Carbon $date): string
    {
        return $date ? $date->format('Y-m-d') : '-';
    }


    /**
     * Format day name column.
     */
    protected function formatDayName(?Carbon $date): string
    {
        return $date ? $date->format('l') : '-';
    }


    /**
     * Format status column.
     */
    protected function formatStatus(?string $status): string
    {
        return $status ? ucfirst($status) : '-';
    }
}
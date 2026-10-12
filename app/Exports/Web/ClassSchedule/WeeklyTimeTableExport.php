<?php

namespace App\Exports\Web\ClassSchedule;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WeeklyTimeTableExport implements FromCollection, ShouldAutoSize, WithStyles
{
    /**
     * @var Collection<int, array<int, mixed>>
     */
    protected Collection $dataset;

    /**
     * Constructor.
     *
     * @param Collection<int, array<int, mixed>> $dataset
     */
    public function __construct(Collection $dataset)
    {
        $this->dataset = $dataset;
    }

    /**
     * Data collection.
     *
     * @return Collection<int, array<int, mixed>>
     */
    public function collection(): Collection
    {
        return $this->dataset;
    }

    /**
     * Apply styles.
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true]],
            3 => ['font' => ['bold' => true]],
            5 => ['font' => ['bold' => true]],
        ];
    }
}
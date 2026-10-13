<?php

namespace App\Services\Web\Report;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Filters shared by all payment reports.
 * Create it with PaymentReportQuery::filter() so branch access is always checked.
 */
class ReportFilter
{
    /**
     * @param  Collection<int, int>  $branchIds  Branches the report covers (already access-checked)
     */
    public function __construct(
        public Carbon $from,
        public Carbon $to,
        public Collection $branchIds,
        public ?int $branchId = null,
        public ?int $collectedBy = null,
        public ?string $paymentMethod = null,
    ) {}

    public function dateLabel(): string
    {
        return $this->from->isSameDay($this->to)
            ? $this->from->format('d M Y')
            : $this->from->format('d M Y') . ' - ' . $this->to->format('d M Y');
    }

    /**
     * Values for links (pagination, export buttons).
     */
    public function toQuery(): array
    {
        return array_filter([
            'from' => $this->from->toDateString(),
            'to' => $this->to->toDateString(),
            'branch_id' => $this->branchId,
            'collected_by' => $this->collectedBy,
            'payment_method' => $this->paymentMethod,
        ], fn ($value) => $value !== null && $value !== '');
    }
}

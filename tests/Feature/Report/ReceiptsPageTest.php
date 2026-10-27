<?php

namespace Tests\Feature\Report;

use App\Models\ExtraIncome;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/** Receipts page: this month by default, 100 rows per page, totals for the whole filter. */
class ReceiptsPageTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    public function test_this_month_by_default_and_paginated(): void
    {
        Carbon::setTestNow('2026-10-27 10:00:00');
        $this->buildInstitute();

        $make = function (string $date, int $n) {
            foreach (range(1, $n) as $i) {
                $income = ExtraIncome::create(['branch_id' => $this->inst['b1'], 'amount' => 10, 'income_date' => $date, 'reason' => 'x',
                    'income_type' => 'extra', 'status' => 'received', 'receipt_number' => 'EXT-' . $date . '-' . $i]);
                $income->created_at = $date . ' 10:00:00';
                $income->save();
            }
        };
        $make('2026-10-05', 120);   // this month
        $make('2025-05-05', 30);    // older

        $this->actingAs($this->inst['admin']);

        $page = $this->get('/receipts')->assertOk();
        $page->assertSee('value="2026-10-01"', false)->assertSee('value="2026-10-27"', false);
        $this->assertSame(120, $page->viewData('receipts')->total());
        $this->assertCount(100, $page->viewData('receipts')->items());
        $this->assertSame(120, $page->viewData('totalReceipts'));
        $this->assertEquals(1200, $page->viewData('totalAmount'));
        $page->assertSee('Showing 1-100 of 120');

        $this->assertCount(20, $this->get('/receipts?page=2')->viewData('receipts')->items());
        $this->assertSame(150, $this->get('/receipts?from_date=2025-01-01&to_date=2026-10-27')->viewData('receipts')->total());

        Carbon::setTestNow();
    }
}

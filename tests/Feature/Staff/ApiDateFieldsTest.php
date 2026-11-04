<?php

namespace Tests\Feature\Staff;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Dates go to the app as Sri Lanka local strings. A Carbon 'date' sent as
 * is becomes "2026-09-30T18:30:00.000000Z" (UTC), and the app showed the
 * October fee as 2026/09 and the paid time 5 h 30 min early.
 */
class ApiDateFieldsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    public function test_today_payments_send_local_month_and_time(): void
    {
        $this->buildInstitute();
        Sanctum::actingAs($this->inst['admin']);

        $row = $this->postJson('/api/payments/today', ['date' => '2026-10-05'])
            ->assertOk()
            ->json('data.0');

        $this->assertSame('2026-10-01', $row['payment_month']);
        $this->assertSame('2026-10-05 09:00:00', $row['paid_at']);
        // for the app receipt (no split saved in the fixture = 0)
        $this->assertEquals(0, $row['hall_fee']);
    }
}

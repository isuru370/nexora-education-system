<?php

namespace Tests\Feature\Staff;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/** Staff app: only an Admin deletes a payment, with a reason (soft delete). */
class PaymentDeleteApiTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    private int $payment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildInstitute();
        $this->payment = (int) DB::table('payments')->where('status', 'completed')->value('id');
        DB::table('payments')->where('id', $this->payment)->update(['created_at' => now()->subDay()]);
    }

    public function test_cashier_cannot_delete(): void
    {
        Sanctum::actingAs($this->inst['cashier']);

        $this->deleteJson("/api/payments/{$this->payment}", ['reason' => 'Wrong class paid'])->assertForbidden();
        $this->assertNull(DB::table('payments')->where('id', $this->payment)->value('deleted_at'));
    }

    public function test_admin_deletes_with_a_reason(): void
    {
        Sanctum::actingAs($this->inst['admin']);

        $this->deleteJson("/api/payments/{$this->payment}", [])->assertStatus(422)->assertJsonValidationErrors('reason');

        $this->deleteJson("/api/payments/{$this->payment}", ['reason' => 'Wrong class paid'])
            ->assertOk()
            ->assertJsonPath('data.delete_reason', 'Wrong class paid');

        $row = DB::table('payments')->where('id', $this->payment)->first();
        $this->assertNotNull($row->deleted_at);                  // kept for audit
        $this->assertSame('Wrong class paid', $row->delete_reason);
        $this->assertSame($this->inst['admin']->id, (int) $row->deleted_by);

        // not in Today Payments any more
        $this->postJson('/api/payments/today', ['date' => '2026-10-05'])->assertOk()->assertJsonCount(0, 'data');

        // already deleted
        $this->deleteJson("/api/payments/{$this->payment}", ['reason' => 'Wrong class paid'])->assertStatus(422);
    }

    public function test_old_payments_cannot_be_deleted(): void
    {
        DB::table('payments')->where('id', $this->payment)->update(['created_at' => now()->subDays(20)]);
        Sanctum::actingAs($this->inst['admin']);

        $this->deleteJson("/api/payments/{$this->payment}", ['reason' => 'Wrong class paid'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'This payment can only be deleted within 14 days.');
    }
}

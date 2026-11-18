<?php

namespace Tests\Feature\Web;

use App\Support\ReceiptRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Settings > Receipt (what the class payment receipt shows) and teacher
 * advance / deduction delete for Admin only.
 */
class ReceiptAndAdvanceDeleteTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-20 10:00:00');
        $this->buildInstitute();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_receipt_shows_everything_until_admin_turns_it_off(): void
    {
        $all = ReceiptRules::all();
        $this->assertTrue($all['teacher']);
        $this->assertTrue($all['receipt_number']);
        $this->assertSame('', $all['footer']);

        $this->actingAs($this->inst['admin'])->get(route('admin.setting.receipt'))
            ->assertOk()->assertSee('Teacher name')->assertSee('Footer line');

        $form = array_fill_keys(array_keys(ReceiptRules::FIELDS), '1');
        $form['teacher'] = '0';
        $form['receipt_number'] = '0';
        $form['footer'] = 'Call 077 123 4567';

        $this->actingAs($this->inst['admin'])->put(route('admin.setting.receipt.update'), $form)
            ->assertRedirect(route('admin.setting.receipt'));

        $all = ReceiptRules::all();
        $this->assertFalse($all['teacher']);
        $this->assertFalse($all['receipt_number']);
        $this->assertTrue($all['student_name']);
        $this->assertSame('Call 077 123 4567', $all['footer']);

        // Staff app reads it
        Sanctum::actingAs($this->inst['cashier']);
        $this->getJson('/api/receipt-settings')->assertOk()
            ->assertJsonPath('data.teacher', false)
            ->assertJsonPath('data.student_name', true)
            ->assertJsonPath('data.footer', 'Call 077 123 4567');

        // Web New Payment page gets it too
        $this->actingAs($this->inst['cashier'])->get(route('new-payment.index'))
            ->assertOk()->assertSee('window.NX_RECEIPT', false)->assertSee('"teacher":false', false);
    }

    public function test_only_admin_can_change_receipt_settings(): void
    {
        $this->actingAs($this->inst['cashier'])->get(route('admin.setting.receipt'))->assertForbidden();

        $this->actingAs($this->inst['cashier'])->put(route('admin.setting.receipt.update'), ['teacher' => '0'])
            ->assertForbidden();

        $this->assertTrue(ReceiptRules::all()['teacher']);
    }

    public function test_footer_is_limited(): void
    {
        $this->actingAs($this->inst['admin'])
            ->put(route('admin.setting.receipt.update'), ['footer' => str_repeat('x', ReceiptRules::FOOTER_MAX + 1)])
            ->assertSessionHasErrors('footer');
    }

    public function test_only_admin_deletes_teacher_advance(): void
    {
        $id = DB::table('teacher_payments')->insertGetId([
            'branch_id' => $this->inst['b1'], 'teacher_id' => $this->inst['teacher'], 'user_id' => $this->inst['admin']->id,
            'payment_type' => 'advance', 'amount' => 500, 'payment_date' => '2026-10-15', 'status' => 'paid',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $branchAdmin = $this->makeRoleUser('BRANCH_ADMIN', $this->inst['b1']);
        $page = route('teacher-salaries.payment-details', [$this->inst['teacher'], 2026, 10]) . '?branch_id=' . $this->inst['b1'];

        // Branch Admin: sees "Admin only", delete is refused
        $this->actingAs($branchAdmin)->get($page)->assertOk()->assertSee('Admin only')
            ->assertDontSee(route('teacher-salaries.payment-delete', $id));
        $this->actingAs($branchAdmin)->delete(route('teacher-salaries.payment-delete', $id))->assertForbidden();
        $this->assertDatabaseHas('teacher_payments', ['id' => $id, 'deleted_at' => null]);

        // Admin: can delete
        $this->actingAs($this->inst['admin'])->get($page)->assertOk()
            ->assertSee(route('teacher-salaries.payment-delete', $id));
        $this->actingAs($this->inst['admin'])->delete(route('teacher-salaries.payment-delete', $id))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('teacher_payments', ['id' => $id]);
    }
}

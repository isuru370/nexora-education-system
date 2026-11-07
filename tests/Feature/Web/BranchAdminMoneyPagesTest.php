<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Extra Income + Expenses: Branch Admins use them for their own branches;
 * extra income always shows in Receipts.
 */
class BranchAdminMoneyPagesTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    protected User $kandyAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildInstitute();

        $this->kandyAdmin = User::forceCreate([
            'name' => 'Kandy Admin', 'email' => 'kandy@test.lk', 'password' => 'password123',
            'user_type_id' => DB::table('user_types')->insertGetId(['name' => 'BRANCH_ADMIN', 'code' => 'BRANCH_ADMIN', 'is_active' => 1]),
            'is_active' => 1,
        ]);
        DB::table('branch_users')->insert(['user_id' => $this->kandyAdmin->id, 'branch_id' => $this->inst['b2']]);
    }

    protected function extra(array $data = []): array
    {
        return $data + [
            'amount' => 1500, 'income_date' => now()->toDateString(), 'reason' => 'Hall rent',
            'income_type' => 'hall_rent', 'status' => 'received',
        ];
    }

    public function test_extra_income_shows_in_receipts(): void
    {
        $this->actingAs($this->inst['admin']);

        // admin, whole institute (no branch) and Colombo
        $this->post('/admin/extra-incomes', $this->extra(['reason' => 'Institute wide']))->assertRedirect();
        $this->post('/admin/extra-incomes', $this->extra(['branch_id' => $this->inst['b1'], 'amount' => 700]))->assertRedirect();

        $numbers = DB::table('extra_incomes')->pluck('receipt_number');
        $this->assertCount(2, $numbers);

        $page = $this->get('/receipts')->assertOk();
        foreach ($numbers as $number) {
            $page->assertSee($number);
        }
    }

    public function test_branch_admin_uses_extra_income_for_their_branch_only(): void
    {
        $colombo = DB::table('extra_incomes')->insertGetId($this->extra(['branch_id' => $this->inst['b1'], 'reason' => 'COLOMBO ROW', 'receipt_number' => 'REC-900001', 'created_at' => now(), 'updated_at' => now()]));

        $this->actingAs($this->kandyAdmin);

        $this->get('/admin/extra-incomes/create')->assertOk()->assertSee('Kandy')->assertDontSee('Whole institute');

        // one branch -> saved to Kandy even without choosing
        $this->post('/admin/extra-incomes', $this->extra(['reason' => 'KANDY ROW']))->assertRedirect();
        $this->assertSame($this->inst['b2'], (int) DB::table('extra_incomes')->where('reason', 'KANDY ROW')->value('branch_id'));

        // another branch is refused
        $this->post('/admin/extra-incomes', $this->extra(['reason' => 'X', 'branch_id' => $this->inst['b1']]))->assertSessionHasErrors('branch_id');

        $this->get('/admin/extra-incomes?month=all')->assertOk()->assertSee('KANDY ROW')->assertDontSee('COLOMBO ROW');
        $this->get('/admin/extra-incomes/' . $colombo)->assertForbidden();

        // receipts: own branch only
        $kandyReceipt = DB::table('extra_incomes')->where('reason', 'KANDY ROW')->value('receipt_number');
        $this->get('/receipts')->assertOk()->assertSee($kandyReceipt)->assertDontSee('REC-900001');
    }

    public function test_branch_admin_uses_expenses_for_their_branch_only(): void
    {
        $colombo = DB::table('institute_payments')->insertGetId([
            'branch_id' => $this->inst['b1'], 'amount' => 999, 'payment_date' => now()->toDateString(), 'reason' => 'COLOMBO EXPENSE',
            'payment_type' => 'expense', 'status' => 'paid', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->kandyAdmin);

        $this->get('/admin/institute-expenses/create')->assertOk()->assertSee('Kandy');
        $this->post('/admin/institute-expenses', ['amount' => 450, 'payment_date' => now()->toDateString(), 'reason' => 'KANDY EXPENSE'])
            ->assertRedirect();
        $this->assertSame($this->inst['b2'], (int) DB::table('institute_payments')->where('reason', 'KANDY EXPENSE')->value('branch_id'));

        $this->get('/admin/institute-expenses')->assertOk()->assertSee('KANDY EXPENSE')->assertDontSee('COLOMBO EXPENSE');
        $this->get('/admin/institute-expenses/' . $colombo)->assertForbidden();

        // the admin still sees both
        $this->actingAs($this->inst['admin'])->get('/admin/institute-expenses')->assertOk()
            ->assertSee('KANDY EXPENSE')->assertSee('COLOMBO EXPENSE');
    }
}

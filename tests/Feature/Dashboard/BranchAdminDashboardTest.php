<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Branch Admin dashboard = the admin dashboard with every number limited
 * to the branches in branch_users (it used to show only zeros).
 */
class BranchAdminDashboardTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    protected User $branchAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildInstitute();

        $this->branchAdmin = User::forceCreate([
            'name' => 'Kandy Admin', 'email' => 'kandy@test.lk', 'password' => 'password123',
            'user_type_id' => DB::table('user_types')->insertGetId(['name' => 'BRANCH_ADMIN', 'code' => 'BRANCH_ADMIN', 'is_active' => 1]),
            'is_active' => 1,
        ]);
        DB::table('branch_users')->insert(['user_id' => $this->branchAdmin->id, 'branch_id' => $this->inst['b2']]);

        // today: Colombo Rs. 3,100, Kandy Rs. 700
        foreach ([[$this->inst['b1'], $this->inst['s1'], $this->inst['e1'], 3100], [$this->inst['b2'], $this->inst['s3'], $this->inst['e3'], 700]] as $i => [$branch, $student, $enrollment, $amount]) {
            DB::table('payments')->insert([
                'branch_id' => $branch, 'student_id' => $student, 'student_class_enrollment_id' => $enrollment,
                'user_id' => $this->inst['admin']->id, 'amount' => $amount, 'paid_at' => now(),
                'payment_month' => now()->startOfMonth()->toDateString(), 'payment_method' => 'cash',
                'status' => 'completed', 'receipt_number' => 'T' . $i,
            ]);
        }
    }

    public function test_branch_admin_sees_only_their_branch(): void
    {
        $this->actingAs($this->branchAdmin)->get('/dashboard')
            ->assertOk()
            ->assertSee('Kandy')
            ->assertSee('here is what is happening at your branch today', false)
            ->assertSee('Rs. 700.00')
            ->assertDontSee('Rs. 3,100.00')
            ->assertDontSee('Rs. 3,800.00')
            ->assertSee('E. Kandy')            // recent payment of the branch
            ->assertSee('Last 6 Months')
            ->assertDontSee('Yearly Payment Report')
            ->assertDontSee('name="branch_id"', false); // one branch -> no picker

        // the admin still sees the whole institute
        $this->actingAs($this->inst['admin'])->get('/dashboard')
            ->assertOk()
            ->assertSee('Rs. 3,800.00')
            ->assertSee('Yearly Payment Report');
    }

    public function test_branch_admin_with_two_branches_can_pick_one(): void
    {
        DB::table('branch_users')->insert(['user_id' => $this->branchAdmin->id, 'branch_id' => $this->inst['b1']]);

        $this->actingAs($this->branchAdmin)->get('/dashboard')
            ->assertOk()->assertSee('All my branches')->assertSee('Rs. 3,800.00');

        $this->get('/dashboard?branch_id=' . $this->inst['b1'])
            ->assertOk()->assertSee('Rs. 3,100.00')->assertDontSee('Rs. 3,800.00');

        // a branch that is not theirs is ignored
        $other = DB::table('branches')->insertGetId(['name' => 'Galle', 'code' => 'GLE', 'is_active' => 1]);
        $this->get('/dashboard?branch_id=' . $other)->assertOk()->assertSee('Rs. 3,800.00');
    }

    public function test_branch_admin_without_a_branch_gets_a_clear_message(): void
    {
        DB::table('branch_users')->where('user_id', $this->branchAdmin->id)->delete();

        $this->actingAs($this->branchAdmin)->get('/dashboard')
            ->assertOk()->assertSee('No branch assigned');
    }
}

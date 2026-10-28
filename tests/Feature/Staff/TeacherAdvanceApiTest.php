<?php

namespace Tests\Feature\Staff;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Staff app: pay a teacher advance (ADMIN / BRANCH_ADMIN only, pay only).
 * The teacher earned 1,200 (60% of a 2,000 October fee) in Colombo.
 */
class TeacherAdvanceApiTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    private User $branchAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-20 10:00:00');
        $this->buildInstitute();
        $i = $this->inst;

        $payment = DB::table('payments')->where('status', 'completed')->value('id');
        DB::table('payment_split_snapshots')->insert([
            'branch_id' => $i['b1'], 'payment_id' => $payment, 'class_fee' => 2000, 'total_fee' => 2000,
            'student_class_id' => $i['c1'], 'student_class_enrollment_id' => $i['e1'],
            'class_payment_config_id' => DB::table('class_payment_configs')->where('student_class_id', $i['c1'])->value('id'),
            'teacher_id' => $i['teacher'], 'payment_amount' => 2000, 'teacher_percentage' => 60,
            'institution_percentage' => 40, 'teacher_amount' => 1200, 'institution_amount' => 800,
            'payment_date' => '2026-10-05 09:00:00', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('teacher_branches')->insert(['teacher_id' => $i['teacher'], 'branch_id' => $i['b1']]);

        $type = DB::table('user_types')->insertGetId(['name' => 'BRANCH_ADMIN', 'code' => 'BRANCH_ADMIN', 'is_active' => 1]);
        $this->branchAdmin = User::forceCreate([
            'name' => 'Branch Admin', 'email' => 'ba@test.lk', 'password' => 'password123',
            'user_type_id' => $type, 'is_active' => 1,
        ]);
        DB::table('branch_users')->insert(['user_id' => $this->branchAdmin->id, 'branch_id' => $i['b1']]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_cashier_cannot_use_it(): void
    {
        Sanctum::actingAs($this->inst['cashier']);

        $this->getJson('/api/teacher-advances/teachers')->assertForbidden();
        $this->postJson('/api/teacher-advances', [
            'teacher_id' => $this->inst['teacher'], 'branch_id' => $this->inst['b1'], 'amount' => 100,
        ])->assertForbidden();
        $this->assertSame(0, DB::table('teacher_payments')->count());
    }

    public function test_branch_admin_sees_teachers_of_own_branch_with_available_amount(): void
    {
        Sanctum::actingAs($this->branchAdmin);

        $this->getJson('/api/teacher-advances/teachers')   // only one branch: used without branch_id
            ->assertOk()
            ->assertJsonPath('data.branch.id', $this->inst['b1'])
            ->assertJsonPath('data.month', 'October 2026')
            ->assertJsonPath('data.teachers.0.name', 'Nimal Perera')
            ->assertJsonPath('data.teachers.0.gross', 1200)
            ->assertJsonPath('data.teachers.0.available', 1200);

        $this->getJson('/api/teacher-advances/teachers?branch_id=' . $this->inst['b2'])->assertForbidden();
    }

    public function test_pay_an_advance_and_the_limit_goes_down(): void
    {
        Sanctum::actingAs($this->branchAdmin);
        $i = $this->inst;

        $this->postJson('/api/teacher-advances', [
            'teacher_id' => $i['teacher'], 'branch_id' => $i['b1'], 'amount' => 500, 'reason' => 'Transport',
        ])
            ->assertCreated()
            ->assertJsonPath('data.teacher.name', 'Nimal Perera')
            ->assertJsonPath('data.amount', 500)
            ->assertJsonPath('data.date', '2026-10-20')
            ->assertJsonPath('data.paid_by', 'Branch Admin')
            ->assertJsonPath('data.month_advance_total', 500)
            ->assertJsonPath('data.available_after', 700);

        $this->assertDatabaseHas('teacher_payments', [
            'teacher_id' => $i['teacher'], 'branch_id' => $i['b1'], 'payment_type' => 'advance',
            'amount' => 500, 'user_id' => $this->branchAdmin->id, 'status' => 'paid', 'reason' => 'Transport',
        ]);
        $this->assertSame('2026-10-20', substr(DB::table('teacher_payments')->value('payment_date'), 0, 10));

        // more than what is left: refused
        $this->postJson('/api/teacher-advances', ['teacher_id' => $i['teacher'], 'branch_id' => $i['b1'], 'amount' => 701])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Amount exceeds the available limit for October 2026. Available: LKR 700.00');

        $this->assertSame(1, DB::table('teacher_payments')->count());
    }

    public function test_not_after_the_month_salary_is_paid(): void
    {
        Sanctum::actingAs($this->inst['admin']);
        $i = $this->inst;

        DB::table('teacher_salaries')->insert([
            'branch_id' => $i['b1'], 'teacher_id' => $i['teacher'], 'salary_year' => 2026, 'salary_month' => 10,
            'gross_amount' => 1200, 'net_amount' => 1200, 'status' => 'paid', 'paid_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->getJson('/api/teacher-advances/teachers?branch_id=' . $i['b1'])
            ->assertJsonPath('data.teachers.0.salary_paid', true)
            ->assertJsonPath('data.teachers.0.available', 0);

        $this->postJson('/api/teacher-advances', ['teacher_id' => $i['teacher'], 'branch_id' => $i['b1'], 'amount' => 100])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Salary for October 2026 is already paid. Advances and deductions cannot be added.');
    }

    public function test_dashboard_shows_the_menu_item_to_admins_only(): void
    {
        Sanctum::actingAs($this->branchAdmin);
        $this->assertContains('teacher_advance', $this->getJson('/api/mobile-dashboard')->json('data.features') ?? []);

        Sanctum::actingAs($this->inst['cashier']);
        $this->assertNotContains('teacher_advance', $this->getJson('/api/mobile-dashboard')->json('data.features') ?? []);
    }
}

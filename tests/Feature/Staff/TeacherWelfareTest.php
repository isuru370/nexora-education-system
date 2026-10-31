<?php

namespace Tests\Feature\Staff;

use App\Models\User;
use App\Services\Web\TeacherSalary\TeacherSalaryService;
use App\Support\Features;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Teacher welfare (feature "welfare"): admin takes a monthly amount from a
 * teacher's salary per branch; it is the branch welfare fund (not income),
 * shown on the salary slip; fund expenses are recorded.
 * The teacher earned 1,200 in October in Colombo (b1).
 */
class TeacherWelfareTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    private User $branchAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-29 10:00:00');
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

        $type = DB::table('user_types')->where('code', 'BRANCH_ADMIN')->value('id')
            ?? DB::table('user_types')->insertGetId(['name' => 'BRANCH_ADMIN', 'code' => 'BRANCH_ADMIN', 'is_active' => 1]);
        $this->branchAdmin = User::forceCreate([
            'name' => 'Branch Admin', 'email' => 'ba@welfare.test', 'password' => 'password123',
            'user_type_id' => $type, 'is_active' => 1,
        ]);
        DB::table('branch_users')->insert(['user_id' => $this->branchAdmin->id, 'branch_id' => $i['b1']]);

        Features::set('welfare', true);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Features::flush();
        parent::tearDown();
    }

    private function take(float $amount, array $extra = [])
    {
        return $this->actingAs($this->inst['admin'])->post('/welfare/deduct', $extra + [
            'branch_id' => $this->inst['b1'], 'year' => 2026, 'month' => 10,
            'teachers' => [$this->inst['teacher']], 'amounts' => [$this->inst['teacher'] => $amount],
        ]);
    }

    public function test_pages_are_hidden_when_the_feature_is_off_and_for_cashiers(): void
    {
        Features::set('welfare', false);
        $this->actingAs($this->inst['admin'])->get('/welfare')->assertNotFound();

        Features::set('welfare', true);
        $this->actingAs($this->inst['cashier'])->get('/welfare')->assertForbidden();
        $this->actingAs($this->inst['admin'])->get('/welfare')->assertOk()->assertSee('Teacher Welfare');
        $this->actingAs($this->inst['admin'])->get('/welfare/fund')->assertOk()->assertSee('Welfare Fund');
    }

    public function test_monthly_amount_is_saved_per_branch_and_shown_for_the_month(): void
    {
        $this->actingAs($this->inst['admin'])->put('/welfare/amounts', [
            'branch_id' => $this->inst['b1'], 'amounts' => [$this->inst['teacher'] => 300],
        ])->assertSessionHas('success');

        $this->assertEquals(300, DB::table('teacher_branches')
            ->where('teacher_id', $this->inst['teacher'])->where('branch_id', $this->inst['b1'])->value('welfare_amount'));

        $this->actingAs($this->inst['admin'])->get('/welfare?year=2026&month=10&branch_id=' . $this->inst['b1'])
            ->assertOk()->assertSee('value="300"', false);
    }

    public function test_welfare_lowers_the_salary_and_shows_on_the_slip(): void
    {
        $this->take(300)->assertSessionHas('success');
        $this->take(100)->assertSessionHas('error');   // once a month

        $salary = app(TeacherSalaryService::class);
        $this->actingAs($this->inst['admin']);
        $details = $salary->getTeacherSalaryDetails($this->inst['teacher'], $this->inst['b1'], 2026, 10);
        $this->assertEquals(300, $details['welfare']);
        $this->assertEquals(900, $details['calculated_net']);

        $slip = $salary->salarySlip($this->inst['teacher'], $this->inst['b1'], 2026, 10);
        $this->assertContains(['description' => 'Welfare', 'amount' => 300.0], $slip['deductions']);
        $this->assertEquals(900, $slip['net_salary']);

        // Pay the salary: welfare saved on the record, then locked
        $this->post('/teacher-salaries/' . $this->inst['teacher'] . '/pay', [
            'branch_id' => $this->inst['b1'], 'year' => 2026, 'month' => 10,
        ]);
        $record = DB::table('teacher_salaries')->where('teacher_id', $this->inst['teacher'])->first();
        $this->assertNotNull($record, 'salary paid');
        $this->assertEquals(300, $record->welfare_deduction);
        $this->assertEquals(900, $record->net_amount);

        $id = DB::table('welfare_deductions')->value('id');
        $this->delete('/welfare/deductions/' . $id)->assertSessionHas('error');
        $this->assertNull(DB::table('welfare_deductions')->where('id', $id)->value('deleted_at'));
    }

    public function test_not_more_than_the_salary_and_not_for_another_branch(): void
    {
        $this->take(1500)->assertSessionHas('error');
        $this->assertSame(0, DB::table('welfare_deductions')->count());

        // Branch admin of b1 cannot use b2
        $this->actingAs($this->branchAdmin)->post('/welfare/deduct', [
            'branch_id' => $this->inst['b2'], 'year' => 2026, 'month' => 10,
            'teachers' => [$this->inst['teacher']], 'amounts' => [$this->inst['teacher'] => 100],
        ])->assertSessionHas('error');
        $this->assertSame(0, DB::table('welfare_deductions')->count());
    }

    public function test_cancel_gives_it_back_before_the_salary_is_paid(): void
    {
        $this->take(200);
        $id = DB::table('welfare_deductions')->value('id');

        $this->delete('/welfare/deductions/' . $id)->assertSessionHas('success');
        $this->assertNotNull(DB::table('welfare_deductions')->where('id', $id)->value('deleted_at'));

        $this->actingAs($this->inst['admin']);
        $details = app(TeacherSalaryService::class)->getTeacherSalaryDetails($this->inst['teacher'], $this->inst['b1'], 2026, 10);
        $this->assertEquals(1200, $details['calculated_net']);
    }

    public function test_fund_balance_expenses_and_pdf(): void
    {
        $this->take(400);

        $expense = fn (float $amount) => $this->actingAs($this->inst['admin'])->post('/welfare/fund/expenses', [
            'branch_id' => $this->inst['b1'], 'expense_date' => '2026-10-28',
            'description' => 'Funeral help', 'paid_to' => 'Mr. X', 'amount' => $amount,
        ]);

        $expense(500)->assertSessionHas('error');   // fund has only 400
        $expense(150)->assertSessionHas('success');

        $this->actingAs($this->inst['admin'])->get('/welfare/fund?year=2026&month=10&branch_id=' . $this->inst['b1'])
            ->assertOk()
            ->assertSee('Funeral help')
            ->assertSee('250.00');   // closing balance

        // Money already spent: that welfare cannot be given back
        DB::table('welfare_expenses')->update(['amount' => 400]);
        $this->delete('/welfare/deductions/' . DB::table('welfare_deductions')->value('id'))->assertSessionHas('error');

        $this->actingAs($this->inst['admin'])->get('/welfare/fund/pdf?year=2026&month=10&branch_id=' . $this->inst['b1'])
            ->assertOk()->assertHeader('content-type', 'application/pdf');

        // Not institute income
        $this->assertSame(0, DB::table('teacher_payments')->count());
    }
}

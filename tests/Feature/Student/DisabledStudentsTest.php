<?php

namespace Tests\Feature\Student;

use App\Models\GradeClosure;
use App\Models\StudentAutoDisable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Disabled Students page: every disabled student with the reason, enable
 * again (both flags), optionally back into the classes taken away.
 */
class DisabledStudentsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildInstitute();

        // s1: auto-disabled, taken out of C1 (e1)
        DB::table('students')->where('id', $this->inst['s1'])->update(['is_active' => 0, 'student_disable' => 1]);
        DB::table('student_class_enrollments')->where('id', $this->inst['e1'])->update(['is_active' => 0, 'left_at' => '2026-11-01']);
        StudentAutoDisable::create([
            'student_id' => $this->inst['s1'], 'branch_id' => $this->inst['b1'], 'disabled_at' => '2026-11-01 00:00:00',
            'classes' => [['enrollment_id' => $this->inst['e1'], 'class' => 'Maths 10']],
        ]);

        // s2: made inactive by staff
        DB::table('students')->where('id', $this->inst['s2'])->update(['is_active' => 0]);

        // s3 (Kandy): batch closed
        DB::table('students')->where('id', $this->inst['s3'])->update(['is_active' => 0, 'student_disable' => 1]);
        GradeClosure::create([
            'grade_names' => ['2026 A/L'], 'counts' => ['students' => 1],
            'changes' => ['students' => [['id' => $this->inst['s3'], 'is_active' => 1, 'student_disable' => 0]], 'enrollments' => [['id' => $this->inst['e3'], 'is_active' => 1, 'left_at' => null]]],
            'source' => 'web', 'closed_at' => '2026-11-02 10:00:00',
        ]);
    }

    public function test_page_lists_every_disabled_student_with_the_reason(): void
    {
        $this->actingAs($this->inst['admin'])->get('/disabled-students')
            ->assertOk()
            ->assertSeeInOrder(['All', '3'])
            ->assertSee('No class for 2 months')->assertSee('Maths 10')
            ->assertSee('Made inactive')
            ->assertSee('Batch closed')->assertSee('2026 A/L');

        $this->get('/disabled-students?reason=auto')->assertOk()->assertSee('S1')->assertDontSee('E. Kandy');
        $this->get('/disabled-students?reason=batch')->assertOk()->assertSee('E. Kandy')->assertDontSee('B. Perera');
        $this->get('/disabled-students?search=Perera&reason=manual')->assertOk()->assertSee('B. Perera');
    }

    public function test_enable_clears_both_flags_and_puts_the_student_back_in_class(): void
    {
        $this->actingAs($this->inst['admin'])
            ->post('/disabled-students/enable', ['only' => $this->inst['s1'], 'restore_classes' => 1])
            ->assertSessionHas('success', '1 student enabled. Put back in 1 class.');

        $student = DB::table('students')->find($this->inst['s1']);
        $this->assertSame(1, (int) $student->is_active);
        $this->assertSame(0, (int) $student->student_disable);
        $this->assertSame(1, (int) DB::table('student_class_enrollments')->where('id', $this->inst['e1'])->value('is_active'));
        $this->assertNotNull(StudentAutoDisable::query()->where('student_id', $this->inst['s1'])->value('reactivated_at'));
        $this->assertDatabaseHas('student_reactivations', [
            'student_id' => $this->inst['s1'], 'reason' => 'auto', 'classes_restored' => 1, 'enabled_by' => $this->inst['admin']->id,
        ]);
    }

    public function test_bulk_enable_without_classes(): void
    {
        $this->actingAs($this->inst['admin'])
            ->post('/disabled-students/enable', ['ids' => [$this->inst['s1'], $this->inst['s2']], 'restore_classes' => 0])
            ->assertSessionHas('success', '2 students enabled.');

        $this->assertSame(0, (int) DB::table('student_class_enrollments')->where('id', $this->inst['e1'])->value('is_active'));
        $this->assertSame(2, DB::table('students')->whereIn('id', [$this->inst['s1'], $this->inst['s2']])->where('is_active', 1)->where('student_disable', 0)->count());
    }

    public function test_cashier_sees_and_enables_only_their_branch(): void
    {
        // cashier is in Colombo (b1); s3 is in Kandy
        $this->actingAs($this->inst['cashier'])->get('/disabled-students')
            ->assertOk()->assertSee('B. Perera')->assertDontSee('E. Kandy');

        $this->post('/disabled-students/enable', ['only' => $this->inst['s3'], 'restore_classes' => 1])
            ->assertSessionHas('error');
        $this->assertSame(1, (int) DB::table('students')->where('id', $this->inst['s3'])->value('student_disable'));
    }

    public function test_activating_from_the_students_list_clears_student_disable(): void
    {
        $this->actingAs($this->inst['admin'])->patch('/students/' . $this->inst['s1'] . '/toggle-active');

        $student = DB::table('students')->find($this->inst['s1']);
        $this->assertSame(1, (int) $student->is_active);
        $this->assertSame(0, (int) $student->student_disable);
        $this->assertNotNull(StudentAutoDisable::query()->where('student_id', $this->inst['s1'])->value('reactivated_at'));
    }
}

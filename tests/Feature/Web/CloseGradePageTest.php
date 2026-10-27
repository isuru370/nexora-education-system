<?php

namespace Tests\Feature\Web;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * "Close Batch (Grade)" page: admin only; preview changes nothing; close,
 * view details and undo.
 */
class CloseGradePageTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    private int $grade;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-01 10:00:00');
        $this->buildInstitute();
        $this->grade = (int) DB::table('grades')->where('grade_name', '10')->value('id');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_only_admin_can_open_it(): void
    {
        $this->actingAs($this->inst['cashier'])->get('/close-grade')->assertForbidden();
        $this->actingAs($this->inst['cashier'])->post('/close-grade', ['grade_ids' => [$this->grade]])->assertForbidden();
        $this->assertTrue((bool) DB::table('students')->where('id', $this->inst['s1'])->value('is_active'));

        $this->actingAs($this->inst['admin'])->get('/close-grade')
            ->assertOk()
            ->assertSee('Close Batch (Grade)')
            ->assertSee('No grade has been closed yet.');
    }

    public function test_preview_changes_nothing(): void
    {
        $this->actingAs($this->inst['admin'])
            ->get('/close-grade?grade_ids[]=' . $this->grade)
            ->assertOk()
            ->assertSee('Preview: 10')
            ->assertSee('Maths 10 CMB')
            ->assertSee('A. Silva')
            ->assertSee('Close 10');

        $this->assertSame(0, DB::table('students')->where('is_active', false)->count());
        $this->assertSame(0, DB::table('grade_closures')->count());
    }

    public function test_close_view_and_undo(): void
    {
        $admin = $this->inst['admin'];

        $response = $this->actingAs($admin)->post('/close-grade', ['grade_ids' => [$this->grade]]);
        $closure = DB::table('grade_closures')->first();
        $response->assertRedirect('/close-grade/' . $closure->id);

        $this->assertSame($admin->id, (int) $closure->closed_by);
        $this->assertSame(3, DB::table('students')->where('is_active', false)->count());
        $this->assertSame(0, DB::table('student_classes')->where('is_active', true)->count());

        $this->actingAs($admin)->get('/close-grade/' . $closure->id)
            ->assertOk()
            ->assertSee('Maths 10 KDY')
            ->assertSee('E. Kandy')
            ->assertSee('Undo');

        $this->actingAs($admin)->get('/close-grade')->assertOk()->assertSee('Closed');

        $this->actingAs($admin)->post('/close-grade/' . $closure->id . '/undo')
            ->assertRedirect('/close-grade/' . $closure->id);

        $this->assertSame(0, DB::table('students')->where('is_active', false)->count());
        $this->assertSame(0, DB::table('student_classes')->where('is_active', false)->count());
        $this->assertSame($admin->id, (int) DB::table('grade_closures')->value('undone_by'));

        // undo twice: refused
        $this->actingAs($admin)->post('/close-grade/' . $closure->id . '/undo')
            ->assertSessionHas('error', 'This close was already undone.');
    }

    public function test_newer_close_must_be_undone_first(): void
    {
        $admin = $this->inst['admin'];
        $other = DB::table('grades')->insertGetId(['grade_name' => '2026 A/L']);

        $this->actingAs($admin)->post('/close-grade', ['grade_ids' => [$this->grade]]);
        $this->actingAs($admin)->post('/close-grade', ['grade_ids' => [$other]]);
        [$first] = DB::table('grade_closures')->orderBy('id')->pluck('id')->all();

        $this->actingAs($admin)->post("/close-grade/$first/undo")
            ->assertSessionHas('error', 'Undo the newer close first.');
    }
}

<?php

namespace Tests\Feature\Web;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Editing a schedule pattern (e.g. turning it off, changing the hall)
 * without changing the time failed with "Duplicate entry ... cs_unique_slot":
 * the future schedules were soft deleted and the same slots inserted again.
 */
class PatternUpdateTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_pattern_can_be_edited_without_changing_the_time(): void
    {
        Carbon::setTestNow('2026-11-04 07:00:00');
        $this->buildInstitute();
        DB::table('class_category_fees')->where('id', $this->inst['f1'])->update(['is_active' => 1]);
        DB::table('student_classes')->where('id', $this->inst['c1'])->update(['is_active' => 1, 'is_ongoing' => 1]);
        DB::table('class_categories')->update(['is_active' => 1]);

        $pattern = (int) DB::table('class_schedule_patterns')->where('student_class_id', $this->inst['c1'])->value('id');
        $hall = DB::table('class_halls')->insertGetId([
            'branch_id' => $this->inst['b1'], 'code' => 'HA', 'hall_name' => 'Hall A', 'hall_price' => 50,
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $form = [
            'class_day' => 'tuesday', 'class_hall_id' => $hall, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31',
            'start_time' => '15:00', 'end_time' => '17:00', 'is_active' => 1, 'note' => null,
        ];

        $this->actingAs($this->inst['admin']);

        // 1st save makes the future Tuesdays, 2nd and 3rd saves (same time) used to fail
        $this->put("/class-schedules/patterns/{$pattern}", $form)->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->put("/class-schedules/patterns/{$pattern}", $form)->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->put("/class-schedules/patterns/{$pattern}", ['is_active' => 0] + $form)
            ->assertSessionHasNoErrors()->assertSessionMissing('error');

        $future = DB::table('class_schedules')
            ->where('class_schedule_pattern_id', $pattern)
            ->whereDate('class_date', '>=', '2026-11-04')
            ->whereNull('deleted_at');

        // Tuesdays from 2026-11-04 to 2026-12-31 = 8, once each, inactive, in Hall A
        $this->assertSame(8, (clone $future)->count());
        $this->assertSame(0, (clone $future)->where('is_active', 1)->count());
        $this->assertSame(8, (clone $future)->where('class_hall_id', $hall)->count());
        $this->assertSame(0, (int) DB::table('class_schedule_patterns')->where('id', $pattern)->value('is_active'));
    }
}

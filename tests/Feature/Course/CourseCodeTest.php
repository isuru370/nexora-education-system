<?php

namespace Tests\Feature\Course;

use App\Models\Course;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\BuildsCourse;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/** Course codes are made by the system: branch code + C + start year + number. */
class CourseCodeTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;
    use BuildsCourse;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-24 10:00:00');
        $this->buildInstitute();
        Features::set('courses', true);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_codes_follow_branch_year_and_number(): void
    {
        $first = $this->buildCourse();                                    // Colombo, starts 2026
        $second = $this->buildCourse(['name' => 'Diploma in English']);   // Colombo, 2026
        $kandy = $this->buildCourse(['branch_id' => $this->inst['b2']]);  // Kandy, 2026
        $next = $this->buildCourse(['start_date' => '2027-01-10', 'end_date' => '2027-06-30']); // Colombo, 2027

        $this->assertSame('CMB-C26001', $first->code);
        $this->assertSame('CMB-C26002', $second->code);
        $this->assertSame('KDY-C26001', $kandy->code);
        $this->assertSame('CMB-C27001', $next->code);

        // A deleted course's number is not given again
        $second->delete();
        $this->assertSame('CMB-C26003', $this->buildCourse(['name' => 'Another'])->code);
    }

    public function test_the_code_cannot_be_typed_or_changed(): void
    {
        $course = $this->buildCourse();
        $this->actingAs($this->inst['admin']);

        $this->get('/courses/create')->assertOk()->assertSee('Made on save');
        $this->get("/courses/{$course->id}/edit")->assertOk()->assertSee('value="CMB-C26001"', false);

        $this->put("/courses/{$course->id}", [
            'branch_id' => $this->inst['b1'], 'name' => 'Renamed', 'code' => 'HACK', 'full_fee' => 12000, 'one_time_fee' => 10000,
            'start_date' => '2026-11-01', 'end_date' => '2027-04-30',
            'days' => [['day' => 'sunday', 'start_time' => '08:00', 'end_time' => '12:00']],
            'teachers' => [['teacher_id' => $this->inst['teacher'], 'percentage' => 35]],
        ])->assertRedirect();

        $this->assertSame('CMB-C26001', $course->fresh()->code);
        $this->assertSame('Renamed', $course->fresh()->name);
        $this->assertSame(1, Course::where('code', 'CMB-C26001')->count());
    }
}

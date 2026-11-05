<?php

namespace Tests\Feature\Cards;

use App\Support\CardMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * Generate Student ID shows 12 students a page. With more than 12 the
 * page links used a view this Laravel does not have (bootstrap-5): 500.
 */
class GenerateStudentIdPaginationTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    public function test_page_with_more_than_12_students_has_page_links(): void
    {
        $this->buildInstitute();
        CardMode::set('temporary');

        $grade = DB::table('grades')->value('id');
        for ($i = 1; $i <= 20; $i++) {
            DB::table('students')->insert([
                'custom_id' => 'P' . $i, 'branch_id' => $this->inst['b1'], 'initial_name' => 'Pupil ' . $i,
                'full_name' => 'Pupil ' . $i, 'guardian_mobile' => '0771234567', 'grade_id' => $grade,
                'img_url' => '', 'permanent_qr_active' => 1, 'is_active' => 1, 'created_at' => now(),
            ]);
        }

        $this->actingAs($this->inst['admin'])
            ->get('/generate-student-id')
            ->assertOk()
            ->assertSee('page=2', false);

        $this->actingAs($this->inst['admin'])->get('/generate-student-id?page=2')->assertOk();
    }
}

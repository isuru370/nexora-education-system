<?php

use App\Services\Course\CourseCodeService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Course codes are made by the system (CMB-C26001) and are unique.
 * Courses made before this get a code here.
 */
class MakeCourseCodesUnique extends Migration
{
    public function up(): void
    {
        $codes = app(CourseCodeService::class);

        DB::transaction(function () use ($codes) {
            // Old hand-typed codes are replaced so every code follows the format
            DB::table('courses')->update(['code' => null]);

            DB::table('courses')->orderBy('id')->get(['id', 'branch_id', 'start_date'])
                ->each(fn ($course) => DB::table('courses')->where('id', $course->id)
                    ->update(['code' => $codes->next((int) $course->branch_id, $course->start_date)]));
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->unique('code', 'courses_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropUnique('courses_code_unique');
        });
    }
}

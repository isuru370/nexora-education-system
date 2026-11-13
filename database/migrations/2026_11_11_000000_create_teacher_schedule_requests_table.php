<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class day changes a teacher asks for from the staff app (Settings >
 * Teacher App). Applied at once when "schedule_auto" is on, otherwise an
 * admin approves or rejects them.
 */
class CreateTeacherScheduleRequestsTable extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_schedule_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();

            $table->string('kind', 10);        // class | course
            $table->string('action', 10);      // create | update | cancel

            $table->foreignId('student_class_id')->nullable()->constrained('student_classes')->cascadeOnDelete();
            $table->foreignId('class_schedule_id')->nullable()->constrained('class_schedules')->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained('courses')->cascadeOnDelete();
            $table->foreignId('course_session_id')->nullable()->constrained('course_sessions')->cascadeOnDelete();

            $table->json('payload')->nullable();   // date, times, hall, category, reason
            $table->string('status', 10)->default('pending'); // pending | approved | rejected | failed
            $table->text('error')->nullable();

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note', 255)->nullable();
            $table->timestamps();

            $table->index(['status', 'branch_id']);
            $table->index(['teacher_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_schedule_requests');
    }
}

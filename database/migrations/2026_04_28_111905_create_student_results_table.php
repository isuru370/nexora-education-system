<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_results', function (Blueprint $table) {
            $table->id();

            // 🔥 Branch ownership (denormalized from exams)
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->foreignId('exam_id')
                ->constrained('exams')
                ->cascadeOnDelete();

            // user delete වුණාට result delete වෙන්න එපා
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // marks
            $table->decimal('marks', 6, 2)->nullable();
            $table->decimal('max_marks', 6, 2)->default(100);

            $table->decimal('percentage', 6, 2)->nullable();
            $table->string('grade', 10)->nullable();
            $table->integer('rank')->nullable();

            $table->enum('status', [
                'pending',
                'passed',
                'failed',
                'absent'
            ])->default('pending');

            $table->string('reason', 255)->nullable();

            $table->text('remark')->nullable();

            $table->boolean('is_updated')->default(false);

            $table->boolean('is_absent')->default(false);

            $table->timestamps();
            $table->softDeletes();

            // prevent duplicate results
            $table->unique(
                ['student_id', 'exam_id'],
                'unique_student_exam'
            );

            // 🔥 Branch-scoped indexes (NEW)
            $table->index(
                ['branch_id', 'status'],
                'sr_branch_status_idx'
            );

            $table->index(
                ['branch_id', 'exam_id', 'status'],
                'sr_branch_exam_status_idx'
            );

            $table->index(
                ['branch_id', 'student_id'],
                'sr_branch_student_idx'
            );

            // පරණ indexes (still useful)
            $table->index(['exam_id', 'status'], 'sr_exam_status_idx');
            $table->index(['student_id', 'status'], 'sr_student_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_results');
    }
};
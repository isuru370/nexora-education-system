<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();

            // 🔥 Branch ownership (denormalized from student_class)
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();

            $table->string('title', 150);

            $table->foreignId('student_class_id')
                ->constrained('student_classes')
                ->cascadeOnDelete();

            $table->foreignId('class_category_id')
                ->constrained('class_categories')
                ->restrictOnDelete();

            $table->foreignId('class_hall_id')
                ->nullable()
                ->constrained('class_halls')
                ->nullOnDelete();

            $table->date('exam_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            $table->enum('status', [
                'scheduled',
                'ongoing',
                'completed',
                'cancelled',
            ])->default('scheduled');

            $table->text('cancel_reason')->nullable();

            $table->foreignId('cancelled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('cancelled_at')->nullable();

            $table->text('note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // 🔥 Branch-scoped indexes (මේවා තමයි important)
            $table->index(
                ['branch_id', 'exam_date', 'status'],
                'ex_branch_date_status_idx'
            );

            $table->index(
                ['branch_id', 'student_class_id', 'exam_date'],
                'ex_branch_class_date_idx'
            );

            // පරණ indexes (still useful)
            $table->index(['student_class_id', 'exam_date'], 'ex_class_date_idx');
            $table->index(['class_category_id', 'exam_date'], 'ex_category_date_idx');
            $table->index(['status', 'exam_date'], 'ex_status_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_tutes', function (Blueprint $table) {
            $table->id();

            // 🔥 Branch ownership (denormalized from student)
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();

            // student
            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            // enrollment (which class + package)
            $table->foreignId('student_class_enrollment_id')
                ->constrained('student_class_enrollments')
                ->cascadeOnDelete();

            $table->date('issued_month');

            // 🔥 issue tracking
            $table->boolean('is_issued')->default(false);
            $table->dateTime('issued_at')->nullable();

            $table->foreignId('issued_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // notes
            $table->text('note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['student_id', 'student_class_enrollment_id', 'issued_month'],
                'student_tute_unique'
            );

            /*
            |--------------------------------------------------------------------------
            | 🔥 Branch-scoped indexes (NEW)
            |--------------------------------------------------------------------------
            */
            $table->index(
                ['branch_id', 'issued_month', 'is_issued'],
                'st_branch_month_issued_idx'
            );

            $table->index(
                ['branch_id', 'student_id', 'issued_month'],
                'st_branch_student_month_idx'
            );

            $table->index(
                ['branch_id', 'student_class_enrollment_id', 'issued_month'],
                'st_branch_enrollment_month_idx'
            );

            /*
            |--------------------------------------------------------------------------
            | පරණ indexes (still useful)
            |--------------------------------------------------------------------------
            */
            $table->index(['student_id', 'is_issued'], 'st_student_issued_idx');
            $table->index(['student_class_enrollment_id'], 'st_enrollment_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_tutes');
    }
};
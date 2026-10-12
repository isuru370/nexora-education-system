<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_schedule_patterns', function (Blueprint $table) {
            $table->id();

            // 🔥 Branch ownership (denormalized from student_class)
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();

            $table->foreignId('student_class_id')
                ->constrained('student_classes')
                ->cascadeOnDelete();

            $table->foreignId('class_category_fee_id')
                ->constrained('class_category_fees')
                ->restrictOnDelete();

            $table->foreignId('class_hall_id')
                ->nullable()
                ->constrained('class_halls')
                ->nullOnDelete();

            $table->date('start_date');
            $table->date('end_date');

            $table->time('start_time');
            $table->time('end_time');

            $table->enum('class_day', [
                'monday',
                'tuesday',
                'wednesday',
                'thursday',
                'friday',
                'saturday',
                'sunday',
            ]);

            $table->boolean('is_active')->default(true);
            $table->text('note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | 🔥 Branch-scoped indexes (NEW)
            |--------------------------------------------------------------------------
            */
            $table->index(
                ['branch_id', 'start_date', 'end_date'],
                'csp_branch_date_idx'
            );

            $table->index(
                ['branch_id', 'class_hall_id'],
                'csp_branch_hall_idx'
            );

            $table->index(
                ['branch_id', 'student_class_id', 'is_active'],
                'csp_branch_class_active_idx'
            );

            /*
            |--------------------------------------------------------------------------
            | පරණ indexes (still useful)
            |--------------------------------------------------------------------------
            */
            $table->index(['student_class_id', 'start_date', 'end_date'], 'csp_class_date_idx');
            $table->index(['class_category_fee_id', 'start_date'], 'csp_category_fee_date_idx');
            $table->index(['class_hall_id', 'start_date'], 'csp_hall_date_idx');
            $table->index(['class_day', 'start_date'], 'csp_day_date_idx');
            $table->index(['is_active', 'start_date'], 'csp_active_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_schedule_patterns');
    }
};

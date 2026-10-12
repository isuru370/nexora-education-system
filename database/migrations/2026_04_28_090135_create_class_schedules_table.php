<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();

            // 🔥 Branch ownership (denormalized from student_class)
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();

            $table->foreignId('class_schedule_pattern_id')
                ->nullable()
                ->constrained('class_schedule_patterns')
                ->nullOnDelete();

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

            $table->date('class_date');

            $table->time('start_time');
            $table->time('end_time');

            $table->enum('day_of_week', [
                'monday',
                'tuesday',
                'wednesday',
                'thursday',
                'friday',
                'saturday',
                'sunday',
            ]);

            $table->enum('status', [
                'scheduled',
                'ongoing',
                'completed',
                'cancelled',
            ])->default('scheduled');

            $table->boolean('is_active')->default(true);

            $table->text('cancel_reason')->nullable();

            $table->foreignId('cancelled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('cancelled_at')->nullable();

            $table->text('note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Prevent duplicate slots
            $table->unique([
                'student_class_id',
                'class_category_fee_id',
                'class_date',
                'start_time',
                'end_time',
            ], 'cs_unique_slot');

            /*
            |--------------------------------------------------------------------------
            | 🔥 Branch-scoped indexes (NEW)
            |--------------------------------------------------------------------------
            */
            $table->index(
                ['branch_id', 'class_date', 'status'],
                'cs_branch_date_status_idx'
            );

            $table->index(
                ['branch_id', 'class_hall_id', 'class_date'],
                'cs_branch_hall_date_idx'
            );

            $table->index(
                ['branch_id', 'student_class_id', 'class_date'],
                'cs_branch_class_date_idx'
            );

            $table->index(
                ['branch_id', 'day_of_week', 'class_date'],
                'cs_branch_day_date_idx'
            );

            /*
            |--------------------------------------------------------------------------
            | පරණ indexes (still useful)
            |--------------------------------------------------------------------------
            */
            $table->index(['class_schedule_pattern_id', 'class_date'], 'cs_pattern_date_idx');
            $table->index(['student_class_id', 'class_date'], 'cs_class_date_idx');
            $table->index(['class_category_fee_id', 'class_date'], 'cs_category_fee_date_idx');
            $table->index(['class_hall_id', 'class_date'], 'cs_hall_date_idx');
            $table->index(['status', 'class_date'], 'cs_status_date_idx');
            $table->index(['is_active', 'class_date'], 'cs_active_date_idx');
            $table->index(['day_of_week', 'class_date'], 'cs_day_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_schedules');
    }
};
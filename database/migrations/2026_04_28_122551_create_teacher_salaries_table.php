<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_salaries', function (Blueprint $table) {
            $table->id();

            // 🔥 Branch ownership (which branch does this salary belong to)
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();

            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->year('salary_year');
            $table->unsignedTinyInteger('salary_month');

            $table->decimal('gross_amount', 12, 2)->default(0);
            $table->decimal('advance_deduction', 12, 2)->default(0);
            $table->decimal('other_deduction', 12, 2)->default(0);

            // 🔥 FIX: bonus_amount add කරන්න (Model එකේ තියෙනවා)
            $table->decimal('bonus_amount', 12, 2)->default(0);

            $table->decimal('net_amount', 12, 2)->default(0);

            $table->enum('status', [
                'pending',
                'approved',
                'paid',
                'cancelled'
            ])->default('paid');

            $table->dateTime('paid_at')->nullable();

            $table->text('note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | 🔥 Branch-scoped unique (NEW)
            |--------------------------------------------------------------------------
            | Teacher can have one salary per branch per month.
            | Multiple branches → multiple salaries per month.
            |--------------------------------------------------------------------------
            */
            $table->unique(
                ['branch_id', 'teacher_id', 'salary_year', 'salary_month'],
                'unique_branch_teacher_salary_month'
            );

            /*
            |--------------------------------------------------------------------------
            | 🔥 Branch-scoped indexes (NEW)
            |--------------------------------------------------------------------------
            */
            $table->index(
                ['branch_id', 'salary_year', 'salary_month'],
                'ts_branch_year_month_idx'
            );

            $table->index(
                ['branch_id', 'teacher_id', 'status'],
                'ts_branch_teacher_status_idx'
            );

            $table->index(
                ['branch_id', 'status', 'paid_at'],
                'ts_branch_status_paid_idx'
            );

            /*
            |--------------------------------------------------------------------------
            | පරණ indexes (still useful)
            |--------------------------------------------------------------------------
            */
            $table->index(['teacher_id', 'status'], 'ts_teacher_status_idx');
            $table->index(['salary_year', 'salary_month'], 'ts_year_month_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_salaries');
    }
};
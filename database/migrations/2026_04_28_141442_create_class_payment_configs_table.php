<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_payment_configs', function (Blueprint $table) {
            $table->id();

            // 🔥 Branch ownership (denormalized from student_class)
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();

            $table->foreignId('student_class_id')
                ->constrained('student_classes')
                ->cascadeOnDelete();

            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->restrictOnDelete();

            $table->foreignId('organizer_id')
                ->nullable()
                ->constrained('organizers')
                ->nullOnDelete();

            $table->decimal('teacher_percentage', 5, 2);
            $table->decimal('organizer_percentage', 5, 2)->default(0);
            $table->decimal('institution_percentage', 5, 2);

            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();

            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | 🔥 Branch-scoped indexes (NEW)
            |--------------------------------------------------------------------------
            */
            $table->index(
                ['branch_id', 'is_active'],
                'cpc_branch_active_idx'
            );

            $table->index(
                ['branch_id', 'student_class_id', 'is_active'],
                'cpc_branch_class_active_idx'
            );

            $table->index(
                ['branch_id', 'teacher_id', 'is_active'],
                'cpc_branch_teacher_active_idx'
            );

            $table->index(
                ['branch_id', 'organizer_id', 'is_active'],
                'cpc_branch_organizer_active_idx'
            );

            /*
            |--------------------------------------------------------------------------
            | පරණ indexes (still useful)
            |--------------------------------------------------------------------------
            */
            $table->index(['student_class_id', 'is_active'], 'cpc_class_active_idx');
            $table->index(['teacher_id', 'is_active'], 'cpc_teacher_active_idx');
            $table->index(['organizer_id', 'is_active'], 'cpc_organizer_active_idx');
            $table->index(['effective_from', 'effective_to'], 'cpc_effective_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_payment_configs');
    }
};
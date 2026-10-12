<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_split_snapshots', function (Blueprint $table) {
            $table->id();

            // Branch ownership
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();

            // Payment
            $table->foreignId('payment_id')
                ->constrained('payments')
                ->cascadeOnDelete();

            // Fee snapshot
            $table->decimal('class_fee', 12, 2)->default(0);
            $table->decimal('hall_fee', 12, 2)->default(0);
            $table->decimal('total_fee', 12, 2)->default(0);

            // Class
            $table->foreignId('student_class_id')
                ->constrained('student_classes')
                ->cascadeOnDelete();

            // Enrollment
            $table->foreignId('student_class_enrollment_id')
                ->constrained('student_class_enrollments')
                ->cascadeOnDelete();

            // Payment configuration snapshot
            $table->foreignId('class_payment_config_id')
                ->nullable()
                ->constrained('class_payment_configs')
                ->nullOnDelete();

            // Teacher
            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->restrictOnDelete();

            // Organizer
            $table->foreignId('organizer_id')
                ->nullable()
                ->constrained('organizers')
                ->nullOnDelete();

            // Created by
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Amount allocated to this class
            $table->decimal('payment_amount', 12, 2);

            // Percentage snapshot
            $table->decimal('teacher_percentage', 5, 2);
            $table->decimal('organizer_percentage', 5, 2)->default(0);
            $table->decimal('institution_percentage', 5, 2);

            // Amount snapshot
            $table->decimal('teacher_amount', 12, 2);
            $table->decimal('organizer_amount', 12, 2)->default(0);
            $table->decimal('institution_amount', 12, 2);

            // Payment date
            $table->dateTime('payment_date');

            $table->timestamps();
            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | One split snapshot per payment
            |--------------------------------------------------------------------------
            */
            $table->unique(
                'payment_id',
                'pss_payment_unique'
            );

            /*
            |--------------------------------------------------------------------------
            | Branch-scoped indexes
            |--------------------------------------------------------------------------
            */
            $table->index(
                ['branch_id', 'payment_date'],
                'pss_branch_payment_date_idx'
            );

            $table->index(
                ['branch_id', 'teacher_id', 'payment_date'],
                'pss_branch_teacher_date_idx'
            );

            $table->index(
                ['branch_id', 'organizer_id', 'payment_date'],
                'pss_branch_organizer_date_idx'
            );

            $table->index(
                ['branch_id', 'student_class_id', 'payment_date'],
                'pss_branch_class_date_idx'
            );

            /*
            |--------------------------------------------------------------------------
            | Existing indexes
            |--------------------------------------------------------------------------
            */
            $table->index(
                ['student_class_id', 'payment_date'],
                'pss_class_payment_date_idx'
            );

            $table->index(
                ['teacher_id', 'payment_date'],
                'pss_teacher_payment_date_idx'
            );

            $table->index(
                ['organizer_id', 'payment_date'],
                'pss_organizer_payment_date_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_split_snapshots');
    }
};
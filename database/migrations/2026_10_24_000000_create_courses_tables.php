<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Courses (feature "courses"): a course with a fixed fee, start / end dates
 * and weekly days, taught by one or more teachers who share the income by
 * percentage. Separate from classes, class payments and teacher salaries.
 */
class CreateCoursesTables extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->string('name', 150);
            $table->string('code', 50)->nullable();
            $table->text('description')->nullable();

            // Paid in installments (one per course month) / paid at once
            $table->decimal('full_fee', 10, 2);
            $table->decimal('one_time_fee', 10, 2);
            $table->unsignedTinyInteger('installment_count');

            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->json('class_days'); // ["saturday","sunday"]
            $table->foreignId('class_hall_id')->nullable()->constrained('class_halls')->nullOnDelete();

            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'is_active']);
        });

        Schema::create('course_teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->restrictOnDelete();
            $table->decimal('percentage', 5, 2);
            $table->timestamps();

            $table->unique(['course_id', 'teacher_id']);
        });

        Schema::create('course_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->date('session_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->foreignId('class_hall_id')->nullable()->constrained('class_halls')->nullOnDelete();
            $table->enum('status', ['scheduled', 'cancelled'])->default('scheduled');
            $table->string('cancel_reason', 255)->nullable();
            $table->timestamps();

            $table->unique(['course_id', 'session_date']);
            $table->index(['branch_id', 'session_date']);
        });

        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->restrictOnDelete();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->enum('payment_plan', ['full', 'installment']);
            $table->decimal('total_fee', 10, 2);
            $table->date('enrolled_at');
            $table->enum('status', ['active', 'cancelled'])->default('active');
            $table->foreignId('enrolled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['course_id', 'student_id']);
        });

        Schema::create('course_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_enrollment_id')->constrained('course_enrollments')->cascadeOnDelete();
            $table->unsignedTinyInteger('installment_no');
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->timestamps();

            $table->unique(['course_enrollment_id', 'installment_no']);
        });

        Schema::create('course_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('course_id')->constrained('courses')->restrictOnDelete();
            $table->foreignId('course_enrollment_id')->constrained('course_enrollments')->restrictOnDelete();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->decimal('amount', 10, 2);
            $table->enum('payment_method', ['cash', 'card', 'bank_transfer', 'online', 'cheque', 'other'])->default('cash');
            $table->enum('mark_method', ['qr_mobile', 'qr_web', 'manual_mobile', 'manual_web'])->default('manual_web');
            $table->string('receipt_number', 100)->unique();
            $table->dateTime('paid_at');
            $table->enum('status', ['completed', 'cancelled'])->default('completed');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason', 255)->nullable();
            $table->timestamps();

            $table->index(['course_enrollment_id', 'status']);
            $table->index(['branch_id', 'paid_at']);
        });

        // Who gets how much of each payment (teacher_id null = institute)
        Schema::create('course_payment_splits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_payment_id')->constrained('course_payments')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->restrictOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->restrictOnDelete();
            $table->decimal('percentage', 5, 2);
            $table->decimal('amount', 10, 2);
            $table->timestamps();

            $table->index(['course_id', 'teacher_id']);
        });

        Schema::create('course_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_session_id')->constrained('course_sessions')->cascadeOnDelete();
            $table->foreignId('course_enrollment_id')->constrained('course_enrollments')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->dateTime('attended_at');
            $table->enum('mark_method', ['qr_mobile', 'qr_web', 'manual_mobile', 'manual_web'])->default('manual_web');
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['course_session_id', 'student_id']);
        });

        // Money paid to course teachers (separate from teacher salaries)
        Schema::create('course_teacher_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('course_id')->constrained('courses')->restrictOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->restrictOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('paid_on');
            $table->enum('payment_method', ['cash', 'card', 'bank_transfer', 'online', 'cheque', 'other'])->default('cash');
            $table->enum('status', ['paid', 'cancelled'])->default('paid');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['course_id', 'teacher_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_teacher_payouts');
        Schema::dropIfExists('course_attendances');
        Schema::dropIfExists('course_payment_splits');
        Schema::dropIfExists('course_payments');
        Schema::dropIfExists('course_installments');
        Schema::dropIfExists('course_enrollments');
        Schema::dropIfExists('course_sessions');
        Schema::dropIfExists('course_teachers');
        Schema::dropIfExists('courses');
    }
}

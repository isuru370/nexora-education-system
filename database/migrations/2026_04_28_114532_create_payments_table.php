<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // 🔥 Branch ownership (denormalized from enrollment → class)
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();

            // 🔥 Offline support
            $table->uuid('local_uuid')->nullable()->unique();

            // 🔹 Student
            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            // 🔹 Enrollment (category-level enrollment)
            $table->foreignId('student_class_enrollment_id')
                ->constrained('student_class_enrollments')
                ->cascadeOnDelete();

            // 🔹 Collected by
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->enum('mark_method', [
                'qr_mobile',
                'qr_web',
                'manual_mobile',
                'manual_web'
            ])->default('qr_mobile');

            // 💰 Paid amount only
            $table->decimal('amount', 10, 2);

            // Optional discount tracking
            $table->decimal('discount_amount', 10, 2)->default(0);

            // Payment time
            $table->dateTime('paid_at');

            // 🔥 Monthly control (IMPORTANT)
            $table->date('payment_month');

            // Payment method
            $table->enum('payment_method', [
                'cash',
                'card',
                'bank_transfer',
                'online',
                'cheque',
                'other'
            ])->default('cash');

            // Status
            $table->enum('status', [
                'completed',
                'pending',
                'failed',
                'cancelled'
            ])->default('completed');

            // Receipt
            $table->string('receipt_number', 100)->nullable()->unique();
            $table->string('reference_number', 100)->nullable();

            // Sync
            $table->boolean('is_synced')->default(true);

            // Notes
            $table->text('note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // 🔥 IMPORTANT BUSINESS RULE
            // same enrollment + same month = only one payment
            $table->unique([
                'student_class_enrollment_id',
                'payment_month',
            ], 'unique_enrollment_month_payment');

            // 🔥 Branch-scoped indexes (NEW)
            $table->index(
                ['branch_id', 'paid_at'],
                'pay_branch_paid_idx'
            );

            $table->index(
                ['branch_id', 'payment_month', 'status'],
                'pay_branch_month_status_idx'
            );

            $table->index(
                ['branch_id', 'student_id'],
                'pay_branch_student_idx'
            );

            // පරණ indexes (still useful)
            $table->index(['student_id', 'paid_at'], 'pay_student_paid_idx');
            $table->index(['student_class_enrollment_id', 'paid_at'], 'pay_enrollment_paid_idx');
            $table->index(['status', 'paid_at'], 'pay_status_paid_idx');
            $table->index(['payment_month', 'status'], 'pay_month_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
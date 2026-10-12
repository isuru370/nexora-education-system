<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_payments', function (Blueprint $table) {
            $table->id();

            // 🔥 Branch ownership (denormalized from student)
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->foreignId('admission_id')
                ->constrained('admissions')
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->decimal('amount', 10, 2);

            $table->dateTime('paid_at')->nullable();

            $table->enum('payment_method', [
                'cash',
                'card',
                'bank_transfer',
                'online',
                'other',
            ])->default('cash');

            $table->enum('status', [
                'pending',
                'paid',
                'cancelled',
                'refunded',
            ])->default('paid');

            $table->string('receipt_number', 100)->nullable()->unique();

            $table->text('note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | 🔥 Branch-scoped indexes (NEW)
            |--------------------------------------------------------------------------
            */
            $table->index(
                ['branch_id', 'paid_at', 'status'],
                'ap_branch_paid_status_idx'
            );

            $table->index(
                ['branch_id', 'student_id', 'status'],
                'ap_branch_student_status_idx'
            );

            $table->index(
                ['branch_id', 'admission_id', 'status'],
                'ap_branch_admission_status_idx'
            );

            /*
            |--------------------------------------------------------------------------
            | පරණ indexes (still useful)
            |--------------------------------------------------------------------------
            */
            $table->index(['student_id', 'status'], 'ap_student_status_idx');
            $table->index(['admission_id', 'status'], 'ap_admission_status_idx');
            $table->index(['paid_at', 'status'], 'ap_paid_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_payments');
    }
};
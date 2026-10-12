<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institute_payments', function (Blueprint $table) {
            $table->id();

            // 🔥 Branch ownership (NULLABLE — institute-wide expenses)
            // Set = branch-specific expense
            // NULL = institute-wide expense (head office rent, etc.)
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->restrictOnDelete();

            // 💰 amount
            $table->decimal('amount', 12, 2);

            // payment date
            $table->date('payment_date');

            // reason
            $table->string('reason', 150)->nullable();

            $table->string('reason_code', 50)->nullable();
            $table->foreign('reason_code')
                ->references('reason_code')
                ->on('payment_reasons')
                ->nullOnDelete();

            // 🔥 type
            $table->enum('payment_type', [
                'expense',
            ])->default('expense');

            // status
            $table->enum('status', [
                'pending',
                'approved',
                'paid',
                'cancelled'
            ])->default('paid');

            // user
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // note
            $table->text('note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | 🔥 Branch-scoped indexes (NEW)
            |--------------------------------------------------------------------------
            */
            $table->index(
                ['branch_id', 'payment_date', 'status'],
                'ip_branch_date_status_idx'
            );

            $table->index(
                ['branch_id', 'payment_type', 'status'],
                'ip_branch_type_status_idx'
            );

            $table->index(
                ['branch_id', 'reason_code'],
                'ip_branch_reason_idx'
            );

            /*
            |--------------------------------------------------------------------------
            | පරණ indexes (still useful)
            |--------------------------------------------------------------------------
            */
            $table->index(['payment_date', 'status'], 'ip_date_status_idx');
            $table->index('reason_code', 'ip_reason_code_idx');
            $table->index('payment_type', 'ip_payment_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institute_payments');
    }
};
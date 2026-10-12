<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizer_payments', function (Blueprint $table) {

            $table->id();

            // 🔥 Branch ownership (which branch does this payment belong to)
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();

            $table->foreignId('organizer_id')
                ->constrained('organizers')
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->enum('payment_type', [
                'salary',
                'advance',
                'deduction',
                'other',
            ])->default('advance');

            $table->decimal('amount', 10, 2);

            $table->date('payment_date');

            // 🔥 reason_code → payment_reasons
            $table->string('reason_code', 50)->nullable();

            $table->foreign('reason_code')
                ->references('reason_code')
                ->on('payment_reasons')
                ->nullOnDelete();

            $table->string('reason', 150)->nullable();

            $table->enum('status', [
                'pending',
                'approved',
                'paid',
                'cancelled',
            ])->default('paid');

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
                'op_branch_date_status_idx'
            );

            $table->index(
                ['branch_id', 'organizer_id', 'payment_type'],
                'op_branch_organizer_type_idx'
            );

            $table->index(
                ['branch_id', 'payment_type', 'status'],
                'op_branch_type_status_idx'
            );

            /*
            |--------------------------------------------------------------------------
            | පරණ indexes (still useful)
            |--------------------------------------------------------------------------
            */
            $table->index(['organizer_id', 'payment_type'], 'op_organizer_type_idx');
            $table->index(['payment_date', 'status'], 'op_date_status_idx');
            $table->index('reason_code', 'op_reason_code_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizer_payments');
    }
};
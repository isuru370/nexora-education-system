<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extra_incomes', function (Blueprint $table) {
            $table->id();

            // 🔥 Branch ownership (NULLABLE — institute-wide incomes)
            // Set = branch-specific income
            // NULL = institute-wide income (interest, donation, etc.)
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->restrictOnDelete();

            // 💰 amount
            $table->decimal('amount', 12, 2);

            // income date
            $table->date('income_date');

            // reason
            $table->string('reason', 150)->nullable();

            // 🔥 income type
            $table->enum('income_type', [
                'hall_rent',
                'extra',
                'refund',
                'other'
            ])->default('hall_rent');

            // status
            $table->enum('status', [
                'pending',
                'approved',
                'received',
                'cancelled'
            ])->default('received');

            $table->string('receipt_number', 100)
                ->nullable()
                ->unique();

            // user
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // notes
            $table->text('note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | 🔥 Branch-scoped indexes (NEW)
            |--------------------------------------------------------------------------
            */
            $table->index(
                ['branch_id', 'income_date', 'status'],
                'ei_branch_date_status_idx'
            );

            $table->index(
                ['branch_id', 'income_type', 'status'],
                'ei_branch_type_status_idx'
            );

            $table->index(
                ['branch_id', 'user_id', 'income_date'],
                'ei_branch_user_date_idx'
            );

            /*
            |--------------------------------------------------------------------------
            | පරණ indexes (still useful)
            |--------------------------------------------------------------------------
            */
            $table->index(['income_date', 'status'], 'ei_date_status_idx');
            $table->index('income_type', 'ei_income_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extra_incomes');
    }
};
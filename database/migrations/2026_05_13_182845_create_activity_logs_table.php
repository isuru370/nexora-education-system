<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // 🔥 Branch ownership (NULLABLE — for system-level events)
            // Set = branch-specific activity
            // NULL = system-wide (login, cron jobs, etc.)
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->nullOnDelete();

            $table->string('table_name');
            $table->unsignedBigInteger('record_id')->nullable();

            $table->string('action');

            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | 🔥 Branch-scoped indexes (NEW)
            |--------------------------------------------------------------------------
            */
            $table->index(
                ['branch_id', 'table_name', 'created_at'],
                'al_branch_table_created_idx'
            );

            $table->index(
                ['branch_id', 'user_id', 'created_at'],
                'al_branch_user_created_idx'
            );

            $table->index(
                ['branch_id', 'action', 'created_at'],
                'al_branch_action_created_idx'
            );

            $table->index(
                ['branch_id', 'record_id', 'table_name'],
                'al_branch_record_idx'
            );

            /*
            |--------------------------------------------------------------------------
            | පරණ indexes (still useful)
            |--------------------------------------------------------------------------
            */
            $table->index(['table_name', 'record_id'], 'al_table_record_idx');
            $table->index(['user_id', 'created_at'], 'al_user_created_idx');
            $table->index(['action', 'created_at'], 'al_action_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Attendance and payments the staff app recorded offline, one row per item
 * sent by a phone. local_uuid makes a sync safe to repeat: an item already
 * here returns its first result and is not saved again.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offline_sync_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('local_uuid')->unique();
            $table->string('device_id', 64);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->enum('type', ['attendance', 'payment']);
            // synced: saved | duplicate: was already there (same result)
            // conflict: refused because of something saved meanwhile (e.g. month paid on another phone)
            // failed: refused by a rule (wrong data) - needs a person
            $table->enum('status', ['synced', 'duplicate', 'conflict', 'failed']);
            $table->string('message', 255)->nullable();
            $table->dateTime('happened_at');
            $table->json('payload');
            $table->foreignId('student_attendance_id')->nullable()->constrained('student_attendances')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'reviewed_at'], 'osi_status_reviewed_idx');
            $table->index(['device_id', 'created_at'], 'osi_device_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offline_sync_items');
    }
};

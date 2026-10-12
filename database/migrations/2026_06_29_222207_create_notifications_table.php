<?php

use App\Enums\NotificationType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            // 🔥 Branch ownership (denormalized from student)
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();

            // Student who receives the notification
            $table->foreignId('student_id')
                ->constrained()
                ->cascadeOnDelete();

            // Notification content
            $table->string('title', 150);
            $table->text('body');

            // Notification category
            $table->string('type', 50)->default(NotificationType::GENERAL);

            // Extra information (class_id, payment_id, etc.)
            $table->json('data')->nullable();

            // Notification state
            $table->enum('status', [
                'pending',
                'processing',
                'sent',
                'failed',
                'cancelled'
            ])->default('pending');

            // Tracking
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();

            // Firebase error (if any)
            $table->text('error_message')->nullable();

            // Retry counter
            $table->integer('retry_count')->default(0);

            // Admin/User who created notification
            $table->foreignId('created_by')
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
                ['branch_id', 'status'],
                'notif_branch_status_idx'
            );

            $table->index(
                ['branch_id', 'student_id', 'read_at'],
                'notif_branch_student_read_idx'
            );

            $table->index(
                ['branch_id', 'scheduled_at', 'status'],
                'notif_branch_scheduled_status_idx'
            );

            $table->index(
                ['branch_id', 'type', 'created_at'],
                'notif_branch_type_created_idx'
            );

            /*
            |--------------------------------------------------------------------------
            | පරණ indexes (still useful)
            |--------------------------------------------------------------------------
            */
            $table->index('student_id', 'notif_student_idx');
            $table->index('status', 'notif_status_idx');
            $table->index('type', 'notif_type_idx');
            $table->index('read_at', 'notif_read_idx');
            $table->index('scheduled_at', 'notif_scheduled_idx');
            $table->index(['student_id', 'read_at'], 'notif_student_read_idx');
            $table->index(['status', 'created_at'], 'notif_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
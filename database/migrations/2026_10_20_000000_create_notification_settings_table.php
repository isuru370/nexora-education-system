<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Which parent notifications are sent, and how (whole system, not per branch).
 *
 *  - registration : SMS only (portal login details) - on / off
 *  - the rest     : on / off + channel push (parent app) or sms
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();

            $table->string('key', 50)->unique();
            $table->string('label', 100);
            $table->string('description', 255)->nullable();

            $table->boolean('is_enabled')->default(true);

            // push = parent app (FCM), sms = text message
            $table->enum('channel', ['push', 'sms'])->default('push');

            // true = channel cannot be changed (registration is always SMS)
            $table->boolean('channel_locked')->default(false);

            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });

        // Defaults = how the system worked before this table
        $now = now();

        DB::table('notification_settings')->insert([
            [
                'key' => 'registration',
                'label' => 'Student registration',
                'description' => 'Admission fee (paid / not paid) and parent portal login details.',
                'is_enabled' => true,
                'channel' => 'sms',
                'channel_locked' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'payment',
                'label' => 'Class fee payment',
                'description' => 'Paid month, amount and receipt number.',
                'is_enabled' => true,
                'channel' => 'push',
                'channel_locked' => false,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'admission_payment',
                'label' => 'Admission payment',
                'description' => 'When the admission fee is paid after registration.',
                'is_enabled' => false,
                'channel' => 'push',
                'channel_locked' => false,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'attendance',
                'label' => 'Attendance marked',
                'description' => 'When the student is marked present for a class.',
                'is_enabled' => true,
                'channel' => 'push',
                'channel_locked' => false,
                'sort_order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'class_schedule',
                'label' => 'Class schedule changes',
                'description' => 'New class, time / hall changed, or class cancelled.',
                'is_enabled' => true,
                'channel' => 'sms',
                'channel_locked' => false,
                'sort_order' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};

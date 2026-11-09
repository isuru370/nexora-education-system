<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Parent reminder before a course installment is due
 * (Settings > Notifications, shown only with the "courses" feature).
 */
class AddCourseInstallmentReminderSetting extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notification_settings')
            || DB::table('notification_settings')->where('key', 'course_installment_reminder')->exists()) {
            return;
        }

        DB::table('notification_settings')->insert([
            'key' => 'course_installment_reminder',
            'label' => 'Course installment reminder',
            'description' => '3 days before and on the due date of an unpaid course installment (sent at 9 AM).',
            'is_enabled' => true,
            'channel' => 'push',
            'channel_locked' => false,
            'sort_order' => 9,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \App\Models\NotificationSetting::clearCache();
    }

    public function down(): void
    {
        DB::table('notification_settings')->where('key', 'course_installment_reminder')->delete();
        \App\Models\NotificationSetting::clearCache();
    }
}

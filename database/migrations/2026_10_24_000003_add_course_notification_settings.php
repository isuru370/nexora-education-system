<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Parent notices for courses (Settings > Notifications, shown only when the
 * institute has the "courses" feature).
 */
class AddCourseNotificationSettings extends Migration
{
    protected const ROWS = [
        [
            'key' => 'course_schedule',
            'label' => 'Course class changes',
            'description' => 'Course class day cancelled, moved, or an extra day added.',
            'is_enabled' => true,
            'channel' => 'sms',
            'sort_order' => 6,
        ],
        [
            'key' => 'course_payment',
            'label' => 'Course fee payment',
            'description' => 'Course, amount, receipt number and balance.',
            'is_enabled' => true,
            'channel' => 'push',
            'sort_order' => 7,
        ],
        [
            'key' => 'course_attendance',
            'label' => 'Course attendance marked',
            'description' => 'When the student is marked present for a course class.',
            'is_enabled' => true,
            'channel' => 'push',
            'sort_order' => 8,
        ],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('notification_settings')) {
            return;
        }

        foreach (self::ROWS as $row) {
            if (DB::table('notification_settings')->where('key', $row['key'])->exists()) {
                continue;
            }

            DB::table('notification_settings')->insert($row + [
                'channel_locked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        \App\Models\NotificationSetting::clearCache();
    }

    public function down(): void
    {
        DB::table('notification_settings')->whereIn('key', array_column(self::ROWS, 'key'))->delete();
    }
}

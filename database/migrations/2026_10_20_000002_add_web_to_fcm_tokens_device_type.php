<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Parent Hub web portal: browsers register FCM tokens with device_type = web.
 */
class AddWebToFcmTokensDeviceType extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fcm_tokens') && DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE fcm_tokens MODIFY device_type ENUM('android','ios','web') NOT NULL DEFAULT 'android'");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fcm_tokens') && DB::getDriverName() === 'mysql') {
            DB::table('fcm_tokens')->where('device_type', 'web')->update(['device_type' => 'android']);
            DB::statement("ALTER TABLE fcm_tokens MODIFY device_type ENUM('android','ios') NOT NULL DEFAULT 'android'");
        }
    }
}

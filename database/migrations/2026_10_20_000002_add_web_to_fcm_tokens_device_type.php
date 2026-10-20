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
        if (!Schema::hasTable('fcm_tokens')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE fcm_tokens MODIFY device_type ENUM('android','ios','web') NOT NULL DEFAULT 'android'");
        }

        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildSqliteCheck("('android', 'ios', 'web')");
        }
    }

    /**
     * SQLite (tests) keeps enums as a CHECK constraint that cannot be altered,
     * so the table is copied into a new one with the new list.
     */
    private function rebuildSqliteCheck(string $values): void
    {
        $table = DB::selectOne("select sql from sqlite_master where type = 'table' and name = 'fcm_tokens'");
        $create = preg_replace('/check \("device_type" in \([^)]*\)\)/i', 'check ("device_type" in ' . $values . ')', $table->sql);

        if ($create === $table->sql) {
            return;
        }

        $indexes = collect(DB::select("select sql from sqlite_master where type = 'index' and tbl_name = 'fcm_tokens' and sql is not null"))
            ->pluck('sql');

        DB::statement('PRAGMA foreign_keys = OFF');
        DB::statement('ALTER TABLE fcm_tokens RENAME TO fcm_tokens_old');
        DB::statement($create);
        DB::statement('INSERT INTO fcm_tokens SELECT * FROM fcm_tokens_old');
        DB::statement('DROP TABLE fcm_tokens_old');

        foreach ($indexes as $sql) {
            DB::statement($sql);
        }

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        if (Schema::hasTable('fcm_tokens') && DB::getDriverName() === 'mysql') {
            DB::table('fcm_tokens')->where('device_type', 'web')->update(['device_type' => 'android']);
            DB::statement("ALTER TABLE fcm_tokens MODIFY device_type ENUM('android','ios') NOT NULL DEFAULT 'android'");
        }
    }
}

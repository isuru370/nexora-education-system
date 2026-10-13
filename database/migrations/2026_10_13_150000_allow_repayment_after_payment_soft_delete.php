<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Allow a new payment for the same class + month after the old one
 * is soft deleted (wrong payment) or cancelled.
 *
 * Before: UNIQUE (student_class_enrollment_id, payment_month)
 *         -> also counted soft-deleted / cancelled rows.
 *
 * After:  active_month_lock = 1 only for a live, completed payment, else NULL.
 *         UNIQUE (student_class_enrollment_id, payment_month, active_month_lock)
 *         -> MySQL/MariaDB allow many NULLs in a unique index, so only ONE
 *            live completed payment per class + month is still enforced.
 *
 * Also records who deleted a payment and why.
 *
 * Works on MySQL 5.7+ / 8.x and MariaDB 10.2+.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Audit: who deleted it and why
            $table->foreignId('deleted_by')
                ->nullable()
                ->after('deleted_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->string('delete_reason', 500)
                ->nullable()
                ->after('deleted_by');

            // 1 = live completed payment, NULL = deleted / cancelled / pending / failed
            $table->unsignedTinyInteger('active_month_lock')
                ->nullable()
                ->storedAs("CASE WHEN deleted_at IS NULL AND status = 'completed' THEN 1 ELSE NULL END")
                ->after('delete_reason');
        });

        /*
         * Add the new unique index BEFORE dropping the old one, so the
         * enrollment foreign key always has an index to use.
         *
         * The name still contains "unique_enrollment_month_payment", so
         * PaymentMarker's duplicate-error check keeps working unchanged.
         */
        Schema::table('payments', function (Blueprint $table) {
            $table->unique(
                ['student_class_enrollment_id', 'payment_month', 'active_month_lock'],
                'unique_enrollment_month_payment_active'
            );
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique('unique_enrollment_month_payment');
        });
    }

    public function down(): void
    {
        /*
         * NOTE: this fails if a class + month now has more than one row
         * (e.g. a soft-deleted payment and its replacement). Remove those
         * duplicates first if you really need to roll back.
         */
        Schema::table('payments', function (Blueprint $table) {
            $table->unique(
                ['student_class_enrollment_id', 'payment_month'],
                'unique_enrollment_month_payment'
            );
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique('unique_enrollment_month_payment_active');
            $table->dropColumn('active_month_lock');
            $table->dropConstrainedForeignId('deleted_by');
            $table->dropColumn('delete_reason');
        });
    }
};
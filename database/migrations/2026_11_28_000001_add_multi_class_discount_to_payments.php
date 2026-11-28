<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-class discount (Payment Rules): the part of a payment's discount
 * given because the student paid several classes at once. The teacher bears
 * it, so the split snapshot keeps it too.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('multi_class_discount', 10, 2)->default(0)->after('discount_amount');
        });

        Schema::table('payment_split_snapshots', function (Blueprint $table) {
            $table->decimal('multi_class_discount', 10, 2)->default(0)->after('payment_amount');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('multi_class_discount');
        });

        Schema::table('payment_split_snapshots', function (Blueprint $table) {
            $table->dropColumn('multi_class_discount');
        });
    }
};

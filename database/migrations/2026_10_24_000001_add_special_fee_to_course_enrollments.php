<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Special fee for one student: total_fee is what they pay, standard_fee the
 * course fee for their plan (the difference is the discount), fee_reason why.
 */
class AddSpecialFeeToCourseEnrollments extends Migration
{
    public function up(): void
    {
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->decimal('standard_fee', 10, 2)->nullable()->after('total_fee');
            $table->string('fee_reason', 255)->nullable()->after('standard_fee');
        });
    }

    public function down(): void
    {
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->dropColumn(['standard_fee', 'fee_reason']);
        });
    }
}

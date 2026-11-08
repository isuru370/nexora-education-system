<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class fees paid after a month's salary was paid (same month) are added
 * to the teacher's next salary:
 *  - teacher_salaries.carried_amount: teacher share brought in from earlier months
 *  - payment_split_snapshots.carried_to_salary_id: the salary that paid it
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_salaries', function (Blueprint $table) {
            $table->decimal('carried_amount', 12, 2)->default(0)->after('gross_amount');
        });

        Schema::table('payment_split_snapshots', function (Blueprint $table) {
            $table->unsignedBigInteger('carried_to_salary_id')->nullable()->index('pss_carried_salary_idx');
        });
    }

    public function down(): void
    {
        Schema::table('payment_split_snapshots', function (Blueprint $table) {
            $table->dropIndex('pss_carried_salary_idx');
            $table->dropColumn('carried_to_salary_id');
        });

        Schema::table('teacher_salaries', function (Blueprint $table) {
            $table->dropColumn('carried_amount');
        });
    }
};

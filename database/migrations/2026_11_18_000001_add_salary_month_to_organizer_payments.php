<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Organizer salary is per branch + month (as teacher salaries): the salary
 * row keeps the month it is for, so paying September on 2 October is
 * September's salary (it was counted in October).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizer_payments', function (Blueprint $table) {
            $table->unsignedSmallInteger('salary_year')->nullable()->after('payment_date');
            $table->unsignedTinyInteger('salary_month')->nullable()->after('salary_year');
            $table->index(['branch_id', 'organizer_id', 'salary_year', 'salary_month'], 'org_pay_salary_month_idx');
        });

        // salary rows saved before this (if any): the month of their date
        foreach (DB::table('organizer_payments')->where('payment_type', 'salary')->get(['id', 'payment_date']) as $row) {
            $date = \Carbon\Carbon::parse($row->payment_date);
            DB::table('organizer_payments')->where('id', $row->id)->update(['salary_year' => $date->year, 'salary_month' => $date->month]);
        }
    }

    public function down(): void
    {
        Schema::table('organizer_payments', function (Blueprint $table) {
            $table->dropIndex('org_pay_salary_month_idx');
            $table->dropColumn(['salary_year', 'salary_month']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Teacher welfare (feature "welfare"): an amount the admin takes from each
 * teacher's salary every month, per branch. It goes into the branch's welfare
 * fund (not institute income), which pays for outside work (expenses).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Monthly welfare amount of a teacher in a branch
        Schema::table('teacher_branches', function (Blueprint $table) {
            $table->decimal('welfare_amount', 10, 2)->nullable()->after('branch_id');
        });

        Schema::create('welfare_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->year('salary_year');
            $table->unsignedTinyInteger('salary_month');
            $table->decimal('amount', 10, 2);
            $table->string('note', 255)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'salary_year', 'salary_month']);
            $table->index(['teacher_id', 'salary_year', 'salary_month']);
        });

        Schema::create('welfare_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->date('expense_date');
            $table->string('description', 200);
            $table->string('paid_to', 150)->nullable();
            $table->decimal('amount', 10, 2);
            $table->text('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'expense_date']);
        });

        Schema::table('teacher_salaries', function (Blueprint $table) {
            $table->decimal('welfare_deduction', 12, 2)->default(0)->after('other_deduction');
        });
    }

    public function down(): void
    {
        Schema::table('teacher_salaries', function (Blueprint $table) {
            $table->dropColumn('welfare_deduction');
        });
        Schema::dropIfExists('welfare_expenses');
        Schema::dropIfExists('welfare_deductions');
        Schema::table('teacher_branches', function (Blueprint $table) {
            $table->dropColumn('welfare_amount');
        });
    }
};

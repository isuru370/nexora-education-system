<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Students the nightly job (disable:expired-students) made inactive, with
 * what they looked like at that moment: the classes they were taken out of,
 * their last attendance and last payment. Re-activated rows keep who / when.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_auto_disables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->dateTime('disabled_at');
            $table->json('classes')->nullable();
            $table->dateTime('last_attendance_at')->nullable();
            $table->string('last_attendance_class', 200)->nullable();
            $table->dateTime('last_payment_at')->nullable();
            $table->decimal('last_payment_amount', 10, 2)->nullable();
            $table->date('last_payment_month')->nullable();
            $table->string('last_payment_class', 200)->nullable();
            $table->dateTime('reactivated_at')->nullable();
            $table->foreignId('reactivated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('disabled_at');
            $table->index(['branch_id', 'disabled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_auto_disables');
    }
};

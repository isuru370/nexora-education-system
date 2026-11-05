<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The institute share of a course's payments handed to the owner
 * (like course teacher payouts, for the institute's part).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_owner_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('course_id')->constrained('courses')->restrictOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('paid_on');
            $table->enum('payment_method', ['cash', 'card', 'bank_transfer', 'online', 'cheque', 'other'])->default('cash');
            $table->enum('status', ['paid', 'cancelled'])->default('paid');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['course_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_owner_payouts');
    }
};

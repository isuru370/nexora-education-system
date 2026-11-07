<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who enabled a disabled student, when, and why the student had been
 * disabled (Disabled Students page).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_reactivations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('reason', 20);                         // auto | batch | manual
            $table->unsignedInteger('classes_restored')->default(0);
            $table->foreignId('enabled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_reactivations');
    }
};

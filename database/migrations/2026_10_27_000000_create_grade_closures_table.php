<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Close Batch" (e.g. 2026 A/L after the exam): one row per close, with what
 * was changed so it can be viewed and undone.
 */
class CreateGradeClosuresTable extends Migration
{
    public function up(): void
    {
        Schema::create('grade_closures', function (Blueprint $table) {
            $table->id();

            $table->json('grade_names');                 // ["2025 A/L", "2026 A/L"]
            $table->json('counts');                      // classes, enrollments, patterns, sessions, students, kept
            $table->longText('changes');                 // JSON: rows changed + their old values (for undo)
            $table->json('kept_students')->nullable();   // students left active (in a class of another grade)

            $table->string('source', 20)->default('web'); // web | command | file
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at');

            $table->foreignId('undone_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('undone_at')->nullable();

            $table->timestamps();

            $table->index('closed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_closures');
    }
}

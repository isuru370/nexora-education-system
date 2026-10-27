<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Promote Class" (e.g. Aa class Grade 9 -> Grade 10 for the new year):
 * one row per promotion, with what was created / changed, for the history
 * and undo.
 */
class CreateClassPromotionsTable extends Migration
{
    public function up(): void
    {
        Schema::create('class_promotions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('from_class_id')->constrained('student_classes');
            $table->foreignId('to_class_id')->nullable()->constrained('student_classes')->nullOnDelete();
            $table->string('from_class_name', 150);
            $table->string('to_class_name', 150);

            $table->date('start_date');            // new class from this day; old class until the day before
            $table->date('schedule_until');

            // scheduled = waiting for start_date; done = old class closed; undone
            $table->string('status', 20)->default('scheduled');

            $table->json('counts');                // moved, left, schedules, sessions, cancelled
            $table->longText('changes');           // JSON: created ids + old values, for undo

            $table->foreignId('promoted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('applied_at')->nullable();
            $table->foreignId('undone_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('undone_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_promotions');
    }
}

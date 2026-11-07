<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Card replacement fee:
 *  - admissions.for_card_replacement: the items offered when a lost card is
 *    replaced (e.g. "ID card replacement Rs. 200", "Free Rs. 0")
 *  - student_id_card_downloads: each ID card download on Generate Student ID
 *    (temporary-card institutes); from the 2nd one a fee item is chosen
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->boolean('for_card_replacement')->default(false)->after('is_active');
        });

        Schema::create('student_id_card_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('admission_payment_id')->nullable()->constrained('admission_payments')->nullOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_id_card_downloads');

        Schema::table('admissions', function (Blueprint $table) {
            $table->dropColumn('for_card_replacement');
        });
    }
};

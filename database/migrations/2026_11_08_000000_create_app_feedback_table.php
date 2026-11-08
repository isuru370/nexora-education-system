<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Feedback sent from the staff app ("Send Feedback") */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 10);                 // bug | idea | other
            $table->text('message');
            $table->string('screenshot_path')->nullable();
            $table->string('app_version', 40)->nullable();
            $table->string('device', 120)->nullable();
            $table->string('platform', 60)->nullable();
            $table->string('status', 10)->default('new'); // new | seen | done
            $table->text('admin_note')->nullable();
            $table->timestamp('emailed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_feedback');
    }
};

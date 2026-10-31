<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ID card designs of this institute (Card Designer page): where the QR,
 * ID number, photo, name ... go on the card, and the card background.
 * One row per card type (permanent / temporary). No row = the default design.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_layouts', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->unique();
            $table->longText('fields');
            $table->string('background')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_layouts');
    }
};
